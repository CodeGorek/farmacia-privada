<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PharmaCore ERP — Sistema de Farmacia Privada</title>
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --azul-sanitario: #1E40AF;
            --azul-oscuro: #0F172A;
            --azul-medio: #1D4ED8;
            --verde-medico: #059669;
            --verde-medico-hover: #047857;
            --verde-claro: #D1FAE5;
            --gris-fondo: #F1F5F9;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--gris-fondo);
            color: #0F172A;
            overflow-x: hidden;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .bg-azul-sanitario {
            background: linear-gradient(180deg, #090E1A 0%, #0F172A 40%, #1E3A8A 100%);
        }

        .sidebar {
            width: 265px;
            min-width: 265px;
            box-shadow: 6px 0 28px rgba(15, 23, 42, 0.22);
            z-index: 1030;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                width: 215px;
                min-width: 215px;
            }
        }

        .brand-box {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.09) 0%, rgba(255, 255, 255, 0.03) 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 13px 15px;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.15);
        }

        .nav-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.78);
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 0.89rem;
            display: flex;
            align-items: center;
            gap: 11px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            margin-bottom: 6px;
            border: 1px solid transparent;
        }

        .nav-sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.09);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateX(3px);
        }

        .nav-sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.28) 0%, rgba(5, 150, 105, 0.12) 100%);
            border: 1px solid rgba(52, 211, 153, 0.55);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.25);
        }

        .nav-svg-icon {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.25));
        }

        .sidebar-status {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 12px 14px;
        }

        .btn-verde-medico {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.28);
            transition: all 0.2s ease;
        }

        .btn-verde-medico:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.38);
        }

        .card-pro {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            box-shadow: 0 6px 24px -4px rgba(15, 23, 42, 0.06);
        }

        .card-pro .card-header {
            background: transparent;
            border-bottom: 1px solid #F1F5F9;
            padding: 16px 20px;
            font-weight: 700;
        }

        .kpi-card {
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #ffffff;
            box-shadow: 0 6px 24px -4px rgba(15, 23, 42, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -4px rgba(15, 23, 42, 0.1);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        @keyframes pulse-scanner {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .scanner-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #10B981;
            display: inline-block;
            animation: pulse-scanner 1.8s infinite;
        }

        .scanner-box {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            border: 2px solid #10B981;
            border-radius: 18px;
            color: #fff;
            box-shadow: 0 12px 28px -6px rgba(16, 185, 129, 0.22);
        }

        .scanner-input {
            background: rgba(255, 255, 255, 0.96);
            border: 2px solid transparent;
            border-radius: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #0F172A;
        }

        .scanner-input:focus {
            background: #ffffff;
            border-color: #10B981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25);
        }

        .topbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #E2E8F0;
            padding: 10px 24px;
        }

        .role-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 5px 13px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .table > :not(caption) > * > * {
            padding: 0.8rem 0.95rem;
        }

        .input-cant-fila::-webkit-outer-spin-button,
        .input-cant-fila::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .input-cant-fila {
            -moz-appearance: textfield;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }
    </style>
</head>
<body>
    {{-- Incluir librería de iconos vectoriales HD diseñados a medida --}}
    @include('partials.svg-icons')

    @php
        $visacionesPendientesCount = \App\Models\SolicitudVisacion::where('estado', 'PENDIENTE')->count();
        $qfEnTurno = \App\Models\User::where('rol', 'qf')->where('activo', true)->first();
        $perfilesRapidos = \App\Models\User::whereIn('email', ['vendedor@farmacia.cl', 'qf@farmacia.cl', 'admin@farmacia.cl'])->get();
        $usuarioActual = auth()->user();
        $rolActual = $usuarioActual ? $usuarioActual->rol : null;
    @endphp

    <div class="d-flex min-vh-100">
        <!-- Sidebar de Navegación Adaptativo por Rol -->
        <nav class="bg-azul-sanitario text-white p-3 vh-100 sticky-top d-flex flex-column justify-content-between sidebar">
            <div>
                <!-- Marca Oficial con Escudo Vectorial 3D -->
                <div class="brand-box d-flex align-items-center gap-3 mb-3">
                    <svg width="42" height="42" class="flex-shrink-0">
                        <use href="#icon-pharmacore-logo"></use>
                    </svg>
                    <div>
                        <h6 class="fw-extrabold mb-0 text-white tracking-tight">PharmaCore ERP</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small mt-1" style="font-size: 0.66rem;">
                            DS 466 MINSAL • SII • FEFO
                        </span>
                    </div>
                </div>

                <!-- Tarjeta de Identidad del Rol Conectado (Con Icono Vectorial HD a Medida) -->
                <div class="p-2.5 rounded-4 mb-3 border border-light border-opacity-15 p-2" style="background: rgba(15, 23, 42, 0.55);">
                    @if($usuarioActual)
                        @php
                            $svgRolId = match($rolActual) {
                                'vendedor' => '#icon-role-vendedor',
                                'qf' => '#icon-role-qf',
                                'admin' => '#icon-role-admin',
                                default => '#icon-role-vendedor'
                            };
                            $colorBadgeRol = match($rolActual) {
                                'vendedor' => 'bg-primary text-white',
                                'qf' => 'bg-success text-white',
                                'admin' => 'bg-warning text-dark',
                                default => 'bg-info text-dark'
                            };
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <svg width="36" height="36" class="flex-shrink-0">
                                <use href="{{ $svgRolId }}"></use>
                            </svg>
                            <div class="flex-grow-1 overflow-hidden" style="min-width: 0;">
                                <div class="fw-bold small text-white text-truncate">{{ $usuarioActual->name }}</div>
                                <span class="badge {{ $colorBadgeRol }}" style="font-size: 0.65rem;">{{ strtoupper($usuarioActual->rol_nombre) }}</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-1 mt-1 border-top border-secondary border-opacity-25" style="font-size: 0.7rem;">
                            <span class="text-white-50 font-mono">{{ $usuarioActual->rut }}</span>
                            <a href="{{ route('login') }}" class="text-info text-decoration-none fw-semibold">Cambiar cuenta →</a>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2">
                            <svg width="34" height="34" class="flex-shrink-0">
                                <use href="#icon-role-admin"></use>
                            </svg>
                            <div>
                                <div class="fw-bold small text-white">Sin Sesión Iniciada</div>
                                <a href="{{ route('login') }}" class="badge bg-warning text-dark text-decoration-none" style="font-size: 0.68rem;">
                                    Iniciar Sesión por Rol →
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Menú Personalizado según el Login Activo -->
                <div class="text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.67rem; letter-spacing: 1px;">
                    @if($rolActual === 'vendedor')
                        Vista Exclusiva Vendedor Caja
                    @elseif($rolActual === 'qf')
                        Vista Dirección Técnica (Q.F.)
                    @elseif($rolActual === 'admin')
                        Vista Administración Total
                    @else
                        Menú General de Módulos
                    @endif
                </div>

                <ul class="nav flex-column nav-sidebar">
                    {{-- Dashboard BI: visible para Admin, QF o sin login --}}
                    @if(!$rolActual || in_array($rolActual, ['admin', 'qf']))
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <svg class="nav-svg-icon"><use href="#icon-nav-dashboard"></use></svg>
                                <span>Dashboard BI Vivo</span>
                            </a>
                        </li>
                    @endif

                    {{-- Punto de Venta POS: Vista principal del Vendedor y supervisable por Admin --}}
                    @if(!$rolActual || in_array($rolActual, ['vendedor', 'admin']))
                        <li class="nav-item">
                            <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                                <svg class="nav-svg-icon"><use href="#icon-nav-pos"></use></svg>
                                <span>Terminal Caja POS</span>
                                <span class="ms-auto badge bg-success text-white rounded-pill" style="font-size: 0.64rem;">FEFO</span>
                            </a>
                        </li>
                    @endif

                    {{-- Central de Visación de Recetas: Vista principal del Químico Farmacéutico (y auditable por Admin) --}}
                    @if(!$rolActual || in_array($rolActual, ['qf', 'admin']))
                        <li class="nav-item">
                            <a href="{{ route('visacion.index') }}" class="nav-link {{ request()->routeIs('visacion.*') ? 'active' : '' }}">
                                <svg class="nav-svg-icon"><use href="#icon-nav-visacion"></use></svg>
                                <span>Visación Recetas Q.F.</span>
                                <span id="sidebarBadgeVisaciones" class="ms-auto badge {{ $visacionesPendientesCount > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-pill">
                                    {{ $visacionesPendientesCount }}
                                </span>
                            </a>
                        </li>
                    @endif

                    {{-- Inventario y Lotes FEFO: disponible para consulta y abastecimiento --}}
                    <li class="nav-item">
                        <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                            <svg class="nav-svg-icon"><use href="#icon-nav-inventario"></use></svg>
                            <span>Inventario y Lotes</span>
                        </a>
                    </li>

                    {{-- Operaciones Caja Z, Boletas SII, Cadena de Frío & Recall ISP --}}
                    <li class="nav-item">
                        <a href="{{ route('operaciones.index') }}" class="nav-link {{ request()->routeIs('operaciones.*') ? 'active' : '' }}">
                            <svg class="nav-svg-icon"><use href="#icon-nav-operaciones"></use></svg>
                            <span>Caja Z, SII & ISP</span>
                        </a>
                    </li>

                    {{-- Administración de Personal, Turnos Nocturnos, Licencias y Reemplazos: EXCLUSIVO DEL ADMINISTRADOR --}}
                    @if($rolActual === 'admin')
                        <li class="nav-item">
                            <a href="{{ route('admin.personal.index') }}" class="nav-link {{ request()->routeIs('admin.personal.*') ? 'active' : '' }}">
                                <svg class="nav-svg-icon"><use href="#icon-nav-personal"></use></svg>
                                <span>Personal y Turnos</span>
                                <span class="ms-auto badge bg-warning text-dark rounded-pill" style="font-size: 0.62rem;">SOLO ADMIN</span>
                            </a>
                        </li>
                    @endif

                    <li class="nav-item mt-1 pt-1 border-top border-secondary border-opacity-25">
                        <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                            <i class="bi bi-person-Bounding-box fs-5 text-info"></i>
                            <span>Portal Login / Cuentas</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Pie de Sidebar: Presencia Obligatoria del Químico Farmacéutico (DS 466 MINSAL) -->
            <div class="sidebar-status text-white">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="scanner-dot"></span>
                    <span class="small fw-bold" style="font-size: 0.76rem;">D.T. en Ejercicio (DS 466):</span>
                </div>
                <div class="text-white fw-semibold" style="font-size: 0.74rem;">
                    {{ $qfEnTurno ? $qfEnTurno->name : 'Dr. Sebastián Morales (Q.F.)' }}
                </div>
                <div class="text-white-50 font-mono" style="font-size: 0.69rem;">
                    Reg. ISP: {{ $qfEnTurno->registro_isp ?? 'SIS-QF-48291' }}
                </div>
            </div>
        </nav>

        <!-- Área Principal -->
        <div class="flex-grow-1 d-flex flex-column min-w-0">
            <!-- Barra Superior con Termohigrómetro ISP y Selector Rápido de las 3 Cuentas (Cada una con su Vista) -->
            <header class="topbar d-flex flex-wrap justify-content-between align-items-center sticky-top gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 rounded-pill small">
                        <i class="bi bi-snow2 me-1 text-primary"></i>Cadena Frío: <strong>4.2°C</strong> (2°–8°C)
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-none d-xl-inline-block">
                        <i class="bi bi-thermometer-half me-1"></i>Sala: <strong>21.4°C</strong> • HR 48%
                    </span>
                </div>

                <!-- Botones con Iconos Vectoriales HD para Alternar entre las 3 Vistas de Usuario en 1 Clic -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-bold d-none d-md-inline" style="font-size: 0.75rem;">Entrar a la vista de:</span>
                    @foreach($perfilesRapidos as $p)
                        @php
                            $isCurrent = $usuarioActual && $usuarioActual->id === $p->id;
                            $svgId = match($p->rol) {
                                'vendedor' => '#icon-role-vendedor',
                                'qf' => '#icon-role-qf',
                                'admin' => '#icon-role-admin',
                                default => '#icon-role-vendedor'
                            };
                            $labelVista = match($p->rol) {
                                'vendedor' => 'Vista Vendedor (Caja POS)',
                                'qf' => 'Vista Químico Farmacéutico (Visación)',
                                'admin' => 'Vista Administrador (Personal y Turnos)',
                                default => $p->rol
                            };
                            $stylePill = $isCurrent
                                ? 'background: #0F172A; color: #fff; border: 2px solid #10B981; box-shadow: 0 4px 12px rgba(16,185,129,0.25);'
                                : 'background: #F8FAFC; color: #0F172A; border: 1px solid #CBD5E1;';
                        @endphp
                        <a href="{{ route('auth.quick-switch', $p->id) }}" class="role-pill-btn" style="{{ $stylePill }}">
                            <svg width="20" height="20"><use href="{{ $svgId }}"></use></svg>
                            <span>{{ $labelVista }}</span>
                        </a>
                    @endforeach
                </div>
            </header>

            <main class="flex-grow-1 p-4">
                {{-- Alerta de Seguridad RBAC cuando un rol sin permisos intenta entrar al panel de otro rol --}}
                @if(session('rbac_warning'))
                    <div class="alert alert-warning border-warning border-2 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4">
                        <svg width="38" height="38" class="flex-shrink-0"><use href="#icon-role-admin"></use></svg>
                        <div>
                            <div class="fw-extrabold text-dark">Control de Permisos por Rol (RBAC)</div>
                            <div class="small text-dark">{{ session('rbac_warning') }}</div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.playScannerBeep = function(success = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                if (success) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(1480, ctx.currentTime);
                    gain.gain.setValueAtTime(0.12, ctx.currentTime);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.09);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(280, ctx.currentTime);
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.22);
                }
            } catch (e) {}
        };
    </script>
    @stack('scripts')
</body>
</html>
