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
    public function dashboard()
    {
        $totalMedicamentos = Medicamento::count();
        $stockVitrinaTotal = Lote::sum('stock_vitrina');
        $lotesPorVencer = Lote::with('medicamento')
            ->where('stock_vitrina', '>', 0)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(60))
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();
        $ventasHoy = Venta::whereDate('created_at', today())->sum('total');
        $ultimasVentas = Venta::with(['detalles.medicamento', 'convenio'])
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard', compact(
            'totalMedicamentos',
            'stockVitrinaTotal',
            'lotesPorVencer',
            'ventasHoy',
            'ultimasVentas'
        ));
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

                    if ($medicamento->es_controlado && $request->filled('numero_receta')) {
                        LibroControlado::create([
                            'venta_id' => $venta->id,
                            'medicamento_id' => $medicamento->id,
                            'lote_id' => $itemLote['lote_id'],
                            'rut_paciente' => $request->input('rut_paciente', 'N/A'),
                            'nombre_paciente' => $request->input('nombre_paciente', 'Paciente Controlado'),
                            'rut_medico' => $request->input('rut_medico', 'N/A'),
                            'nombre_medico' => $request->input('nombre_medico', 'Médico Tratante'),
                            'numero_receta' => $request->input('numero_receta'),
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
