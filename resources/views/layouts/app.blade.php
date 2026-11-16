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

        /* Sidebar Profesional */
        .bg-azul-sanitario {
            background: linear-gradient(180deg, #0F172A 0%, #1E3A8A 55%, #1E40AF 100%);
        }

        .sidebar {
            width: 245px;
            min-width: 245px;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.15);
            z-index: 1030;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                width: 200px;
                min-width: 200px;
            }
        }

        .brand-box {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 14px 16px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        }

        .nav-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            border-radius: 11px;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.94rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            margin-bottom: 6px;
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
            padding: 12px 14px;
        }

        /* Botones y Tarjetas */
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
            padding: 16px 20px;
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
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        /* Animación de pulso para el lector de códigos de barra */
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
            font-size: 1.1rem;
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
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #E2E8F0;
            padding: 12px 28px;
        }

        .table > :not(caption) > * > * {
            padding: 0.85rem 1rem;
        }
    </style>
</head>
<body>
    <div class="d-flex min-vh-100">
        <!-- Sidebar de Navegación -->
        <nav class="bg-azul-sanitario text-white p-3 vh-100 sticky-top d-flex flex-column justify-content-between sidebar">
            <div>
                <div class="brand-box d-flex align-items-center gap-3 mb-4">
                    <div class="brand-icon">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-white tracking-tight">Sistema Farmacia</h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small mt-1">
                            FEFO v2.4 • POS Clínico
                        </span>
                    </div>
                </div>

                <div class="text-uppercase text-white-50 small fw-bold px-2 mb-2" style="font-size: 0.72rem; letter-spacing: 1px;">
                    Módulos Operativos
                </div>
                <ul class="nav flex-column nav-sidebar">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-grid-1x2-fill fs-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="bi bi-upc-scan fs-5"></i>
                            <span>Punto de Venta</span>
                            <span class="ms-auto badge bg-light text-dark rounded-pill small">POS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inventario.index') }}" class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                            <i class="bi bi-boxes fs-5"></i>
                            <span>Inventario y Lotes</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-status text-white">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="scanner-dot"></span>
                    <span class="small fw-bold">Lector HID Barcode Activo</span>
                </div>
                <div class="text-white-50" style="font-size: 0.76rem;">
                    Compatible con pistolas láser USB / Bluetooth (EAN-13, Code128).
                </div>
            </div>
        </nav>

        <!-- Área Principal -->
        <div class="flex-grow-1 d-flex flex-column min-w-0">
            <!-- Barra Superior -->
            <header class="topbar d-flex justify-content-between align-items-center sticky-top">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-shield-check me-1"></i> Algoritmo FEFO Transaccional (LockForUpdate)
                    </span>
                    <span class="text-muted small d-none d-md-inline">
                        <i class="bi bi-database-check me-1 text-success"></i> Trazabilidad de Lotes & Controlados
                    </span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold small leading-none">Turno Farmacéutico Activo</div>
                        <div class="text-muted" style="font-size: 0.75rem;" id="relojTopbar">{{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                    <a href="{{ route('pos.index') }}" class="btn btn-verde-medico btn-sm px-3 py-2 rounded-3 fw-semibold">
                        <i class="bi bi-upc-scan me-1"></i> Ir a Caja POS
                    </a>
                </div>
            </header>

            <main class="flex-grow-1 p-4">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sonido clínico para confirmación/error del lector de código de barras (Web Audio API)
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
