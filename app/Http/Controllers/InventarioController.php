<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use App\Models\Lote;
use App\Models\LibroControlado;
use App\Models\AuditoriaSistema;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public const LABORATORIOS_CHILE = [
        'Laboratorio Chile',
        'Laboratorio Saval',
        'Mintlab',
        'Bagó / CENABAST',
        'Laboratorio Bagó',
        'Laboratorios Andrómaco',
        'CFR / Recalcine',
        'Laboratorio Pasteur',
        'Tecnofarma',
        'Laboratorio Silesia',
        'Opko Chile',
        'MSD Internacional',
        'Pfizer',
        'Roche',
        'Bayer',
        'Novartis',
        'Sanofi',
        'Abbott',
        'GlaxoSmithKline (GSK)',
        'AstraZeneca',
        'Eurofarma',
        'Laboratorio Sanderson',
    ];

    public const CATEGORIAS_NO_MEDICAMENTOS = [
        'Dermocosmética',
        'Higiene Personal',
        'Insumos Médicos',
        'Bebé y Maternidad',
        'Suplementos y Vitaminas',
        'Perfumería',
        'Ortopedia y Rehabilitación',
        'Otros Productos',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $medicamentos = Medicamento::with('lotes')->orderBy('nombre_comercial')->get();
        $soloMedicamentos = $medicamentos->where('es_medicamento', true)->values();
        $productosNoMed = $medicamentos->where('es_medicamento', false)->values();

        $lotesQuery = Lote::with('medicamento')->orderBy('fecha_vencimiento', 'asc');
        if ($q !== '') {
            $lotesQuery->where(function ($builder) use ($q) {
                $builder->where('numero_lote', 'like', "%{$q}%")
                    ->orWhereHas('medicamento', function ($m) use ($q) {
                        $m->where('nombre_comercial', 'like', "%{$q}%")
                            ->orWhere('codigo_barra', 'like', "%{$q}%")
                            ->orWhere('principio_activo', 'like', "%{$q}%")
                            ->orWhere('laboratorio', 'like', "%{$q}%")
                            ->orWhere('categoria_producto', 'like', "%{$q}%");
                    });
            });
        }

        $lotes = $lotesQuery->get();
        $controlados = LibroControlado::with(['medicamento', 'lote', 'venta'])->latest()->take(20)->get();
        $laboratorios = self::LABORATORIOS_CHILE;
        $categoriasNoMed = self::CATEGORIAS_NO_MEDICAMENTOS;

        return view('inventario.index', compact(
            'medicamentos',
            'soloMedicamentos',
            'productosNoMed',
            'lotes',
            'controlados',
            'laboratorios',
            'categoriasNoMed',
            'q'
        ));
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
            'laboratorio' => 'required|string|max:100',
            'laboratorio_otro' => 'nullable|string|max:100',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:1',
            'requiere_receta' => 'nullable|boolean',
            'es_controlado' => 'nullable|boolean',
        ]);

        if ($validated['laboratorio'] === '__OTRO__' && !empty($validated['laboratorio_otro'])) {
            $validated['laboratorio'] = trim($validated['laboratorio_otro']);
        } elseif ($validated['laboratorio'] === '__OTRO__') {
            $validated['laboratorio'] = 'Otro Laboratorio';
        }
        unset($validated['laboratorio_otro']);

        $validated['es_medicamento'] = true;
        $validated['categoria_producto'] = 'Medicamento';
        $validated['requiere_receta'] = $request->boolean('requiere_receta');
        $validated['es_controlado'] = $request->boolean('es_controlado');

        $med = Medicamento::create($validated);

        AuditoriaSistema::registrar(
            'CREAR_MEDICAMENTO',
            'Inventario',
            "Registró medicamento {$med->nombre_comercial} (EAN: {$med->codigo_barra}, Lab: {$med->laboratorio})."
        );

        return redirect()->route('inventario.index')->with('success', 'Medicamento registrado exitosamente en el catálogo.');
    }

    /**
     * Sección aparte para ingresar productos que NO son medicamentos (Perfumería, Dermocosmética, Insumos, etc.)
     */
    public function storeProductoNoMedicamento(Request $request)
    {
        if (auth()->check() && auth()->user()->rol === 'vendedor') {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: Solo el Administrador o Químico Farmacéutico pueden registrar productos en el catálogo.'
            );
        }

        $validated = $request->validate([
            'codigo_barra' => 'required|string|max:50|unique:medicamentos,codigo_barra',
            'nombre_comercial' => 'required|string|max:150',
            'categoria_producto' => 'required|string|max:60',
            'marca_fabricante' => 'nullable|string|max:100',
            'precio_unitario' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:1',
            'stock_inicial_vitrina' => 'nullable|integer|min:0',
            'stock_inicial_bodega' => 'nullable|integer|min:0',
        ]);

        $prod = Medicamento::create([
            'codigo_barra' => $validated['codigo_barra'],
            'nombre_comercial' => $validated['nombre_comercial'],
            'principio_activo' => $validated['categoria_producto'],
            'laboratorio' => $validated['marca_fabricante'] ?: 'Marca General',
            'es_medicamento' => false,
            'categoria_producto' => $validated['categoria_producto'],
            'precio_unitario' => $validated['precio_unitario'],
            'stock_minimo' => $validated['stock_minimo'],
            'requiere_receta' => false,
            'es_controlado' => false,
            'es_bioequivalente' => false,
            'es_cenabast' => false,
            'requiere_cadena_frio' => false,
        ]);

        $stockVitrina = (int) ($validated['stock_inicial_vitrina'] ?? 0);
        $stockBodega = (int) ($validated['stock_inicial_bodega'] ?? 0);
        if ($stockVitrina > 0 || $stockBodega > 0) {
            Lote::create([
                'medicamento_id' => $prod->id,
                'numero_lote' => 'PROD-' . date('Ym') . '-' . str_pad((string) $prod->id, 3, '0', STR_PAD_LEFT),
                'fecha_elaboracion' => today()->format('Y-m-d'),
                'fecha_vencimiento' => today()->addYears(2)->format('Y-m-d'),
                'stock_vitrina' => $stockVitrina,
                'stock_bodega' => $stockBodega,
            ]);
        }

        AuditoriaSistema::registrar(
            'CREAR_PRODUCTO',
            'Inventario No Farmacéutico',
            "Registró producto no farmacéutico {$prod->nombre_comercial} (Cat: {$prod->categoria_producto}, EAN: {$prod->codigo_barra})."
        );

        return redirect()->route('inventario.index')->with('success', "Producto no farmacéutico \"{$prod->nombre_comercial}\" registrado exitosamente.");
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

        $lote = Lote::create($validated);
        $med = Medicamento::find($validated['medicamento_id']);

        AuditoriaSistema::registrar(
            'RECEPCIONAR_LOTE',
            'Inventario',
            "Recepcionó lote {$lote->numero_lote} de " . ($med->nombre_comercial ?? 'Producto') . " (Vitrina: {$lote->stock_vitrina}, Bodega: {$lote->stock_bodega})."
        );

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

        AuditoriaSistema::registrar(
            'TRASPASO_VITRINA',
            'Bodega FEFO',
            "Traspasó {$request->cantidad} u. del lote {$lote->numero_lote} desde bodega hacia vitrina."
        );

        return redirect()->route('inventario.index')->with('success', 'Traspaso de bodega a vitrina realizado.');
    }

    /**
     * Aplicar descuento individual por lote (Liquidación FEFO o Descuento Diario) — Solo Admin / SuperAdmin
     */
    public function aplicarDescuentoLote(Request $request, Lote $lote)
    {
        if (!auth()->check() || !auth()->user()->isAdminOrSuper()) {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: Solo el Administrador o Super Administrador puede aplicar descuentos FEFO o descuentos diarios.'
            );
        }

        $validated = $request->validate([
            'descuento_porcentaje' => 'required|numeric|min:0|max:90',
            'motivo_descuento' => 'nullable|string|max:80',
        ]);

        $pct = round((float) $validated['descuento_porcentaje'], 2);
        $motivo = $pct > 0
            ? ($validated['motivo_descuento'] ?: 'Descuento Diario')
            : null;

        $lote->update([
            'descuento_porcentaje' => $pct,
            'motivo_descuento' => $motivo,
        ]);

        $medNombre = $lote->medicamento->nombre_comercial ?? 'Producto';
        AuditoriaSistema::registrar(
            'DESCUENTO_LOTE',
            'Inventario / Precios',
            $pct > 0
                ? "Aplicó descuento de {$pct}% ({$motivo}) al lote {$lote->numero_lote} ({$medNombre})."
                : "Quitó el descuento del lote {$lote->numero_lote} ({$medNombre})."
        );

        $msg = $pct > 0
            ? "Descuento de {$pct}% ({$motivo}) aplicado al lote {$lote->numero_lote}. Ya está activo en el POS."
            : "Descuento eliminado del lote {$lote->numero_lote}.";

        return redirect()->route('inventario.index')->with('success', $msg);
    }

    /**
     * Activar o desactivar promoción real 2x1 en un lote — Solo Admin / SuperAdmin
     */
    public function togglePromo2x1(Lote $lote)
    {
        if (!auth()->check() || !auth()->user()->isAdminOrSuper()) {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: Solo el Administrador o Super Administrador puede activar o desactivar promociones 2x1.'
            );
        }

        $lote->promo_2x1 = !$lote->promo_2x1;
        $lote->save();

        $medNombre = $lote->medicamento->nombre_comercial ?? 'Producto';
        $estado = $lote->promo_2x1 ? 'ACTIVADA' : 'DESACTIVADA';

        AuditoriaSistema::registrar(
            'PROMO_2X1',
            'Inventario / Precios',
            "Promoción 2x1 {$estado} en lote {$lote->numero_lote} ({$medNombre})."
        );

        return redirect()->route('inventario.index')->with(
            'success',
            "Promoción 2x1 {$estado} en el lote {$lote->numero_lote} ({$medNombre})."
        );
    }

    /**
     * Liquidación automática por prioridad FEFO (<= 30 días: 30% dcto, 31-60 días: 15% dcto) — Solo Admin / SuperAdmin
     */
    public function aplicarDescuentoFefoAuto()
    {
        if (!auth()->check() || !auth()->user()->isAdminOrSuper()) {
            return redirect()->route('inventario.index')->with(
                'rbac_warning',
                'Acceso Restringido: Solo el Administrador o Super Administrador puede activar la liquidación FEFO.'
            );
        }

        $actualizados = 0;
        $lotes = Lote::where('bloqueado_recall_isp', false)
            ->whereDate('fecha_vencimiento', '>=', today())
            ->get();

        foreach ($lotes as $lote) {
            $dias = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
            if ($dias <= 30) {
                $lote->update([
                    'descuento_porcentaje' => 30,
                    'motivo_descuento' => 'Liquidación FEFO Crítico (≤30d)',
                ]);
                $actualizados++;
            } elseif ($dias <= 60) {
                $lote->update([
                    'descuento_porcentaje' => 15,
                    'motivo_descuento' => 'Liquidación FEFO Prioritario (≤60d)',
                ]);
                $actualizados++;
            }
        }

        AuditoriaSistema::registrar(
            'LIQUIDACION_FEFO_AUTO',
            'Inventario / Precios',
            "Aplicó liquidación FEFO automática por prioridad de vencimiento a {$actualizados} lotes próximos a vencer."
        );

        return redirect()->route('inventario.index')->with(
            'success',
            "Liquidación FEFO aplicada automáticamente a {$actualizados} lote(s) próximos a vencer (≤30d: 30% dcto, 31–60d: 15% dcto). Ya rige en Caja POS."
        );
    }

    /**
     * Etiqueta térmica imprimible formato 50mm x 30mm
     */
    public function etiqueta50x30(Request $request, Medicamento $medicamento)
    {
        $medicamento->load('lotes');
        $lote = $request->query('lote_id')
            ? Lote::find($request->query('lote_id'))
            : $medicamento->lotes->first();
        $copias = max(1, min(30, (int) $request->query('copias', 1)));

        return view('inventario.etiqueta_50x30', compact('medicamento', 'lote', 'copias'));
    }
}
