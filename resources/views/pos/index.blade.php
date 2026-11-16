@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo POS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-upc-scan text-primary me-2"></i>Terminal Punto de Venta (POS) — Carrito, Switch RUT & Webpay</h2>
            <p class="text-muted mb-0">Escanea productos, valida el <strong>RUT del paciente en el Switch de Convenios</strong> (descuento selectivo por vademécum) y cobra en Transbank.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-person-vcard-fill me-1"></i>Switch Aprobador RUT (I-Med) Activo
            </span>
            <span class="badge px-3 py-2 rounded-pill text-white fw-semibold" style="background: linear-gradient(135deg, #D5006D 0%, #6B1D7A 100%);">
                <i class="bi bi-credit-card-2-front me-1"></i>Pasarela Tarjeta Webpay Activa
            </span>
        </div>
    </div>

    <!-- Alertas de Retorno desde Pasarela Oficial Webpay Sandbox -->
    @if(session('webpay_error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-x-octagon-fill fs-4"></i>
            <div>
                <strong>Transacción Webpay Rechazada / Anulada:</strong> {{ session('webpay_error') }}
            </div>
        </div>
    @endif

    @if(session('webpay_success'))
        @php $ws = session('webpay_success'); @endphp
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-patch-check-fill me-2"></i>¡Pago con Tarjeta Aprobado en Transbank Webpay Plus!</h5>
                    <div class="small">
                        Venta <strong>#{{ $ws['venta_id'] }}</strong> • Orden: <code class="font-mono">{{ $ws['buy_order'] }}</code> •
                        Tarjeta terminada en: <strong>**** {{ $ws['card_number'] }}</strong> •
                        Código Autorización: <span class="badge bg-dark font-mono">{{ $ws['authorization_code'] }}</span>
                    </div>
                </div>
                <span class="fs-4 fw-extrabold font-mono text-success">${{ number_format($ws['total'], 0, ',', '.') }}</span>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Columna Izquierda: Lector de Código de Barras + Switch Aprobador RUT + Lista de Productos -->
        <div class="col-lg-7">
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
                        <i class="bi bi-cart-check-fill text-warning me-1"></i>
                        Haz clic o escanea varios códigos para agregar distintos productos al carrito:
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

            <!-- 2. SWITCH APROBADOR DE CONVENIOS EN LÍNEA POR RUT (I-MED / ISAPRE / FONASA) -->
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
                                       placeholder="Ingresa RUT del cliente (ej. 12.345.678-5)..."
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

                    <!-- Botones rápidos de RUTs de prueba para demostrar distintos escenarios -->
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="text-muted small me-1"><i class="bi bi-lightning-charge-fill text-warning"></i> Probar RUT:</span>
                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 small font-mono btn-test-rut" data-rut="12.345.678-5">
                            12.345.678-5 (Isapre: cubre Losartán/Amox/Clonaz, NO Paracetamol)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small font-mono btn-test-rut" data-rut="9.876.543-3">
                            9.876.543-3 (Adulto Mayor: cubre Paracetamol -15% y Losartán -30%)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 small font-mono btn-test-rut" data-rut="15.222.333-4">
                            15.222.333-4 (Rechazado por Mora)
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small font-mono btn-test-rut" data-rut="18.999.111-2">
                            18.999.111-2 (Sin Convenio)
                        </button>
                    </div>

                    <!-- Respuesta en vivo del Switch Aprobador -->
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
                                        <div class="small">Dispara el lector de códigos de barra o haz clic en los códigos de arriba para ir sumando productos.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pie del Carrito: Medio de Pago, Receta Controlada y Totales -->
                <div class="card-body p-4 border-top bg-light bg-opacity-50">
                    <form id="posForm">
                        @csrf
                        <input type="hidden" name="convenio_id" id="convenio_id" value="">
                        <input type="hidden" name="rut_cliente" id="rut_cliente" value="">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Convenio Aplicado por Switch RUT</label>
                                <input type="text" id="txtConvenioActivoInput" class="form-control bg-white fw-semibold" value="Particular (0% Dcto. — Valida RUT arriba)" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Medio de Pago</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-select fw-semibold">
                                    <option value="debito" selected>💳 Tarjeta Débito (Redcompra)</option>
                                    <option value="credito">💳 Tarjeta de Crédito (Webpay)</option>
                                    <option value="prepago">💳 Tarjeta Prepago</option>
                                    <option value="efectivo">💵 Efectivo en Caja</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bloque de Receta Controlada -->
                        <div id="bloqueControlado" class="border border-warning border-2 rounded-4 p-3 mb-3 bg-warning-subtle d-none">
                            <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold">
                                <i class="bi bi-journal-medical fs-5 text-danger"></i>
                                <span>Registro Legal Obligatorio — Libro de Medicamentos Controlados</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">RUT Paciente</label>
                                    <input type="text" name="rut_paciente" id="inputRutPacienteCtrl" class="form-control form-control-sm" placeholder="12.345.678-9">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold mb-1">Nombre Completo Paciente</label>
                                    <input type="text" name="nombre_paciente" id="inputNombrePacienteCtrl" class="form-control form-control-sm" placeholder="Nombre y Apellidos del comprador">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">RUT Médico Prescriptor</label>
                                    <input type="text" name="rut_medico" class="form-control form-control-sm" placeholder="9.876.543-2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Nombre Médico</label>
                                    <input type="text" name="nombre_medico" class="form-control form-control-sm" placeholder="Dr. Nombre Apellido">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Folio Receta Retenida/Cheque</label>
                                    <input type="text" name="numero_receta" class="form-control form-control-sm font-mono" placeholder="REC-2026-0091">
                                </div>
                            </div>
                        </div>

                        <!-- Totales Generales de la Lista -->
                        <div class="rounded-4 p-3 mb-3 bg-white border shadow-sm">
                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <div class="small text-muted">
                                        Unidades totales a dispensar: <strong id="txtUnidadesTotales" class="text-dark">0 u.</strong>
                                    </div>
                                    <div class="small text-muted">
                                        El descuento del convenio se aplica <strong>solo a los medicamentos adheridos</strong> a su vademécum.
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
                            <i class="bi bi-credit-card-2-front me-2"></i>Cobrar Lista Completa en Simulador Transbank
                        </button>
                    </form>

                    <div id="resultadoVenta" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: SIMULADOR DE PAGO CON TARJETA TRANSBANK (WEBPAY / REDCOMPRA) + Cola FEFO -->
        <div class="col-lg-5">
            <!-- SIMULADOR DE PAGO CON TARJETA WEBPAY / REDCOMPRA -->
            <div class="card-pro mb-4 overflow-hidden border-2" id="cardSimuladorTarjeta" style="border-color: #D5006D;">
                <div class="card-header d-flex justify-content-between align-items-center text-white py-3"
                     style="background: linear-gradient(135deg, #2D0B3A 0%, #6B1D7A 55%, #D5006D 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card-2-back-fill fs-4"></i>
                        <div>
                            <div class="fw-extrabold leading-none">Webpay Plus • Transbank</div>
                            <div class="text-white-50" style="font-size: 0.72rem;">Simulador de Pago con Tarjeta (Crédito / Redcompra)</div>
                        </div>
                    </div>
                    <span class="badge bg-white text-dark font-mono fw-bold fs-6" id="tbkCardAmountBadge">$0</span>
                </div>

                <div class="card-body p-3">
                    <!-- Tarjeta Bancaria Visual 3D -->
                    <div class="p-3 rounded-4 text-white mb-3 shadow-sm position-relative overflow-hidden"
                         id="visualCardBox"
                         style="background: linear-gradient(135deg, #1E293B 0%, #0F172A 65%, #1E40AF 100%); min-height: 155px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-2 bg-warning" style="width: 38px; height: 28px; opacity: 0.9; border: 1px solid rgba(255,255,255,0.4);"></div>
                                <i class="bi bi-wifi fs-5 text-white-50" style="transform: rotate(90deg);"></i>
                            </div>
                            <span id="visualCardBrand" class="badge bg-white text-dark fw-extrabold px-2 py-1">REDCOMPRA / VISA</span>
                        </div>
                        <div class="fs-5 fw-bold font-mono tracking-wider mb-3" id="visualCardNumber" style="letter-spacing: 2px;">
                            5452 8808 0440 0003
                        </div>
                        <div class="d-flex justify-content-between align-items-end small">
                            <div>
                                <div class="text-white-50" style="font-size: 0.65rem;">TITULAR TARJETA</div>
                                <div class="fw-bold text-uppercase font-mono" id="visualCardHolder">CLIENTE PRUEBA TRANSBANK</div>
                            </div>
                            <div class="text-end">
                                <div class="text-white-50" style="font-size: 0.65rem;">VENCE</div>
                                <div class="fw-bold font-mono" id="visualCardExpiry">10/29</div>
                            </div>
                        </div>
                    </div>

                    <!-- Selector Rápido de Tarjetas Oficiales de Prueba Transbank -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="bi bi-lightning-fill text-warning me-1"></i>Cargar Tarjeta de Prueba Oficial Transbank:
                        </label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1 fw-semibold btn-preset-card"
                                    data-tipo="debito"
                                    data-numero="5452 8808 0440 0003"
                                    data-exp="10/29"
                                    data-cvv="123"
                                    data-brand="REDCOMPRA DÉBITO"
                                    data-status="APPROVED">
                                💳 Débito Aprobada
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success flex-grow-1 fw-semibold btn-preset-card"
                                    data-tipo="credito"
                                    data-numero="4051 8856 0044 6623"
                                    data-exp="12/28"
                                    data-cvv="123"
                                    data-brand="VISA CRÉDITO"
                                    data-status="APPROVED">
                                💳 Crédito Aprobada
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold btn-preset-card"
                                    data-tipo="credito"
                                    data-numero="4051 8856 0044 0000"
                                    data-exp="05/27"
                                    data-cvv="999"
                                    data-brand="TARJETA SIN FONDOS"
                                    data-status="REJECTED">
                                ❌ Simular Rechazo
                            </button>
                        </div>
                    </div>

                    <!-- Campos del Formulario de Tarjeta -->
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold mb-1">Número de Tarjeta</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" id="tbkInputNumber" class="form-control font-mono fw-bold" value="5452 8808 0440 0003" maxlength="19">
                            </div>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold mb-1">Vencimiento</label>
                            <input type="text" id="tbkInputExp" class="form-control form-control-sm font-mono text-center" value="10/29" maxlength="5">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold mb-1">CVV / PIN</label>
                            <input type="password" id="tbkInputCvv" class="form-control form-control-sm font-mono text-center" value="1234" maxlength="4">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold mb-1">Cuotas / Banco</label>
                            <select id="tbkInputCuotas" class="form-select form-select-sm">
                                <option value="0">Sin Cuotas (Venta Normal)</option>
                                <option value="3">3 Cuotas Sin Interés</option>
                                <option value="6">6 Cuotas Normales</option>
                                <option value="12">12 Cuotas Normales</option>
                            </select>
                        </div>
                    </div>

                    <div id="tbkPinpadStatus" class="rounded-3 p-2 bg-light border text-center small text-muted mb-3">
                        <i class="bi bi-shield-lock-fill text-success me-1"></i>Conexión cifrada TLS 1.3 lista con Transbank.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" id="btnPagarSimuladorLocal" class="btn text-white fw-bold py-2 rounded-3"
                                style="background: linear-gradient(135deg, #D5006D 0%, #6B1D7A 100%);">
                            <i class="bi bi-check2-circle me-1"></i>Autorizar Pago con esta Tarjeta Ahora
                        </button>
                        <button type="button" id="btnRedirigirWebpaySandbox" class="btn btn-outline-dark btn-sm py-2 rounded-3 fw-semibold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir Pasarela Oficial Webpay Sandbox (webpay3gint.transbank.cl)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cola de Lotes en Vitrina -->
            <div class="card-pro">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-sort-numeric-down text-success me-2"></i>
                        <span>Cola de Lotes en Vitrina (Orden Estricto FEFO)</span>
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
                                    <th>Vencimiento</th>
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
                                                    <div class="text-success fw-bold" style="font-size: 0.68rem;">★ 1° EN SALIR (FEFO)</div>
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

    <form id="webpayRedirectForm" method="POST" action="" class="d-none">
        <input type="hidden" name="token_ws" id="webpayTokenInput" value="">
    </form>
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

    // Estado del Carrito Multi-Producto
    let carrito = [];

    // Estado del Switch Aprobador de Convenios por RUT:
    // coberturaActiva es un mapa { medicamento_id: porcentaje_descuento }
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
    const txtUnidadesTotales = document.getElementById('txtUnidadesTotales');
    const txtSubtotal = document.getElementById('txtSubtotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotal = document.getElementById('txtTotal');
    const tbkCardAmountBadge = document.getElementById('tbkCardAmountBadge');

    // Inputs ocultos / visibles de Convenio por RUT
    const hiddenConvenioId = document.getElementById('convenio_id');
    const hiddenRutCliente = document.getElementById('rut_cliente');
    const txtConvenioActivoInput = document.getElementById('txtConvenioActivoInput');
    const inputRutSwitch = document.getElementById('inputRutSwitch');
    const badgeSwitchStatus = document.getElementById('badgeSwitchStatus');
    const switchResultadoBox = document.getElementById('switchResultadoBox');
    const inputRutPacienteCtrl = document.getElementById('inputRutPacienteCtrl');
    const inputNombrePacienteCtrl = document.getElementById('inputNombrePacienteCtrl');

    // Elementos del Simulador de Pago con Tarjeta
    const visualCardBox = document.getElementById('visualCardBox');
    const visualCardBrand = document.getElementById('visualCardBrand');
    const visualCardNumber = document.getElementById('visualCardNumber');
    const visualCardHolder = document.getElementById('visualCardHolder');
    const visualCardExpiry = document.getElementById('visualCardExpiry');
    const tbkInputNumber = document.getElementById('tbkInputNumber');
    const tbkInputExp = document.getElementById('tbkInputExp');
    const tbkInputCvv = document.getElementById('tbkInputCvv');
    const tbkInputCuotas = document.getElementById('tbkInputCuotas');
    const tbkPinpadStatus = document.getElementById('tbkPinpadStatus');

    const formatCLP = (val) => '$' + Math.round(val || 0).toLocaleString('es-CL');

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
                        <div class="small">Dispara el lector de códigos de barra o haz clic en los códigos de arriba para ir sumando productos.</div>
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
            tbkCardAmountBadge.textContent = '$0';
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

            // Verificar si este medicamento específico está cubierto por el convenio del RUT activo
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
                ? `<span class="badge bg-warning text-dark ms-1" style="font-size: 0.68rem;">⚠️ CONTROLADO</span>`
                : '';

            const subtotalColHtml = dctoLinea > 0
                ? `<div class="text-decoration-line-through text-muted small font-mono">${formatCLP(subBruto)}</div>
                   <div class="font-mono fw-extrabold text-success">${formatCLP(subFinalLinea)}</div>`
                : `<div class="font-mono fw-extrabold text-primary">${formatCLP(subBruto)}</div>`;

            return `
                <tr>
                    <td>
                        <div class="fw-bold">${med.nombre} ${badgeCtrl}</div>
                        <div class="text-muted small font-mono mb-1"><i class="bi bi-upc me-1"></i>${med.codigo} • Stock vitrina: ${med.stock} u.</div>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            ${calcularBadgesLotesFefo(med, cant)}
                            ${badgeCoberturaHtml}
                        </div>
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

        const total = Math.max(0, subtotalGlobal - descuentoGlobal);

        txtSubtotal.textContent = formatCLP(subtotalGlobal);
        txtDescuento.textContent = '-' + formatCLP(descuentoGlobal);
        txtTotal.textContent = formatCLP(total);
        tbkCardAmountBadge.textContent = formatCLP(total);

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
    }

    // Consultar el Switch Aprobador de Convenios por RUT en el servidor
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
                visualCardHolder.textContent = data.nombre_afiliado.toUpperCase();
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

    selectMetodo.addEventListener('change', renderizarCarrito);

    tbkInputNumber.addEventListener('input', function () {
        visualCardNumber.textContent = this.value || '•••• •••• •••• ••••';
    });
    tbkInputExp.addEventListener('input', function () {
        visualCardExpiry.textContent = this.value || 'MM/AA';
    });

    document.querySelectorAll('.btn-preset-card').forEach(btn => {
        btn.addEventListener('click', function () {
            tbkInputNumber.value = this.dataset.numero;
            tbkInputExp.value = this.dataset.exp;
            tbkInputCvv.value = this.dataset.cvv;
            visualCardNumber.textContent = this.dataset.numero;
            visualCardExpiry.textContent = this.dataset.exp;
            visualCardBrand.textContent = this.dataset.brand;
            selectMetodo.value = this.dataset.tipo;

            if (this.dataset.status === 'REJECTED') {
                visualCardBox.style.background = 'linear-gradient(135deg, #450A0A 0%, #7F1D1D 60%, #991B1B 100%)';
                tbkPinpadStatus.className = 'rounded-3 p-2 bg-danger-subtle border border-danger text-center small text-danger fw-semibold mb-3';
                tbkPinpadStatus.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Tarjeta de prueba configurada para simular RECHAZO BANCARIO (sin fondos).';
            } else {
                visualCardBox.style.background = 'linear-gradient(135deg, #1E293B 0%, #0F172A 65%, #1E40AF 100%)';
                tbkPinpadStatus.className = 'rounded-3 p-2 bg-success-subtle border border-success text-center small text-success fw-semibold mb-3';
                tbkPinpadStatus.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Tarjeta de prueba Transbank (${this.dataset.brand}) lista para aprobar.`;
            }
            renderizarCarrito();
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scan')) {
        procesarCodigoDeBarras(urlParams.get('scan'));
    }

    function construirPayloadCarrito() {
        const formData = new FormData(document.getElementById('posForm'));
        const base = Object.fromEntries(formData.entries());
        base.items = carrito.map(i => ({
            medicamento_id: i.med.id,
            cantidad: i.cantidad
        }));
        return base;
    }

    async function ejecutarPagoConSimuladorTarjeta() {
        const resultadoDiv = document.getElementById('resultadoVenta');
        if (carrito.length === 0) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-warning';
            resultadoDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Primero escanea o agrega al menos un producto a la lista de venta.';
            return;
        }

        const numTarjeta = (tbkInputNumber.value || '').replace(/\s+/g, '');
        const last4 = numTarjeta.slice(-4) || '0003';
        const cuotas = tbkInputCuotas.value;

        tbkPinpadStatus.className = 'rounded-3 p-2 bg-info-subtle border border-info text-center small text-dark fw-semibold mb-3';
        tbkPinpadStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div>Conectando con Transbank Webpay... Validando tarjeta **** ' + last4;

        await new Promise(r => setTimeout(r, 650));

        if (numTarjeta.endsWith('0000')) {
            window.playScannerBeep(false);
            tbkPinpadStatus.className = 'rounded-3 p-2 bg-danger text-white text-center small fw-bold mb-3';
            tbkPinpadStatus.innerHTML = '<i class="bi bi-x-octagon-fill me-1"></i>TRANSACCIÓN RECHAZADA POR BANCO EMISOR (Fondos Insuficientes • Cód. -1)';
            resultadoDiv.className = 'mt-3 alert alert-danger';
            resultadoDiv.innerHTML = '<strong>Pago Rechazado en Transbank:</strong> La tarjeta terminada en **** 0000 fue rechazada por el banco. <strong>No se descontó stock FEFO de la lista.</strong>';
            return;
        }

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
                const tbk = data.transbank || {};
                const authCode = tbk.codigo_autorizacion_tbk || 'TBK-948201';

                tbkPinpadStatus.className = 'rounded-3 p-2 bg-success text-white text-center small fw-bold mb-3';
                tbkPinpadStatus.innerHTML = `<i class="bi bi-patch-check-fill me-1"></i>¡APROBADA! Tarjeta **** ${last4} • Aut: ${authCode}`;

                const lotesHtml = data.lotes_fefo.map(l =>
                    `<li><strong>${l.medicamento || ''}</strong> — Lote <strong class="font-mono">${l.lote}</strong>: <strong>${l.cantidad} u.</strong> descontadas</li>`
                ).join('');

                resultadoDiv.className = 'mt-3 alert alert-success border-success shadow-sm';
                resultadoDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-check-circle-fill me-1"></i>¡Venta #${data.venta_id ?? ''} Procesada! (Ahorro Convenio: ${formatCLP(data.descuento || 0)})</strong>
                        <span class="badge bg-success fs-6">${formatCLP(data.total || 0)}</span>
                    </div>
                    <div class="mt-2 p-2 rounded-3 bg-white border small text-dark">
                        <div class="d-flex justify-content-between fw-bold border-bottom pb-1 mb-1">
                            <span><i class="bi bi-credit-card-2-front text-danger me-1"></i>COMPROBANTE PAGO CON TARJETA TRANSBANK</span>
                            <span class="font-mono badge bg-dark">${authCode}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tarjeta: <strong>**** **** **** ${last4}</strong> (${cuotas === '0' ? 'Sin cuotas' : cuotas + ' cuotas'})</span>
                            <span>Estado: <strong class="text-success">AUTORIZADO (Cód. 0)</strong></span>
                        </div>
                    </div>
                    <div class="mt-2 small">Lotes descontados por algoritmo FEFO:</div>
                    <ul class="mb-0 small">${lotesHtml}</ul>
                `;
                carrito = [];
                renderizarCarrito();
                setTimeout(() => window.location.reload(), 2600);
            } else {
                window.playScannerBeep(false);
                resultadoDiv.className = 'mt-3 alert alert-danger';
                resultadoDiv.innerHTML = `<i class="bi bi-x-octagon-fill me-1"></i>${data.message || 'Error al procesar la venta.'}`;
            }
        } catch (err) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-danger';
            resultadoDiv.textContent = 'Error de conexión con el servidor POS.';
        }
    }

    document.getElementById('posForm').addEventListener('submit', function (e) {
        e.preventDefault();
        ejecutarPagoConSimuladorTarjeta();
    });

    document.getElementById('btnPagarSimuladorLocal').addEventListener('click', function () {
        ejecutarPagoConSimuladorTarjeta();
    });

    document.getElementById('btnRedirigirWebpaySandbox').addEventListener('click', async function () {
        const resultadoDiv = document.getElementById('resultadoVenta');
        if (carrito.length === 0) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-warning';
            resultadoDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Primero agrega al menos un producto a la lista para enviar el total a Webpay.';
            return;
        }

        tbkPinpadStatus.className = 'rounded-3 p-2 bg-warning-subtle border border-warning text-center small text-dark fw-semibold mb-3';
        tbkPinpadStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Creando sesión en servidor oficial Transbank Sandbox (webpay3gint.transbank.cl)...';

        const payload = construirPayloadCarrito();

        try {
            const res = await fetch('{{ route("pos.webpay.iniciar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.status === 'success' && data.url && data.token) {
                const redirectForm = document.getElementById('webpayRedirectForm');
                redirectForm.action = data.url;
                document.getElementById('webpayTokenInput').value = data.token;
                redirectForm.submit();
            } else {
                tbkPinpadStatus.className = 'rounded-3 p-2 bg-danger-subtle border border-danger text-center small text-danger mb-3';
                tbkPinpadStatus.textContent = data.message || 'No se pudo iniciar transacción en Webpay.';
            }
        } catch (err) {
            tbkPinpadStatus.className = 'rounded-3 p-2 bg-danger-subtle border border-danger text-center small text-danger mb-3';
            tbkPinpadStatus.textContent = 'Error de red al conectar con Transbank.';
        }
    });
})();
</script>
@endpush
