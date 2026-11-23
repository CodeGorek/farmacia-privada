@extends('layouts.app')

@section('content')
@php
    $esSuperAdmin = auth()->user() && auth()->user()->isSuperAdmin();
@endphp
<div class="container-fluid px-0">
    <!-- Encabezado con Selector de Módulos (Distintas Páginas) -->
    <div class="mb-4 rounded-4 text-white p-4"
         style="background: linear-gradient(125deg, #090E1A 0%, #1E293B 55%, #1E3A8A 100%); box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.35);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <svg width="52" height="52" class="flex-shrink-0"><use href="#icon-role-admin"></use></svg>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="badge {{ $esSuperAdmin ? 'bg-danger text-white' : 'bg-warning text-dark' }} fw-bold rounded-pill px-3 py-1" style="font-size: 0.72rem;">
                            {{ $esSuperAdmin ? 'SUPER ADMINISTRADOR • CONTROL TOTAL' : 'ADMINISTRADOR GENERAL • RRHH & OPERACIONES' }}
                        </span>
                        <span class="badge bg-white bg-opacity-10 text-white rounded-pill" style="font-size: 0.72rem;">
                            DS 466 MINSAL • Turnos 24/7 • Auditoría
                        </span>
                    </div>
                    <h3 class="fw-extrabold mb-0">Administración de Personal, Turnos, Licencias y Auditoría</h3>
                </div>
            </div>

            <!-- Selector Desplegable de Módulo -->
            <div class="d-flex align-items-center gap-2 bg-white bg-opacity-10 p-2 rounded-3 border border-white border-opacity-25">
                <label for="selectModuloAdmin" class="small fw-bold text-white-50 mb-0 text-nowrap">Ir a módulo:</label>
                <select id="selectModuloAdmin" class="form-select form-select-sm fw-bold bg-dark text-white border-secondary" style="min-width: 230px;"
                        onchange="window.location.href = this.value;">
                    <option value="{{ route('admin.personal.index', ['seccion' => 'cuentas']) }}" {{ $seccion === 'cuentas' ? 'selected' : '' }}>
                        1. Cuentas de Personal y Nómina
                    </option>
                    <option value="{{ route('admin.personal.index', ['seccion' => 'turnos']) }}" {{ $seccion === 'turnos' ? 'selected' : '' }}>
                        2. Turnos y Guardia Nocturna 24/7
                    </option>
                    <option value="{{ route('admin.personal.index', ['seccion' => 'licencias']) }}" {{ $seccion === 'licencias' ? 'selected' : '' }}>
                        3. Licencias Médicas y Reemplazos
                    </option>
                    <option value="{{ route('admin.personal.index', ['seccion' => 'auditoria']) }}" {{ $seccion === 'auditoria' ? 'selected' : '' }}>
                        4. Auditoría del Sistema (IP / SO)
                    </option>
                </select>
            </div>
        </div>

        <!-- Barra de Navegación por Páginas -->
        <div class="d-flex flex-wrap gap-2 pt-2 border-top border-white border-opacity-15">
            <a href="{{ route('admin.personal.index', ['seccion' => 'cuentas']) }}"
               class="btn btn-sm rounded-pill px-3 fw-bold {{ $seccion === 'cuentas' ? 'btn-success' : 'btn-outline-light' }}">
                <i class="bi bi-people-fill me-1"></i>Cuentas y Nómina ({{ $usuarios->count() }})
            </a>
            <a href="{{ route('admin.personal.index', ['seccion' => 'turnos']) }}"
               class="btn btn-sm rounded-pill px-3 fw-bold {{ $seccion === 'turnos' ? 'btn-success' : 'btn-outline-light' }}">
                <i class="bi bi-moon-stars-fill me-1"></i>Turnos y Guardias ({{ $turnos->count() }})
            </a>
            <a href="{{ route('admin.personal.index', ['seccion' => 'licencias']) }}"
               class="btn btn-sm rounded-pill px-3 fw-bold {{ $seccion === 'licencias' ? 'btn-success' : 'btn-outline-light' }}">
                <i class="bi bi-calendar2-x-fill me-1"></i>Licencias y Reemplazos ({{ $ausencias->count() }})
            </a>
            <a href="{{ route('admin.personal.index', ['seccion' => 'auditoria']) }}"
               class="btn btn-sm rounded-pill px-3 fw-bold {{ $seccion === 'auditoria' ? 'btn-warning text-dark' : 'btn-outline-light' }}">
                <i class="bi bi-shield-lock-fill me-1"></i>Auditoría del Sistema ({{ $auditorias->count() }})
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 rounded-3 mb-4">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ==================== PÁGINA 1: CUENTAS DE PERSONAL Y NÓMINA ==================== --}}
    @if($seccion === 'cuentas')
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-pro h-100">
                    <div class="card-header">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Crear Cuenta de Personal
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('admin.personal.usuarios.store') }}" method="POST">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-5">
                                    <label class="form-label small fw-bold mb-1">RUT *</label>
                                    <input type="text" name="rut" class="form-control form-control-sm font-mono" placeholder="18.222.333-4" required>
                                </div>
                                <div class="col-7">
                                    <label class="form-label small fw-bold mb-1">Nombre Completo *</label>
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej. Carla Soto" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Correo *</label>
                                    <input type="email" name="email" class="form-control form-control-sm" placeholder="carla@farmacia.cl" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Rol del Sistema *</label>
                                    <select name="rol" class="form-select form-select-sm fw-semibold" required>
                                        <option value="vendedor">Auxiliar / Vendedor POS</option>
                                        <option value="qf">Químico Farmacéutico (D.T.)</option>
                                        <option value="bodeguero">Encargado de Bodega</option>
                                        @if($esSuperAdmin)
                                            <option value="admin">Administrador General</option>
                                            <option value="superadmin">Super Administrador</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Registro ISP / SIS</label>
                                    <input type="text" name="registro_isp" class="form-control form-control-sm font-mono" placeholder="SIS-QF-XXXXX">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control form-control-sm" placeholder="+56 9 ...">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Horario Habitual *</label>
                                <input type="text" name="horario_habitual" class="form-control form-control-sm" value="Lunes a Viernes 08:30 - 17:30" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-1">Contraseña Inicial Segura *</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="password" id="inputNuevaCuentaPwd" class="form-control font-mono fw-bold" placeholder="Mín. 10 chars (Mayús, minús, núm, símbolo)" minlength="10" required>
                                    <button type="button" class="btn btn-outline-secondary fw-bold btn-generar-pwd" data-target="inputNuevaCuentaPwd" title="Generar contraseña segura">
                                        <i class="bi bi-shuffle me-1"></i>Generar
                                    </button>
                                </div>
                                <div class="form-text" style="font-size: 0.72rem;">Política OWASP: mín. 10 caracteres, mayúscula, minúscula, número y símbolo.</div>
                            </div>
                            @if(!$esSuperAdmin)
                                <div class="alert alert-light border small py-2 mb-3 text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Solo el <strong>Super Administrador</strong> puede crear o modificar cuentas de Administradores.
                                </div>
                            @endif
                            <button type="submit" class="btn btn-verde-medico w-100 py-2 fw-bold rounded-3">
                                <i class="bi bi-person-check-fill me-1"></i>Crear Cuenta de Personal
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-pro">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people-fill text-primary me-2"></i>Nómina Oficial de Personal y Gestión de Contraseñas</span>
                        <span class="badge bg-dark rounded-pill">{{ $usuarios->count() }} cuentas</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th>Funcionario</th>
                                        <th>Rol / Registro ISP</th>
                                        <th>Horario</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($usuarios as $u)
                                        @php
                                            $puedeGestionar = $esSuperAdmin || !$u->isAdminOrSuper();
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $u->name }}</div>
                                                <div class="small text-muted font-mono">{{ $u->rut }} • {{ $u->email }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $badgeRol = match($u->rol) {
                                                        'superadmin' => 'bg-danger text-white',
                                                        'admin' => 'bg-warning text-dark',
                                                        'qf' => 'bg-success text-white',
                                                        'vendedor' => 'bg-primary text-white',
                                                        default => 'bg-secondary text-white',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeRol }}">{{ $u->rol_nombre }}</span>
                                                @if($u->registro_isp)
                                                    <div class="small text-muted font-mono mt-1">{{ $u->registro_isp }}</div>
                                                @endif
                                            </td>
                                            <td class="small">{{ $u->horario_habitual }}</td>
                                            <td class="text-center">
                                                @if($u->activo)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">INACTIVA</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                @if($puedeGestionar)
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary rounded-pill me-1 btn-open-reset-pwd"
                                                            data-user-id="{{ $u->id }}"
                                                            data-user-name="{{ $u->name }}"
                                                            data-action="{{ route('admin.personal.usuarios.reset-password', $u) }}">
                                                        <i class="bi bi-key-fill me-1"></i>Restablecer Clave
                                                    </button>
                                                    @if($u->id !== auth()->id())
                                                        <form action="{{ route('admin.personal.usuarios.toggle', $u) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill">
                                                                {{ $u->activo ? 'Deshabilitar' : 'Habilitar' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-muted border">Solo Super Admin</span>
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
    @endif

    {{-- ==================== PÁGINA 2: TURNOS Y GUARDIA NOCTURNA ==================== --}}
    @if($seccion === 'turnos')
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-pro">
                    <div class="card-header">
                        <i class="bi bi-moon-stars-fill text-primary me-2"></i>Asignar Turno o Guardia Nocturna 24/7
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('admin.personal.turnos.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Funcionario *</label>
                                <select name="user_id" class="form-select form-select-sm" required>
                                    @foreach($usuarios as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->rol_nombre }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Tipo de Turno *</label>
                                <select name="tipo_turno" class="form-select form-select-sm" required>
                                    <option value="DIURNO_MANANA">Turno Diurno Mañana</option>
                                    <option value="DIURNO_TARDE">Turno Diurno Tarde</option>
                                    <option value="NOCTURNO_GUARDIA">Turno Nocturno / Guardia 24h SEREMI</option>
                                    <option value="ROTATIVO">Turno Rotativo Fin de Semana</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Fecha Término *</label>
                                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ today()->addDays(5)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Hora Entrada</label>
                                    <input type="time" name="hora_entrada" class="form-control form-control-sm" value="08:00" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Hora Salida</label>
                                    <input type="time" name="hora_salida" class="form-control form-control-sm" value="18:00" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-1">Observaciones</label>
                                <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Ej. Cobertura Dirección Técnica DS 466">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3">
                                <i class="bi bi-calendar-plus me-1"></i>Guardar Turno en Calendario
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-pro">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history text-primary me-2"></i>Bitácora de Turnos Diurnos y Guardia Nocturna</span>
                        <span class="badge bg-primary rounded-pill">{{ $turnos->count() }} turnos</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th>Funcionario</th>
                                        <th>Modalidad</th>
                                        <th>Vigencia</th>
                                        <th>Horario</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($turnos as $t)
                                        <tr>
                                            <td class="fw-bold">{{ $t->user->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($t->es_nocturno)
                                                    <span class="badge bg-dark text-warning"><i class="bi bi-moon-stars-fill me-1"></i>NOCTURNO</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info-emphasis">{{ str_replace('_', ' ', $t->tipo_turno) }}</span>
                                                @endif
                                            </td>
                                            <td class="small font-mono">{{ $t->fecha_inicio->format('d/m/Y') }} → {{ $t->fecha_fin->format('d/m/Y') }}</td>
                                            <td class="small fw-semibold">{{ $t->hora_entrada }} - {{ $t->hora_salida }}</td>
                                            <td class="small text-muted">{{ $t->observaciones ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Sin turnos registrados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== PÁGINA 3: LICENCIAS Y REEMPLAZOS ==================== --}}
    @if($seccion === 'licencias')
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-pro">
                    <div class="card-header">
                        <i class="bi bi-calendar2-x-fill text-danger me-2"></i>Registrar Licencia, Día Libre o Reemplazo
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('admin.personal.ausencias.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Funcionario Titular *</label>
                                <select name="user_id" class="form-select form-select-sm" required>
                                    @foreach($usuarios as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Reemplazante Asignado</label>
                                <select name="reemplazante_id" class="form-select form-select-sm">
                                    <option value="">-- Sin reemplazo asignado --</option>
                                    @foreach($usuarios as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->rol_nombre }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold mb-1">Tipo de Ausencia *</label>
                                <select name="tipo" class="form-select form-select-sm" required>
                                    <option value="LICENCIA_MEDICA">Licencia Médica (Bloquea acceso POS)</option>
                                    <option value="DIA_LIBRE">Día Libre / Compensatorio</option>
                                    <option value="VACACIONES">Vacaciones Legales</option>
                                    <option value="PERMISO_ADMINISTRATIVO">Permiso Administrativo</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Desde *</label>
                                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1">Hasta *</label>
                                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="{{ today()->addDays(3)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-1">Folio / Motivo</label>
                                <input type="text" name="motivo_folio" class="form-control form-control-sm" placeholder="Ej. LME #9921044">
                            </div>
                            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold rounded-3">
                                <i class="bi bi-file-earmark-medical me-1"></i>Registrar Ausencia y Reemplazo
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-pro">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-card-checklist text-danger me-2"></i>Registro de Licencias, Días Libres y Reemplazos</span>
                        <span class="badge bg-danger rounded-pill">{{ $ausencias->count() }} registros</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th>Titular</th>
                                        <th>Tipo</th>
                                        <th>Período</th>
                                        <th>Reemplazante</th>
                                        <th>Folio / Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ausencias as $a)
                                        <tr>
                                            <td class="fw-bold">{{ $a->titular->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ $a->tipo === 'LICENCIA_MEDICA' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                    {{ str_replace('_', ' ', $a->tipo) }}
                                                </span>
                                            </td>
                                            <td class="small font-mono">{{ $a->fecha_inicio->format('d/m/Y') }} → {{ $a->fecha_fin->format('d/m/Y') }}</td>
                                            <td class="small fw-semibold text-success">{{ $a->reemplazante->name ?? 'Sin asignar' }}</td>
                                            <td class="small text-muted">{{ $a->motivo_folio ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">No hay ausencias registradas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== PÁGINA 4: AUDITORÍA DEL SISTEMA (EN TIEMPO REAL) ==================== --}}
    @if($seccion === 'auditoria')
        <div class="card-pro">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill text-warning"></i>
                    <span>Bitácora de Auditoría del Sistema (IP, Navegador y SO)</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill d-inline-flex align-items-center gap-1 px-2.5 py-1">
                        <span class="scanner-dot" style="width: 8px; height: 8px;"></span>
                        <span>EN VIVO • Hora Chile: <strong id="auditHoraServidor" class="font-mono">{{ now()->format('H:i:s') }}</strong></span>
                    </span>
                </div>
                <form method="GET" action="{{ route('admin.personal.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="seccion" value="auditoria">
                    <input type="text" name="q_auditoria" id="inputFiltroAuditoria" value="{{ request('q_auditoria') }}" class="form-control form-control-sm" placeholder="Buscar usuario, acción, IP, navegador, SO..." style="min-width: 260px;">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Filtrar</button>
                    @if(request('q_auditoria'))
                        <a href="{{ route('admin.personal.index', ['seccion' => 'auditoria']) }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th>Fecha / Hora (Chile)</th>
                                <th>Usuario & Rol</th>
                                <th>Acción / Módulo</th>
                                <th>Detalle</th>
                                <th>Dirección IP</th>
                                <th>Navegador</th>
                                <th>Dispositivo & SO</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAuditoriaRealtime">
                            @forelse($auditorias as $aud)
                                @php
                                    $disp = $aud->tipo_dispositivo;
                                    $iconoDisp = $disp === 'Móvil' ? 'bi-phone-fill text-success' : ($disp === 'Tablet' ? 'bi-tablet-fill text-info' : 'bi-pc-display text-primary');
                                @endphp
                                <tr>
                                    <td class="small font-mono text-nowrap">{{ $aud->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="small">
                                        <div class="fw-bold">{{ $aud->usuario_nombre }}</div>
                                        <span class="badge bg-light text-dark border">{{ strtoupper($aud->usuario_rol ?? 'N/A') }}</span>
                                    </td>
                                    <td class="small">
                                        <span class="badge bg-primary-subtle text-primary font-mono">{{ $aud->accion }}</span>
                                        <div class="text-muted mt-1">{{ $aud->modulo }}</div>
                                    </td>
                                    <td class="small" style="max-width: 300px;">{{ $aud->detalle }}</td>
                                    <td class="font-mono small fw-bold text-dark">{{ $aud->ip }}</td>
                                    <td class="small"><i class="bi bi-globe2 me-1 text-primary"></i>{{ $aud->navegador }}</td>
                                    <td class="small">
                                        <div class="fw-semibold"><i class="bi {{ $iconoDisp }} me-1"></i>{{ $disp }}</div>
                                        <div class="text-muted" style="font-size: 0.74rem;">{{ $aud->sistema_operativo }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay eventos de auditoría registrados aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal Restablecer Contraseña -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-key-fill text-warning me-2"></i>Restablecer Contraseña</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formResetPassword" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Establecer una nueva contraseña de acceso para la cuenta: <strong id="resetPwdUserName" class="text-dark"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nueva Contraseña Segura *</label>
                        <div class="input-group">
                            <input type="text" name="nueva_password" id="inputNuevaPassword" class="form-control font-mono fw-bold" placeholder="Mín. 10 caracteres (Mayús, minús, núm, símbolo)" minlength="10" required>
                            <button type="button" class="btn btn-outline-secondary fw-bold btn-generar-pwd" data-target="inputNuevaPassword" title="Generar contraseña segura">
                                <i class="bi bi-shuffle me-1"></i>Generar
                            </button>
                        </div>
                        <div class="form-text small">Debe incluir mínimo 10 caracteres, mayúsculas, minúsculas, números y símbolos (ej. <code>Farma#2026!</code>).</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
                        <i class="bi bi-check2-circle me-1"></i>Guardar Nueva Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-generar-pwd').forEach(btn => {
    btn.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.target);
        if (!target) return;
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnopqrstuvwxyz';
        const nums = '23456789';
        const syms = '!@#$%&*?';
        const all = upper + lower + nums + syms;
        let pwd = [
            upper[Math.floor(Math.random() * upper.length)],
            lower[Math.floor(Math.random() * lower.length)],
            nums[Math.floor(Math.random() * nums.length)],
            syms[Math.floor(Math.random() * syms.length)],
        ];
        for (let i = 0; i < 8; i++) {
            pwd.push(all[Math.floor(Math.random() * all.length)]);
        }
        pwd.sort(() => Math.random() - 0.5);
        target.value = pwd.join('');
    });
});

document.querySelectorAll('.btn-open-reset-pwd').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('resetPwdUserName').textContent = this.dataset.userName;
        document.getElementById('formResetPassword').action = this.dataset.action;
        document.getElementById('inputNuevaPassword').value = '';
        const modal = new bootstrap.Modal(document.getElementById('modalResetPassword'));
        modal.show();
    });
});

@if($seccion === 'auditoria')
(function () {
    const tbody = document.getElementById('tbodyAuditoriaRealtime');
    const horaEl = document.getElementById('auditHoraServidor');
    const filtroInput = document.getElementById('inputFiltroAuditoria');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

    async function refrescarAuditoria() {
        try {
            const q = filtroInput ? filtroInput.value.trim() : '';
            const url = "{{ route('admin.personal.auditoria.json') }}" + (q ? ('?q_auditoria=' + encodeURIComponent(q)) : '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (horaEl && data.hora_servidor) {
                horaEl.textContent = data.hora_servidor.split(' ')[1] || data.hora_servidor;
            }
            if (!tbody || !Array.isArray(data.auditorias)) return;
            if (data.auditorias.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay eventos de auditoría registrados aún.</td></tr>';
                return;
            }
            tbody.innerHTML = data.auditorias.map(aud => `
                <tr>
                    <td class="small font-mono text-nowrap">${esc(aud.fecha)}</td>
                    <td class="small">
                        <div class="fw-bold">${esc(aud.usuario_nombre)}</div>
                        <span class="badge bg-light text-dark border">${esc(aud.usuario_rol)}</span>
                    </td>
                    <td class="small">
                        <span class="badge bg-primary-subtle text-primary font-mono">${esc(aud.accion)}</span>
                        <div class="text-muted mt-1">${esc(aud.modulo)}</div>
                    </td>
                    <td class="small" style="max-width: 300px;">${esc(aud.detalle)}</td>
                    <td class="font-mono small fw-bold text-dark">${esc(aud.ip)}</td>
                    <td class="small"><i class="bi bi-globe2 me-1 text-primary"></i>${esc(aud.navegador)}</td>
                    <td class="small">
                        <div class="fw-semibold">
                            <i class="bi ${aud.dispositivo === 'Móvil' ? 'bi-phone-fill text-success' : (aud.dispositivo === 'Tablet' ? 'bi-tablet-fill text-info' : 'bi-pc-display text-primary')} me-1"></i>${esc(aud.dispositivo || 'Escritorio (PC)')}
                        </div>
                        <div class="text-muted" style="font-size: 0.74rem;">${esc(aud.sistema_operativo)}</div>
                    </td>
                </tr>
            `).join('');
        } catch (e) {}
    }

    setInterval(refrescarAuditoria, 4000);
})();
@endif
</script>
@endpush
