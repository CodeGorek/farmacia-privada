@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-boxes text-primary me-2"></i>Gestión de Inventario, Bodega FEFO y Productos</h2>
            <p class="text-muted mb-0">Alta de medicamentos, sección aparte de productos no farmacéuticos, búsqueda en bodega, etiquetas 50×30 mm y traspaso Bodega → Vitrina.</p>
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

    @if(!auth()->check() || in_array(auth()->user()->rol, ['superadmin', 'admin', 'qf', 'bodeguero']))
    <div class="row g-4 mb-4">
        <!-- 1. Registrar Nuevo Medicamento (con Select de Laboratorio) -->
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
                                <select name="laboratorio" id="selectLaboratorioMed" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccione laboratorio --</option>
                                    @foreach($laboratorios as $lab)
                                        <option value="{{ $lab }}">{{ $lab }}</option>
                                    @endforeach
                                    <option value="__OTRO__">Otro laboratorio (especificar)...</option>
                                </select>
                                <input type="text"
                                       name="laboratorio_otro"
                                       id="inputLaboratorioOtro"
                                       class="form-control form-control-sm mt-2 d-none"
                                       placeholder="Escriba el nombre del laboratorio...">
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
                                <label class="form-label small fw-bold">Medicamento o Producto a Abastecer</label>
                                <select name="medicamento_id" id="selectLoteMedicamento" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccione producto --</option>
                                    @foreach($medicamentos as $med)
                                        <option value="{{ $med->id }}" data-codigo="{{ $med->codigo_barra }}">
                                            [{{ $med->codigo_barra }}] {{ $med->nombre_comercial }} {{ !$med->es_medicamento ? '(No Med.)' : '' }}
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

    <!-- 3. SECCIÓN APARTE: Productos que NO son medicamentos (Perfumería, Dermocosmética, Insumos, Higiene) -->
    <div class="card-pro mb-4 border-info-subtle">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 bg-info-subtle bg-opacity-25">
            <div>
                <i class="bi bi-bag-heart-fill text-info me-2"></i>
                <span>3. Sección de Productos No Farmacéuticos (Perfumería, Dermocosmética, Higiene e Insumos)</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="badgeScanNoMed" class="badge bg-secondary-subtle text-secondary small">Listo para escanear con pistola</span>
                <span class="badge bg-info text-dark rounded-pill">{{ $productosNoMed->count() }} productos registrados</span>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('inventario.productos.store') }}" method="POST" class="mb-4" id="formNuevoProductoNoMed">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold"><i class="bi bi-upc-scan text-info me-1"></i>Código de Barra</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-dark text-white"><i class="bi bi-upc"></i></span>
                            <input type="text" name="codigo_barra" id="inputNuevoCodigoNoMed" class="form-control font-mono fw-bold" placeholder="Escanea aquí..." autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Nombre del Producto</label>
                        <input type="text" name="nombre_comercial" id="inputNombreNoMed" class="form-control form-control-sm" placeholder="Ej. Crema Hidratante CeraVe 454g" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Categoría</label>
                        <select name="categoria_producto" class="form-select form-select-sm" required>
                            @foreach($categoriasNoMed as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Marca / Fabricante</label>
                        <input type="text" name="marca_fabricante" class="form-control form-control-sm" placeholder="Ej. Eucerin, Omron...">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-bold">Precio ($)</label>
                        <input type="number" name="precio_unitario" class="form-control form-control-sm fw-bold" value="9990" min="0" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-bold">Vitrina</label>
                        <input type="number" name="stock_inicial_vitrina" class="form-control form-control-sm" value="10" min="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small fw-bold">Bodega</label>
                        <input type="number" name="stock_inicial_bodega" class="form-control form-control-sm" value="20" min="0">
                    </div>
                </div>
                <div class="row g-3 mt-2 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-0">Stock Mínimo Alerta:</label>
                        <input type="number" name="stock_minimo" class="form-control form-control-sm d-inline-block ms-2" style="width: 80px;" value="5" min="1" required>
                    </div>
                    <div class="col-md-9 text-md-end">
                        <button type="submit" class="btn btn-info text-dark fw-bold px-4 py-2 rounded-3">
                            <i class="bi bi-bag-plus-fill me-1"></i>Guardar Producto No Farmacéutico
                        </button>
                    </div>
                </div>
            </form>

            @if($productosNoMed->count() > 0)
            <div class="table-responsive border rounded-3">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Código Barra</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Marca / Fabricante</th>
                            <th class="text-end">Precio</th>
                            <th class="text-center">Vitrina / Bodega</th>
                            <th class="text-end">Etiqueta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productosNoMed as $prod)
                            <tr>
                                <td class="font-mono small text-muted">{{ $prod->codigo_barra }}</td>
                                <td class="fw-bold">{{ $prod->nombre_comercial }}</td>
                                <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $prod->categoria_producto }}</span></td>
                                <td class="small">{{ $prod->laboratorio }}</td>
                                <td class="text-end fw-bold">${{ number_format($prod->precio_unitario, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">{{ $prod->stock_total_vitrina }} v.</span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $prod->stock_total_bodega }} b.</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventario.etiqueta', $prod) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill">
                                        <i class="bi bi-printer me-1"></i>Etiqueta 50×30
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Matriz de Lotes Activos con BARRA DE BÚSQUEDA BODEGA y DESCUENTOS FEFO / DIARIOS -->
    <div class="card-pro mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <i class="bi bi-layers-fill text-primary me-2"></i>
                <span>Matriz General de Bodega y Lotes (Ordenados por Fecha de Vencimiento FEFO)</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(auth()->check() && auth()->user()->isAdminOrSuper())
                    <form action="{{ route('inventario.lotes.descuento-fefo-auto') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark rounded-pill px-3"
                                title="Aplica 30% a lotes ≤30 días y 15% a lotes entre 31 y 60 días">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Liquidación FEFO Auto (≤30d: 30% / ≤60d: 15%)
                        </button>
                    </form>
                @endif
                <form action="{{ route('inventario.index') }}" method="GET" class="d-flex align-items-center gap-2" id="formBuscarBodega">
                    <div class="input-group input-group-sm" style="min-width: 280px;">
                        <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="q"
                               id="inputBuscarBodega"
                               value="{{ $q }}"
                               class="form-control border-primary"
                               placeholder="Buscar en bodega (nombre, código, lote, lab...)"
                               autocomplete="off">
                        @if($q !== '')
                            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary" title="Limpiar búsqueda"><i class="bi bi-x-lg"></i></a>
                        @endif
                        <button type="submit" class="btn btn-primary fw-bold">Buscar</button>
                    </div>
                </form>
                <span class="badge bg-dark rounded-pill" id="badgeContadorLotes">{{ $lotes->count() }} lotes</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="tablaLotesBodega">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Serie Lote</th>
                            <th>Medicamento / Producto / Laboratorio</th>
                            <th>Vencimiento (FEFO)</th>
                            <th class="text-center">Vitrina / Bodega</th>
                            <th>Descuento FEFO / Diario</th>
                            <th class="text-center">Etiqueta</th>
                            <th class="text-end">Traspaso Bodega → Vitrina</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lotes as $lote)
                            @php
                                $dias = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);
                                $precioBase = (float) ($lote->medicamento->precio_unitario ?? 0);
                                $dctoActual = (float) ($lote->descuento_porcentaje ?? 0);
                                $precioFinal = round($precioBase * (1 - $dctoActual / 100));
                                $dctoSugerido = $dias <= 30 ? 30 : ($dias <= 60 ? 15 : 10);
                                $motivoSugerido = $dias <= 60 ? 'Liquidación FEFO' : 'Descuento Diario';
                                $searchKey = strtolower(
                                    $lote->numero_lote . ' ' .
                                    ($lote->medicamento->codigo_barra ?? '') . ' ' .
                                    ($lote->medicamento->nombre_comercial ?? '') . ' ' .
                                    ($lote->medicamento->principio_activo ?? '') . ' ' .
                                    ($lote->medicamento->laboratorio ?? '') . ' ' .
                                    ($lote->medicamento->categoria_producto ?? '')
                                );
                            @endphp
                            <tr data-search="{{ $searchKey }}">
                                <td>
                                    <span class="badge bg-dark font-mono px-2 py-1">{{ $lote->numero_lote }}</span>
                                    <div class="font-mono text-muted mt-1" style="font-size: 0.72rem;"><i class="bi bi-upc me-1"></i>{{ $lote->medicamento->codigo_barra ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold d-flex align-items-center gap-2 flex-wrap">
                                        <span>{{ $lote->medicamento->nombre_comercial ?? 'N/A' }}</span>
                                        @if($lote->medicamento && !$lote->medicamento->es_medicamento)
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 0.65rem;">{{ $lote->medicamento->categoria_producto }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $lote->medicamento->principio_activo ?? '' }} • {{ $lote->medicamento->laboratorio ?? 'Lab. General' }}</div>
                                    <div class="small mt-1">
                                        @if($dctoActual > 0)
                                            <span class="text-muted text-decoration-line-through me-1">${{ number_format($precioBase, 0, ',', '.') }}</span>
                                            <span class="fw-extrabold text-success">${{ number_format($precioFinal, 0, ',', '.') }}</span>
                                            <span class="badge bg-danger text-white ms-1">-{{ rtrim(rtrim(number_format($dctoActual, 2, '.', ''), '0'), '.') }}% {{ $lote->motivo_descuento }}</span>
                                        @else
                                            <span class="fw-semibold text-dark">Precio: ${{ number_format($precioBase, 0, ',', '.') }}</span>
                                        @endif
                                        @if($lote->promo_2x1)
                                            <span class="badge bg-success text-white ms-1"><i class="bi bi-gift-fill me-1"></i>PROMO 2×1</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold {{ $dias <= 60 ? 'text-danger' : 'text-dark' }}">
                                        {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                                    </div>
                                    @if($dias <= 30)
                                        <span class="badge bg-danger text-white" style="font-size: 0.68rem;">FEFO Crítico ({{ $dias }}d)</span>
                                    @elseif($dias <= 60)
                                        <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">FEFO Prioritario ({{ $dias }}d)</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success" style="font-size: 0.68rem;">Vigente ({{ $dias }}d)</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" title="Stock Vitrina">
                                        V: {{ $lote->stock_vitrina }}
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1" title="Stock Bodega">
                                        B: {{ $lote->stock_bodega }}
                                    </span>
                                </td>
                                <td>
                                    @if(auth()->check() && auth()->user()->isAdminOrSuper())
                                        <form action="{{ route('inventario.lotes.descuento', $lote) }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center">
                                            @csrf
                                            <div class="input-group input-group-sm" style="width: 92px;">
                                                <input type="number" name="descuento_porcentaje" step="1" min="0" max="90"
                                                       value="{{ $dctoActual > 0 ? (int) $dctoActual : $dctoSugerido }}"
                                                       class="form-control text-center fw-bold" title="Porcentaje de descuento (0 para quitar)">
                                                <span class="input-group-text px-1">%</span>
                                            </div>
                                            <select name="motivo_descuento" class="form-select form-select-sm" style="width: 135px;">
                                                <option value="Liquidación FEFO" {{ ($lote->motivo_descuento ?? $motivoSugerido) === 'Liquidación FEFO' || str_contains((string) $lote->motivo_descuento, 'FEFO') ? 'selected' : '' }}>Liquidación FEFO</option>
                                                <option value="Descuento Diario" {{ ($lote->motivo_descuento ?? $motivoSugerido) === 'Descuento Diario' ? 'selected' : '' }}>Descuento Diario</option>
                                                <option value="Oferta Especial" {{ $lote->motivo_descuento === 'Oferta Especial' ? 'selected' : '' }}>Oferta Especial</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm {{ $dctoActual > 0 ? 'btn-outline-danger' : 'btn-warning text-dark' }} fw-bold px-2" title="Guardar descuento">
                                                <i class="bi bi-percent"></i>
                                            </button>
                                        </form>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                            <form action="{{ route('inventario.lotes.promo-2x1', $lote) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $lote->promo_2x1 ? 'btn-success fw-bold' : 'btn-outline-primary fw-semibold' }} py-0 px-2" style="font-size: 0.74rem;">
                                                    <i class="bi bi-gift-fill me-1"></i>{{ $lote->promo_2x1 ? '2×1 ACTIVO (Desactivar)' : 'Activar 2×1' }}
                                                </button>
                                            </form>
                                            @if($dctoActual > 0)
                                                <form action="{{ route('inventario.lotes.descuento', $lote) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <input type="hidden" name="descuento_porcentaje" value="0">
                                                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger text-decoration-none" style="font-size: 0.72rem;">
                                                        <i class="bi bi-x-circle me-1"></i>Quitar %
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        @if($dctoActual > 0)
                                            <span class="badge bg-danger">-{{ (int) $dctoActual }}% {{ $lote->motivo_descuento }}</span>
                                        @else
                                            <span class="text-muted small">Sin descuento</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($lote->medicamento)
                                        <a href="{{ route('inventario.etiqueta', ['medicamento' => $lote->medicamento->id, 'lote_id' => $lote->id]) }}"
                                           target="_blank"
                                           class="btn btn-outline-dark btn-sm rounded-pill text-nowrap"
                                           title="Imprimir etiqueta térmica 50x30 mm">
                                            <i class="bi bi-printer-fill me-1"></i>50×30
                                        </a>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($lote->stock_bodega > 0)
                                        <form action="{{ route('inventario.lotes.traspasar', $lote) }}" method="POST" class="d-inline-flex gap-1 align-items-center justify-content-end">
                                            @csrf
                                            <input type="number" name="cantidad" class="form-control form-control-sm text-center fw-bold" style="width: 65px;" min="1" max="{{ $lote->stock_bodega }}" value="{{ min(10, $lote->stock_bodega) }}">
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
                                <td colspan="7" class="text-center text-muted py-4">No se encontraron lotes en bodega para los criterios buscados.</td>
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
    // 1. En Alta de Medicamento: interceptar Enter de pistola lectora
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

    // 1b. En Alta de Producto No Farmacéutico: interceptar Enter de pistola lectora y saltar a Nombre del Producto
    const inputNuevoCodigoNoMed = document.getElementById('inputNuevoCodigoNoMed');
    const inputNombreNoMed = document.getElementById('inputNombreNoMed');
    const badgeScanNoMed = document.getElementById('badgeScanNoMed');

    if (inputNuevoCodigoNoMed) {
        inputNuevoCodigoNoMed.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim().length > 0) {
                    window.playScannerBeep(true);
                    if (badgeScanNoMed) {
                        badgeScanNoMed.className = 'badge bg-success text-white small';
                        badgeScanNoMed.innerHTML = `<i class="bi bi-check2-circle me-1"></i>Código capturado: ${this.value.trim()}`;
                    }
                    if (inputNombreNoMed) inputNombreNoMed.focus();
                }
            }
        });
    }

    // 2. Select de Laboratorio con opción "Otro"
    const selectLab = document.getElementById('selectLaboratorioMed');
    const inputLabOtro = document.getElementById('inputLaboratorioOtro');
    if (selectLab && inputLabOtro) {
        selectLab.addEventListener('change', function () {
            if (this.value === '__OTRO__') {
                inputLabOtro.classList.remove('d-none');
                inputLabOtro.required = true;
                inputLabOtro.focus();
            } else {
                inputLabOtro.classList.add('d-none');
                inputLabOtro.required = false;
                inputLabOtro.value = '';
            }
        });
    }

    // 3. En Recepción de Lotes: escanear la caja selecciona automáticamente el medicamento
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

    // 4. Filtro instantáneo en vivo en Barra de Búsqueda de Bodega
    const inputBuscar = document.getElementById('inputBuscarBodega');
    const tabla = document.getElementById('tablaLotesBodega');
    const badgeCount = document.getElementById('badgeContadorLotes');
    if (inputBuscar && tabla) {
        inputBuscar.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            const rows = tabla.querySelectorAll('tbody tr[data-search]');
            let visibles = 0;
            rows.forEach(row => {
                const match = !term || row.dataset.search.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) visibles++;
            });
            if (badgeCount) {
                badgeCount.textContent = visibles + ' lotes';
            }
        });
    }
})();
</script>
@endpush
