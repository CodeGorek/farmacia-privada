@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Banner Principal -->
    <div class="p-4 mb-4 rounded-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
         style="background: linear-gradient(125deg, #0F172A 0%, #1E3A8A 60%, #059669 100%); box-shadow: 0 12px 30px -8px rgba(30, 58, 138, 0.35);">
        <div>
            <span class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill fw-semibold">
                <i class="bi bi-activity me-1"></i> Centro de Control Farmacéutico
            </span>
            <h2 class="fw-extrabold mb-1">Dashboard Operativo & Motor FEFO</h2>
            <p class="mb-0 text-white-50">Supervisión en tiempo real de stock en vitrina, caducidad de lotes (First Expired, First Out) y ventas del turno.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-light px-3 py-2 rounded-3 fw-semibold">
                <i class="bi bi-box-seam me-1"></i> Recepcionar Lotes
            </a>
            <a href="{{ route('pos.index') }}" class="btn btn-verde-medico px-4 py-2 rounded-3 fw-bold">
                <i class="bi bi-upc-scan me-1"></i> Abrir Punto de Venta (POS)
            </a>
        </div>
    </div>

    <!-- Tarjetas KPI -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card p-3 h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Catálogo Activo</div>
                    <h3 class="fw-bold mb-0">{{ $totalMedicamentos }} <span class="fs-6 fw-normal text-muted">productos</span></h3>
                </div>
                <div class="kpi-icon bg-primary-subtle text-primary">
                    <i class="bi bi-capsule"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card p-3 h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Unidades en Vitrina</div>
                    <h3 class="fw-bold text-primary mb-0">{{ number_format($stockVitrinaTotal, 0, ',', '.') }} <span class="fs-6 fw-normal text-muted">u.</span></h3>
                </div>
                <div class="kpi-icon bg-info-subtle text-info">
                    <i class="bi bi-shop-window"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card p-3 h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Alertas Vencimiento (60d)</div>
                    <h3 class="fw-bold {{ $lotesPorVencer->count() > 0 ? 'text-danger' : 'text-success' }} mb-0">
                        {{ $lotesPorVencer->count() }} <span class="fs-6 fw-normal text-muted">lotes</span>
                    </h3>
                </div>
                <div class="kpi-icon {{ $lotesPorVencer->count() > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card p-3 h-100 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold mb-1">Recaudación del Día</div>
                    <h3 class="fw-bold text-success mb-0 text-nowrap">${{ number_format($ventasHoy, 0, ',', '.') }}</h3>
                </div>
                <div class="kpi-icon bg-success-subtle text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tabla Prioridad FEFO -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 text-danger">
                        <i class="bi bi-hourglass-split fs-5"></i>
                        <span>Prioridad de Salida FEFO (Vencimientos ≤ 60 días)</span>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">{{ $lotesPorVencer->count() }} en alerta</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Código Lote</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end">Vitrina</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lotesPorVencer as $lote)
                                    @php
                                        $diasRestantes = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</div>
                                            <div class="text-muted small font-mono"><i class="bi bi-upc me-1"></i>{{ $lote->medicamento->codigo_barra ?? '' }}</div>
                                        </td>
                                        <td><span class="badge bg-dark font-mono">{{ $lote->numero_lote }}</span></td>
                                        <td>
                                            <div class="fw-bold text-danger">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</div>
                                            <span class="badge bg-danger-subtle text-danger small">
                                                {{ $diasRestantes >= 0 ? "Vence en {$diasRestantes} días" : "Vencido" }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold fs-6">{{ $lote->stock_vitrina }} u.</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-success mb-2"><i class="bi bi-check2-circle fs-1"></i></div>
                                            <div class="fw-bold">Inventario Saludable</div>
                                            <div class="text-muted small">No existen lotes en vitrina próximos a vencer en los siguientes 60 días.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas Transacciones POS -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-receipt-cutoff text-primary fs-5"></i>
                        <span>Últimas Transacciones POS & Lotes Descontados</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Tiempo Real</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Folio</th>
                                    <th>Detalle & Lotes FEFO</th>
                                    <th>Convenio / Pago</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ultimasVentas as $venta)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark font-mono">#{{ str_pad($venta->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $venta->created_at->format('H:i:s') }}</div>
                                        </td>
                                        <td>
                                            @foreach($venta->detalles as $det)
                                                <div class="small">
                                                    <span class="fw-semibold">{{ $det->medicamento->nombre_comercial ?? 'Producto' }}</span>
                                                    <span class="badge bg-primary-subtle text-primary font-mono ms-1">{{ $det->numero_lote }} (x{{ $det->cantidad }})</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $venta->convenio->nombre ?? 'Particular' }}</div>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-uppercase" style="font-size: 0.68rem;">
                                                {{ $venta->metodo_pago }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-success text-nowrap">
                                            ${{ number_format($venta->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                            Aún no se registran ventas en este turno.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
