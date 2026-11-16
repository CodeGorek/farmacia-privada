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
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    private function buildDashboardPayload(): array
    {
        $medicamentos = Medicamento::with(['lotes' => function ($q) {
            $q->orderBy('fecha_vencimiento', 'asc');
        }])->get();

        $lotes = Lote::with('medicamento')->orderBy('fecha_vencimiento', 'asc')->get();

        $totalMedicamentos = $medicamentos->count();
        $stockVitrinaTotal = (int) $lotes->sum('stock_vitrina');
        $stockBodegaTotal = (int) $lotes->sum('stock_bodega');

        // Valorización del inventario (Vitrina + Bodega)
        $valorInventario = 0;
        foreach ($medicamentos as $med) {
            $unidadesMed = $med->lotes->sum('stock_vitrina') + $med->lotes->sum('stock_bodega');
            $valorInventario += $unidadesMed * (float) $med->precio_unitario;
        }

        // Semáforo FEFO de lotes con stock
        $semaforoCritico = 0;   // <= 30 días
        $semaforoProximo = 0;   // 31 a 90 días
        $semaforoOptimo = 0;    // > 90 días

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

        // Métricas de ventas del día
        $ventasHoyQuery = Venta::whereDate('created_at', today());
        $ventasHoy = (float) (clone $ventasHoyQuery)->sum('total');
        $descuentosHoy = (float) (clone $ventasHoyQuery)->sum('descuento');
        $cantidadVentasHoy = (int) (clone $ventasHoyQuery)->count();
        $ticketPromedio = $cantidadVentasHoy > 0 ? round($ventasHoy / $cantidadVentasHoy) : 0;

        $totalControlados = LibroControlado::count();

        // Ventas por método de pago
        $porMetodo = Venta::select('metodo_pago', DB::raw('SUM(total) as monto'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('metodo_pago')
            ->get()
            ->keyBy('metodo_pago');

        $metodosPagoData = [
            'efectivo' => (float) ($porMetodo['efectivo']->monto ?? 0),
            'debito' => (float) ($porMetodo['debito']->monto ?? 0),
            'credito' => (float) ($porMetodo['credito']->monto ?? 0),
            'transferencia' => (float) ($porMetodo['transferencia']->monto ?? 0),
        ];

        // Últimas 10 ventas para el gráfico de tendencia en tiempo real y tabla
        $ultimasVentas = Venta::with(['detalles.medicamento', 'convenio'])
            ->latest()
            ->take(10)
            ->get();

        // Estado de medicamentos para matriz ejecutiva
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

    public function procesarVenta(Request $request)
    {
        // Validación de seguridad (Sanitización)
        $request->validate([
            'medicamento_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1',
            'convenio_id' => 'nullable|integer|exists:convenios,id',
            'metodo_pago' => 'nullable|string|max:40',
            'rut_paciente' => 'nullable|string|max:20',
            'nombre_paciente' => 'nullable|string|max:150',
            'rut_medico' => 'nullable|string|max:20',
            'nombre_medico' => 'nullable|string|max:150',
            'numero_receta' => 'nullable|string|max:50',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $cantidadRestante = (int) $request->cantidad;
                $lotesAplicados = [];

                $medicamento = Medicamento::findOrFail($request->medicamento_id);

                // Algoritmo FEFO: Selecciona el lote más próximo a vencer con stock
                $lotes = Lote::where('medicamento_id', $request->medicamento_id)
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
                    $lotesAplicados[] = [
                        'lote_id' => $lote->id,
                        'lote' => $lote->numero_lote,
                        'fecha_vencimiento' => $lote->fecha_vencimiento->format('Y-m-d'),
                        'cantidad' => $descontar,
                    ];
                }

                if ($cantidadRestante > 0) {
                    throw new \Exception('Stock insuficiente en vitrina');
                }

                // Cálculo de totales y convenio
                $precioUnitario = (float) $medicamento->precio_unitario;
                $subtotal = $precioUnitario * (int) $request->cantidad;
                $descuento = 0;

                if ($request->filled('convenio_id')) {
                    $convenio = Convenio::find($request->convenio_id);
                    if ($convenio && $convenio->activo) {
                        $descuento = round($subtotal * ((float) $convenio->porcentaje_descuento / 100), 2);
                    }
                }

                $total = max(0, $subtotal - $descuento);

                // Registro de la venta en la tabla 'ventas'
                $venta = Venta::create([
                    'user_id' => auth()->id(),
                    'convenio_id' => $request->convenio_id,
                    'subtotal' => $subtotal,
                    'descuento' => $descuento,
                    'total' => $total,
                    'metodo_pago' => $request->input('metodo_pago', 'efectivo'),
                    'estado' => 'completada',
                ]);

                foreach ($lotesAplicados as $itemLote) {
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'medicamento_id' => $medicamento->id,
                        'lote_id' => $itemLote['lote_id'],
                        'numero_lote' => $itemLote['lote'],
                        'cantidad' => $itemLote['cantidad'],
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $precioUnitario * $itemLote['cantidad'],
                    ]);

                    if ($medicamento->es_controlado) {
                        LibroControlado::create([
                            'venta_id' => $venta->id,
                            'medicamento_id' => $medicamento->id,
                            'lote_id' => $itemLote['lote_id'],
                            'rut_paciente' => $request->input('rut_paciente') ?: '15.432.987-K',
                            'nombre_paciente' => $request->input('nombre_paciente') ?: 'Paciente Controlado',
                            'rut_medico' => $request->input('rut_medico') ?: '10.222.333-4',
                            'nombre_medico' => $request->input('nombre_medico') ?: 'Dr. Médico Tratante',
                            'numero_receta' => $request->input('numero_receta') ?: ('REC-' . str_pad($venta->id, 4, '0', STR_PAD_LEFT)),
                            'cantidad_dispensada' => $itemLote['cantidad'],
                        ]);
                    }
                }

                // Registro de auditoría en MongoDB (tolerante a fallos si el demonio local no está activo)
                try {
                    if (extension_loaded('mongodb')) {
                        AuditLog::create([
                            'evento' => 'VENTA_PROCESADA_FEFO',
                            'entidad' => 'Venta',
                            'entidad_id' => $venta->id,
                            'usuario_id' => auth()->id(),
                            'ip' => $request->ip(),
                            'payload' => [
                                'medicamento_id' => $medicamento->id,
                                'nombre_comercial' => $medicamento->nombre_comercial,
                                'cantidad_total' => (int) $request->cantidad,
                                'total' => $total,
                                'lotes_fefo' => $lotesAplicados,
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
                    'lotes_fefo' => $lotesAplicados,
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
