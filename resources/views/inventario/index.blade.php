@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-boxes text-primary me-2"></i>Gestión de Inventario, Lotes FEFO y Controlados</h2>
            <p class="text-muted mb-0">Alta de catálogo con pistola de código de barras, recepción de lotes indexados por caducidad y abastecimiento Bodega → Vitrina.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
            <i class="bi bi-upc-scan me-1"></i>Campos protegidos contra envío prematuro por Enter del lector
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
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Verifica los datos ingresados:</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- 1. Registrar Nuevo Medicamento -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-capsule-pill text-success me-2"></i>1. Registrar Nuevo Medicamento en Catálogo</span>
                    <span id="badgeScanNuevoMed" class="badge bg-secondary-subtle text-secondary small">Listo para escanear caja</span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('inventario.medicamentos.store') }}" method="POST" id="formNuevoMedicamento">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">
                                    <i class="bi bi-upc-scan text-success me-1"></i>Código de Barra (EAN / UPC)
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-dark text-white"><i class="bi bi-upc"></i></span>
                                    <input type="text"
                                           name="codigo_barra"
                                           id="inputNuevoCodigoBarra"
                                           class="form-control font-mono fw-bold"
                                           placeholder="Escanea la caja aquí..."
                                           autocomplete="off"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">Nombre Comercial</label>
                                <input type="text"
                                       name="nombre_comercial"
                                       id="inputNombreComercial"
                                       class="form-control form-control-sm"
                                       placeholder="Ej. Losartán Potásico 50mg Caja x30"
                                       required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Principio Activo (DCI)</label>
                                <input type="text" name="principio_activo" class="form-control form-control-sm" placeholder="Ej. Losartán" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Laboratorio Fabricante</label>
                                <input type="text" name="laboratorio" class="form-control form-control-sm" placeholder="Ej. Laboratorio Saval / Chile">
                            </div>
                        </div>

                        <div class="row g-3 mb-4 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Precio Venta ($ CLP)</label>
                                <input type="number" step="1" name="precio_unitario" class="form-control form-control-sm fw-bold" value="3990" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Stock Crítico Mínimo</label>
                                <input type="number" name="stock_minimo" class="form-control form-control-sm" value="15" required>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 rounded-3 border bg-light">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="requiere_receta" value="1" id="reqReceta">
                                        <label class="form-check-label small fw-semibold" for="reqReceta">Bajo Receta Médica</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="es_controlado" value="1" id="esControlado">
                                        <label class="form-check-label small fw-semibold text-danger" for="esControlado">Controlado / Psicotrópico</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-verde-medico w-100 py-2 rounded-3 fw-bold">
                            <i class="bi bi-plus-circle me-1"></i>Guardar Medicamento en Catálogo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Recepcionar Lote FEFO -->
        <div class="col-lg-6">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar2-check text-primary me-2"></i>2. Recepcionar Nuevo Lote (Indexación FEFO)</span>
                    <span class="badge bg-primary-subtle text-primary small">Índice en Fecha de Vencimiento</span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('inventario.lotes.store') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">
                                    <i class="bi bi-upc-scan text-primary me-1"></i>Buscar por Lector (Opcional)
                                </label>
                                <input type="text"
                                       id="inputScanLoteMed"
                                       class="form-control form-control-sm font-mono"
                                       placeholder="Escanea para elegir prod..."
                                       autocomplete="off">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">Medicamento a Abastecer</label>
                                <select name="medicamento_id" id="selectLoteMedicamento" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccione medicamento --</option>
                                    @foreach($medicamentos as $med)
                                        <option value="{{ $med->id }}" data-codigo="{{ $med->codigo_barra }}">
                                            [{{ $med->codigo_barra }}] {{ $med->nombre_comercial }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Código / Serie de Lote</label>
                                <input type="text" name="numero_lote" id="inputNumeroLote" class="form-control form-control-sm font-mono" placeholder="LOT-2026-X1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Fecha Elaboración</label>
                                <input type="date" name="fecha_elaboracion" class="form-control form-control-sm" value="{{ now()->subMonths(2)->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-danger">Fecha Vencimiento (FEFO)</label>
                                <input type="date" name="fecha_vencimiento" class="form-control form-control-sm border-danger-subtle" value="{{ now()->addMonths(14)->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unidades Directas a Vitrina (POS)</label>
                                <input type="number" name="stock_vitrina" class="form-control form-control-sm fw-bold text-primary" value="20" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Unidades en Reserva de Bodega</label>
                                <input type="number" name="stock_bodega" class="form-control form-control-sm fw-bold" value="80" min="0" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Ingresar Lote al Motor FEFO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Matriz de Lotes Activos -->
    <div class="card-pro mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <i class="bi bi-layers-fill text-primary me-2"></i>
                <span>Matriz General de Lotes (Ordenados por Fecha de Vencimiento FEFO)</span>
            </div>
            <span class="badge bg-dark rounded-pill">{{ $lotes->count() }} lotes registrados</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Serie Lote</th>
                            <th>Código Barra</th>
                            <th>Medicamento / Laboratorio</th>
                            <th>Elaboración</th>
                            <th>Vencimiento (FEFO)</th>
                            <th class="text-center">Stock Vitrina</th>
                            <th class="text-center">Stock Bodega</th>
                            <th class="text-end">Traspaso Bodega → Vitrina</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lotes as $lote)
                            @php
                                $dias = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
                            @endphp
                            <tr>
                                <td><span class="badge bg-dark font-mono px-2 py-1">{{ $lote->numero_lote }}</span></td>
                                <td class="font-mono small text-muted"><i class="bi bi-upc me-1"></i>{{ $lote->medicamento->codigo_barra ?? 'N/A' }}</td>
                                <td>
                                    <div class="fw-bold">{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $lote->medicamento->principio_activo ?? '' }} • {{ $lote->medicamento->laboratorio ?? 'Lab. General' }}</div>
                                </td>
                                <td class="small">{{ $lote->fecha_elaboracion->format('d/m/Y') }}</td>
                                <td>
                                    <div class="fw-bold {{ $dias <= 60 ? 'text-danger' : 'text-dark' }}">
                                        {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                                    </div>
                                    @if($dias <= 60)
                                        <span class="badge bg-danger-subtle text-danger" style="font-size: 0.68rem;">Prioridad Crítica ({{ $dias }}d)</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success" style="font-size: 0.68rem;">Vigente ({{ $dias }}d)</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3">
                                        {{ $lote->stock_vitrina }} u.
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary fs-6 px-3">
                                        {{ $lote->stock_bodega }} u.
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($lote->stock_bodega > 0)
                                        <form action="{{ route('inventario.lotes.traspasar', $lote) }}" method="POST" class="d-inline-flex gap-1 align-items-center justify-content-end">
                                            @csrf
                                            <input type="number" name="cantidad" class="form-control form-control-sm text-center fw-bold" style="width: 75px;" min="1" max="{{ $lote->stock_bodega }}" value="{{ min(10, $lote->stock_bodega) }}">
                                            <button class="btn btn-verde-medico btn-sm text-nowrap">
                                                <i class="bi bi-arrow-left-right me-1"></i>A Vitrina
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-light text-muted border">Bodega agotada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay lotes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Libro Oficial de Controlados -->
    <div class="card-pro">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-journal-medical text-danger me-2"></i>
                <span>Libro Oficial de Psicotrópicos y Estupefacientes (Trazabilidad Sanitaria)</span>
            </div>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">Fiscalización ISP / SEREMI</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Fecha / Venta</th>
                            <th>Medicamento Controlado</th>
                            <th>Lote Dispensado</th>
                            <th>Paciente (RUT / Nombre)</th>
                            <th>Médico Prescriptor</th>
                            <th>N° Receta</th>
                            <th class="text-end">Cant.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($controlados as $reg)
                            <tr>
                                <td class="small">
                                    <span class="fw-bold">#{{ $reg->venta_id }}</span>
                                    <div class="text-muted">{{ $reg->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="fw-bold">{{ $reg->medicamento->nombre_comercial ?? 'N/A' }}</td>
                                <td><span class="badge bg-dark font-mono">{{ $reg->lote->numero_lote ?? 'N/A' }}</span></td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $reg->nombre_paciente }}</div>
                                    <div class="text-muted font-mono">{{ $reg->rut_paciente }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $reg->nombre_medico }}</div>
                                    <div class="text-muted font-mono">{{ $reg->rut_medico }}</div>
                                </td>
                                <td><span class="badge bg-danger-subtle text-danger font-mono">{{ $reg->numero_receta }}</span></td>
                                <td class="text-end fw-bold">{{ $reg->cantidad_dispensada }} u.</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Sin dispensaciones de medicamentos controlados registradas hasta el momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // 1. En Alta de Medicamento: interceptar el Enter automático de la pistola lectora para no enviar el formulario incompleto
    const inputNuevoCodigo = document.getElementById('inputNuevoCodigoBarra');
    const inputNombreComercial = document.getElementById('inputNombreComercial');
    const badgeScanNuevoMed = document.getElementById('badgeScanNuevoMed');

    if (inputNuevoCodigo) {
        inputNuevoCodigo.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim().length > 0) {
                    window.playScannerBeep(true);
                    badgeScanNuevoMed.className = 'badge bg-success text-white small';
                    badgeScanNuevoMed.innerHTML = `<i class="bi bi-check2-circle me-1"></i>Código capturado: ${this.value.trim()}`;
                    inputNombreComercial.focus();
                }
            }
        });
    }

    // 2. En Recepción de Lotes: escanear la caja selecciona automáticamente el medicamento en el <select>
    const inputScanLoteMed = document.getElementById('inputScanLoteMed');
    const selectLoteMed = document.getElementById('selectLoteMedicamento');
    const inputNumeroLote = document.getElementById('inputNumeroLote');

    if (inputScanLoteMed && selectLoteMed) {
        inputScanLoteMed.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = this.value.trim();
                if (!code) return;

                let match = null;
                for (let i = 0; i < selectLoteMed.options.length; i++) {
                    if (selectLoteMed.options[i].dataset.codigo === code) {
                        match = selectLoteMed.options[i];
                        break;
                    }
                }

                if (match) {
                    window.playScannerBeep(true);
                    selectLoteMed.value = match.value;
                    this.value = '';
                    this.placeholder = '¡Seleccionado: ' + code + '!';
                    inputNumeroLote.focus();
                } else {
                    window.playScannerBeep(false);
                    this.select();
                }
            }
        });
    }
})();
</script>
@endpush
