<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\Convenio;
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
     * Normaliza la entrada para soportar tanto un carrito multi-producto (items[])
     * como una venta individual (medicamento_id, cantidad).
     */
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
        $medicamentos = Medicamento::with(['lotes' => function ($q) {
            $q->where('stock_vitrina', '>', 0)->orderBy('fecha_vencimiento', 'asc');
        }])->get();

        $convenios = Convenio::where('activo', true)->get();

        return view('pos.index', compact('medicamentos', 'convenios'));
    }

    public function iniciarWebpay(Request $request)
    {
        $items = $this->normalizeCartItems($request);

        $subtotal = 0;
        foreach ($items as $item) {
            $med = Medicamento::findOrFail($item['medicamento_id']);
            $stockDisponible = Lote::where('medicamento_id', $med->id)->sum('stock_vitrina');
            if ($stockDisponible < (int) $item['cantidad']) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente en vitrina para {$med->nombre_comercial}.",
                ], 422);
            }
            $subtotal += (float) $med->precio_unitario * (int) $item['cantidad'];
        }

        $descuento = 0;
        if ($request->filled('convenio_id')) {
            $conv = Convenio::find($request->convenio_id);
            if ($conv && $conv->activo) {
                $descuento = round($subtotal * ((float) $conv->porcentaje_descuento / 100));
            }
        }

        $montoTotal = (int) max(50, round($subtotal - $descuento));
        $buyOrder = 'FARM-' . time() . '-' . mt_rand(100, 999);
        $sessionId = session()->getId() ?: ('SES-' . uniqid());

        session([
            'webpay_pending_sale' => [
                'items' => $items,
                'convenio_id' => $request->input('convenio_id'),
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

                foreach ($items as $cartItem) {
                    $medId = (int) $cartItem['medicamento_id'];
                    $cantidadSolicitada = (int) $cartItem['cantidad'];
                    $cantidadRestante = $cantidadSolicitada;

                    $medicamento = Medicamento::findOrFail($medId);

                    // Algoritmo FEFO: Selecciona el lote más próximo a vencer con stock
                    $lotes = Lote::where('medicamento_id', $medId)
                        ->where('stock_vitrina', '>', 0)
                        ->orderBy('fecha_vencimiento', 'asc')
                        ->lockForUpdate() // Evita condiciones de carrera
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

                    $subtotalGlobal += (float) $medicamento->precio_unitario * $cantidadSolicitada;
                }

                $descuento = 0;
                if ($request->filled('convenio_id')) {
                    $convenio = Convenio::find($request->convenio_id);
                    if ($convenio && $convenio->activo) {
                        $descuento = round($subtotalGlobal * ((float) $convenio->porcentaje_descuento / 100), 2);
                    }
                }

                $total = max(0, $subtotalGlobal - $descuento);
                $metodoPago = $request->input('metodo_pago', 'efectivo');
                $authCodeOverride = $request->input('codigo_autorizacion_tbk');

                $tbk = self::calcularTarifaTransbank($total, $metodoPago, $authCodeOverride);

                $venta = Venta::create([
                    'user_id' => auth()->id(),
                    'convenio_id' => $request->convenio_id,
                    'subtotal' => $subtotalGlobal,
                    'descuento' => $descuento,
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
                            'rut_paciente' => $request->input('rut_paciente') ?: '15.432.987-K',
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
                            'evento' => 'VENTA_CARRITO_FEFO_TBK',
                            'entidad' => 'Venta',
                            'entidad_id' => $venta->id,
                            'usuario_id' => auth()->id(),
                            'ip' => $request->ip(),
                            'payload' => [
                                'items_count' => count($items),
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
