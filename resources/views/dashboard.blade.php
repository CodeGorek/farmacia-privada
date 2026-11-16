@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Hero Banner con Estado de Telemetría en Tiempo Real -->
    <div class="p-4 mb-4 rounded-4 text-white position-relative overflow-hidden"
         style="background: linear-gradient(125deg, #0F172A 0%, #1E3A8A 55%, #059669 100%); box-shadow: 0 14px 32px -8px rgba(15, 23, 42, 0.35);">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-2">
                        <span class="scanner-dot" style="background-color: #fff;"></span>
                        EN VIVO • TELEMETRÍA ACTIVA (4s)
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-white px-3 py-1 rounded-pill font-mono small">
                        Última sincr.: <span id="liveTimestamp">{{ $timestamp }}</span>
                    </span>
                </div>
                <h2 class="fw-extrabold mb-1">Centro de Comando Farmacéutico & BI en Tiempo Real</h2>
                <p class="mb-0 text-white-50 small">
                    Monitoreo continuo del motor FEFO (First Expired, First Out), valorización de inventario, auditoría de psicotrópicos y flujo de caja POS.
                </p>
            </div>
            <div class="col-lg-5">
                <!-- Consultor Rápido de Precio/Stock con Lector de Código de Barras desde el Dashboard -->
                <div class="p-3 rounded-4" style="background: rgba(15, 23, 42, 0.55); border: 1px solid rgba(52, 211, 153, 0.45); backdrop-filter: blur(8px);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-uppercase" style="color: #34D399; font-size: 0.72rem; letter-spacing: 0.6px;">
                            <i class="bi bi-upc-scan me-1"></i>Verificador Rápido de Precio y Lote (Pistola HID)
                        </span>
                        <button type="button" id="btnRefreshNow" class="btn btn-sm btn-link text-white-50 text-decoration-none p-0 small">
                            <i class="bi bi-arrow-clockwise me-1"></i>Sincronizar
                        </button>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-success text-white border-0"><i class="bi bi-upc"></i></span>
                        <input type="text"
                               id="dashboardBarcodeCheck"
                               class="form-control font-mono fw-bold"
                               placeholder="Escanea cualquier producto para consultar precio y stock FEFO..."
                               autocomplete="off">
                    </div>
                    <div id="dashboardScanResult" class="mt-2 d-none small rounded-3 p-2 bg-dark bg-opacity-75 border border-secondary"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6 Tarjetas KPI Ejecutivas en Tiempo Real -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Ventas Hoy</span>
                    <div class="kpi-icon bg-success-subtle text-success" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-success mb-1 font-mono" id="kpiVentasHoy">${{ number_format($ventasHoy, 0, ',', '.') }}</h4>
                <div class="text-muted" style="font-size: 0.75rem;">
                    <span class="fw-bold text-dark" id="kpiCantidadVentas">{{ $cantidadVentasHoy }}</span> tickets • Prom: <span class="font-mono fw-semibold" id="kpiTicketPromedio">${{ number_format($ticketPromedio, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Stock Vitrina</span>
                    <div class="kpi-icon bg-primary-subtle text-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-shop-window"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-primary mb-1 font-mono"><span id="kpiStockVitrina">{{ number_format($stockVitrinaTotal, 0, ',', '.') }}</span> <small class="fs-6 fw-normal">u.</small></h4>
                <div class="text-muted" style="font-size: 0.75rem;">
                    En Bodega: <span class="fw-bold text-dark font-mono" id="kpiStockBodega">{{ number_format($stockBodegaTotal, 0, ',', '.') }}</span> u.
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Valor Inventario</span>
                    <div class="kpi-icon bg-info-subtle text-info" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-safe2"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-dark mb-1 font-mono" id="kpiValorInventario">${{ number_format($valorInventario, 0, ',', '.') }}</h4>
                <div class="text-muted" style="font-size: 0.75rem;">
                    <span class="fw-bold text-dark" id="kpiTotalMedicamentos">{{ $totalMedicamentos }}</span> SKU activos
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Abono Líquido TBK</span>
                    <div class="kpi-icon text-white" style="width: 38px; height: 38px; font-size: 1.1rem; background: linear-gradient(135deg, #D5006D 0%, #6B1D7A 100%);">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-dark mb-1 font-mono" id="kpiAbonoLiquido">${{ number_format($abonoLiquidoHoy, 0, ',', '.') }}</h4>
                <div class="text-muted" style="font-size: 0.73rem;">
                    Comisión+IVA TBK: <span class="fw-bold text-danger font-mono" id="kpiCostoTbk">-${{ number_format($costoTotalTbkHoy, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Alertas FEFO ≤90d</span>
                    <div class="kpi-icon bg-danger-subtle text-danger" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-hourglass-bottom"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-danger mb-1 font-mono"><span id="kpiAlertasCount">{{ $lotesPorVencer->count() }}</span> <small class="fs-6 fw-normal">lotes</small></h4>
                <div class="text-muted" style="font-size: 0.75rem;">
                    Críticos (≤30d): <span class="fw-bold text-danger" id="kpiCriticosCount">{{ $semaforo['critico'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">Libro Controlados</span>
                    <div class="kpi-icon bg-warning-subtle text-warning-emphasis" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                </div>
                <h4 class="fw-extrabold text-dark mb-1 font-mono"><span id="kpiControlados">{{ $totalControlados }}</span> <small class="fs-6 fw-normal">recetas</small></h4>
                <div class="text-muted" style="font-size: 0.75rem;">
                    Fiscalización ISP / SEREMI
                </div>
            </div>
        </div>
    </div>

    <!-- Fila de Gráficos Interactivos en Tiempo Real -->
    <div class="row g-4 mb-4">
        <!-- Gráfico de Flujo de Transacciones y Medios de Pago -->
        <div class="col-lg-8">
            <div class="card-pro h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <i class="bi bi-graph-up-arrow text-success me-2"></i>
                        <span>Curva de Ventas POS en Tiempo Real (Últimos Folios Emitidos)</span>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success-subtle text-success font-mono">Efectivo: ${{ number_format($metodosPagoData['efectivo'], 0, ',', '.') }}</span>
                        <span class="badge bg-primary-subtle text-primary font-mono">Débito: ${{ number_format($metodosPagoData['debito'], 0, ',', '.') }}</span>
                        <span class="badge bg-info-subtle text-info-emphasis font-mono">Crédito: ${{ number_format($metodosPagoData['credito'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 265px;">
                        <canvas id="chartVentasRealtime"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Semáforo Sanitario FEFO -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>
                        <span>Semáforo Sanitario FEFO</span>
                    </div>
                    <span class="badge bg-light text-dark border">Lotes en Vitrina</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div style="height: 185px;" class="position-relative">
                        <canvas id="chartSemaforoFefo"></canvas>
                    </div>
                    <div class="row text-center g-2 mt-2 pt-2 border-top">
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-danger-subtle">
                                <div class="fw-extrabold text-danger fs-5 font-mono" id="semCritico">{{ $semaforo['critico'] }}</div>
                                <div class="text-danger fw-semibold" style="font-size: 0.7rem;">Crítico (≤30d)</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-warning-subtle">
                                <div class="fw-extrabold text-warning-emphasis fs-5 font-mono" id="semProximo">{{ $semaforo['proximo'] }}</div>
                                <div class="text-warning-emphasis fw-semibold" style="font-size: 0.7rem;">Próximo (31-90d)</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-success-subtle">
                                <div class="fw-extrabold text-success fs-5 font-mono" id="semOptimo">{{ $semaforo['optimo'] }}</div>
                                <div class="text-success fw-semibold" style="font-size: 0.7rem;">Óptimo (>90d)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Matriz Ejecutiva de Disponibilidad por Medicamento y Próximo Lote FEFO -->
    <div class="card-pro mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <i class="bi bi-boxes text-primary me-2"></i>
                <span>Monitor de Disponibilidad en Vitrina, Bodega y Lote Activo FEFO</span>
            </div>
            <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-arrow-left-right me-1"></i>Gestionar Lotes y Traspasos
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Código EAN-13</th>
                            <th>Medicamento / Principio Activo</th>
                            <th>Régimen</th>
                            <th>Precio Unit.</th>
                            <th>1° Lote en Salir (FEFO)</th>
                            <th style="min-width: 190px;">Nivel Vitrina vs Mínimo</th>
                            <th class="text-center">Bodega</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEstadoCatalogo">
                        @foreach($estadoCatalogo as $item)
                            @php
                                $pct = min(100, round(($item['stock_vitrina'] / max(1, $item['stock_minimo'] * 2)) * 100));
                                $barColor = $item['stock_vitrina'] < $item['stock_minimo'] ? 'bg-danger' : 'bg-success';
                            @endphp
                            <tr>
                                <td class="font-mono small fw-bold text-secondary">
                                    <i class="bi bi-upc me-1 text-dark"></i>{{ $item['codigo_barra'] }}
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $item['nombre_comercial'] }}</div>
                                    <div class="text-muted small">{{ $item['principio_activo'] }} • {{ $item['laboratorio'] }}</div>
                                </td>
                                <td>
                                    @if($item['es_controlado'])
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-shield-exclamation me-1"></i>Controlado
                                        </span>
                                    @elseif($item['requiere_receta'])
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Receta Simple</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">Venta Directa</span>
                                    @endif
                                </td>
                                <td class="fw-bold font-mono">${{ number_format($item['precio_unitario'], 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-dark font-mono">{{ $item['proximo_lote'] }}</span>
                                    <div class="small {{ ($item['dias_vencimiento'] !== null && $item['dias_vencimiento'] <= 30) ? 'text-danger fw-bold' : 'text-muted' }}">
                                        Vence: {{ $item['proximo_vencimiento'] }}
                                        @if($item['dias_vencimiento'] !== null)
                                            ({{ $item['dias_vencimiento'] }}d)
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-bold {{ $item['stock_vitrina'] < $item['stock_minimo'] ? 'text-danger' : 'text-primary' }}">
                                            {{ $item['stock_vitrina'] }} u. en vitrina
                                        </span>
                                        <span class="text-muted">Mín: {{ $item['stock_minimo'] }}</span>
                                    </div>
                                    <div class="progress" style="height: 7px;">
                                        <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="text-center font-mono fw-semibold">{{ $item['stock_bodega'] }} u.</td>
                                <td class="text-end">
                                    <a href="{{ route('pos.index') }}?scan={{ $item['codigo_barra'] }}" class="btn btn-sm btn-verde-medico rounded-3 px-3">
                                        <i class="bi bi-cart-plus me-1"></i>Dispensar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fila Inferior: Cola Prioritaria FEFO y Feed de Transacciones en Tiempo Real -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 text-danger">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                        <span>Cola de Prioridad FEFO (Vencimientos ≤ 90 días)</span>
                    </div>
                    <span class="badge bg-danger text-white rounded-pill">{{ $lotesPorVencer->count() }} lotes</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Lote</th>
                                    <th>Caducidad</th>
                                    <th class="text-end">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lotesPorVencer as $lote)
                                    @php
                                        $diasRestantes = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold small">{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</div>
                                            <div class="text-muted font-mono" style="font-size: 0.72rem;">{{ $lote->medicamento->codigo_barra ?? '' }}</div>
                                        </td>
                                        <td><span class="badge bg-dark font-mono">{{ $lote->numero_lote }}</span></td>
                                        <td>
                                            <div class="fw-bold {{ $diasRestantes <= 30 ? 'text-danger' : 'text-warning-emphasis' }} small">
                                                {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                                            </div>
                                            <span class="badge {{ $diasRestantes <= 30 ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis' }}" style="font-size: 0.68rem;">
                                                En {{ $diasRestantes }} días
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold font-mono">{{ $lote->stock_vitrina }} u.</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-check2-all text-success fs-3 d-block"></i>
                                            Sin lotes próximos a vencer en los siguientes 90 días.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-broadcast-pin text-success fs-5"></i>
                        <span>Stream de Ventas POS y Trazabilidad de Lotes en Vivo</span>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill">Auto-Refresh 4s</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Folio / Hora</th>
                                    <th>Medicamentos & Lotes FEFO</th>
                                    <th>Convenio / Medio</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyUltimasVentas">
                                @foreach($ultimasVentas as $venta)
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark font-mono">#{{ str_pad($venta->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            <div class="text-muted font-mono" style="font-size: 0.72rem;">{{ $venta->created_at->format('H:i:s') }}</div>
                                        </td>
                                        <td>
                                            @foreach($venta->detalles as $det)
                                                <div class="small mb-1">
                                                    <span class="fw-bold">{{ $det->medicamento->nombre_comercial ?? 'Producto' }}</span>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono ms-1">
                                                        {{ $det->numero_lote }} (-{{ $det->cantidad }} u.)
                                                    </span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $venta->convenio->nombre ?? 'Particular' }}</div>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-uppercase" style="font-size: 0.68rem;">
                                                {{ $venta->metodo_pago }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-extrabold text-success font-mono">${{ number_format($venta->total, 0, ',', '.') }}</div>
                                            @if($venta->descuento > 0)
                                                <div class="text-muted font-mono" style="font-size: 0.7rem;">Dcto: -${{ number_format($venta->descuento, 0, ',', '.') }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
    const formatCLP = (val) => '$' + Math.round(val || 0).toLocaleString('es-CL');

    // Datos iniciales inyectados desde el servidor
    let catalogoActual = @json($estadoCatalogo);
    const ventasIniciales = @json($ultimasVentas).slice().reverse();
    const semaforoInicial = @json($semaforo);

    // 1. Gráfico de Ventas en Tiempo Real (Barras + Línea)
    const ctxVentas = document.getElementById('chartVentasRealtime').getContext('2d');
    const chartVentas = new Chart(ctxVentas, {
        type: 'bar',
        data: {
            labels: ventasIniciales.map(v => '#' + String(v.id).padStart(4, '0')),
            datasets: [
                {
                    type: 'line',
                    label: 'Tendencia Ticket ($ CLP)',
                    data: ventasIniciales.map(v => parseFloat(v.total)),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderWidth: 3,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: '#059669',
                },
                {
                    type: 'bar',
                    label: 'Monto Venta ($ CLP)',
                    data: ventasIniciales.map(v => parseFloat(v.total)),
                    backgroundColor: 'rgba(30, 64, 175, 0.78)',
                    borderRadius: 8,
                    maxBarThickness: 42,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (val) => '$' + val.toLocaleString('es-CL')
                    }
                }
            }
        }
    });

    // 2. Gráfico Semáforo FEFO
    const ctxSem = document.getElementById('chartSemaforoFefo').getContext('2d');
    const chartSemaforo = new Chart(ctxSem, {
        type: 'doughnut',
        data: {
            labels: ['Crítico (≤30d)', 'Próximo (31-90d)', 'Óptimo (>90d)'],
            datasets: [{
                data: [semaforoInicial.critico, semaforoInicial.proximo, semaforoInicial.optimo],
                backgroundColor: ['#EF4444', '#F59E0B', '#10B981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '66%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });

    // 3. Verificador Rápido de Precio y Stock por Pistola de Código de Barras en el Dashboard
    const inputCheck = document.getElementById('dashboardBarcodeCheck');
    const resultCheck = document.getElementById('dashboardScanResult');

    function consultarCodigoDashboard(codeRaw) {
        const code = (codeRaw || '').trim();
        if (!code) return;

        const med = catalogoActual.find(m => m.codigo_barra === code);
        resultCheck.classList.remove('d-none');

        if (med) {
            window.playScannerBeep(true);
            resultCheck.innerHTML = `
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-success me-1">ENCONTRADO</span>
                        <strong class="text-white">${med.nombre_comercial}</strong>
                        <span class="text-white-50 font-mono ms-1">[${med.codigo_barra}]</span>
                        <div class="text-white-50 mt-1">
                            Vitrina: <strong class="text-white">${med.stock_vitrina} u.</strong> |
                            1° Lote FEFO: <code class="text-warning">${med.proximo_lote}</code> (vence ${med.proximo_vencimiento})
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fs-5 fw-extrabold text-success font-mono">${formatCLP(med.precio_unitario)}</div>
                        <a href="{{ route('pos.index') }}?scan=${med.codigo_barra}" class="btn btn-xs btn-verde-medico py-0 px-2 small">Ir a vender →</a>
                    </div>
                </div>
            `;
            inputCheck.value = '';
        } else {
            window.playScannerBeep(false);
            resultCheck.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Código ${code} no registrado en el catálogo.</span>`;
            inputCheck.select();
        }
    }

    inputCheck.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarCodigoDashboard(this.value);
        }
    });

    // 4. Motor de Sincronización en Tiempo Real (Polling AJAX cada 4 segundos)
    async function sincronizarDashboard() {
        try {
            const res = await fetch('{{ route("dashboard.realtime") }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status !== 'ok') return;

            document.getElementById('liveTimestamp').textContent = data.timestamp;
            document.getElementById('kpiVentasHoy').textContent = formatCLP(data.kpis.ventasHoy);
            document.getElementById('kpiCantidadVentas').textContent = data.kpis.cantidadVentasHoy;
            document.getElementById('kpiTicketPromedio').textContent = formatCLP(data.kpis.ticketPromedio);
            document.getElementById('kpiStockVitrina').textContent = data.kpis.stockVitrinaTotal.toLocaleString('es-CL');
            document.getElementById('kpiStockBodega').textContent = data.kpis.stockBodegaTotal.toLocaleString('es-CL');
            document.getElementById('kpiValorInventario').textContent = formatCLP(data.kpis.valorInventario);
            document.getElementById('kpiTotalMedicamentos').textContent = data.kpis.totalMedicamentos;
            if (document.getElementById('kpiAbonoLiquido')) {
                document.getElementById('kpiAbonoLiquido').textContent = formatCLP(data.kpis.abonoLiquidoHoy);
                document.getElementById('kpiCostoTbk').textContent = '-' + formatCLP(data.kpis.costoTotalTbkHoy);
            }
            document.getElementById('kpiAlertasCount').textContent = data.kpis.alertasCount;
            document.getElementById('kpiCriticosCount').textContent = data.semaforo.critico;
            document.getElementById('kpiControlados').textContent = data.kpis.totalControlados;

            document.getElementById('semCritico').textContent = data.semaforo.critico;
            document.getElementById('semProximo').textContent = data.semaforo.proximo;
            document.getElementById('semOptimo').textContent = data.semaforo.optimo;

            catalogoActual = data.estadoCatalogo;

            // Actualizar gráfico semáforo
            chartSemaforo.data.datasets[0].data = [
                data.semaforo.critico,
                data.semaforo.proximo,
                data.semaforo.optimo
            ];
            chartSemaforo.update('none');

            // Actualizar gráfico de ventas y tabla en tiempo real
            const ventasRev = data.ultimasVentas.slice().reverse();
            chartVentas.data.labels = ventasRev.map(v => v.folio);
            chartVentas.data.datasets[0].data = ventasRev.map(v => v.total);
            chartVentas.data.datasets[1].data = ventasRev.map(v => v.total);
            chartVentas.update('none');

            const tbodyVentas = document.getElementById('tbodyUltimasVentas');
            if (tbodyVentas && data.ultimasVentas.length > 0) {
                tbodyVentas.innerHTML = data.ultimasVentas.map(v => {
                    const detHtml = v.detalles.map(d => `
                        <div class="small mb-1">
                            <span class="fw-bold">${d.medicamento}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono ms-1">
                                ${d.lote} (-${d.cantidad} u.)
                            </span>
                        </div>
                    `).join('');
                    const dctoHtml = v.descuento > 0
                        ? `<div class="text-muted font-mono" style="font-size: 0.7rem;">Dcto: -${formatCLP(v.descuento)}</div>`
                        : '';
                    return `
                        <tr>
                            <td>
                                <span class="badge bg-dark font-mono">${v.folio}</span>
                                <div class="text-muted font-mono" style="font-size: 0.72rem;">${v.hora}</div>
                            </td>
                            <td>${detHtml}</td>
                            <td>
                                <div class="small fw-semibold">${v.convenio}</div>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-uppercase" style="font-size: 0.68rem;">
                                    ${v.metodo_pago}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="fw-extrabold text-success font-mono">${formatCLP(v.total)}</div>
                                ${dctoHtml}
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } catch (err) {}
    }

    document.getElementById('btnRefreshNow').addEventListener('click', sincronizarDashboard);
    setInterval(sincronizarDashboard, 4000);
})();
</script>
@endpush
