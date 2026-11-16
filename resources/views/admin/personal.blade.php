@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado de Administración de Personal y Turnos -->
    <div class="p-4 mb-4 rounded-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3"
         style="background: linear-gradient(125deg, #0F172A 0%, #1E293B 55%, #1E40AF 100%); box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.35);">
        <div>
            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                <i class="bi bi-shield-shaded me-1"></i>PANEL EXCLUSIVO DE ADMINISTRACIÓN
            </span>
            <h2 class="fw-extrabold mb-1">Gestión de Cuentas, Turnos Nocturnos, Licencias y Reemplazos</h2>
            <p class="mb-0 text-white-50 small">
                Crea y administra cuentas de Vendedores y Químicos Farmacéuticos aprobadores, habilita/deshabilita accesos y programa turnos, licencias médicas, días libres y reemplazos con fechas.
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-white bg-opacity-10 text-white px-3 py-2 rounded-3">
                <i class="bi bi-people me-1"></i>{{ $usuarios->count() }} Cuentas Registradas
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Corrige los siguientes campos:</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Fila de 3 Formularios de Gestión Administrativa -->
    <div class="row g-4 mb-4">
        <!-- 1. CREAR NUEVA CUENTA (VENDEDOR / QUÍMICO FARMACÉUTICO / ADMIN) -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-plus-fill text-success me-2"></i>1. Crear Cuenta de Personal</span>
                    <span class="badge bg-success-subtle text-success small">Nuevo Ingreso</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('admin.personal.usuarios.store') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label small fw-bold mb-1">RUT</label>
                                <input type="text" name="rut" class="form-control form-control-sm font-mono" placeholder="18.234.567-8" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label small fw-bold mb-1">Rol en la Farmacia</label>
                                <select name="rol" class="form-select form-select-sm fw-semibold" required>
                                    <option value="vendedor">👨‍💼 Vendedor / Auxiliar POS</option>
                                    <option value="qf">🧪 Químico Farmacéutico (Aprobador)</option>
                                    <option value="bodeguero">📦 Encargado de Bodega FEFO</option>
                                    <option value="admin">🛡️ Administrador General</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Nombre Completo del Profesional / Vendedor</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej. Dra. Claudia Soto (Q.F.)" required>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="form-label small fw-bold mb-1">Correo de Acceso (Login)</label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="claudia@farmacia.cl" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold mb-1">N° Reg. ISP / SIS</label>
                                <input type="text" name="registro_isp" class="form-control form-control-sm font-mono" placeholder="SIS-QF-70122">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label small fw-bold mb-1">Horario Habitual Asignado</label>
                                <input type="text" name="horario_habitual" class="form-control form-control-sm" value="Lunes a Viernes 08:30 - 17:30" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold mb-1">Contraseña</label>
                                <input type="text" name="password" class="form-control form-control-sm font-mono" value="123456" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-verde-medico btn-sm w-100 py-2 fw-bold rounded-3">
                            <i class="bi bi-person-check-fill me-1"></i>Crear y Habilitar Cuenta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. PROGRAMAR HORARIOS, TURNOS DIURNOS Y TURNOS NOCTURNOS -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-week-fill text-primary me-2"></i>2. Asignar Turnos y Guardia Nocturna</span>
                    <span class="badge bg-primary-subtle text-primary small">24/7</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('admin.personal.turnos.store') }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Funcionario Asignado</label>
                            <select name="user_id" class="form-select form-select-sm" required>
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ strtoupper($u->rol) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Modalidad de Turno</label>
                            <select name="tipo_turno" id="selectTipoTurno" class="form-select form-select-sm fw-semibold" required>
                                <option value="DIURNO_MANANA">☀️ Turno Diurno Mañana (08:00 - 16:30)</option>
                                <option value="DIURNO_TARDE">🌤️ Turno Diurno Tarde (14:00 - 22:00)</option>
                                <option value="NOCTURNO_GUARDIA">🌙 Turno Nocturno / Guardia Urgencia (22:00 - 08:00)</option>
                                <option value="FIN_DE_SEMANA">📅 Turno Fin de Semana / Festivo</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Desde Fecha</label>
                                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Hasta Fecha</label>
                                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ today()->addDays(5)->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold mb-1">Entrada</label>
                                <input type="time" name="hora_entrada" id="inputHoraEntrada" class="form-control form-control-sm font-mono" value="08:00" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold mb-1">Salida</label>
                                <input type="time" name="hora_salida" id="inputHoraSalida" class="form-control form-control-sm font-mono" value="16:30" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold mb-1">Observación</label>
                                <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Caja / D.T.">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold rounded-3">
                            <i class="bi bi-clock-fill me-1"></i>Guardar Turno en Calendario
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. REGISTRAR LICENCIAS MÉDICAS, DÍAS LIBRES Y REEMPLAZOS CON FECHAS -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bandaid-fill text-danger me-2"></i>3. Licencias, Días Libres y Reemplazos</span>
                    <span class="badge bg-danger-subtle text-danger small">Con Fechas</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('admin.personal.ausencias.store') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Titular Ausente</label>
                                <select name="user_id" class="form-select form-select-sm" required>
                                    @foreach($usuarios as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1 text-success">Asignar Reemplazante</label>
                                <select name="reemplazante_id" class="form-select form-select-sm border-success">
                                    <option value="">-- Sin reemplazo --</option>
                                    @foreach($usuarios as $u)
                                        <option value="{{ $u->id }}" {{ $loop->index === 3 ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Causal / Tipo de Ausencia</label>
                            <select name="tipo" class="form-select form-select-sm fw-semibold" required>
                                <option value="LICENCIA_MEDICA">🏥 Licencia Médica (Bloquea Login durante reposo)</option>
                                <option value="DIA_LIBRE">🌴 Día Libre / Descanso Compensatorio</option>
                                <option value="VACACIONES">✈️ Feriado Legal / Vacaciones</option>
                                <option value="PERMISO_ADMINISTRATIVO">📋 Permiso Administrativo</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold mb-1">Fecha Término</label>
                                <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ today()->addDays(4)->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1">N° Folio Licencia (LME) / Motivo Reemplazo</label>
                            <input type="text" name="motivo_folio" class="form-control form-control-sm" placeholder="Ej. Folio LME #9920144 — Reemplazo D.T." required>
                        </div>

                        <button type="submit" class="btn btn-danger btn-sm w-100 py-2 fw-bold rounded-3">
                            <i class="bi bi-calendar2-x-fill me-1"></i>Registrar Ausencia y Activar Reemplazo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. NÓMINA GENERAL DE CUENTAS (HABILITAR / DESHABILITAR CUENTAS) -->
    <div class="card-pro mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-person-lines-fill text-primary me-2"></i>
                <span>Nómina Oficial de Personal Farmacéutico y Control de Estado de Cuentas</span>
            </div>
            <span class="badge bg-dark rounded-pill">{{ $usuarios->count() }} funcionarios</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Funcionario / RUT</th>
                            <th>Rol / Registro ISP</th>
                            <th>Correo Login</th>
                            <th>Horario Habitual</th>
                            <th>Estado Actual / Licencias</th>
                            <th class="text-end">Control de Cuenta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $u)
                            @php
                                $ausenciaActiva = $u->ausencias
                                    ->where('estado', 'VIGENTE')
                                    ->filter(fn($a) => $a->fecha_inicio <= today() && $a->fecha_fin >= today())
                                    ->first();
                            @endphp
                            <tr class="{{ !$u->activo ? 'table-secondary text-muted' : '' }}">
                                <td>
                                    <div class="fw-bold">{{ $u->name }}</div>
                                    <div class="font-mono small text-muted">{{ $u->rut }} • {{ $u->telefono }}</div>
                                </td>
                                <td>
                                    @if($u->rol === 'qf')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">🧪 Químico Farmacéutico</span>
                                    @elseif($u->rol === 'admin')
                                        <span class="badge bg-dark text-white">🛡️ Administrador</span>
                                    @elseif($u->rol === 'vendedor')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">👨‍💼 Vendedor POS</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">📦 Bodeguero</span>
                                    @endif
                                    @if($u->registro_isp)
                                        <div class="font-mono small text-muted mt-1">Reg: {{ $u->registro_isp }}</div>
                                    @endif
                                </td>
                                <td class="font-mono small">{{ $u->email }}</td>
                                <td class="small">{{ $u->horario_habitual }}</td>
                                <td>
                                    @if(!$u->activo)
                                        <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>CUENTA DESHABILITADA</span>
                                    @elseif($ausenciaActiva)
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-bandaid-fill me-1"></i>{{ $ausenciaActiva->tipo }} (hasta {{ $ausenciaActiva->fecha_fin->format('d/m') }})
                                        </span>
                                        @if($ausenciaActiva->reemplazante)
                                            <div class="small text-success fw-semibold mt-1">
                                                ↳ Reemplaza: {{ $ausenciaActiva->reemplazante->name }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i>ACTIVO EN FUNCIONES
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.personal.usuarios.toggle', $u->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @if($u->activo)
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold">
                                                <i class="bi bi-person-slash me-1"></i>Deshabilitar
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                                                <i class="bi bi-person-check me-1"></i>Habilitar Cuenta
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

    <!-- 5. TABLAS DE TURNOS PROGRAMADOS (DIURNOS / NOCTURNOS) Y LICENCIAS / REEMPLAZOS CON FECHAS -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-moon-stars-fill text-primary me-2"></i>Bitácora de Turnos Diurnos y Guardia Nocturna</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $turnos->count() }} turnos</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Personal</th>
                                    <th>Modalidad</th>
                                    <th>Periodo Fechas</th>
                                    <th>Horario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($turnos as $t)
                                    <tr>
                                        <td>
                                            <div class="fw-bold small">{{ $t->user->name ?? 'N/A' }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $t->observaciones }}</div>
                                        </td>
                                        <td>
                                            @if($t->es_nocturno)
                                                <span class="badge bg-dark text-warning border border-warning">
                                                    🌙 NOCTURNO GUARDIA
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                    ☀️ {{ $t->tipo_turno }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="small font-mono">
                                            {{ $t->fecha_inicio->format('d/m/Y') }} → {{ $t->fecha_fin->format('d/m/Y') }}
                                        </td>
                                        <td class="font-mono fw-bold small">{{ $t->hora_entrada }} - {{ $t->hora_salida }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-arrow-left-right text-danger me-2"></i>Registro de Licencias, Días Libres y Reemplazos</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">{{ $ausencias->count() }} registros</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Titular Ausente</th>
                                    <th>Tipo / Folio</th>
                                    <th>Fechas (Desde — Hasta)</th>
                                    <th>Profesional Reemplazante</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ausencias as $a)
                                    <tr>
                                        <td class="fw-bold small">{{ $a->titular->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $a->tipo === 'LICENCIA_MEDICA' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-dark' }}">
                                                {{ $a->tipo }}
                                            </span>
                                            <div class="text-muted" style="font-size: 0.7rem;">{{ $a->motivo_folio }}</div>
                                        </td>
                                        <td class="font-mono small fw-semibold">
                                            {{ $a->fecha_inicio->format('d/m/Y') }} al {{ $a->fecha_fin->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            @if($a->reemplazante)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-person-check-fill me-1"></i>{{ $a->reemplazante->name }}
                                                </span>
                                            @else
                                                <span class="text-muted small">Sin reemplazo</span>
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
<script>
document.getElementById('selectTipoTurno').addEventListener('change', function () {
    const entrada = document.getElementById('inputHoraEntrada');
    const salida = document.getElementById('inputHoraSalida');
    if (this.value === 'NOCTURNO_GUARDIA') {
        entrada.value = '22:00';
        salida.value = '08:00';
    } else if (this.value === 'DIURNO_TARDE') {
        entrada.value = '14:00';
        salida.value = '22:00';
    } else {
        entrada.value = '08:00';
        salida.value = '16:30';
    }
});
</script>
@endpush
