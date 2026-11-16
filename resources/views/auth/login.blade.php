@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="row g-4 align-items-stretch">
        <!-- Columna Izquierda: Portal de Autenticación Institucional -->
        <div class="col-lg-5">
            <div class="card-pro h-100 overflow-hidden">
                <div class="p-4 text-white" style="background: linear-gradient(135deg, #090E1A 0%, #1E3A8A 60%, #059669 100%);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <svg width="54" height="54" class="flex-shrink-0">
                            <use href="#icon-pharmacore-logo"></use>
                        </svg>
                        <div>
                            <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1 mb-1">
                                Control de Acceso por Rol (RBAC)
                            </span>
                            <h3 class="fw-extrabold mb-0">Portal de Ingreso</h3>
                        </div>
                    </div>
                    <p class="text-white-50 small mb-0">
                        Cada cuenta abre su propia vista e interfaz especializada: <strong>Vendedor</strong> (Terminal Caja POS), <strong>Químico Farmacéutico</strong> (Central de Visación ISP) y <strong>Administrador</strong> (Gestión exclusiva de Personal, Turnos y Licencias).
                    </p>
                </div>

                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-4 small d-flex align-items-center gap-2">
                            <i class="bi bi-shield-exclamation fs-4"></i>
                            <div><strong>Acceso Denegado:</strong> {{ $errors->first() }}</div>
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Correo Institucional o RUT del Funcionario</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person-vcard text-primary"></i></span>
                                <input type="text"
                                       name="identificador"
                                       id="inputIdentificador"
                                       class="form-control fw-semibold"
                                       value="{{ old('identificador', 'admin@farmacia.cl') }}"
                                       placeholder="ej. admin@farmacia.cl, qf@farmacia.cl o vendedor@farmacia.cl"
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Contraseña de Turno</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key-fill text-primary"></i></span>
                                <input type="password"
                                       name="password"
                                       id="inputPassword"
                                       class="form-control font-mono"
                                       value="123456"
                                       required>
                            </div>
                            <div class="form-text small">Contraseña oficial de prueba: <code>123456</code></div>
                        </div>

                        <button type="submit" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-6">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar a Mi Vista Asignada
                        </button>
                    </form>

                    @if(auth()->check())
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Sesión actual: <strong>{{ auth()->user()->name }}</strong></span>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Cerrar Sesión</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Selección de Cuentas con Iconos Vectoriales HD y Vistas Diferenciadas -->
        <div class="col-lg-7">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-layers-half text-primary me-2"></i>
                        <span>Cuentas por Rol Operativo (Cada Login abre su propia Vista Exclusiva)</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Seguridad RBAC Activa</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($usuariosDemo as $u)
                            @php
                                $licenciaHoy = $u->ausencias()
                                    ->where('estado', 'VIGENTE')
                                    ->where('tipo', 'LICENCIA_MEDICA')
                                    ->whereDate('fecha_inicio', '<=', today())
                                    ->whereDate('fecha_fin', '>=', today())
                                    ->first();

                                $rolConfig = match($u->rol) {
                                    'vendedor' => [
                                        'svg' => '#icon-role-vendedor',
                                        'badge' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'vista' => 'Abre Vista Caja POS (/pos) — Sin acceso a Personal ni Visación Q.F.',
                                    ],
                                    'qf' => [
                                        'svg' => '#icon-role-qf',
                                        'badge' => 'bg-success-subtle text-success border-success-subtle',
                                        'vista' => 'Abre Central de Visación de Recetas (/visacion-qf) — Sin acceso a modificar Personal',
                                    ],
                                    'admin' => [
                                        'svg' => '#icon-role-admin',
                                        'badge' => 'bg-dark text-warning border-dark',
                                        'vista' => 'Abre Gestión de Personal, Turnos Nocturnos, Licencias y Reemplazos (/admin/personal)',
                                    ],
                                    default => [
                                        'svg' => '#icon-role-vendedor',
                                        'badge' => 'bg-secondary-subtle text-secondary',
                                        'vista' => 'Vista Operador',
                                    ]
                                };
                            @endphp
                            <div class="col-12">
                                <div class="p-3 rounded-4 border {{ !$u->activo || $licenciaHoy ? 'bg-danger-subtle bg-opacity-25 border-danger-subtle' : 'bg-white' }} d-flex flex-wrap justify-content-between align-items-center gap-3 shadow-sm">
                                    <div class="d-flex align-items-center gap-3">
                                        <svg width="48" height="48" class="flex-shrink-0">
                                            <use href="{{ $rolConfig['svg'] }}"></use>
                                        </svg>
                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <span class="badge border {{ $rolConfig['badge'] }}">
                                                    {{ strtoupper($u->rol_nombre) }}
                                                </span>
                                                <span class="font-mono small text-muted">RUT: {{ $u->rut }}</span>
                                                @if($u->registro_isp)
                                                    <span class="badge bg-info-subtle text-info-emphasis font-mono">{{ $u->registro_isp }}</span>
                                                @endif
                                                @if(!$u->activo)
                                                    <span class="badge bg-danger">CUENTA DESHABILITADA</span>
                                                @elseif($licenciaHoy)
                                                    <span class="badge bg-warning text-dark">BLOQUEADO POR LICENCIA MÉDICA ACTIVA</span>
                                                @endif
                                            </div>
                                            <h6 class="fw-extrabold mb-0">{{ $u->name }}</h6>
                                            <div class="small text-primary fw-semibold">{{ $rolConfig['vista'] }}</div>
                                            <div class="text-muted" style="font-size: 0.74rem;">
                                                Login: <code class="font-mono text-dark">{{ $u->email }}</code> • Horario: {{ $u->horario_habitual }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 ms-auto">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-dark fw-bold btn-cargar-credenciales"
                                                data-email="{{ $u->email }}">
                                            Probar Login
                                        </button>
                                        @if($u->activo && !$licenciaHoy)
                                            <a href="{{ route('auth.quick-switch', $u->id) }}" class="btn btn-sm btn-verde-medico fw-bold px-3">
                                                Abrir Su Vista →
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-cargar-credenciales').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('inputIdentificador').value = this.dataset.email;
        document.getElementById('inputPassword').value = '123456';
        document.getElementById('loginForm').submit();
    });
});
</script>
@endpush
