<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — PharmaCore ERP Farmacia Privada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at 15% 20%, #1E3A8A 0%, #0F172A 55%, #090D16 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0F172A;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.55);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .login-header {
            background: linear-gradient(135deg, #090E1A 0%, #1E3A8A 60%, #059669 100%);
            padding: 34px 32px 28px;
            text-align: center;
            color: #ffffff;
        }
        .btn-verde-medico {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            border: none;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.32);
            transition: all 0.2s ease;
        }
        .btn-verde-medico:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            transform: translateY(-1px);
        }
        .form-control:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18);
        }
    </style>
</head>
<body>
    @include('partials.svg-icons')

    <div class="login-card">
        <div class="login-header">
            <div class="mb-3 d-inline-flex p-2 rounded-4" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255,255,255,0.18);">
                <svg width="56" height="56">
                    <use href="#icon-pharmacore-logo"></use>
                </svg>
            </div>
            <h4 class="fw-extrabold mb-1">PharmaCore ERP</h4>
            <p class="text-white-50 small mb-0">Sistema de Gestión y Dispensación Farmacéutica</p>
        </div>

        <div class="p-4 p-sm-5">
            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-4 small d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-shield-exclamation fs-5"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary">Correo Institucional o RUT</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-vcard text-primary"></i></span>
                        <input type="text"
                               name="identificador"
                               class="form-control bg-light border-start-0 fs-6 fw-semibold"
                               value="{{ old('identificador') }}"
                               placeholder="usuario@farmacia.cl o RUT"
                               autofocus
                               required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-secondary">Contraseña</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                        <input type="password"
                               name="password"
                               class="form-control bg-light border-start-0 fs-6 font-mono"
                               placeholder="••••••••"
                               required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small text-muted" for="remember">Mantener sesión de turno</label>
                    </div>
                    <span class="badge bg-light text-secondary border small">DS 466 MINSAL</span>
                </div>

                <button type="submit" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-6">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
