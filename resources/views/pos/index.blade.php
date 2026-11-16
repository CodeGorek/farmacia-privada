@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo POS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-upc-scan text-primary me-2"></i>Terminal Punto de Venta (POS) — Motor FEFO & Webpay</h2>
            <p class="text-muted mb-0">Escanea con pistola lectora de código de barras y procesa el pago en el <strong>Simulador de Pago con Tarjeta Transbank (Webpay / Redcompra)</strong>.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge px-3 py-2 rounded-pill text-white fw-semibold" style="background: linear-gradient(135deg, #D5006D 0%, #6B1D7A 100%);">
                <i class="bi bi-credit-card-2-front me-1"></i>Pasarela Pago con Tarjeta Webpay / Redcompra Activa
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <span class="scanner-dot me-2"></span>Escáner HID Global Escuchando
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
        <!-- Columna Izquierda: Lector de Código de Barras + Ficha de Dispensación -->
        <div class="col-lg-7">
            <!-- Módulo Lector de Código de Barras Real -->
            <div class="scanner-box p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="scanner-dot"></span>
                        <span class="fw-bold text-uppercase small tracking-wider" style="color: #34D399 !important;">
                            Lector Láser / Código de Barras Activo (EAN-13 / Code128)
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
                           placeholder="Dispara el lector de código de barras aquí (ej. 780123456001) + Enter..."
                           autocomplete="off"
                           autofocus>
                    <button type="button" id="btnLimpiarScan" class="btn btn-outline-light px-3" title="Limpiar lector">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 pt-2 border-top border-secondary border-opacity-25 gap-2">
                    <small class="text-white-50">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                        <strong>Modo Continuo:</strong> Escanear el mismo código suma <code>+1</code> a la cantidad automáticamente.
                    </small>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <span class="text-white-50 small me-1">Probar código:</span>
                        @foreach($medicamentos as $med)
                            <button type="button"
                                    class="btn btn-sm btn-dark border border-secondary font-mono py-0 px-2 btn-simular-scan"
                                    data-barcode="{{ $med->codigo_barra }}">
                                <i class="bi bi-upc me-1 text-success"></i>{{ $med->codigo_barra }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Dispensación Farmacéutica -->
            <div class="card-pro">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bag-plus-fill text-primary me-2"></i>Ficha de Dispensación Farmacéutica</span>
                    <span id="badgeControladoHeader" class="badge bg-warning text-dark d-none">
                        <i class="bi bi-shield-exclamation me-1"></i>MEDICAMENTO CONTROLADO
                    </span>
                </div>
                <div class="card-body p-4">
                    <form id="posForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Medicamento Seleccionado</label>
                            <select name="medicamento_id" id="medicamento_id" class="form-select form-select-lg" required>
                                <option value="">-- Escanea un código o selecciona del catálogo --</option>
                                @foreach($medicamentos as $med)
                                    @php
                                        $lotesJson = $med->lotes->map(fn($l) => [
                                            'lote' => $l->numero_lote,
                                            'vence' => $l->fecha_vencimiento->format('Y-m-d'),
                                            'stock' => $l->stock_vitrina
                                        ])->values();
                                    @endphp
                                    <option value="{{ $med->id }}"
                                            data-codigo="{{ $med->codigo_barra }}"
                                            data-nombre="{{ $med->nombre_comercial }}"
                                            data-principio="{{ $med->principio_activo }}"
                                            data-precio="{{ $med->precio_unitario }}"
                                            data-controlado="{{ $med->es_controlado ? '1' : '0' }}"
                                            data-receta="{{ $med->requiere_receta ? '1' : '0' }}"
                                            data-stock="{{ $med->lotes->sum('stock_vitrina') }}"
                                            data-lotes='@json($lotesJson)'>
                                        [{{ $med->codigo_barra }}] {{ $med->nombre_comercial }} ({{ $med->principio_activo }})
                                        — ${{ number_format($med->precio_unitario, 0, ',', '.') }}
                                        — Vitrina: {{ $med->lotes->sum('stock_vitrina') }} u.
                                        {{ $med->es_controlado ? ' ⚠️ [CONTROLADO]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cantidad a Dispensar</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="btnRestarCant"><i class="bi bi-dash"></i></button>
                                    <input type="number" name="cantidad" id="cantidad" class="form-control text-center fw-bold fs-5" min="1" value="1" required>
                                    <button type="button" class="btn btn-outline-secondary" id="btnSumarCant"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Convenio / Isapre</label>
                                <select name="convenio_id" id="convenio_id" class="form-select">
                                    <option value="" data-descuento="0">Particular (0% Dcto.)</option>
                                    @foreach($convenios as $conv)
                                        <option value="{{ $conv->id }}" data-descuento="{{ $conv->porcentaje_descuento }}">
                                            {{ $conv->nombre }} (-{{ number_format($conv->porcentaje_descuento, 0) }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Medio de Pago</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-select fw-semibold">
                                    <option value="debito" selected>💳 Tarjeta Débito (Redcompra)</option>
                                    <option value="credito">💳 Tarjeta de Crédito (Webpay)</option>
                                    <option value="prepago">💳 Tarjeta Prepago</option>
                                    <option value="efectivo">💵 Efectivo en Caja</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bloque de Receta Controlada -->
                        <div id="bloqueControlado" class="border border-warning border-2 rounded-4 p-3 mb-4 bg-warning-subtle d-none">
                            <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold">
                                <i class="bi bi-journal-medical fs-5 text-danger"></i>
                                <span>Registro Legal Obligatorio — Libro de Medicamentos Controlados</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">RUT Paciente</label>
                                    <input type="text" name="rut_paciente" class="form-control form-control-sm" placeholder="12.345.678-9">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold mb-1">Nombre Completo Paciente</label>
                                    <input type="text" name="nombre_paciente" class="form-control form-control-sm" placeholder="Nombre y Apellidos del comprador">
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

                        <!-- Resumen de Lotes FEFO y Total a Pagar -->
                        <div class="rounded-4 p-3 mb-4 border" style="background-color: #F8FAFC;">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <div class="small fw-bold text-uppercase text-muted mb-1">
                                        <i class="bi bi-layers-half text-primary me-1"></i>Asignación de Lotes por Vencimiento (FEFO):
                                    </div>
                                    <div id="previewLotesFefo" class="small text-muted">
                                        Selecciona o escanea un medicamento para previsualizar qué lotes se descontarán.
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-end border-start-md ps-md-3">
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Subtotal:</span>
                                        <span id="txtSubtotal" class="font-mono">$0</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-success">
                                        <span>Descuento Convenio:</span>
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

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnSubmitVenta" class="btn btn-verde-medico py-3 rounded-3 fw-bold fs-5">
                                <i class="bi bi-credit-card-2-front me-2"></i>Cobrar en Simulador de Tarjeta Transbank (Webpay / Redcompra)
                            </button>
                        </div>
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
                    <span class="badge bg-white text-dark font-mono fw-bold" id="tbkCardAmountBadge">$0</span>
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
                                <div class="fw-bold font-mono" id="visualCardExpiry">12/28</div>
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

                    <!-- Estado de Comunicación con el Banco Emisor -->
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

    <!-- Formulario oculto para redirección POST a Webpay Plus Sandbox oficial -->
    <form id="webpayRedirectForm" method="POST" action="" class="d-none">
        <input type="hidden" name="token_ws" id="webpayTokenInput" value="">
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const selectMed = document.getElementById('medicamento_id');
    const inputCant = document.getElementById('cantidad');
    const selectConv = document.getElementById('convenio_id');
    const selectMetodo = document.getElementById('metodo_pago');
    const scannerInput = document.getElementById('barcodeScannerInput');
    const feedbackBadge = document.getElementById('scannerFeedbackBadge');
    const bloqueControlado = document.getElementById('bloqueControlado');
    const badgeControladoHeader = document.getElementById('badgeControladoHeader');
    const previewLotesFefo = document.getElementById('previewLotesFefo');
    const txtSubtotal = document.getElementById('txtSubtotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotal = document.getElementById('txtTotal');
    const tbkCardAmountBadge = document.getElementById('tbkCardAmountBadge');

    // Elementos del Simulador de Pago con Tarjeta
    const visualCardBox = document.getElementById('visualCardBox');
    const visualCardBrand = document.getElementById('visualCardBrand');
    const visualCardNumber = document.getElementById('visualCardNumber');
    const visualCardExpiry = document.getElementById('visualCardExpiry');
    const tbkInputNumber = document.getElementById('tbkInputNumber');
    const tbkInputExp = document.getElementById('tbkInputExp');
    const tbkInputCvv = document.getElementById('tbkInputCvv');
    const tbkInputCuotas = document.getElementById('tbkInputCuotas');
    const tbkPinpadStatus = document.getElementById('tbkPinpadStatus');

    const formatCLP = (val) => '$' + Math.round(val || 0).toLocaleString('es-CL');

    function actualizarCalculosYPreview() {
        const opt = selectMed.options[selectMed.selectedIndex];

        if (!opt || !opt.value) {
            bloqueControlado.classList.add('d-none');
            badgeControladoHeader.classList.add('d-none');
            previewLotesFefo.innerHTML = 'Selecciona o escanea un medicamento para previsualizar qué lotes se descontarán.';
            txtSubtotal.textContent = '$0';
            txtDescuento.textContent = '-$0';
            txtTotal.textContent = '$0';
            tbkCardAmountBadge.textContent = '$0';
            return;
        }

        const esControlado = opt.dataset.controlado === '1';
        bloqueControlado.classList.toggle('d-none', !esControlado);
        badgeControladoHeader.classList.toggle('d-none', !esControlado);

        const precio = parseFloat(opt.dataset.precio || '0');
        const cantidad = Math.max(1, parseInt(inputCant.value || '1', 10));
        const convOpt = selectConv.options[selectConv.selectedIndex];
        const pctDescuento = parseFloat((convOpt && convOpt.dataset.descuento) || '0');

        const subtotal = precio * cantidad;
        const descuento = Math.round(subtotal * (pctDescuento / 100));
        const total = Math.max(0, subtotal - descuento);

        txtSubtotal.textContent = formatCLP(subtotal);
        txtDescuento.textContent = '-' + formatCLP(descuento);
        txtTotal.textContent = formatCLP(total);
        tbkCardAmountBadge.textContent = formatCLP(total);

        // Simulación en vivo de lotes FEFO
        try {
            const lotes = JSON.parse(opt.dataset.lotes || '[]');
            let restante = cantidad;
            const asignados = [];

            for (const l of lotes) {
                if (restante <= 0) break;
                if (l.stock <= 0) continue;
                const tomar = Math.min(l.stock, restante);
                asignados.push(`<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono me-1 mb-1">${l.lote}: ${tomar} u. (vence ${l.vence})</span>`);
                restante -= tomar;
            }

            if (restante > 0) {
                previewLotesFefo.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon-fill me-1"></i>Stock insuficiente en vitrina (faltan ${restante} u.).</span>`;
            } else {
                previewLotesFefo.innerHTML = asignados.join(' ') || '<span class="text-danger">Sin lotes disponibles</span>';
            }
        } catch (e) {}
    }

    // Sincronizar tarjeta 3D cuando el usuario escribe o selecciona un preset de tarjeta de prueba
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
            actualizarCalculosYPreview();
        });
    });

    // Procesar código de barras
    function procesarCodigoDeBarras(codigoRaw) {
        const codigo = (codigoRaw || '').trim();
        if (!codigo) return;

        let encontrado = null;
        for (let i = 0; i < selectMed.options.length; i++) {
            const opt = selectMed.options[i];
            if (opt.dataset && opt.dataset.codigo === codigo) {
                encontrado = opt;
                break;
            }
        }

        if (encontrado) {
            window.playScannerBeep(true);
            if (selectMed.value === encontrado.value) {
                inputCant.value = parseInt(inputCant.value || '1', 10) + 1;
            } else {
                selectMed.value = encontrado.value;
                inputCant.value = 1;
            }

            feedbackBadge.className = 'badge bg-success text-white fw-semibold';
            feedbackBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Escaneado: ${encontrado.dataset.nombre} [${codigo}]`;
            scannerInput.value = '';
            actualizarCalculosYPreview();
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

    document.getElementById('btnLimpiarScan').addEventListener('click', function () {
        scannerInput.value = '';
        scannerInput.focus();
    });

    document.querySelectorAll('.btn-simular-scan').forEach(btn => {
        btn.addEventListener('click', function () {
            procesarCodigoDeBarras(this.dataset.barcode);
        });
    });

    document.getElementById('btnRestarCant').addEventListener('click', function () {
        const actual = parseInt(inputCant.value || '1', 10);
        if (actual > 1) {
            inputCant.value = actual - 1;
            actualizarCalculosYPreview();
        }
    });

    document.getElementById('btnSumarCant').addEventListener('click', function () {
        const actual = parseInt(inputCant.value || '1', 10);
        inputCant.value = actual + 1;
        actualizarCalculosYPreview();
    });

    selectMed.addEventListener('change', actualizarCalculosYPreview);
    inputCant.addEventListener('input', actualizarCalculosYPreview);
    selectConv.addEventListener('change', actualizarCalculosYPreview);
    selectMetodo.addEventListener('change', actualizarCalculosYPreview);

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scan')) {
        procesarCodigoDeBarras(urlParams.get('scan'));
    }

    // Función central para autorizar en el Simulador de Tarjeta y ejecutar el descuento FEFO
    async function ejecutarPagoConSimuladorTarjeta() {
        const resultadoDiv = document.getElementById('resultadoVenta');
        if (!selectMed.value) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-warning';
            resultadoDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Primero escanea o selecciona un medicamento para cobrar.';
            return;
        }

        const numTarjeta = (tbkInputNumber.value || '').replace(/\s+/g, '');
        const last4 = numTarjeta.slice(-4) || '0003';
        const cuotas = tbkInputCuotas.value;

        // Paso 1: Animación en el Pinpad / Simulador de Tarjeta
        tbkPinpadStatus.className = 'rounded-3 p-2 bg-info-subtle border border-info text-center small text-dark fw-semibold mb-3';
        tbkPinpadStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div>Conectando con Transbank Webpay... Validando tarjeta **** ' + last4;

        await new Promise(r => setTimeout(r, 650));

        // Si es la tarjeta de prueba terminada en 0000, simulamos Rechazo Bancario
        if (numTarjeta.endsWith('0000')) {
            window.playScannerBeep(false);
            tbkPinpadStatus.className = 'rounded-3 p-2 bg-danger text-white text-center small fw-bold mb-3';
            tbkPinpadStatus.innerHTML = '<i class="bi bi-x-octagon-fill me-1"></i>TRANSACCIÓN RECHAZADA POR BANCO EMISOR (Fondos Insuficientes • Cód. -1)';
            resultadoDiv.className = 'mt-3 alert alert-danger';
            resultadoDiv.innerHTML = '<strong>Pago Rechazado en Transbank:</strong> La tarjeta terminada en **** 0000 fue rechazada por el banco. <strong>No se descontó stock FEFO.</strong>';
            return;
        }

        const formData = new FormData(document.getElementById('posForm'));
        const payload = Object.fromEntries(formData.entries());

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
                    `<li>Lote <strong class="font-mono">${l.lote}</strong>: <strong>${l.cantidad} u.</strong> descontadas</li>`
                ).join('');

                resultadoDiv.className = 'mt-3 alert alert-success border-success shadow-sm';
                resultadoDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-check-circle-fill me-1"></i>¡Venta #${data.venta_id ?? ''} y Pago con Tarjeta Aprobados!</strong>
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
                setTimeout(() => window.location.reload(), 2400);
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

    // Opción B: Redirección a la Pasarela Oficial Webpay Plus Sandbox (webpay3gint.transbank.cl)
    document.getElementById('btnRedirigirWebpaySandbox').addEventListener('click', async function () {
        const resultadoDiv = document.getElementById('resultadoVenta');
        if (!selectMed.value) {
            window.playScannerBeep(false);
            resultadoDiv.className = 'mt-3 alert alert-warning';
            resultadoDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Primero escanea o selecciona un medicamento para enviar el monto a Webpay.';
            return;
        }

        tbkPinpadStatus.className = 'rounded-3 p-2 bg-warning-subtle border border-warning text-center small text-dark fw-semibold mb-3';
        tbkPinpadStatus.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Creando sesión en servidor oficial Transbank Sandbox (webpay3gint.transbank.cl)...';

        const formData = new FormData(document.getElementById('posForm'));
        const payload = Object.fromEntries(formData.entries());

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
