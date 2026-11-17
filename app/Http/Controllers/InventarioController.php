<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use App\Models\Lote;
use App\Models\LibroControlado;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index()
    {
        $medicamentos = Medicamento::with('lotes')->orderBy('nombre_comercial')->get();
        $lotes = Lote::with('medicamento')->orderBy('fecha_vencimiento', 'asc')->get();
        $controlados = LibroControlado::with(['medicamento', 'lote', 'venta'])->latest()->take(20)->get();

        return view('inventario.index', compact('medicamentos', 'lotes', 'controlados'));
    }

    public function storeMedicamento(Request $request)
    {
        if (auth()->check() && auth()->user()->rol === 'vendedor') {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: El rol Vendedor solo tiene permisos de consulta de stock y traspaso a vitrina. Solo el Administrador o Químico Farmacéutico pueden registrar nuevos medicamentos o lotes.'
            );
        }

        $validated = $request->validate([
            'codigo_barra' => 'required|string|max:50|unique:medicamentos,codigo_barra',
            'nombre_comercial' => 'required|string|max:150',
            'principio_activo' => 'required|string|max:150',
            'laboratorio' => 'nullable|string|max:100',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:1',
            'requiere_receta' => 'nullable|boolean',
            'es_controlado' => 'nullable|boolean',
        ]);

        $validated['requiere_receta'] = $request->boolean('requiere_receta');
        $validated['es_controlado'] = $request->boolean('es_controlado');

        Medicamento::create($validated);

        return redirect()->route('inventario.index')->with('success', 'Medicamento registrado exitosamente.');
    }

    public function storeLote(Request $request)
    {
        if (auth()->check() && auth()->user()->rol === 'vendedor') {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: El rol Vendedor solo tiene permisos de consulta de stock y traspaso a vitrina. Solo el Administrador o Químico Farmacéutico pueden registrar nuevos medicamentos o lotes.'
            );
        }

        $validated = $request->validate([
            'medicamento_id' => 'required|exists:medicamentos,id',
            'numero_lote' => 'required|string|max:30',
            'fecha_elaboracion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_elaboracion',
            'stock_vitrina' => 'required|integer|min:0',
            'stock_bodega' => 'required|integer|min:0',
        ]);

        Lote::create($validated);

        return redirect()->route('inventario.index')->with('success', 'Lote ingresado correctamente al inventario FEFO.');
    }

    public function traspasarBodegaAVitrina(Request $request, Lote $lote)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $lote) {
            $loteBloqueado = Lote::lockForUpdate()->findOrFail($lote->id);
            $mover = min($loteBloqueado->stock_bodega, (int) $request->cantidad);

            if ($mover <= 0) {
                throw new \Exception('El lote no dispone de stock en bodega.');
            }

            $loteBloqueado->stock_bodega -= $mover;
            $loteBloqueado->stock_vitrina += $mover;
            $loteBloqueado->save();
        });

        return redirect()->route('inventario.index')->with('success', 'Traspaso de bodega a vitrina realizado.');
    }
}
