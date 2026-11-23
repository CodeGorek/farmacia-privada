@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1">
                <i class="bi bi-person-vcard-fill text-primary me-2"></i>Registro y Padrón de Clientes
            </h2>
            <p class="text-muted mb-0">Inscripción de pacientes/clientes frecuentes con vinculación automática al Switch de Convenios en Caja POS.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6">
            {{ $clientes->count() }} clientes registrados
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 rounded-3">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Formulario Registrar Cliente -->
        <div class="col-lg-4">
            <div class="card-pro">
                <div class="card-header">
                    <i class="bi bi-person-plus-fill text-success me-2"></i>Registrar Nuevo Cliente
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('clientes.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">RUT del Cliente *</label>
                            <input type="text" name="rut" value="{{ old('rut') }}" class="form-control form-control-sm font-mono fw-bold" placeholder="Ej. 18.450.123-K" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre Completo *</label>
                            <input type="text" name="nombre_completo" value="{{ old('nombre_completo') }}" class="form-control form-control-sm" placeholder="Ej. Patricia Soto Vega" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Teléfono</label>
                                <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-control form-control-sm" placeholder="+56 9 ...">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" placeholder="correo@ejemplo.cl">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Dirección</label>
                            <input type="text" name="direccion" value="{{ old('direccion') }}" class="form-control form-control-sm" placeholder="Calle, número, comuna">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Convenio Asociado (Opcional)</label>
                            <select name="convenio_id" class="form-select form-select-sm">
                                <option value="">-- Cliente Particular (Sin convenio) --</option>
                                @foreach($convenios as $conv)
                                    <option value="{{ $conv->id }}" {{ old('convenio_id') == $conv->id ? 'selected' : '' }}>
                                        {{ $conv->nombre }} ({{ number_format($conv->porcentaje_descuento, 0) }}% Dcto.)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Si eliges un convenio, su RUT quedará activo de inmediato en el POS.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Observaciones</label>
                            <input type="text" name="observaciones" value="{{ old('observaciones') }}" class="form-control form-control-sm" placeholder="Ej. Paciente crónico, alergias...">
                        </div>
                        <button type="submit" class="btn btn-verde-medico w-100 py-2 rounded-3 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i>Guardar Cliente
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla de Clientes Registrados -->
        <div class="col-lg-8">
            <div class="card-pro">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-people-fill text-primary me-2"></i>Clientes Registrados en la Farmacia</span>
                    <form method="GET" action="{{ route('clientes.index') }}" class="d-flex gap-2">
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Buscar RUT, nombre, email..." style="min-width: 230px;">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Buscar</button>
                        @if($q !== '')
                            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                        @endif
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>RUT</th>
                                    <th>Cliente</th>
                                    <th>Contacto</th>
                                    <th>Convenio</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientes as $cli)
                                    <tr>
                                        <td class="font-mono fw-bold text-primary">{{ \App\Models\Cliente::formatearRut($cli->rut) }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $cli->nombre_completo }}</div>
                                            <div class="text-muted small">{{ $cli->direccion ?: 'Sin dirección registrada' }}</div>
                                        </td>
                                        <td class="small">
                                            <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $cli->telefono ?: '—' }}</div>
                                            <div class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $cli->email ?: '—' }}</div>
                                        </td>
                                        <td>
                                            @if($cli->convenio)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    {{ $cli->convenio->nombre }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border">Particular</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $cli->observaciones ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No se encontraron clientes registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
