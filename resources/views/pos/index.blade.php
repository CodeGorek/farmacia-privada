@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo POS con Identidad Visual del Rol Vendedor -->
    <div class="mb-4 rounded-4 text-white d-flex flex-wrap justify-content-between align-items-center gap-3 p-3"
         style="background: linear-gradient(125deg, #0F172A 0%, #1E3A8A 60%, #1D4ED8 100%); box-shadow: 0 10px 26px -6px rgba(30, 58, 138, 0.35);">
        <div class="d-flex align-items-center gap-3">
            <svg width="50" height="50" class="flex-shrink-0"><use href="#icon-role-vendedor"></use></svg>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-info text-dark fw-bold rounded-pill px-2.5 py-1" style="font-size: 0.7rem;">
                        TERMINAL DE CAJA PRESENCIAL • VENDEDOR DE FARMACIA
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-white rounded-pill font-mono" style="font-size: 0.7rem;">
                        Cajero(a): {{ auth()->user()->name ?? 'Camila Andrea Muñoz' }}
                    </span>
                </div>
                <h3 class="fw-extrabold mb-0">Punto de Venta (POS) — Escáner FEFO & Switch Convenios</h3>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <span class="scanner-dot me-2"></span>Lector Código de Barras y Pinpad POS Conectados
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda (Principal): Lector de Código de Barras + Switch Aprobador RUT + Lista de Productos y Cobro -->
        <div class="col-lg-8">
            <!-- 1. Módulo Lector de Código de Barras Real -->
            <div class="scanner-box p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="scanner-dot"></span>
                        <span class="fw-bold text-uppercase small tracking-wider" style="color: #34D399 !important;">
                            Lector Láser / Código de Barras Activo (Agrega directo a la lista)
                        </span>
                    </div>
                    <span id="scannerFeedbackBadge" class="badge bg-white bg-opacity-10 text-white fw-normal">
                        Esperando lectura de pistola láser...
                    </span>
                </div>

                <div class="input-group input-group-lg mt-2">
                    <span class="input-group-text bg-success text-white border-0 px-3">
                        <i class="bi bi-upc-scan fs-4"></i>
                    </span>
                    <input type="text"
                           id="barcodeScannerInput"
                           class="form-control scanner-input"
                           placeholder="Escanea cada producto aquí (ej. 780123456001) para sumarlo a la lista..."
                           autocomplete="off"
                           autofocus>
                    <button type="button" id="btnLimpiarScan" class="btn btn-outline-light px-3" title="Limpiar lector">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Búsqueda / Agregado Manual Rápido -->
                <div class="row g-2 mt-2 align-items-center">
                    <div class="col-sm-9">
                        <select id="selectManualMed" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="">-- O selecciona manualmente un medicamento para agregar a la lista --</option>
                            @foreach($medicamentos as $med)
                                <option value="{{ $med->codigo_barra }}">
                                    [{{ $med->codigo_barra }}] {{ $med->nombre_comercial }} — ${{ number_format($med->precio_unitario, 0, ',', '.') }} (Stock: {{ $med->lotes->sum('stock_vitrina') }} u.)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3 d-grid">
                        <button type="button" id="btnAgregarManual" class="btn btn-sm btn-verde-medico fw-bold">
                            <i class="bi bi-cart-plus me-1"></i>Agregar a Lista
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 pt-2 border-top border-secondary border-opacity-25 gap-2">
                    <small class="text-white-50">
                        <i class="bi bi-upc me-1 text-warning"></i>Códigos rápidos de prueba:
                    </small>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        @foreach($medicamentos as $med)
                            <button type="button"
                                    class="btn btn-sm btn-dark border border-secondary font-mono py-0 px-2 btn-simular-scan"
                                    data-barcode="{{ $med->codigo_barra }}"
                                    title="Agregar {{ $med->nombre_comercial }}">
                                <i class="bi bi-plus-circle text-success me-1"></i>{{ $med->codigo_barra }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 2. SWITCH APROBADOR DE CONVENIOS EN LÍNEA POR RUT -->
            <div class="card-pro mb-4 border-primary border-opacity-25">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"
                     style="background: linear-gradient(90deg, #EFF6FF 0%, #F8FAFC 100%);">
                    <div class="d-flex align-items-center gap-2 text-primary">
                        <i class="bi bi-person-vcard-fill fs-5"></i>
                        <span class="fw-extrabold">Switch Aprobador de Convenios por RUT (Cobertura por Vademécum)</span>
                    </div>
                    <span id="badgeSwitchStatus" class="badge bg-secondary-subtle text-secondary rounded-pill">
                        Sin RUT validado (Tarifa Particular)
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-sm-7">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white border-0"><i class="bi bi-person-badge"></i></span>
                                <input type="text"
                                       id="inputRutSwitch"
                                       class="form-control font-mono fw-bold"
                                       placeholder="Ingresa RUT del cliente en caja (ej. 12.345.678-5)..."
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="col-sm-5 d-flex gap-1">
                            <button type="button" id="btnValidarRutSwitch" class="btn btn-primary fw-bold flex-grow-1">
                                <i class="bi bi-shield-check me-1"></i>Validar Beneficio RUT
                            </button>
                            <button type="button" id="btnQuitarRutSwitch" class="btn btn-outline-secondary" title="Quitar convenio / Volver a Particular">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="text-muted small me-1">Probar RUT:</span>
                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 small font-mono btn-test-rut" data-rut="12.345.678-5">
                            12.345.678-5 (Isapre SaludVida)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small font-mono btn-test-rut" data-rut="9.876.543-3">
                            9.876.543-3 (Adulto Mayor / FONASA)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 small font-mono btn-test-rut" data-rut="15.222.333-4">
                            15.222.333-4 (Rechazado por Mora)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small font-mono btn-test-rut" data-rut="18.999.111-2">
                            18.999.111-2 (Sin Convenio)
                        </button>
                    </div>

                    <div id="switchResultadoBox" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- 3. LISTA DE PRODUCTOS DE LA VENTA ACTUAL (CARRITO MULTI-PRODUCTO) -->
            <div class="card-pro">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart4 text-primary fs-5"></i>
                        <span>Lista de Productos en esta Venta (Ticket Actual)</span>
                        <span class="badge bg-primary rounded-pill" id="badgeTotalItems">0 ítems</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="badgeControladoHeader" class="badge bg-warning text-dark d-none">
                            <i class="bi bi-shield-exclamation me-1"></i>CONTIENE CONTROLADOS
                        </span>
                        <button type="button" id="btnVaciarCarrito" class="btn btn-sm btn-outline-danger py-1 px-2 d-none">
                            <i class="bi bi-trash3 me-1"></i>Vaciar Lista
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Medicamento / Lotes FEFO & Cobertura Convenio</th>
                                    <th class="text-end">P. Unitario</th>
                                    <th class="text-center" style="width: 140px;">Cantidad</th>
                                    <th class="text-end">Subtotal Línea</th>
                                    <th class="text-center" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCarritoPos">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-upc-scan fs-1 d-block mb-2 text-secondary"></i>
                                        <div class="fw-bold">La lista de venta está vacía</div>
                                        <div class="small">Dispara el lector de códigos de barra o selecciona un medicamento arriba para ir armando el pedido.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pie del Carrito: Medio de Pago en Caja, Vuelto Efectivo, Receta Controlada y Botón Único de Cobro -->
                <div class="card-body p-4 border-top bg-light bg-opacity-50">
                    <form id="posForm">
                        @csrf
                        <input type="hidden" name="convenio_id" id="convenio_id" value="">
                        <input type="hidden" name="rut_cliente" id="rut_cliente" value="">

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Convenio Aplicado por Switch RUT</label>
                                <input type="text" id="txtConvenioActivoInput" class="form-control bg-white fw-semibold" value="Particular (0% Dcto. — Valida RUT arriba)" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Medio de Pago en Caja</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-select fw-semibold">
                                    <option value="debito" selected>Tarjeta Débito (Pinpad Redcompra)</option>
                                    <option value="credito">Tarjeta de Crédito (Pinpad Transbank)</option>
                                    <option value="prepago">Tarjeta Prepago (Pinpad Transbank)</option>
                                    <option value="efectivo">Efectivo en Caja (Con cálculo de vuelto)</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="colEfectivoRecibido">
                                <label class="form-label fw-bold small">Efectivo Recibido ($)</label>
                                <input type="number" id="inputEfectivoCliente" class="form-control font-mono fw-bold" placeholder="Ej. 20000">
                                <div id="txtVueltoCliente" class="small fw-bold text-success mt-1"></div>
                            </div>
                        </div>

                        <!-- Bloque de Receta Controlada + Flujo de Aprobación por Químico Farmacéutico -->
                        <div id="bloqueControlado" class="border border-warning border-2 rounded-4 p-3 mb-3 bg-warning-subtle d-none">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 text-dark fw-bold">
                                    <i class="bi bi-journal-medical fs-5 text-danger"></i>
                                    <span>Visación Obligatoria Químico Farmacéutico — Libro de Controlados</span>
                                </div>
                                <span id="badgeEstadoVisacionPos" class="badge bg-danger text-white">
                                    Requiere Visación Q.F. antes de cobrar
                                </span>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">RUT Paciente</label>
                                    <input type="text" name="rut_paciente" id="inputRutPacienteCtrl" class="form-control form-control-sm" value="12.345.678-5">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold mb-1">Nombre Completo Paciente</label>
                                    <input type="text" name="nombre_paciente" id="inputNombrePacienteCtrl" class="form-control form-control-sm" value="María Alejandra González">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">RUT Médico Prescriptor</label>
                                    <input type="text" name="rut_medico" id="inputRutMedicoCtrl" class="form-control form-control-sm" value="9.412.880-2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Nombre Médico</label>
                                    <input type="text" name="nombre_medico" id="inputNombreMedicoCtrl" class="form-control form-control-sm" value="Dr. Roberto Valdés (Psiquiatría)">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Folio Receta Retenida/Cheque</label>
                                    <input type="text" name="numero_receta" id="inputNumeroRecetaCtrl" class="form-control form-control-sm font-mono" value="REC-CHQ-2026-0105">
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top border-warning">
                                <button type="button" id="btnEnviarSolicitudQf" class="btn btn-sm btn-dark fw-bold px-3">
                                    <i class="bi bi-send-check-fill text-warning me-1"></i>Enviar Solicitud de Aprobación al Químico Farmacéutico
                                </button>
                            </div>
                            <div id="boxRespuestaVisacionQf" class="mt-2 d-none"></div>
                        </div>

                        <!-- Totales Generales de la Lista -->
                        <div class="rounded-4 p-3 mb-3 bg-white border shadow-sm">
                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <div class="small text-muted">
                                        Unidades totales a dispensar: <strong id="txtUnidadesTotales" class="text-dark">0 u.</strong>
                                    </div>
                                    <div class="small text-muted">
                                        Descuento de stock automático por vencimiento <strong>FEFO</strong> y emisión de <strong>Boleta Electrónica SII (DTE 39)</strong>.
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end border-start-md ps-md-3">
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Subtotal Bruto Lista:</span>
                                        <span id="txtSubtotal" class="font-mono">$0</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-success fw-semibold">
                                        <span>Bonificación Productos en Convenio:</span>
                                        <span id="txtDescuento" class="font-mono">-$0</span>
                                    </div>
                                    <hr class="my-1">
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <span class="fw-bold small">TOTAL A COBRAR:</span>
                                        <span id="txtTotal" class="fs-3 fw-extrabold text-success font-mono">$0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitVenta" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-5">
                            <i class="bi bi-check2-circle me-2"></i>Cobrar Venta y Emitir Boleta Electrónica SII
                        </button>
                    </form>

                    <div id="resultadoVenta" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Cola de Lotes en Vitrina (Orden Estricto FEFO) -->
        <div class="col-lg-4">
            <div class="card-pro h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-sort-numeric-down text-success me-2"></i>
                        <span>Stock en Vitrina (Orden de Salida FEFO)</span>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill">Prioridad Activa</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Medicamento / Barcode</th>
                                    <th>Lote</th>
                                    <th>Vence</th>
                                    <th class="text-end">Vitrina</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($medicamentos as $med)
                                    @foreach($med->lotes as $idx => $lote)
                                        <tr class="{{ $idx === 0 ? 'table-success bg-opacity-10' : '' }}">
                                            <td>
                                                <div class="fw-bold small">{{ $med->nombre_comercial }}</div>
                                                <span class="badge bg-light text-dark border font-mono" style="font-size: 0.72rem;">
                                                    <i class="bi bi-upc me-1"></i>{{ $med->codigo_barra }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-dark font-mono">{{ $lote->numero_lote }}</span>
                                                @if($idx === 0)
                                                    <div class="text-success fw-bold" style="font-size: 0.68rem;">★ 1° EN SALIR</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold small">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $lote->stock_vitrina <= 5 ? 'bg-danger' : 'bg-primary' }} rounded-pill px-3 py-1">
                                                    {{ $lote->stock_vitrina }} u.
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Máquina Física Pinpad POS Transbank (Solo aparece al presionar Cobrar con Tarjeta) -->
    <div class="modal fade" id="modalPinpadPos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 410px;">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="p-3 text-white d-flex justify-content-between align-items-center"
                     style="background: linear-gradient(135deg, #0F172A 0%, #4A154B 60%, #D5006D 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-Cpu-fill fs-5"></i>
                        <div>
                            <div class="fw-extrabold small">PINPAD POS INTEGRADO TRANSBANK</div>
                            <div class="text-white-50" style="font-size: 0.7rem;">Terminal Físico de Mesón (Contactless / Chip)</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="p-3 rounded-4 mb-3 text-white font-mono" style="background: #0F172A; border: 2px solid #334155;">
                        <div class="text-info small mb-1" id="pinpadModalMetodo">VENTA TARJETA DÉBITO REDCOMPRA</div>
                        <div class="display-6 fw-extrabold text-success mb-2" id="pinpadModalMonto">$0</div>
                        <div id="pinpadModalScreenMsg" class="small text-white-50">
                            <i class="bi bi-wifi me-1"></i>ACERQUE, INSERTE O DESLICE TARJETA EN EL PINPAD...
                        </div>
                    </div>

                    <p class="text-muted small mb-3">
                        El monto ya fue enviado por cable al Pinpad físico del mesón. Simula la acción del cliente en la máquina:
                    </p>

                    <div class="d-grid gap-2">
                        <button type="button" id="btnClienteAcercaTarjetaOk" class="btn btn-verde-medico py-2.5 fw-bold rounded-3">
                            <i class="bi bi-contactless me-1"></i>Cliente Acerca Tarjeta (Aprobar en Pinpad)
                        </button>
                        <button type="button" id="btnClienteTarjetaRechazada" class="btn btn-outline-danger btn-sm py-2 fw-semibold rounded-3">
                            <i class="bi bi-x-circle me-1"></i>Simular Tarjeta Sin Fondos / Rechazada en Pinpad
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $catalogoPosJson = $medicamentos->map(function ($med) {
        return [
            'id' => $med->id,
            'codigo' => $med->codigo_barra,
            'nombre' => $med->nombre_comercial,
            'principio' => $med->principio_activo,
            'precio' => (float) $med->precio_unitario,
            'controlado' => (bool) $med->es_controlado,
            'receta' => (bool) $med->requiere_receta,
            'bioequivalente' => (bool) $med->es_bioequivalente,
            'cenabast' => (bool) $med->es_cenabast,
            'stock' => (int) $med->lotes->sum('stock_vitrina'),
            'lotes' => $med->lotes->map(fn($l) => [
                'lote' => $l->numero_lote,
                'vence' => $l->fecha_vencimiento->format('d/m/Y'),
                'stock' => (int) $l->stock_vitrina,
            ])->values(),
        ];
    })->values();
@endphp

@push('scripts')
<script>
(function () {
    const CATALOGO = @json($catalogoPosJson);
    let carrito = [];

    let convenioSwitch = {
        activo: false,
        rut: '',
        nombre_afiliado: '',
        convenio_id: null,
        convenio_nombre: 'Particular (0% Dcto.)',
        cobertura: {}
    };

    const scannerInput = document.getElementById('barcodeScannerInput');
    const feedbackBadge = document.getElementById('scannerFeedbackBadge');
    const selectManualMed = document.getElementById('selectManualMed');
    const tbodyCarritoPos = document.getElementById('tbodyCarritoPos');
    const badgeTotalItems = document.getElementById('badgeTotalItems');
    const btnVaciarCarrito = document.getElementById('btnVaciarCarrito');
    const bloqueControlado = document.getElementById('bloqueControlado');
    const badgeControladoHeader = document.getElementById('badgeControladoHeader');
    const selectMetodo = document.getElementById('metodo_pago');
    const inputEfectivoCliente = document.getElementById('inputEfectivoCliente');
    const txtVueltoCliente = document.getElementById('txtVueltoCliente');
    const txtUnidadesTotales = document.getElementById('txtUnidadesTotales');
    const txtSubtotal = document.getElementById('txtSubtotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotal = document.getElementById('txtTotal');

    const hiddenConvenioId = document.getElementById('convenio_id');
    const hiddenRutCliente = document.getElementById('rut_cliente');
    const txtConvenioActivoInput = document.getElementById('txtConvenioActivoInput');
    const inputRutSwitch = document.getElementById('inputRutSwitch');
    const badgeSwitchStatus = document.getElementById('badgeSwitchStatus');
    const switchResultadoBox = document.getElementById('switchResultadoBox');
    const inputRutPacienteCtrl = document.getElementById('inputRutPacienteCtrl');
    const inputNombrePacienteCtrl = document.getElementById('inputNombrePacienteCtrl');

    let totalActualNumerico = 0;
    const formatCLP = (val) => '$' + Math.round(val || 0).toLocaleString('es-CL');

    function actualizarVueltoEfectivo() {
        const pagado = parseFloat(inputEfectivoCliente.value || '0');
        if (pagado > 0 && totalActualNumerico > 0) {
            const vuelto = pagado - totalActualNumerico;
            if (vuelto >= 0) {
                txtVueltoCliente.className = 'small fw-bold text-success mt-1 font-mono';
                txtVueltoCliente.textContent = 'Vuelto: ' + formatCLP(vuelto);
            } else {
                txtVueltoCliente.className = 'small fw-bold text-danger mt-1 font-mono';
                txtVueltoCliente.textContent = 'Faltan: ' + formatCLP(Math.abs(vuelto));
            }
        } else {
            txtVueltoCliente.textContent = '';
        }
    }

    inputEfectivoCliente.addEventListener('input', actualizarVueltoEfectivo);

    function calcularBadgesLotesFefo(med, cantidad) {
        let restante = cantidad;
        const badges = [];
        for (const l of (med.lotes || [])) {
            if (restante <= 0) break;
            if (l.stock <= 0) continue;
            const tomar = Math.min(l.stock, restante);
            badges.push(`<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono me-1">${l.lote}: ${tomar} u. (vence ${l.vence})</span>`);
            restante -= tomar;
        }
        if (restante > 0) {
            badges.push(`<span class="badge bg-danger text-white">Faltan ${restante} u. en vitrina</span>`);
        }
        return badges.join(' ');
    }

    function renderizarCarrito() {
        if (carrito.length === 0) {
            tbodyCarritoPos.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-upc-scan fs-1 d-block mb-2 text-secondary"></i>
                        <div class="fw-bold">La lista de venta está vacía</div>
                        <div class="small">Dispara el lector de códigos de barra o selecciona un medicamento arriba para ir armando el pedido.</div>
                    </td>
                </tr>
            `;
            badgeTotalItems.textContent = '0 ítems';
            btnVaciarCarrito.classList.add('d-none');
            bloqueControlado.classList.add('d-none');
            badgeControladoHeader.classList.add('d-none');
            txtUnidadesTotales.textContent = '0 u.';
            txtSubtotal.textContent = '$0';
            txtDescuento.textContent = '-$0';
            txtTotal.textContent = '$0';
            totalActualNumerico = 0;
            actualizarVueltoEfectivo();
            return;
        }

        btnVaciarCarrito.classList.remove('d-none');
        let subtotalGlobal = 0;
        let descuentoGlobal = 0;
        let unidadesTotales = 0;
        let hayControlado = false;

        tbodyCarritoPos.innerHTML = carrito.map((item, idx) => {
            const med = item.med;
            const cant = item.cantidad;
            const subBruto = med.precio * cant;
            subtotalGlobal += subBruto;
            unidadesTotales += cant;
            if (med.controlado) hayControlado = true;

            const pctCobertura = convenioSwitch.activo && convenioSwitch.cobertura[med.id] !== undefined
                ? parseFloat(convenioSwitch.cobertura[med.id])
                : 0;
            const dctoLinea = pctCobertura > 0 ? Math.round(subBruto * (pctCobertura / 100)) : 0;
            const subFinalLinea = subBruto - dctoLinea;
            descuentoGlobal += dctoLinea;

            let badgeCoberturaHtml = '';
            if (convenioSwitch.activo) {
                if (pctCobertura > 0) {
                    badgeCoberturaHtml = `
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                            <i class="bi bi-check-circle-fill me-1"></i>Cubierto por ${convenioSwitch.convenio_nombre} (-${pctCobertura}%: -${formatCLP(dctoLinea)})
                        </span>
                    `;
                } else {
                    badgeCoberturaHtml = `
                        <span class="badge bg-secondary-subtle text-secondary border ms-1">
                            <i class="bi bi-dash-circle me-1"></i>No adherido a este convenio ($0 dcto)
                        </span>
                    `;
                }
            }

            const badgeCtrl = med.controlado
                ? `<span class="badge bg-warning text-dark ms-1" style="font-size: 0.68rem;">CONTROLADO</span>`
                : '';

            const badgeBio = med.bioequivalente
                ? `<span class="badge bg-warning text-dark border border-dark ms-1" style="font-size: 0.68rem;">BIOEQUIVALENTE ISP</span>`
                : '';

            let alertaBioequivalenteHtml = '';
            if (!med.bioequivalente) {
                const alternativaBio = CATALOGO.find(m =>
                    m.id !== med.id &&
                    m.principio.toLowerCase() === med.principio.toLowerCase() &&
                    m.bioequivalente &&
                    m.precio < med.precio &&
                    m.stock > 0
                );
                if (alternativaBio) {
                    const ahorroUnit = med.precio - alternativaBio.precio;
                    alertaBioequivalenteHtml = `
                        <div class="mt-2 p-2 rounded-3 border border-warning bg-warning-subtle d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                            <div>
                                <span class="badge bg-warning text-dark border border-dark fw-bold me-1">FRANJA AMARILLA ISP • LEY DE FÁRMACOS</span>
                                Alternativa Bioequivalente: <strong>${alternativaBio.nombre}</strong> a <strong>${formatCLP(alternativaBio.precio)}</strong> (Ahorra ${formatCLP(ahorroUnit)}/u.)
                            </div>
                            <button type="button" class="btn btn-xs btn-dark fw-bold py-1 px-2 small btn-cambiar-bio" data-idx="${idx}" data-bio-id="${alternativaBio.id}">
                                <i class="bi bi-arrow-left-right text-warning me-1"></i>Cambiar por Bioequivalente
                            </button>
                        </div>
                    `;
                }
            }

            const subtotalColHtml = dctoLinea > 0
                ? `<div class="text-decoration-line-through text-muted small font-mono">${formatCLP(subBruto)}</div>
                   <div class="font-mono fw-extrabold text-success">${formatCLP(subFinalLinea)}</div>`
                : `<div class="font-mono fw-extrabold text-primary">${formatCLP(subBruto)}</div>`;

            return `
                <tr>
                    <td>
                        <div class="fw-bold">${med.nombre} ${badgeBio} ${badgeCtrl}</div>
                        <div class="text-muted small font-mono mb-1"><i class="bi bi-upc me-1"></i>${med.codigo} • Principio: ${med.principio} • Stock vitrina: ${med.stock} u.</div>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            ${calcularBadgesLotesFefo(med, cant)}
                            ${badgeCoberturaHtml}
                        </div>
                        ${alertaBioequivalenteHtml}
                    </td>
                    <td class="text-end font-mono fw-semibold">${formatCLP(med.precio)}</td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center">
                            <button type="button" class="btn btn-outline-secondary btn-cant-menos" data-idx="${idx}"><i class="bi bi-dash"></i></button>
                            <input type="number" class="form-control text-center fw-bold input-cant-fila" data-idx="${idx}" value="${cant}" min="1" max="${med.stock}" style="max-width: 58px;">
                            <button type="button" class="btn btn-outline-secondary btn-cant-mas" data-idx="${idx}"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                    <td class="text-end">${subtotalColHtml}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 btn-quitar-fila" data-idx="${idx}" title="Quitar de la lista">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        badgeTotalItems.textContent = `${carrito.length} ${carrito.length === 1 ? 'producto' : 'productos'}`;
        txtUnidadesTotales.textContent = `${unidadesTotales} u.`;

        bloqueControlado.classList.toggle('d-none', !hayControlado);
        badgeControladoHeader.classList.toggle('d-none', !hayControlado);

        totalActualNumerico = Math.max(0, subtotalGlobal - descuentoGlobal);

        txtSubtotal.textContent = formatCLP(subtotalGlobal);
        txtDescuento.textContent = '-' + formatCLP(descuentoGlobal);
        txtTotal.textContent = formatCLP(totalActualNumerico);
        actualizarVueltoEfectivo();

        tbodyCarritoPos.querySelectorAll('.btn-cant-menos').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.idx, 10);
                if (carrito[i].cantidad > 1) {
                    carrito[i].cantidad--;
                } else {
                    carrito.splice(i, 1);
                }
                renderizarCarrito();
            });
        });

        tbodyCarritoPos.querySelectorAll('.btn-cant-mas').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.idx, 10);
                carrito[i].cantidad++;
                renderizarCarrito();
            });
        });

        tbodyCarritoPos.querySelectorAll('.input-cant-fila').forEach(inp => {
            inp.addEventListener('change', function () {
                const i = parseInt(this.dataset.idx, 10);
                const val = Math.max(1, parseInt(this.value || '1', 10));
                carrito[i].cantidad = val;
                renderizarCarrito();
            });
        });

        tbodyCarritoPos.querySelectorAll('.btn-quitar-fila').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.idx, 10);
                carrito.splice(i, 1);
                renderizarCarrito();
            });
        });

        tbodyCarritoPos.querySelectorAll('.btn-cambiar-bio').forEach(btn => {
            btn.addEventListener('click', function () {
                const i = parseInt(this.dataset.idx, 10);
                const bioId = parseInt(this.dataset.bioId, 10);
                const bioMed = CATALOGO.find(m => m.id === bioId);
                if (bioMed) {
                    window.playScannerBeep(true);
                    carrito[i].med = bioMed;
                    renderizarCarrito();
                }
            });
        });
    }

    async function consultarRutEnSwitch(rutRaw) {
        const rut = (rutRaw || '').trim();
        if (!rut) return;

        inputRutSwitch.value = rut;
        switchResultadoBox.className = 'mt-3 p-2 rounded-3 bg-info-subtle border border-info small d-flex align-items-center gap-2';
        switchResultadoBox.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div><span>Consultando Switch Aprobador de Convenios en línea...</span>';

        try {
            const res = await fetch('{{ route("pos.validar-rut") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ rut })
            });
            const data = await res.json();

            if (data.tiene_beneficio) {
                window.playScannerBeep(true);
                convenioSwitch = {
                    activo: true,
                    rut: data.rut,
                    nombre_afiliado: data.nombre_afiliado,
                    convenio_id: data.convenio_id,
                    convenio_nombre: data.convenio_nombre,
                    cobertura: data.cobertura_medicamentos || {}
                };

                hiddenConvenioId.value = data.convenio_id;
                hiddenRutCliente.value = data.rut;
                txtConvenioActivoInput.value = `${data.convenio_nombre} — Afiliado: ${data.nombre_afiliado} [${data.rut}]`;
                inputRutPacienteCtrl.value = data.rut;
                inputNombrePacienteCtrl.value = data.nombre_afiliado;

                badgeSwitchStatus.className = 'badge bg-success text-white rounded-pill';
                badgeSwitchStatus.innerHTML = `<i class="bi bi-patch-check-fill me-1"></i>APROBADO • ${data.codigo_switch}`;

                const numCubiertos = Object.keys(convenioSwitch.cobertura).length;
                switchResultadoBox.className = 'mt-3 p-3 rounded-3 bg-success-subtle border border-success small text-dark';
                switchResultadoBox.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                        <strong><i class="bi bi-check-circle-fill text-success me-1"></i>${data.convenio_nombre} — APROBADO EN LÍNEA</strong>
                        <span class="badge bg-dark font-mono">Aut. Switch: ${data.codigo_switch}</span>
                    </div>
                    <div>Paciente: <strong>${data.nombre_afiliado}</strong> (<code class="font-mono">${data.rut}</code>) • <strong>${numCubiertos} medicamentos</strong> asociados al vademécum de este convenio.</div>
                `;
            } else {
                window.playScannerBeep(false);
                convenioSwitch = {
                    activo: false,
                    rut: data.rut || rut,
                    nombre_afiliado: data.nombre_afiliado || '',
                    convenio_id: null,
                    convenio_nombre: 'Particular (0% Dcto.)',
                    cobertura: {}
                };
                hiddenConvenioId.value = '';
                hiddenRutCliente.value = data.rut || rut;
                txtConvenioActivoInput.value = `Sin Convenio Activo (${data.estado_switch === 'RECHAZADO_MORA' ? 'Rechazado por Mora' : 'Tarifa Particular 0%'})`;

                if (data.estado_switch === 'RECHAZADO_MORA') {
                    badgeSwitchStatus.className = 'badge bg-danger text-white rounded-pill';
                    badgeSwitchStatus.innerHTML = `<i class="bi bi-x-octagon-fill me-1"></i>RECHAZADO POR MORA`;
                    switchResultadoBox.className = 'mt-3 p-3 rounded-3 bg-danger-subtle border border-danger small text-danger-emphasis';
                } else {
                    badgeSwitchStatus.className = 'badge bg-secondary text-white rounded-pill';
                    badgeSwitchStatus.innerHTML = `<i class="bi bi-info-circle me-1"></i>SIN CONVENIO (PARTICULAR)`;
                    switchResultadoBox.className = 'mt-3 p-3 rounded-3 bg-secondary-subtle border border-secondary small text-dark';
                }
                switchResultadoBox.innerHTML = `<strong>${data.estado_switch}:</strong> ${data.mensaje}`;
            }

            renderizarCarrito();
        } catch (err) {
            switchResultadoBox.className = 'mt-3 p-2 rounded-3 bg-danger-subtle text-danger small';
            switchResultadoBox.textContent = 'Error al consultar el Switch Aprobador de Convenios.';
        }
    }

    document.getElementById('btnValidarRutSwitch').addEventListener('click', function () {
        consultarRutEnSwitch(inputRutSwitch.value);
    });

    inputRutSwitch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarRutEnSwitch(this.value);
        }
    });

    document.querySelectorAll('.btn-test-rut').forEach(btn => {
        btn.addEventListener('click', function () {
            consultarRutEnSwitch(this.dataset.rut);
        });
    });

    document.getElementById('btnQuitarRutSwitch').addEventListener('click', function () {
        convenioSwitch = {
            activo: false,
            rut: '',
            nombre_afiliado: '',
            convenio_id: null,
            convenio_nombre: 'Particular (0% Dcto.)',
            cobertura: {}
        };
        inputRutSwitch.value = '';
        hiddenConvenioId.value = '';
        hiddenRutCliente.value = '';
        txtConvenioActivoInput.value = 'Particular (0% Dcto. — Valida RUT arriba)';
        badgeSwitchStatus.className = 'badge bg-secondary-subtle text-secondary rounded-pill';
        badgeSwitchStatus.textContent = 'Sin RUT validado (Tarifa Particular)';
        switchResultadoBox.className = 'mt-3 d-none';
        renderizarCarrito();
    });

    function procesarCodigoDeBarras(codigoRaw) {
        const codigo = (codigoRaw || '').trim();
        if (!codigo) return;

        const med = CATALOGO.find(m => m.codigo === codigo);

        if (med) {
            window.playScannerBeep(true);
            const existente = carrito.find(item => item.med.id === med.id);
            if (existente) {
                existente.cantidad += 1;
            } else {
                carrito.push({ med: med, cantidad: 1 });
            }

            feedbackBadge.className = 'badge bg-success text-white fw-semibold';
            feedbackBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Agregado a la lista: ${med.nombre} [${codigo}]`;
            scannerInput.value = '';
            renderizarCarrito();
        } else {
            window.playScannerBeep(false);
            feedbackBadge.className = 'badge bg-danger text-white fw-semibold';
            feedbackBadge.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Código no registrado: ${codigo}`;
            scannerInput.select();
        }
    }

    scannerInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            procesarCodigoDeBarras(this.value);
        }
    });

    let hidBuffer = '';
    let lastKeyTime = 0;
    document.addEventListener('keydown', function (e) {
        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
            return;
        }

        const now = Date.now();
        if (now - lastKeyTime > 80) {
            hidBuffer = '';
        }
        lastKeyTime = now;

        if (e.key === 'Enter') {
            if (hidBuffer.length >= 4) {
                e.preventDefault();
                procesarCodigoDeBarras(hidBuffer);
                hidBuffer = '';
            }
        } else if (e.key.length === 1) {
            hidBuffer += e.key;
        }
    });

    document.getElementById('btnAgregarManual').addEventListener('click', function () {
        if (selectManualMed.value) {
            procesarCodigoDeBarras(selectManualMed.value);
            selectManualMed.value = '';
        }
    });

    document.getElementById('btnVaciarCarrito').addEventListener('click', function () {
        carrito = [];
        renderizarCarrito();
    });

    document.getElementById('btnLimpiarScan').addEventListener('click', function () {
        scannerInput.value = '';
        scannerInput.focus();
    });

    document.querySelectorAll('.btn-simular-scan').forEach(btn => {
        btn.addEventListener('click', function () {
            procesarCodigoDeBarras(this.dataset.barcode);
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scan')) {
        procesarCodigoDeBarras(urlParams.get('scan'));
    }

    // Solicitud de Aprobación de Receta al Químico Farmacéutico en Tiempo Real
    let visacionPollingTimer = null;
    let solicitudVisacionIdAprobada = null;
    const btnEnviarSolicitudQf = document.getElementById('btnEnviarSolicitudQf');
    const boxRespuestaVisacionQf = document.getElementById('boxRespuestaVisacionQf');
    const badgeEstadoVisacionPos = document.getElementById('badgeEstadoVisacionPos');

    function construirPayloadCarrito() {
        const formData = new FormData(document.getElementById('posForm'));
        const base = Object.fromEntries(formData.entries());
        base.items = carrito.map(i => ({
            medicamento_id: i.med.id,
            cantidad: i.cantidad
        }));
        if (solicitudVisacionIdAprobada) {
            base.solicitud_visacion_id = solicitudVisacionIdAprobada;
        }
        return base;
    }

    if (btnEnviarSolicitudQf) {
        btnEnviarSolicitudQf.addEventListener('click', async function () {
            if (carrito.length === 0) return;
            const payload = construirPayloadCarrito();
            payload.monto_total = totalActualNumerico;
            payload.tipo_receta = 'RECETA_CHEQUE_PSICOTROPICO';

            boxRespuestaVisacionQf.className = 'mt-2 p-2 rounded-3 bg-white border small d-flex align-items-center gap-2';
            boxRespuestaVisacionQf.innerHTML = '<div class="spinner-border spinner-border-sm text-warning"></div><span>Enviando solicitud a la bandeja del Químico Farmacéutico...</span>';

            try {
                const res = await fetch('{{ route("pos.solicitar-visacion") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'ok') {
                    window.playScannerBeep(true);
                    badgeEstadoVisacionPos.className = 'badge bg-warning text-dark';
                    badgeEstadoVisacionPos.textContent = `PENDIENTE EN BANDEJA Q.F. (${data.folio})`;

                    boxRespuestaVisacionQf.className = 'mt-2 p-2 rounded-3 bg-white border border-warning small';
                    boxRespuestaVisacionQf.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-hourglass-split text-warning me-1"></i>Solicitud <strong>${data.folio}</strong> enviada. Esperando firma del Químico Farmacéutico...</span>
                        </div>
                    `;

                    if (visacionPollingTimer) clearInterval(visacionPollingTimer);
                    visacionPollingTimer = setInterval(async () => {
                        try {
                            const rEst = await fetch(`/pos/solicitud-visacion/${data.solicitud_id}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const dEst = await rEst.json();
                            if (dEst.estado === 'APROBADA') {
                                clearInterval(visacionPollingTimer);
                                solicitudVisacionIdAprobada = data.solicitud_id;
                                window.playScannerBeep(true);
                                badgeEstadoVisacionPos.className = 'badge bg-success text-white';
                                badgeEstadoVisacionPos.innerHTML = `<i class="bi bi-patch-check-fill me-1"></i>APROBADA POR Q.F. (${dEst.codigo_firma_qf})`;
                                boxRespuestaVisacionQf.className = 'mt-2 p-2 rounded-3 bg-success-subtle border border-success small text-dark';
                                boxRespuestaVisacionQf.innerHTML = `
                                    <strong><i class="bi bi-check-circle-fill text-success me-1"></i>¡Visación Aprobada en Línea por ${dEst.qf_nombre}!</strong>
                                    <div>Firma Digital ISP: <code class="font-mono fw-bold">${dEst.codigo_firma_qf}</code> • Obs: ${dEst.observaciones_qf || 'Conforme'}</div>
                                `;
                            } else if (dEst.estado === 'RECHAZADA') {
                                clearInterval(visacionPollingTimer);
                                window.playScannerBeep(false);
                                badgeEstadoVisacionPos.className = 'badge bg-danger text-white';
                                badgeEstadoVisacionPos.textContent = 'RECHAZADA POR Q.F.';
                                boxRespuestaVisacionQf.className = 'mt-2 p-2 rounded-3 bg-danger-subtle border border-danger small text-danger';
                                boxRespuestaVisacionQf.innerHTML = `<strong>Receta Rechazada por Q.F.:</strong> ${dEst.observaciones_qf}`;
                            }
                        } catch (e) {}
                    }, 2500);
                }
            } catch (e) {
                boxRespuestaVisacionQf.className = 'mt-2 p-2 rounded-3 bg-danger-subtle text-danger small';
                boxRespuestaVisacionQf.textContent = 'Error al enviar solicitud al Químico Farmacéutico.';
            }
        });
    }

    // Procesamiento final de la venta (Directo en Efectivo o tras pasar tarjeta en el Pinpad Físico Modal)
    const modalPinpadEl = document.getElementById('modalPinpadPos');
    const modalPinpad = new bootstrap.Modal(modalPinpadEl);
    const pinpadModalMonto = document.getElementById('pinpadModalMonto');
    const pinpadModalMetodo = document.getElementById('pinpadModalMetodo');
    const pinpadModalScreenMsg = document.getElementById('pinpadModalScreenMsg');

    async function enviarVentaAlServidor() {
        const resultadoDiv = document.getElementById('resultadoVenta');
        const payload = construirPayloadCarrito();

        try {
            const response = await fetch('{{ route("pos.procesar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                window.playScannerBeep(true);
                modalPinpad.hide();

                const lotesHtml = data.lotes_fefo.map(l =>
                    `<li><strong>${l.medicamento || ''}</strong> — Lote <strong class="font-mono">${l.lote}</strong>: <strong>${l.cantidad} u.</strong> descontadas</li>`
                ).join('');

                resultadoDiv.className = 'mt-3 alert alert-success border-success shadow-sm';
                resultadoDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <strong><i class="bi bi-check-circle-fill me-1"></i>¡Venta #${data.venta_id ?? ''} Emitida! Folio SII: <code class="font-mono">${data.folio_dte_sii || ''}</code></strong>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success fs-6">${formatCLP(data.total || 0)}</span>
                            <a href="${data.boleta_url || '/operaciones'}" class="btn btn-sm btn-dark fw-bold">
                                <i class="bi bi-printer-fill me-1"></i>Imprimir Boleta SII 80mm
                            </a>
                        </div>
                    </div>
                    <div class="mt-2 small">Lotes descontados por algoritmo FEFO:</div>
                    <ul class="mb-0 small">${lotesHtml}</ul>
                `;
                carrito = [];
                solicitudVisacionIdAprobada = null;
                renderizarCarrito();
            } else {
                window.playScannerBeep(false);
                modalPinpad.hide();
                resultadoDiv.className = 'mt-3 alert alert-danger';
                resultadoDiv.innerHTML = `<i class="bi bi-x-octagon-fill me-1"></i>${data.message || 'Error al procesar la venta.'}`;
            }
        } catch (err) {
            window.playScannerBeep(false);
            modalPinpad.hide();
            resultadoDiv.className = 'mt-3 alert alert-danger';
            resultadoDiv.textContent = 'Error de conexión con el servidor POS.';
        }
    }

    document.getElementById('posForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const resultadoDiv = document.getElementById('resultadoVenta');
        if (carrito.length === 0) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-warning';
            resultadoDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Primero escanea o agrega al menos un producto a la lista de venta.';
            return;
        }

        if (selectMetodo.value === 'efectivo') {
            enviarVentaAlServidor();
        } else {
            // Abrir pantalla de Pinpad Físico en el mesón esperando que el cliente acerque su tarjeta
            pinpadModalMonto.textContent = formatCLP(totalActualNumerico);
            pinpadModalMetodo.textContent = 'PINPAD TRANSBANK • ' + selectMetodo.options[selectMetodo.selectedIndex].text.toUpperCase();
            pinpadModalScreenMsg.className = 'small text-white-50';
            pinpadModalScreenMsg.innerHTML = '<i class="bi bi-wifi me-1"></i>ACERQUE, INSERTE O DESLICE TARJETA EN EL PINPAD...';
            modalPinpad.show();
        }
    });

    document.getElementById('btnClienteAcercaTarjetaOk').addEventListener('click', async function () {
        pinpadModalScreenMsg.className = 'small text-warning fw-bold';
        pinpadModalScreenMsg.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>PROCESANDO CON TRANSBANK...';
        await new Promise(r => setTimeout(r, 500));
        await enviarVentaAlServidor();
    });

    document.getElementById('btnClienteTarjetaRechazada').addEventListener('click', function () {
        window.playScannerBeep(false);
        pinpadModalScreenMsg.className = 'small text-danger fw-bold';
        pinpadModalScreenMsg.innerHTML = '❌ TARJETA RECHAZADA POR EL BANCO EMISOR (SIN FONDOS)';
    });
})();
</script>
@endpush
