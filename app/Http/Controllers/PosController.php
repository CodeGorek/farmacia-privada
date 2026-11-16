<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\Convenio;
use App\Models\AfiliadoConvenio;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\LibroControlado;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    private const TBK_SANDBOX_URL = 'https://webpay3gint.transbank.cl/rswebpaytransaction/api/webpay/v1.2/transactions';
    private const TBK_COMMERCE_CODE = '597055555532';
    private const TBK_API_KEY = '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';

    public static function calcularTarifaTransbank(float $montoBruto, string $metodoPago, ?string $authCodeOverride = null): array
    {
        $metodo = strtolower(trim($metodoPago));
        $tasa = 0.0;
        $plazoAbono = 'Inmediato en Caja';

        if (in_array($metodo, ['debito', 'prepago'], true)) {
            $tasa = 1.49;
            $plazoAbono = '24 hrs hábiles (Transbank)';
        } elseif ($metodo === 'credito') {
            $tasa = 2.29;
            $plazoAbono = '48 hrs hábiles (Transbank)';
        }

        $comisionNeta = round($montoBruto * ($tasa / 100));
        $ivaComision = round($comisionNeta * 0.19);
        $totalDescuentoTbk = $comisionNeta + $ivaComision;
        $abonoLiquido = max(0, $montoBruto - $totalDescuentoTbk);

        $esTransbank = $tasa > 0;
        $codigoAutorizacion = $authCodeOverride ?: ($esTransbank
            ? 'TBK-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6))
            : null);

        return [
            'es_transbank' => $esTransbank,
            'tasa_transbank' => $tasa,
            'comision_transbank' => $comisionNeta,
            'iva_comision' => $ivaComision,
            'total_descuento_tbk' => $totalDescuentoTbk,
            'abono_liquido' => $abonoLiquido,
            'plazo_abono' => $plazoAbono,
            'codigo_autorizacion_tbk' => $codigoAutorizacion,
        ];
    }

    /**
     * Switch Aprobador de Convenios en Línea por RUT (POST /pos/validar-rut-convenio)
     * Devuelve el convenio del afiliado y el mapa exacto de medicamentos cubiertos (vademécum).
     */
    public function validarRutConvenio(Request $request)
    {
        $request->validate([
            'rut' => 'required|string|max:25',
        ]);

        $rutNorm = AfiliadoConvenio::normalizarRut($request->rut);
        $rutFormateado = AfiliadoConvenio::formatearRut($rutNorm);

        $afiliado = AfiliadoConvenio::with(['convenio.medicamentos'])
            ->where('rut', $rutNorm)
            ->first();

        if (!$afiliado || !$afiliado->convenio) {
            return response()->json([
                'status' => 'ok',
                'tiene_beneficio' => false,
                'estado_switch' => 'SIN_CONVENIO',
                'rut' => $rutFormateado,
                'nombre_afiliado' => 'Cliente Particular (No afiliado)',
                'convenio_id' => null,
                'convenio_nombre' => 'Particular (0% Dcto.)',
                'codigo_switch' => null,
                'mensaje' => 'El RUT consultado no registra convenios activos. Se aplica tarifa Particular (0% descuento).',
                'cobertura_medicamentos' => (object) [],
            ]);
        }

        if ($afiliado->estado !== 'ACTIVO' || !$afiliado->convenio->activo) {
            return response()->json([
                'status' => 'ok',
                'tiene_beneficio' => false,
                'estado_switch' => 'RECHAZADO_MORA',
                'rut' => $rutFormateado,
                'nombre_afiliado' => $afiliado->nombre_afiliado,
                'convenio_id' => null,
                'convenio_nombre' => $afiliado->convenio->nombre,
                'codigo_switch' => 'ERR-MORA-403',
                'mensaje' => "Beneficio RECHAZADO por {$afiliado->convenio->nombre}: Afiliado con suspensión o mora vigente. No se aplican descuentos.",
                'cobertura_medicamentos' => (object) [],
            ]);
        }

        // Construir mapa { medicamento_id: porcentaje_descuento } solo para los medicamentos adheridos al convenio
        $cobertura = [];
        foreach ($afiliado->convenio->medicamentos as $medCubierto) {
            $pct = $medCubierto->pivot->porcentaje_descuento !== null
                ? (float) $medCubierto->pivot->porcentaje_descuento
                : (float) $afiliado->convenio->porcentaje_descuento;
            $cobertura[$medCubierto->id] = $pct;
        }

        return response()->json([
            'status' => 'ok',
            'tiene_beneficio' => true,
            'estado_switch' => 'APROBADO',
            'rut' => $rutFormateado,
            'nombre_afiliado' => $afiliado->nombre_afiliado,
            'convenio_id' => $afiliado->convenio->id,
            'convenio_nombre' => $afiliado->convenio->nombre,
            'codigo_switch' => 'IMED-' . mt_rand(100000, 999999),
            'mensaje' => "Beneficio APROBADO en línea para {$afiliado->nombre_afiliado} ({$afiliado->convenio->nombre}). Descuento aplicado exclusivamente a los productos adheridos al vademécum.",
            'cobertura_medicamentos' => $cobertura,
        ]);
    }

    /**
     * Obtiene el mapa { medicamento_id => %_descuento } para un convenio dado.
     */
    private function obtenerMapaVademecum(?int $convenioId): array
    {
        if (!$convenioId) {
            return [];
        }
        $convenio = Convenio::with('medicamentos')->find($convenioId);
        if (!$convenio || !$convenio->activo) {
            return [];
        }

        // Si el convenio tiene medicamentos específicos asociados en convenio_medicamento, solo esos tienen descuento
        if ($convenio->medicamentos->count() > 0) {
            $mapa = [];
            foreach ($convenio->medicamentos as $med) {
                $mapa[$med->id] = $med->pivot->porcentaje_descuento !== null
                    ? (float) $med->pivot->porcentaje_descuento
                    : (float) $convenio->porcentaje_descuento;
            }
            return $mapa;
        }

        return [];
    }

    private function normalizeCartItems(Request $request): array
    {
        if ($request->has('items') && is_array($request->input('items')) && count($request->input('items')) > 0) {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.medicamento_id' => 'required|integer|exists:medicamentos,id',
                'items.*.cantidad' => 'required|integer|min:1',
            ]);
            return $request->input('items');
        }

        $request->validate([
            'medicamento_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1',
        ]);

        return [[
            'medicamento_id' => (int) $request->medicamento_id,
            'cantidad' => (int) $request->cantidad,
        ]];
    }

    private function buildDashboardPayload(): array
    {
        $medicamentos = Medicamento::with(['lotes' => function ($q) {
            $q->orderBy('fecha_vencimiento', 'asc');
        }])->get();

        $lotes = Lote::with('medicamento')->orderBy('fecha_vencimiento', 'asc')->get();

        $totalMedicamentos = $medicamentos->count();
        $stockVitrinaTotal = (int) $lotes->sum('stock_vitrina');
        $stockBodegaTotal = (int) $lotes->sum('stock_bodega');

        $valorInventario = 0;
        foreach ($medicamentos as $med) {
            $unidadesMed = $med->lotes->sum('stock_vitrina') + $med->lotes->sum('stock_bodega');
            $valorInventario += $unidadesMed * (float) $med->precio_unitario;
        }

        $semaforoCritico = 0;
        $semaforoProximo = 0;
        $semaforoOptimo = 0;

        foreach ($lotes->where('stock_vitrina', '>', 0) as $lote) {
            $dias = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
            if ($dias <= 30) {
                $semaforoCritico++;
            } elseif ($dias <= 90) {
                $semaforoProximo++;
            } else {
                $semaforoOptimo++;
            }
        }

        $lotesPorVencer = $lotes->filter(function ($lote) {
            return $lote->stock_vitrina > 0 && $lote->fecha_vencimiento <= now()->addDays(90);
        })->values();

        $ventasHoyQuery = Venta::whereDate('created_at', today());
        $ventasHoy = (float) (clone $ventasHoyQuery)->sum('total');
        $descuentosHoy = (float) (clone $ventasHoyQuery)->sum('descuento');
        $cantidadVentasHoy = (int) (clone $ventasHoyQuery)->count();
        $ticketPromedio = $cantidadVentasHoy > 0 ? round($ventasHoy / $cantidadVentasHoy) : 0;

        $comisionTbkHoy = (float) (clone $ventasHoyQuery)->sum('comision_transbank');
        $ivaTbkHoy = (float) (clone $ventasHoyQuery)->sum('iva_comision');
        $costoTotalTbkHoy = $comisionTbkHoy + $ivaTbkHoy;
        $abonoLiquidoHoy = (float) (clone $ventasHoyQuery)->sum('abono_liquido');

        $totalControlados = LibroControlado::count();

        $porMetodo = Venta::select('metodo_pago', DB::raw('SUM(total) as monto'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('metodo_pago')
            ->get()
            ->keyBy('metodo_pago');

        $metodosPagoData = [
            'efectivo' => (float) ($porMetodo['efectivo']->monto ?? 0),
            'debito' => (float) ($porMetodo['debito']->monto ?? 0),
            'prepago' => (float) ($porMetodo['prepago']->monto ?? 0),
            'credito' => (float) ($porMetodo['credito']->monto ?? 0),
            'transferencia' => (float) ($porMetodo['transferencia']->monto ?? 0),
        ];

        $ultimasVentas = Venta::with(['detalles.medicamento', 'convenio'])
            ->latest()
            ->take(10)
            ->get();

        $estadoCatalogo = $medicamentos->map(function ($med) {
            $vitrina = (int) $med->lotes->sum('stock_vitrina');
            $bodega = (int) $med->lotes->sum('stock_bodega');
            $proximoLote = $med->lotes->where('stock_vitrina', '>', 0)->first();

            return [
                'id' => $med->id,
                'codigo_barra' => $med->codigo_barra,
                'nombre_comercial' => $med->nombre_comercial,
                'principio_activo' => $med->principio_activo,
                'laboratorio' => $med->laboratorio,
                'precio_unitario' => (float) $med->precio_unitario,
                'es_controlado' => (bool) $med->es_controlado,
                'requiere_receta' => (bool) $med->requiere_receta,
                'stock_minimo' => (int) $med->stock_minimo,
                'stock_vitrina' => $vitrina,
                'stock_bodega' => $bodega,
                'proximo_lote' => $proximoLote ? $proximoLote->numero_lote : 'SIN STOCK',
                'proximo_vencimiento' => $proximoLote ? $proximoLote->fecha_vencimiento->format('d/m/Y') : '-',
                'dias_vencimiento' => $proximoLote ? (int) now()->startOfDay()->diffInDays($proximoLote->fecha_vencimiento, false) : null,
            ];
        });

        return [
            'totalMedicamentos' => $totalMedicamentos,
            'stockVitrinaTotal' => $stockVitrinaTotal,
            'stockBodegaTotal' => $stockBodegaTotal,
            'valorInventario' => $valorInventario,
            'semaforo' => [
                'critico' => $semaforoCritico,
                'proximo' => $semaforoProximo,
                'optimo' => $semaforoOptimo,
            ],
            'lotesPorVencer' => $lotesPorVencer,
            'ventasHoy' => $ventasHoy,
            'descuentosHoy' => $descuentosHoy,
            'cantidadVentasHoy' => $cantidadVentasHoy,
            'ticketPromedio' => $ticketPromedio,
            'comisionTbkHoy' => $comisionTbkHoy,
            'ivaTbkHoy' => $ivaTbkHoy,
            'costoTotalTbkHoy' => $costoTotalTbkHoy,
            'abonoLiquidoHoy' => $abonoLiquidoHoy,
            'totalControlados' => $totalControlados,
            'metodosPagoData' => $metodosPagoData,
            'ultimasVentas' => $ultimasVentas,
            'estadoCatalogo' => $estadoCatalogo,
            'timestamp' => now()->format('H:i:s'),
        ];
    }

    public function dashboard()
    {
        $data = $this->buildDashboardPayload();
        return view('dashboard', $data);
    }

    public function dashboardRealtime()
    {
        $data = $this->buildDashboardPayload();

        return response()->json([
            'status' => 'ok',
            'timestamp' => $data['timestamp'],
            'kpis' => [
                'totalMedicamentos' => $data['totalMedicamentos'],
                'stockVitrinaTotal' => $data['stockVitrinaTotal'],
                'stockBodegaTotal' => $data['stockBodegaTotal'],
                'valorInventario' => $data['valorInventario'],
                'ventasHoy' => $data['ventasHoy'],
                'descuentosHoy' => $data['descuentosHoy'],
                'cantidadVentasHoy' => $data['cantidadVentasHoy'],
                'ticketPromedio' => $data['ticketPromedio'],
                'comisionTbkHoy' => $data['comisionTbkHoy'],
                'ivaTbkHoy' => $data['ivaTbkHoy'],
                'costoTotalTbkHoy' => $data['costoTotalTbkHoy'],
                'abonoLiquidoHoy' => $data['abonoLiquidoHoy'],
                'alertasCount' => $data['lotesPorVencer']->count(),
                'totalControlados' => $data['totalControlados'],
            ],
            'semaforo' => $data['semaforo'],
            'metodosPago' => $data['metodosPagoData'],
            'estadoCatalogo' => $data['estadoCatalogo'],
            'ultimasVentas' => $data['ultimasVentas']->map(function ($v) {
                return [
                    'id' => $v->id,
                    'folio' => '#' . str_pad($v->id, 4, '0', STR_PAD_LEFT),
                    'hora' => $v->created_at->format('H:i:s'),
                    'convenio' => $v->convenio->nombre ?? 'Particular',
                    'rut_cliente' => $v->rut_cliente,
                    'metodo_pago' => strtoupper($v->metodo_pago),
                    'subtotal' => (float) $v->subtotal,
                    'descuento' => (float) $v->descuento,
                    'total' => (float) $v->total,
                    'tasa_transbank' => (float) $v->tasa_transbank,
                    'costo_tbk' => (float) ($v->comision_transbank + $v->iva_comision),
                    'abono_liquido' => (float) $v->abono_liquido,
                    'codigo_autorizacion_tbk' => $v->codigo_autorizacion_tbk,
                    'detalles' => $v->detalles->map(fn($d) => [
                        'medicamento' => $d->medicamento->nombre_comercial ?? 'Producto',
                        'lote' => $d->numero_lote,
                        'cantidad' => $d->cantidad,
                    ]),
                ];
            }),
        ]);
    }

    public function index()
    {
        $medicamentos = Medicamento::with([
            'lotes' => function ($q) {
                $q->where('stock_vitrina', '>', 0)->orderBy('fecha_vencimiento', 'asc');
            },
            'convenios'
        ])->get();

        $convenios = Convenio::with('medicamentos')->where('activo', true)->get();
        $afiliadosDemo = AfiliadoConvenio::with('convenio')->get();

        return view('pos.index', compact('medicamentos', 'convenios', 'afiliadosDemo'));
    }

    public function iniciarWebpay(Request $request)
    {
        $items = $this->normalizeCartItems($request);
        $convenioId = $request->filled('convenio_id') ? (int) $request->convenio_id : null;
        $mapaVademecum = $this->obtenerMapaVademecum($convenioId);

        $subtotal = 0;
        $descuento = 0;

        foreach ($items as $item) {
            $med = Medicamento::findOrFail($item['medicamento_id']);
            $stockDisponible = Lote::where('medicamento_id', $med->id)->sum('stock_vitrina');
            if ($stockDisponible < (int) $item['cantidad']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente en vitrina para {$med->nombre_comercial}.",
                ], 422);
            }
            $subLinea = (float) $med->precio_unitario * (int) $item['cantidad'];
            $subtotal += $subLinea;

            if (isset($mapaVademecum[$med->id])) {
                $descuento += round($subLinea * ($mapaVademecum[$med->id] / 100));
            }
        }

        $montoTotal = (int) max(50, round($subtotal - $descuento));
        $buyOrder = 'FARM-' . time() . '-' . mt_rand(100, 999);
        $sessionId = session()->getId() ?: ('SES-' . uniqid());

        session([
            'webpay_pending_sale' => [
                'items' => $items,
                'convenio_id' => $convenioId,
                'rut_cliente' => $request->input('rut_cliente'),
                'metodo_pago' => $request->input('metodo_pago', 'credito'),
                'rut_paciente' => $request->input('rut_paciente'),
                'nombre_paciente' => $request->input('nombre_paciente'),
                'rut_medico' => $request->input('rut_medico'),
                'nombre_medico' => $request->input('nombre_medico'),
                'numero_receta' => $request->input('numero_receta'),
            ],
        ]);

        $response = Http::withoutVerifying()->withHeaders([
            'Tbk-Api-Key-Id' => self::TBK_COMMERCE_CODE,
            'Tbk-Api-Key-Secret' => self::TBK_API_KEY,
            'Content-Type' => 'application/json',
        ])->post(self::TBK_SANDBOX_URL, [
            'buy_order' => $buyOrder,
            'session_id' => $sessionId,
            'amount' => $montoTotal,
            'return_url' => route('pos.webpay.retorno'),
        ]);

        if ($response->successful()) {
            $body = $response->json();
            return response()->json([
                'status' => 'success',
                'url' => $body['url'],
                'token' => $body['token'],
                'buy_order' => $buyOrder,
                'amount' => $montoTotal,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No se pudo conectar con Webpay Sandbox: ' . $response->body(),
        ], 500);
    }

    public function retornoWebpay(Request $request)
    {
        $token = $request->input('token_ws');
        $pending = session('webpay_pending_sale');

        if (!$token || !$pending) {
            return redirect()->route('pos.index')->with('webpay_error', 'Transacción anulada o expirada por el usuario en Webpay.');
        }

        $response = Http::withoutVerifying()->withHeaders([
            'Tbk-Api-Key-Id' => self::TBK_COMMERCE_CODE,
            'Tbk-Api-Key-Secret' => self::TBK_API_KEY,
            'Content-Type' => 'application/json',
        ])->put(self::TBK_SANDBOX_URL . '/' . $token);

        if (!$response->successful()) {
            return redirect()->route('pos.index')->with('webpay_error', 'Error al confirmar transacción con Transbank Webpay.');
        }

        $tbkData = $response->json();
        if (($tbkData['response_code'] ?? -1) !== 0 || ($tbkData['status'] ?? '') !== 'AUTHORIZED') {
            return redirect()->route('pos.index')->with('webpay_error', 'Pago rechazado por el banco emisor en Webpay (Código: ' . ($tbkData['response_code'] ?? 'N/A') . '). No se descontó stock FEFO.');
        }

        $paymentTypeCode = $tbkData['payment_type_code'] ?? 'VD';
        $metodoReal = in_array($paymentTypeCode, ['VD', 'VP'], true) ? 'debito' : 'credito';
        $authCode = 'TBK-' . ($tbkData['authorization_code'] ?? '000000');
        $last4 = $tbkData['card_detail']['card_number'] ?? 'XXXX';

        $subRequest = new Request(array_merge($pending, [
            'metodo_pago' => $metodoReal,
            'codigo_autorizacion_tbk' => $authCode,
        ]));

        $jsonResponse = $this->procesarVenta($subRequest);
        $resData = $jsonResponse->getData(true);
        session()->forget('webpay_pending_sale');

        if (($resData['status'] ?? '') === 'success') {
            return redirect()->route('pos.index')->with('webpay_success', [
                'venta_id' => $resData['venta_id'],
                'total' => $resData['total'],
                'buy_order' => $tbkData['buy_order'] ?? '',
                'authorization_code' => $authCode,
                'card_number' => $last4,
                'payment_type' => $paymentTypeCode,
                'lotes_fefo' => $resData['lotes_fefo'],
            ]);
        }

        return redirect()->route('pos.index')->with('webpay_error', $resData['message'] ?? 'Error al descontar stock FEFO.');
    }

    public function procesarVenta(Request $request)
    {
        $items = $this->normalizeCartItems($request);

        $request->validate([
            'convenio_id' => 'nullable|integer|exists:convenios,id',
            'rut_cliente' => 'nullable|string|max:25',
            'metodo_pago' => 'nullable|string|max:40',
            'rut_paciente' => 'nullable|string|max:20',
            'nombre_paciente' => 'nullable|string|max:150',
            'rut_medico' => 'nullable|string|max:20',
            'nombre_medico' => 'nullable|string|max:150',
            'numero_receta' => 'nullable|string|max:50',
        ]);

        try {
            return DB::transaction(function () use ($request, $items) {
                $lotesAplicadosGlobal = [];
                $lineasProcesadas = [];
                $subtotalGlobal = 0;
                $descuentoGlobal = 0;

                $convenioId = $request->filled('convenio_id') ? (int) $request->convenio_id : null;
                $mapaVademecum = $this->obtenerMapaVademecum($convenioId);

                foreach ($items as $cartItem) {
                    $medId = (int) $cartItem['medicamento_id'];
                    $cantidadSolicitada = (int) $cartItem['cantidad'];
                    $cantidadRestante = $cantidadSolicitada;

                    $medicamento = Medicamento::findOrFail($medId);

                    // Algoritmo FEFO: Selecciona el lote más próximo a vencer con stock
                    $lotes = Lote::where('medicamento_id', $medId)
                        ->where('stock_vitrina', '>', 0)
                        ->orderBy('fecha_vencimiento', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($lotes as $lote) {
                        if ($cantidadRestante <= 0) break;

                        $descontar = min($lote->stock_vitrina, $cantidadRestante);
                        $lote->stock_vitrina -= $descontar;
                        $lote->save();

                        $cantidadRestante -= $descontar;

                        $registroLote = [
                            'medicamento_id' => $medicamento->id,
                            'medicamento' => $medicamento->nombre_comercial,
                            'es_controlado' => (bool) $medicamento->es_controlado,
                            'precio_unitario' => (float) $medicamento->precio_unitario,
                            'lote_id' => $lote->id,
                            'lote' => $lote->numero_lote,
                            'fecha_vencimiento' => $lote->fecha_vencimiento->format('Y-m-d'),
                            'cantidad' => $descontar,
                        ];

                        $lotesAplicadosGlobal[] = [
                            'medicamento' => $medicamento->nombre_comercial,
                            'lote' => $lote->numero_lote,
                            'cantidad' => $descontar,
                        ];
                        $lineasProcesadas[] = $registroLote;
                    }

                    if ($cantidadRestante > 0) {
                        throw new \Exception('Stock insuficiente en vitrina para ' . $medicamento->nombre_comercial);
                    }

                    $subItem = (float) $medicamento->precio_unitario * $cantidadSolicitada;
                    $subtotalGlobal += $subItem;

                    // Descuento selectivo SOLO si este medicamento pertenece al vademécum del convenio
                    if (isset($mapaVademecum[$medicamento->id])) {
                        $descuentoGlobal += round($subItem * ($mapaVademecum[$medicamento->id] / 100));
                    }
                }

                $total = max(0, $subtotalGlobal - $descuentoGlobal);
                $metodoPago = $request->input('metodo_pago', 'efectivo');
                $authCodeOverride = $request->input('codigo_autorizacion_tbk');

                $tbk = self::calcularTarifaTransbank($total, $metodoPago, $authCodeOverride);

                $venta = Venta::create([
                    'user_id' => auth()->id(),
                    'convenio_id' => $convenioId,
                    'rut_cliente' => $request->input('rut_cliente'),
                    'subtotal' => $subtotalGlobal,
                    'descuento' => $descuentoGlobal,
                    'total' => $total,
                    'metodo_pago' => $metodoPago,
                    'tasa_transbank' => $tbk['tasa_transbank'],
                    'comision_transbank' => $tbk['comision_transbank'],
                    'iva_comision' => $tbk['iva_comision'],
                    'abono_liquido' => $tbk['abono_liquido'],
                    'codigo_autorizacion_tbk' => $tbk['codigo_autorizacion_tbk'],
                    'estado' => 'completada',
                ]);

                foreach ($lineasProcesadas as $linea) {
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'medicamento_id' => $linea['medicamento_id'],
                        'lote_id' => $linea['lote_id'],
                        'numero_lote' => $linea['lote'],
                        'cantidad' => $linea['cantidad'],
                        'precio_unitario' => $linea['precio_unitario'],
                        'subtotal' => $linea['precio_unitario'] * $linea['cantidad'],
                    ]);

                    if ($linea['es_controlado']) {
                        LibroControlado::create([
                            'venta_id' => $venta->id,
                            'medicamento_id' => $linea['medicamento_id'],
                            'lote_id' => $linea['lote_id'],
                            'rut_paciente' => $request->input('rut_paciente') ?: ($request->input('rut_cliente') ?: '15.432.987-K'),
                            'nombre_paciente' => $request->input('nombre_paciente') ?: 'Paciente Controlado',
                            'rut_medico' => $request->input('rut_medico') ?: '10.222.333-4',
                            'nombre_medico' => $request->input('nombre_medico') ?: 'Dr. Médico Tratante',
                            'numero_receta' => $request->input('numero_receta') ?: ('REC-' . str_pad($venta->id, 4, '0', STR_PAD_LEFT)),
                            'cantidad_dispensada' => $linea['cantidad'],
                        ]);
                    }
                }

                try {
                    if (extension_loaded('mongodb')) {
                        AuditLog::create([
                            'evento' => 'VENTA_CARRITO_FEFO_TBK_CONVENIO',
                            'entidad' => 'Venta',
                            'entidad_id' => $venta->id,
                            'usuario_id' => auth()->id(),
                            'ip' => $request->ip(),
                            'payload' => [
                                'items_count' => count($items),
                                'rut_cliente' => $request->input('rut_cliente'),
                                'convenio_id' => $convenioId,
                                'subtotal' => $subtotalGlobal,
                                'descuento' => $descuentoGlobal,
                                'total' => $total,
                                'transbank' => $tbk,
                                'lotes_fefo' => $lotesAplicadosGlobal,
                            ],
                            'fecha_evento' => now()->toIso8601String(),
                        ]);
                    }
                } catch (\Throwable $mongoEx) {
                    Log::warning('No se pudo registrar auditoría en MongoDB: ' . $mongoEx->getMessage());
                }

                return response()->json([
                    'status' => 'success',
                    'venta_id' => $venta->id,
                    'subtotal' => $subtotalGlobal,
                    'descuento' => $descuentoGlobal,
                    'total' => $total,
                    'transbank' => $tbk,
                    'lotes_fefo' => $lotesAplicadosGlobal,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
