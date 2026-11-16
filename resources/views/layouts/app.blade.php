<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - Farmacia Privada</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-azul-sanitario { background-color: #1E40AF; }
        .btn-verde-medico { background-color: #059669; color: white; }
        .btn-verde-medico:hover { background-color: #047857; color: white; }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav class="bg-azul-sanitario text-white p-3 vh-100 sticky-top" style="width: 250px;">
            <h4>Sistema Farmacia</h4>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'fw-bold' : '' }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('pos.index') }}" class="nav-link text-white {{ request()->routeIs('pos.*') ? 'fw-bold' : '' }}">Punto de Venta</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventario.index') }}" class="nav-link text-white {{ request()->routeIs('inventario.*') ? 'fw-bold' : '' }}">Inventario</a>
                </li>
            </ul>
        </nav>
        <main class="flex-grow-1 p-4 bg-light min-vh-100">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
