@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="row g-4 align-items-stretch">
        <!-- Columna Izquierda: Formulario de Inicio de Sesión Institucional -->
        <div class="col-lg-5">
            <div class="card-pro h-100 overflow-hidden">
                <div class="p-4 text-white" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 60%, #059669 100%);">
                    <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1 mb-2">
                        <i class="bi bi-shield-lock-fill me-1"></i>Control de Acceso Sanitario (RBAC)
                    </span>
                    <h3 class="fw-extrabold mb-1">Portal de Ingreso Farmacéutico</h3>
                    <p class="text-white-50 small mb-0">
                        Autenticación por rol operativo. El sistema verifica estado de cuenta y vigencia de licencias médicas antes de autorizar el turno.
                    </p>
                </div>

                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 small">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                            <strong>Acceso Denegado:</strong> {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" id="loginForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Correo Institucional o RUT del Funcionario</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person-badge text-primary"></i></span>
                                <input type="text"
                                       name="identificador"
                                       id="inputIdentificador"
                                       class="form-control fw-semibold"
                                       value="{{ old('identificador', 'vendedor@farmacia.cl') }}"
                                       placeholder="ej. vendedor@farmacia.cl o 17.450.221-8"
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
                            <div class="form-text small">Contraseña de prueba para todas las cuentas: <code>123456</code></div>
                        </div>

                        <button type="submit" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-6">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión en el Sistema
                        </button>
                    </form>

                    @if(auth()->check())
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Conectado como: <strong>{{ auth()->user()->name }}</strong></span>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Cerrar Sesión</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Tarjetas de Acceso Rápido por Rol (1 Clic) -->
        <div class="col-lg-7">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-people-fill text-primary me-2"></i>
                        <span>Cuentas Oficiales Configuradas (Haz clic para probar cada perfil)</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">Clave: 123456</span>
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

                                $badgeRol = match($u->rol) {
                                    'vendedor' => ['bg-primary-subtle text-primary border-primary-subtle', 'bi-cart4', 'Atiende Caja POS y envía solicitudes de recetas'],
                                    'qf' => ['bg-success-subtle text-success border-success-subtle', 'bi-award-fill', 'Aprueba / Visa recetas retenidas, cheques y antibióticos'],
                                    'admin' => ['bg-dark text-white', 'bi-shield-shaded', 'Crea cuentas, define horarios, turnos nocturnos, licencias y reemplazos'],
                                    default => ['bg-secondary-subtle text-secondary', 'bi-person', 'Operador']
                                };
                            @endphp
                            <div class="col-12">
                                <div class="p-3 rounded-4 border {{ !$u->activo || $licenciaHoy ? 'bg-danger-subtle bg-opacity-25 border-danger-subtle' : 'bg-white' }} d-flex flex-wrap justify-content-between align-items-center gap-3 shadow-sm">
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="badge border {{ $badgeRol[0] }}">
                                                <i class="bi {{ $badgeRol[1] }} me-1"></i>{{ strtoupper($u->rol_nombre) }}
                                            </span>
                                            <span class="font-mono small text-muted">RUT: {{ $u->rut }}</span>
                                            @if($u->registro_isp)
                                                <span class="badge bg-info-subtle text-info-emphasis font-mono">{{ $u->registro_isp }}</span>
                                            @endif
                                            @if(!$u->activo)
                                                <span class="badge bg-danger">CUENTA DESHABILITADA</span>
                                            @elseif($licenciaHoy)
                                                <span class="badge bg-warning text-dark"><i class="bi bi-bandaid-fill me-1"></i>CON LICENCIA MÉDICA ACTIVA</span>
                                            @endif
                                        </div>
                                        <h6 class="fw-extrabold mb-0">{{ $u->name }}</h6>
                                        <div class="small text-muted">
                                            <code class="font-mono text-dark">{{ $u->email }}</code> • {{ $badgeRol[2] }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-clock me-1"></i>Horario: {{ $u->horario_habitual }}
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary fw-bold btn-cargar-credenciales"
                                                data-email="{{ $u->email }}">
                                            Cargar en Login
                                        </button>
                                        @if($u->activo && !$licenciaHoy)
                                            <a href="{{ route('auth.quick-switch', $u->id) }}" class="btn btn-sm btn-verde-medico fw-bold">
                                                Entrar Directo →
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
