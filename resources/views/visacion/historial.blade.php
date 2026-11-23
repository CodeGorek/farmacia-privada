@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-extrabold mb-1">
                <i class="bi bi-clipboard2-check-fill text-success me-2"></i>Historial de Recetas Aprobadas y Resoluciones Q.F.
            </h2>
            <p class="text-muted mb-0">Consulta y auditoría de recetas médicas visadas con firma digital sanitaria ISP / MINSAL.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                <i class="bi bi-check-circle-fill me-1"></i>{{ $totalAprobadas }} Aprobadas
            </span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6">
                <i class="bi bi-x-circle-fill me-1"></i>{{ $totalRechazadas }} Rechazadas
            </span>
        </div>
    </div>

    <!-- Filtros y buscador -->
    <div class="card-pro mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('visacion.historial') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark text-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por RUT paciente, nombre, folio receta, médico o firma Q.F...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select form-select-sm fw-semibold">
                        <option value="APROBADA" {{ $estado === 'APROBADA' ? 'selected' : '' }}>Solo Recetas Aprobadas / Procesadas</option>
                        <option value="TODAS" {{ $estado === 'TODAS' ? 'selected' : '' }}>Todas (Aprobadas y Rechazadas)</option>
                        <option value="RECHAZADA" {{ $estado === 'RECHAZADA' ? 'selected' : '' }}>Solo Recetas Rechazadas</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('visacion.historial') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                    @if(auth()->user()->rol === 'qf')
                        <a href="{{ route('visacion.index') }}" class="btn btn-verde-medico btn-sm ms-auto fw-bold">
                            <i class="bi bi-inbox-fill me-1"></i>Ir a Pendientes
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Recetas Aprobadas -->
    <div class="card-pro">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-journal-check text-success me-2"></i>Registro Histórico de Recetas Visadas</span>
            <span class="badge bg-dark rounded-pill">{{ $recetas->count() }} registros encontrados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Folio / Fecha</th>
                            <th>Estado & Firma Q.F.</th>
                            <th>Paciente</th>
                            <th>Médico & N° Receta</th>
                            <th>Medicamentos Visados</th>
                            <th>Q.F. Responsable</th>
                            <th>Observación Clínica</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recetas as $rec)
                            <tr>
                                <td class="small">
                                    <span class="badge bg-dark font-mono">VIS-{{ str_pad($rec->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    <div class="text-muted mt-1">{{ ($rec->aprobada_at ?? $rec->updated_at)->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    @if(in_array($rec->estado, ['APROBADA', 'PROCESADA'], true))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                            <i class="bi bi-patch-check-fill me-1"></i>{{ $rec->estado }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                            <i class="bi bi-x-octagon-fill me-1"></i>RECHAZADA
                                        </span>
                                    @endif
                                    @if($rec->codigo_firma_qf)
                                        <div class="font-mono small fw-bold text-primary">{{ $rec->codigo_firma_qf }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="fw-bold">{{ $rec->nombre_paciente }}</div>
                                    <div class="text-muted font-mono">{{ $rec->rut_paciente }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $rec->nombre_medico }}</div>
                                    <span class="badge bg-primary-subtle text-primary font-mono">{{ $rec->numero_receta }}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ str_replace('_', ' ', $rec->tipo_receta) }}</div>
                                </td>
                                <td class="small">
                                    @foreach(($rec->items_json ?? []) as $it)
                                        <div>
                                            <span class="fw-bold">{{ $it['nombre_comercial'] ?? 'Medicamento' }}</span>
                                            <span class="badge bg-dark ms-1">x{{ $it['cantidad'] ?? 1 }}</span>
                                        </div>
                                    @endforeach
                                    <div class="text-success fw-bold mt-1">Total: ${{ number_format($rec->monto_total, 0, ',', '.') }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $rec->quimico->name ?? 'Director Técnico Q.F.' }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Solicitó: {{ $rec->vendedor->name ?? 'Caja POS' }}</div>
                                </td>
                                <td class="small text-muted" style="max-width: 240px;">
                                    {{ $rec->observaciones_qf ?: 'Sin observaciones adicionales.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    No se encontraron recetas en el historial para el filtro seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
