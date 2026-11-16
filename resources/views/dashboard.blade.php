@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Dashboard Operativo</h2>
            <p class="text-muted mb-0">Monitoreo FEFO, Stock en Vitrina y Auditoría</p>
        </div>
        <a href="{{ route('pos.index') }}" class="btn btn-verde-medico px-4">+ Nueva Venta POS</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Catálogo Medicamentos</h6>
                    <h3 class="fw-bold mb-0">{{ $totalMedicamentos }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Unidades en Vitrina</h6>
                    <h3 class="fw-bold text-primary mb-0">{{ $stockVitrinaTotal }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Lotes Próximos a Vencer (60d)</h6>
                    <h3 class="fw-bold text-danger mb-0">{{ $lotesPorVencer->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Ventas del Día</h6>
                    <h3 class="fw-bold text-success mb-0">${{ number_format($ventasHoy, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold text-danger">
                     Prioridad de Salida FEFO (Próximos Vencimientos)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Lote</th>
                                <th>Vencimiento</th>
                                <th>Vitrina</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lotesPorVencer as $lote)
                                <tr>
                                    <td>{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</td>
                                    <td><span class="badge bg-secondary">{{ $lote->numero_lote }}</span></td>
                                    <td class="text-danger fw-semibold">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td>{{ $lote->stock_vitrina }} u.</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Sin lotes próximos a vencer en los siguientes 60 días.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    Últimas Transacciones POS
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th># Venta</th>
                                <th>Convenio</th>
                                <th>Método</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimasVentas as $venta)
                                <tr>
                                    <td>#{{ $venta->id }}</td>
                                    <td>{{ $venta->convenio->nombre ?? 'Particular' }}</td>
                                    <td><span class="badge bg-info text-dark text-uppercase">{{ $venta->metodo_pago }}</span></td>
                                    <td class="fw-bold text-success">${{ number_format($venta->total, 0, ',', '.') }}</td>
                                    <td class="text-muted small">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Aún no se registran ventas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
