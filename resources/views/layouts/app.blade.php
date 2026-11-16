<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PharmaCore POS — Sistema de Farmacia Privada</title>
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
            background: linear-gradient(180deg, #0F172A 0%, #1E3A8A 55%, #1E40AF 100%);
        }

        .sidebar {
            width: 255px;
            min-width: 255px;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.15);
            z-index: 1030;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                width: 210px;
                min-width: 210px;
            }
        }

        .brand-box {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 13px 15px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            flex-shrink: 0;
        }

        .nav-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.78);
            border-radius: 11px;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            margin-bottom: 5px;
        }

        .nav-sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        .nav-sidebar .nav-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.9) 100%);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
        }

        .sidebar-status {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 11px 13px;
        }

        .btn-verde-medico {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            transition: all 0.2s ease;
        }

        .btn-verde-medico:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
        }

        .card-pro {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        }

        .card-pro .card-header {
            background: transparent;
            border-bottom: 1px solid #F1F5F9;
            padding: 15px 20px;
            font-weight: 700;
        }

        .kpi-card {
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -4px rgba(15, 23, 42, 0.1);
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
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
            border-radius: 16px;
            color: #fff;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.2);
        }

        .scanner-input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            border-radius: 10px;
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #E2E8F0;
            padding: 10px 24px;
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
    @php
        $visacionesPendientesCount = \App\Models\SolicitudVisacion::where('estado', 'PENDIENTE')->count();
        $qfEnTurno = \App\Models\User::where('rol', 'qf')->where('activo', true)->first();
        $perfilesRapidos = \App\Models\User::whereIn('email', ['vendedor@farmacia.cl', 'qf@farmacia.cl', 'admin@farmacia.cl'])->get();
        $usuarioActual = auth()->user();
    @endphp

    <div class="d-flex min-vh-100">
        <!-- Sidebar de Navegación -->
        <nav class="bg-azul-sanitario text-white p-3 vh-100 sticky-top d-flex flex-column justify-content-between sidebar">
            <div>
                <div class="brand-box d-flex align-items-center gap-3 mb-3">
                    <div class="brand-icon">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-white">Sistema Farmacia</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small mt-1" style="font-size: 0.68rem;">
                            DS 466 MINSAL • FEFO
                        </span>
                    </div>
                </div>

                <!-- Tarjeta de Sesión Activa -->
                <div class="p-2 rounded-3 mb-3 border border-light border-opacity-10" style="background: rgba(255,255,255,0.06);">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-white-50" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Sesión Activa</span>
                        <a href="{{ route('login') }}" class="text-info text-decoration-none" style="font-size: 0.72rem;">
                            <i class="bi bi-box-arrow-in-right"></i> Cambiar
                        </a>
                    </div>
                    @if($usuarioActual)
                        <div class="fw-bold small text-white text-truncate mt-1">{{ $usuarioActual->name }}</div>
                        <span class="badge bg-info text-dark" style="font-size: 0.68rem;">{{ $usuarioActual->rol_nombre }}</span>
                    @else
                        <div class="fw-bold small text-white mt-1">Modo Demostración Multirrol</div>
                        <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">Acceso Completo Habilitado</span>
                    @endif
                </div>

                <div class="text-uppercase text-white-50 small fw-bold px-2 mb-1" style="font-size: 0.68rem; letter-spacing: 1px;">
                    Módulos del Sistema
                </div>
                <ul class="nav flex-column nav-sidebar">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span>Dashboard BI</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="bi bi-upc-scan"></i>
                            <span>Punto de Venta</span>
                            <span class="ms-auto badge bg-light text-dark rounded-pill" style="font-size: 0.68rem;">Vendedor</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('visacion.index') }}" class="nav-link {{ request()->routeIs('visacion.*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-medical-fill"></i>
                            <span>Visación Recetas</span>
                            <span id="sidebarBadgeVisaciones" class="ms-auto badge {{ $visacionesPendientesCount > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-pill">
                                {{ $visacionesPendientesCount }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                            <i class="bi bi-boxes"></i>
                            <span>Inventario y Lotes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.personal.index') }}" class="nav-link {{ request()->routeIs('admin.personal.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Personal y Turnos</span>
                            <span class="ms-auto badge bg-warning text-dark rounded-pill" style="font-size: 0.65rem;">Admin</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>Login / Cuentas</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-status text-white">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="scanner-dot"></span>
                    <span class="small fw-bold" style="font-size: 0.78rem;">D.T. en Ejercicio (DS 466):</span>
                </div>
                <div class="text-white fw-semibold" style="font-size: 0.75rem;">
                    {{ $qfEnTurno ? $qfEnTurno->name : 'Dr. Sebastián Morales (Q.F.)' }}
                </div>
                <div class="text-white-50 font-mono" style="font-size: 0.7rem;">
                    Reg. ISP: {{ $qfEnTurno->registro_isp ?? 'SIS-QF-48291' }}
                </div>
            </div>
        </nav>

        <!-- Área Principal -->
        <div class="flex-grow-1 d-flex flex-column min-w-0">
            <!-- Barra Superior con Selector Rápido de Cuentas y Termohigrómetro ISP -->
            <header class="topbar d-flex flex-wrap justify-content-between align-items-center sticky-top gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill small">
                        <i class="bi bi-thermometer-snow me-1"></i>Cadena Frío: <strong>4.2°C</strong> (Óptimo 2°-8°C)
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small d-none d-xl-inline-block">
                        <i class="bi bi-thermometer-half me-1"></i>Sala: <strong>21.4°C</strong> • HR 48%
                    </span>
                </div>

                <!-- Botones de Cambio Rápido de Rol en 1 Clic para Pruebas -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small fw-semibold d-none d-md-inline">Simular vista como:</span>
                    @foreach($perfilesRapidos as $p)
                        @php
                            $isCurrent = $usuarioActual && $usuarioActual->id === $p->id;
                            $btnClass = match($p->rol) {
                                'vendedor' => $isCurrent ? 'btn-primary' : 'btn-outline-primary',
                                'qf' => $isCurrent ? 'btn-success' : 'btn-outline-success',
                                'admin' => $isCurrent ? 'btn-dark' : 'btn-outline-dark',
                                default => 'btn-outline-secondary'
                            };
                            $rolCorto = match($p->rol) {
                                'vendedor' => '👨‍💼 Vendedor POS',
                                'qf' => '🧪 Químico Farmacéutico',
                                'admin' => '🛡️ Administrador',
                                default => $p->rol
                            };
                        @endphp
                        <a href="{{ route('auth.quick-switch', $p->id) }}" class="btn btn-sm {{ $btnClass }} rounded-pill px-3 py-1 fw-bold" style="font-size: 0.78rem;">
                            {{ $rolCorto }}
                        </a>
                    @endforeach
                </div>
            </header>

            <main class="flex-grow-1 p-4">
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
