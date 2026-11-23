@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado de Dirección Técnica y Visación QF con Icono Vectorial HD -->
    <div class="p-4 mb-4 rounded-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
         style="background: linear-gradient(125deg, #064E3B 0%, #047857 55%, #0F172A 100%); box-shadow: 0 12px 28px -8px rgba(4, 120, 87, 0.4);">
        <div class="d-flex align-items-center gap-3">
            <svg width="58" height="58" class="flex-shrink-0"><use href="#icon-role-qf"></use></svg>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill">
                        VISTA EXCLUSIVA DIRECCIÓN TÉCNICA • QUÍMICO FARMACÉUTICO
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-white px-3 py-1 rounded-pill font-mono small">
                        <span class="scanner-dot me-1"></span>Escuchando Cajas POS en Vivo (3s)
                    </span>
                </div>
                <h2 class="fw-extrabold mb-1">Central de Visación de Recetas y Controlados (ISP / MINSAL)</h2>
                <p class="mb-0 text-white-50 small">
                    Revisa, aprueba con firma digital sanitaria o rechaza las solicitudes de medicamentos con prescripción enviadas en tiempo real desde las cajas de venta.
                </p>
            </div>
        </div>
        <div class="text-end">
            <div class="display-6 fw-extrabold font-mono mb-0" id="contadorPendientesHeader">{{ $pendientes->count() }}</div>
            <div class="small text-white-50">Solicitudes esperando tu aprobación</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-patch-check-fill fs-4"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- 1. BANDEJA DE SOLICITUDES PENDIENTES ENVIADAS DESDE EL VENDEDOR -->
    <div class="card-pro mb-4 border-2 border-warning">
        <div class="card-header d-flex justify-content-between align-items-center bg-warning-subtle">
            <div class="d-flex align-items-center gap-2 text-dark">
                <i class="bi bi-bell-fill text-danger fs-5"></i>
                <span class="fw-extrabold">Solicitudes de Aprobación Pendientes (Enviadas desde Caja Vendedor)</span>
            </div>
            <span class="badge bg-danger rounded-pill" id="badgePendientesCount">{{ $pendientes->count() }} pendientes</span>
        </div>
        <div class="card-body p-4" id="contenedorSolicitudesPendientes">
            @forelse($pendientes as $sol)
                <div class="p-4 rounded-4 border border-warning bg-white shadow-sm mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-dark font-mono me-2">VIS-{{ str_pad($sol->id, 4, '0', STR_PAD_LEFT) }}</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">{{ $sol->tipo_receta }}</span>
                            <span class="text-muted small ms-2">
                                <i class="bi bi-person-workspace me-1"></i>Solicitado por: <strong>{{ $sol->vendedor->name ?? 'Vendedor POS' }}</strong> • <i class="bi bi-clock me-1"></i>{{ $sol->created_at->format('d/m/Y H:i:s') }}
                            </span>
                        </div>
                        <div class="fs-5 fw-extrabold text-success font-mono">
                            Total Ticket: ${{ number_format($sol->monto_total, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Datos del Paciente</div>
                                <div class="fw-bold">{{ $sol->nombre_paciente }}</div>
                                <div class="font-mono small text-secondary">RUT: {{ $sol->rut_paciente }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Médico Prescriptor & Receta</div>
                                <div class="fw-bold">{{ $sol->nombre_medico }}</div>
                                <div class="font-mono small text-secondary">RUT Médico: {{ $sol->rut_medico }}</div>
                                <div class="mt-1"><span class="badge bg-primary font-mono">Folio Receta: {{ $sol->numero_receta }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <div class="text-muted small fw-bold text-uppercase mb-1">Medicamentos por Visar</div>
                                <ul class="mb-0 ps-3 small">
                                    @foreach($sol->items_json ?? [] as $it)
                                        <li class="mb-1">
                                            <strong>{{ $it['nombre_comercial'] ?? 'Medicamento' }}</strong>
                                            <span class="badge bg-dark font-mono ms-1">x{{ $it['cantidad'] ?? 1 }} u.</span>
                                            @if(!empty($it['es_controlado']))
                                                <span class="badge bg-danger ms-1">Psicotrópico</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center">
                        <div class="col-md-7">
                            <form action="{{ route('visacion.aprobar', $sol->id) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="text" name="observaciones_qf" class="form-control form-control-sm" placeholder="Observación clínica Q.F. (Opcional: ej. Dosis e identidad verificadas)...">
                                <button type="submit" class="btn btn-verde-medico fw-bold text-nowrap px-4">
                                    <i class="bi bi-check2-circle me-1"></i>Aprobar y Firmar (Liberar Caja)
                                </button>
                            </form>
                        </div>
                        <div class="col-md-5">
                            <form action="{{ route('visacion.rechazar', $sol->id) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="text" name="observaciones_qf" class="form-control form-control-sm border-danger" placeholder="Causal de rechazo (ej. Receta vencida)...">
                                <button type="submit" class="btn btn-outline-danger fw-bold text-nowrap px-3">
                                    <i class="bi bi-x-octagon me-1"></i>Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-clipboard2-check text-success fs-1 d-block mb-2"></i>
                    <div class="fw-bold">No hay solicitudes pendientes de visación en este momento</div>
                    <div class="small">Cuando un vendedor escanee un medicamento controlado o bajo receta en <code>/pos</code> y solicite visación, aparecerá aquí instantáneamente.</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 2. HISTORIAL DE VISACIONES DEL TURNO -->
    <div class="card-pro">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-clock-history text-primary me-2"></i>
                <span>Historial de Visaciones y Resoluciones del Químico Farmacéutico</span>
            </div>
            <span class="badge bg-light text-dark border">Registro de Auditoría ISP</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Folio / Fecha y Hora</th>
                            <th>Estado</th>
                            <th>Vendedor Caja</th>
                            <th>Paciente / Receta</th>
                            <th>Medicamentos</th>
                            <th>Químico Farmacéutico / Firma</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $h)
                            <tr>
                                <td class="font-mono small">
                                    <strong>VIS-{{ str_pad($h->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                    <div class="text-muted">{{ ($h->aprobada_at ?? $h->created_at)->format('d/m/Y H:i:s') }}</div>
                                </td>
                                <td>
                                    @if($h->estado === 'APROBADA' || $h->estado === 'PROCESADA')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i>APROBADA
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-x-circle-fill me-1"></i>RECHAZADA
                                        </span>
                                    @endif
                                </td>
                                <td class="small">{{ $h->vendedor->name ?? 'Vendedor' }}</td>
                                <td class="small">
                                    <div class="fw-bold">{{ $h->nombre_paciente }} ({{ $h->rut_paciente }})</div>
                                    <div class="text-muted font-mono">Receta: {{ $h->numero_receta }} • Méd: {{ $h->nombre_medico }}</div>
                                </td>
                                <td class="small">
                                    @foreach($h->items_json ?? [] as $it)
                                        <div>{{ $it['nombre_comercial'] ?? '' }} (x{{ $it['cantidad'] ?? 1 }})</div>
                                    @endforeach
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $h->quimico->name ?? 'Dr. Sebastián Morales (Q.F.)' }}</div>
                                    @if($h->codigo_firma_qf)
                                        <span class="badge bg-dark font-mono">{{ $h->codigo_firma_qf }}</span>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $h->observaciones_qf }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Sin resoluciones previas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Polling cada 3 segundos para detectar nuevas solicitudes enviadas desde el POS del vendedor
(function () {
    let ultimoConteo = {{ $pendientes->count() }};
    setInterval(async () => {
        try {
            const res = await fetch('{{ route("visacion.pendientes.json") }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status === 'ok' && data.count !== ultimoConteo) {
                window.playScannerBeep(true);
                window.location.reload();
            }
        } catch (e) {}
    }, 3000);
})();
</script>
@endpush
