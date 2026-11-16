@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Banner Principal de Operaciones Sanitarias y ERP -->
    <div class="p-4 mb-4 rounded-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
         style="background: linear-gradient(125deg, #0F172A 0%, #1E3A8A 50%, #047857 100%); box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.35);">
        <div>
            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                <i class="bi bi-building-check me-1"></i>CUMPLIMIENTO SII • ISP • SEREMI • LEY CENABAST
            </span>
            <h2 class="fw-extrabold mb-1">Centro de Operaciones: Caja Z, Boletas SII, Cadena de Frío, Recall ISP y CENABAST</h2>
            <p class="mb-0 text-white-50 small">
                Arqueo y cierre de turno (Reporte Z), emisión de Boletas Electrónicas DTE 39 / Notas de Crédito DTE 61, bitácora térmica DS 466, bloqueo de lotes por Recall ISP y Farmacovigilancia.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('webpay_error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <span class="fw-semibold">{{ session('webpay_error') }}</span>
        </div>
    @endif

    <!-- FILA 1: APERTURA / ARQUEO DE CAJA Z Y BITÁCORA DE TEMPERATURA / CADENA DE FRÍO ISP -->
    <div class="row g-4 mb-4">
        <!-- 1. Apertura, Arqueo y Cierre de Caja Z por Turno -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cash-Register text-success me-2"></i>1. Apertura, Arqueo y Cierre de Caja por Turno (Reporte Z)</span>
                    @if($cajaActual)
                        <span class="badge bg-success rounded-pill">CAJA #{{ $cajaActual->id }} ABIERTA</span>
                    @else
                        <span class="badge bg-secondary rounded-pill">CAJA CERRADA</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    @php
                        $fondoInicial = $cajaActual ? (float) $cajaActual->fondo_fijo_inicial : 50000;
                        $efectivoEsperado = $fondoInicial + $efectivoHoy;
                    @endphp
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-3">
                            <div class="p-2 rounded-3 bg-light border">
                                <div class="text-muted" style="font-size: 0.7rem;">Fondo Inicial</div>
                                <div class="fw-bold font-mono">${{ number_format($fondoInicial, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle">
                                <div class="text-success" style="font-size: 0.7rem;">Ventas Efectivo</div>
                                <div class="fw-bold font-mono text-success">${{ number_format($efectivoHoy, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded-3 bg-primary-subtle border border-primary-subtle">
                                <div class="text-primary" style="font-size: 0.7rem;">TBK Débito/Créd.</div>
                                <div class="fw-bold font-mono text-primary">${{ number_format($debitoHoy + $creditoHoy, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 rounded-3 bg-warning-subtle border border-warning-subtle">
                                <div class="text-dark fw-semibold" style="font-size: 0.7rem;">Esperado Gaveta</div>
                                <div class="fw-extrabold font-mono text-dark">${{ number_format($efectivoEsperado, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    @if($cajaActual)
                        <form action="{{ route('operaciones.caja.cerrar', $cajaActual->id) }}" method="POST" class="p-3 rounded-3 border bg-light">
                            @csrf
                            <h6 class="fw-bold small mb-2"><i class="bi bi-calculator me-1 text-primary"></i>Ejecutar Cierre y Arqueo Z de Turno</h6>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold mb-1">Efectivo Físico Contado ($)</label>
                                    <input type="number" name="efectivo_fisico_declarado" class="form-control form-control-sm font-mono fw-bold" value="{{ $efectivoEsperado }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-1">Observación Arqueo</label>
                                    <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Cuadratura exacta">
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-sm btn-danger fw-bold py-2">
                                        Emitir Cierre Z
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('operaciones.caja.abrir') }}" method="POST" class="p-3 rounded-3 border bg-light">
                            @csrf
                            <h6 class="fw-bold small mb-2">Abrir Nuevo Turno de Caja POS</h6>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold mb-1">Fondo Fijo Inicial (Sencillo $)</label>
                                    <input type="number" name="fondo_fijo_inicial" class="form-control form-control-sm font-mono fw-bold" value="50000" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-1">Observación</label>
                                    <input type="text" name="observaciones" class="form-control form-control-sm" value="Apertura Turno Mañana">
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-sm btn-verde-medico fw-bold py-2">
                                        Abrir Caja
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Bitácora Diaria de Temperatura y Cadena de Frío ISP (DS 466) -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-thermometer-snow text-info me-2"></i>2. Bitácora de Temperatura y Cadena de Frío ISP (DS 466)</span>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill">2 Controles Diarios</span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('operaciones.temperatura.store') }}" method="POST" class="mb-3">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">Jornada</label>
                                <select name="jornada" class="form-select form-select-sm fw-semibold">
                                    <option value="MANANA_09H">☀️ 09:00 AM</option>
                                    <option value="TARDE_17H">🌇 17:00 PM</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">Sala (18°-25°C)</label>
                                <input type="number" step="0.1" name="temp_sala" class="form-control form-control-sm font-mono fw-bold" value="21.4" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">HR (%)</label>
                                <input type="number" name="humedad_sala" class="form-control form-control-sm font-mono" value="48" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1 text-primary">Refrig. Frío (2°-8°C)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" name="temp_refrigerador" class="form-control font-mono fw-bold border-primary" value="4.2" required>
                                    <button type="submit" class="btn btn-primary fw-bold">Registrar</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha / Jornada</th>
                                    <th>Sala / HR</th>
                                    <th>Refrigerador</th>
                                    <th>Estado ISP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($temperaturas->take(3) as $t)
                                    <tr>
                                        <td class="font-mono">{{ $t->created_at->format('d/m H:i') }} ({{ $t->jornada }})</td>
                                        <td class="font-mono">{{ $t->temp_sala }}°C • {{ $t->humedad_sala }}%</td>
                                        <td class="font-mono fw-bold text-primary">{{ $t->temp_refrigerador }}°C</td>
                                        <td>
                                            @if($t->conforme_isp)
                                                <span class="badge bg-success-subtle text-success">✓ Conforme DS 466</span>
                                            @else
                                                <span class="badge bg-danger">⚠️ Fuera de Rango</span>
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

    <!-- FILA 2: BOLETAS ELECTRÓNICAS SII (DTE 39) Y NOTAS DE CRÉDITO / ANULACIONES (DTE 61) -->
    <div class="card-pro mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-receipt text-primary me-2"></i>
                <span>3. Emisión de Boletas Electrónicas SII (DTE 39) y Anulación con Nota de Crédito (DTE 61 + Reintegro FEFO)</span>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill">Facturación Electrónica SII</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Folio SII / Hora</th>
                            <th>RUT Cliente / Convenio</th>
                            <th>Medicamentos & Lotes FEFO</th>
                            <th>Medio Pago / TBK</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Acciones Tributarias SII</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ultimasVentas as $v)
                            <tr class="{{ $v->anulada ? 'table-danger bg-opacity-10' : '' }}">
                                <td>
                                    <span class="badge {{ $v->anulada ? 'bg-danger' : 'bg-dark' }} font-mono">
                                        {{ $v->folio_dte_sii ?? ('DTE39-0010' . $v->id) }}
                                    </span>
                                    <div class="text-muted font-mono" style="font-size: 0.72rem;">{{ $v->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-bold font-mono">{{ $v->rut_cliente ?: 'Particular' }}</div>
                                    <div class="text-muted">{{ $v->convenio->nombre ?? 'Tarifa Normal' }}</div>
                                </td>
                                <td class="small">
                                    @foreach($v->detalles as $d)
                                        <div>
                                            <strong>{{ $d->medicamento->nombre_comercial ?? 'Med' }}</strong>
                                            <span class="badge bg-primary-subtle text-primary font-mono ms-1">{{ $d->numero_lote }} (x{{ $d->cantidad }})</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="small">
                                    <span class="badge bg-info-subtle text-info-emphasis border text-uppercase">{{ $v->metodo_pago }}</span>
                                    @if($v->codigo_autorizacion_tbk)
                                        <div class="font-mono text-muted" style="font-size: 0.7rem;">{{ $v->codigo_autorizacion_tbk }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-extrabold font-mono {{ $v->anulada ? 'text-decoration-line-through text-danger' : 'text-success' }}">
                                    ${{ number_format($v->total, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('operaciones.boleta', $v->id) }}" class="btn btn-sm btn-outline-dark fw-semibold">
                                            <i class="bi bi-printer me-1"></i>Boleta SII
                                        </a>
                                        @if(!$v->anulada)
                                            <form action="{{ route('operaciones.venta.anular', $v->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold" title="Emitir Nota de Crédito DTE 61 y devolver stock al lote FEFO">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Nota Crédito DTE 61
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-danger">ANULADA (NC 61)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FILA 3: BLOQUEO DE LOTES RECALL ISP / CUARENTENA + ABASTECIMIENTO LEY CENABAST + FARMACOVIGILANCIA RAM -->
    <div class="row g-4">
        <!-- 4. Bloqueo de Lotes por Alerta Sanitaria Recall ISP / Cuarentena -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-radioactive text-danger me-2"></i>4. Recall ISP y Cuarentena de Lotes</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">Seguridad ISP</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Lote / Medicamento</th>
                                    <th>Vitrina / Cuarent.</th>
                                    <th class="text-end">Acción ISP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lotes as $lote)
                                    <tr class="{{ $lote->bloqueado_recall_isp ? 'table-danger' : '' }}">
                                        <td>
                                            <span class="badge bg-dark font-mono">{{ $lote->numero_lote }}</span>
                                            <div class="fw-bold">{{ $lote->medicamento->nombre_comercial ?? '' }}</div>
                                            <div class="text-muted">Vence: {{ $lote->fecha_vencimiento->format('d/m/Y') }}</div>
                                        </td>
                                        <td class="font-mono">
                                            <div>Vit: <strong>{{ $lote->stock_vitrina }} u.</strong></div>
                                            @if($lote->stock_cuarentena > 0)
                                                <div class="text-danger fw-bold">Cuar: {{ $lote->stock_cuarentena }} u.</div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form action="{{ route('operaciones.lote.recall', $lote->id) }}" method="POST">
                                                @csrf
                                                @if($lote->bloqueado_recall_isp)
                                                    <button type="submit" class="btn btn-xs btn-success py-1 px-2 small fw-bold">
                                                        Liberar Lote
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2 small fw-semibold">
                                                        Bloquear Recall ISP
                                                    </button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Abastecimiento Droguerías y Ley CENABAST -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-truck text-primary me-2"></i>5. Órdenes de Compra & Ley CENABAST</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Droguerías</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('operaciones.oc.store') }}" method="POST" class="mb-3 p-2 rounded-3 bg-light border">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Proveedor / Droguería</label>
                                <select name="proveedor" class="form-select form-select-sm fw-semibold">
                                    <option value="CENABAST (Ley 21.198)">🏛️ CENABAST (Ley 21.198)</option>
                                    <option value="Droguería Socofar">🚚 Droguería Socofar</option>
                                    <option value="Laboratorio Chile">🧪 Laboratorio Chile</option>
                                    <option value="Laboratorio Saval">🧪 Laboratorio Saval</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Medicamento</label>
                                <select name="medicamento_id" class="form-select form-select-sm">
                                    @foreach($medicamentos as $m)
                                        <option value="{{ $m->id }}">{{ $m->nombre_comercial }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <label class="form-label small fw-bold mb-1">Cantidad</label>
                                <input type="number" name="cantidad_solicitada" class="form-control form-control-sm font-mono" value="100" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold mb-1">Costo Neto ($)</label>
                                <input type="number" name="costo_unitario_neto" class="form-control form-control-sm font-mono" value="950" required>
                            </div>
                            <div class="col-4 d-grid">
                                <button type="submit" class="btn btn-sm btn-primary fw-bold">Emitir OC</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio OC</th>
                                    <th>Proveedor / Producto</th>
                                    <th class="text-end">Total Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordenesCompra->take(4) as $oc)
                                    <tr>
                                        <td><span class="badge bg-dark font-mono">{{ $oc->folio_oc }}</span></td>
                                        <td>
                                            <div class="fw-bold">{{ $oc->proveedor }}</div>
                                            <div class="text-muted">{{ $oc->medicamento->nombre_comercial ?? '' }} (x{{ $oc->cantidad_solicitada }})</div>
                                        </td>
                                        <td class="text-end fw-bold font-mono">${{ number_format($oc->total_neto, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Farmacovigilancia (RAM ISP) y Libro de Reclamos SEREMI -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-heart-pulse-fill text-danger me-2"></i>6. Farmacovigilancia RAM & Reclamos</span>
                    <span class="badge bg-warning-subtle text-dark rounded-pill">Red RAM ISP</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('operaciones.ram.store') }}" method="POST" class="mb-3 p-2 rounded-3 bg-light border">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <select name="tipo_registro" class="form-select form-select-sm fw-semibold">
                                    <option value="RAM_ISP">⚕️ Reacción Adversa (RAM ISP)</option>
                                    <option value="RECLAMO_SEREMI">📕 Reclamo Sanitario SEREMI</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="severidad" class="form-select form-select-sm">
                                    <option value="LEVE">Severidad Leve</option>
                                    <option value="MODERADA" selected>Severidad Moderada</option>
                                    <option value="GRAVE">Severidad Grave (Urgente ISP)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <input type="text" name="rut_paciente" class="form-control form-control-sm font-mono" placeholder="RUT Paciente" value="12.345.678-5" required>
                            </div>
                            <div class="col-7">
                                <input type="text" name="nombre_paciente" class="form-control form-control-sm" placeholder="Nombre Paciente" value="María Alejandra González" required>
                            </div>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" name="descripcion_clinica" class="form-control" placeholder="Describir reacción adversa o reclamo..." required>
                            <button type="submit" class="btn btn-danger fw-bold">Notificar ISP</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Paciente / Detalle</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportesRam->take(3) as $ram)
                                    <tr>
                                        <td><span class="badge bg-danger-subtle text-danger">{{ $ram->tipo_registro }}</span></td>
                                        <td>
                                            <div class="fw-bold">{{ $ram->nombre_paciente }}</div>
                                            <div class="text-muted">{{ $ram->descripcion_clinica }}</div>
                                        </td>
                                        <td><span class="badge bg-dark" style="font-size: 0.65rem;">{{ $ram->estado_reporte }}</span></td>
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
