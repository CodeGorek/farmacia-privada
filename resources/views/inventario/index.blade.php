@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold mb-0">Gestión de Inventario, Lotes FEFO y Controlados</h2>
        <p class="text-muted mb-0">Administración de catálogo farmacológico, recepción de lotes con fecha de vencimiento y traspaso Bodega → Vitrina.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Nuevo Medicamento -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">1. Registrar Nuevo Medicamento</div>
                <div class="card-body">
                    <form action="{{ route('inventario.medicamentos.store') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Código de Barra</label>
                                <input type="text" name="codigo_barra" class="form-control form-control-sm" placeholder="7800000001" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Nombre Comercial</label>
                                <input type="text" name="nombre_comercial" class="form-control form-control-sm" placeholder="Paracetamol 500mg Caja x16" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Principio Activo</label>
                                <input type="text" name="principio_activo" class="form-control form-control-sm" placeholder="Paracetamol" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Laboratorio</label>
                                <input type="text" name="laboratorio" class="form-control form-control-sm" placeholder="Laboratorio Chile">
                            </div>
                        </div>
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Precio Unitario ($)</label>
                                <input type="number" step="0.01" name="precio_unitario" class="form-control form-control-sm" value="2500" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" class="form-control form-control-sm" value="15" required>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requiere_receta" value="1" id="reqReceta">
                                    <label class="form-check-label small" for="reqReceta">Requiere Receta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="es_controlado" value="1" id="esControlado">
                                    <label class="form-check-label small" for="esControlado">Psicotrópico/Estup.</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-verde-medico btn-sm w-100">Guardar Medicamento</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Nuevo Lote FEFO -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">2. Recepcionar Lote (Índice FEFO)</div>
                <div class="card-body">
                    <form action="{{ route('inventario.lotes.store') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Medicamento</label>
                                <select name="medicamento_id" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($medicamentos as $med)
                                        <option value="{{ $med->id }}">{{ $med->nombre_comercial }} ({{ $med->codigo_barra }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Número de Lote</label>
                                <input type="text" name="numero_lote" class="form-control form-control-sm" placeholder="LOT-2026-A1" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Fecha Elaboración</label>
                                <input type="date" name="fecha_elaboracion" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Fecha Vencimiento (FEFO)</label>
                                <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stock Inicial Vitrina</label>
                                <input type="number" name="stock_vitrina" class="form-control form-control-sm" value="25" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stock Inicial Bodega</label>
                                <input type="number" name="stock_bodega" class="form-control form-control-sm" value="100" min="0" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Ingresar Lote al Inventario</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Lotes ordenados por Fecha de Vencimiento (FEFO) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Matriz de Lotes Activos (Ordenados por Fecha de Vencimiento FEFO)</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Lote</th>
                        <th>Medicamento</th>
                        <th>Elaboración</th>
                        <th>Vencimiento</th>
                        <th>Stock Vitrina</th>
                        <th>Stock Bodega</th>
                        <th>Abastecer Vitrina</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lotes as $lote)
                        <tr>
                            <td><code>{{ $lote->numero_lote }}</code></td>
                            <td>{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</td>
                            <td>{{ $lote->fecha_elaboracion->format('d/m/Y') }}</td>
                            <td class="fw-semibold">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</td>
                            <td class="fw-bold text-primary">{{ $lote->stock_vitrina }}</td>
                            <td>{{ $lote->stock_bodega }}</td>
                            <td>
                                @if($lote->stock_bodega > 0)
                                    <form action="{{ route('inventario.lotes.traspasar', $lote) }}" method="POST" class="d-flex gap-1">
                                        @csrf
                                        <input type="number" name="cantidad" class="form-control form-control-sm" style="width: 80px;" min="1" max="{{ $lote->stock_bodega }}" value="10">
                                        <button class="btn btn-outline-success btn-sm">Mover a Vitrina</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Sin stock bodega</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No hay lotes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
