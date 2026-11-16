@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo POS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-upc-scan text-primary me-2"></i>Terminal Punto de Venta (POS) — Motor FEFO & Transbank</h2>
            <p class="text-muted mb-0">Escanea el código de barras con tu pistola lectora USB/Bluetooth y simula en vivo el abono líquido según tarifas oficiales de Transbank.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge px-3 py-2 rounded-pill text-white fw-semibold" style="background: linear-gradient(135deg, #D5006D 0%, #6B1D7A 100%);">
                <i class="bi bi-credit-card-2-front me-1"></i>Simulador Tarifas Transbank Integrado
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <span class="scanner-dot me-2"></span>Escáner HID Global Escuchando
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Lector de Código de Barras + Formulario de Dispensación + Desglose Transbank -->
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

            <!-- Tarjeta de Dispensación y Cálculo -->
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
                                <label class="form-label fw-bold">Medio de Pago (Tarifa TBK)</label>
                                <select name="metodo_pago" id="metodo_pago" class="form-select fw-semibold">
                                    <option value="debito" selected>💳 Débito Redcompra (TBK 1,49% + IVA)</option>
                                    <option value="credito">💳 Tarjeta Crédito (TBK 2,29% + IVA)</option>
                                    <option value="prepago">💳 Tarjeta Prepago (TBK 1,49% + IVA)</option>
                                    <option value="efectivo">💵 Efectivo en Caja (0% Comisión)</option>
                                    <option value="transferencia">🏦 Transferencia (0% Comisión)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bloque de Receta Controlada (Psicotrópicos / Estupefacientes) -->
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

                        <!-- Resumen Financiero + Simulador de Comisiones y Abono Transbank en Vivo -->
                        <div class="rounded-4 p-3 mb-4 border" style="background-color: #F8FAFC;">
                            <div class="row g-3">
                                <!-- Columna Izquierda: Lotes FEFO y Totales Cliente -->
                                <div class="col-md-6">
                                    <div class="small fw-bold text-uppercase text-muted mb-1">
                                        <i class="bi bi-layers-half text-primary me-1"></i>Asignación de Lotes (FEFO):
                                    </div>
                                    <div id="previewLotesFefo" class="small text-muted mb-3">
                                        Selecciona o escanea un medicamento para previsualizar qué lotes se descontarán.
                                    </div>

                                    <div class="p-3 rounded-3 bg-white border">
                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                            <span>Subtotal Bruto:</span>
                                            <span id="txtSubtotal" class="font-mono">$0</span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-success mb-1">
                                            <span>Bonificación Convenio:</span>
                                            <span id="txtDescuento" class="font-mono">-$0</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="fw-bold small">PAGA EL CLIENTE:</span>
                                            <span id="txtTotal" class="fs-4 fw-extrabold text-dark font-mono">$0</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Simulador Oficial de Tarifas Transbank (publico.transbank.cl/tarifas) -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 h-100 text-white position-relative"
                                         style="background: linear-gradient(145deg, #2D0B3A 0%, #4A154B 55%, #880E4F 100%);">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-white text-dark fw-bold" style="font-size: 0.7rem;">
                                                <i class="bi bi-calculator-fill text-danger me-1"></i>SIMULADOR TARIFAS TRANSBANK
                                            </span>
                                            <span id="txtTbkTasaBadge" class="badge bg-warning text-dark font-mono" style="font-size: 0.7rem;">
                                                Tasa: 1,49% + IVA
                                            </span>
                                        </div>
                                        <div class="small text-white-50 mb-2" style="font-size: 0.72rem;">
                                            Rubro: <strong>Farmacias (MCC 5912)</strong> • POS Presencial
                                        </div>

                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-white-50">Comisión Transbank (<span id="txtTbkPct">1.49</span>%):</span>
                                            <span id="txtTbkComision" class="font-mono text-warning">-$0</span>
                                        </div>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-white-50">IVA sobre Comisión (19%):</span>
                                            <span id="txtTbkIva" class="font-mono text-warning">-$0</span>
                                        </div>
                                        <div class="d-flex justify-content-between small mb-2 pb-1 border-bottom border-light border-opacity-25">
                                            <span class="text-white-50 fw-semibold">Total Descuento TBK:</span>
                                            <span id="txtTbkDescuentoTotal" class="font-mono fw-bold text-danger-emphasis" style="color: #FDA4AF !important;">-$0</span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <div>
                                                <div class="fw-bold small text-white">ABONO LÍQUIDO FARMACIA:</div>
                                                <div id="txtTbkPlazo" class="text-white-50" style="font-size: 0.68rem;">Abono en 24 hrs hábiles</div>
                                            </div>
                                            <span id="txtTbkAbonoLiquido" class="fs-4 fw-extrabold font-mono" style="color: #34D399;">$0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitVenta" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-5">
                            <i class="bi bi-credit-card-check me-2"></i>Procesar Venta FEFO y Emitir Voucher Transbank
                        </button>
                    </form>

                    <div id="resultadoVenta" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Calculadora Libre Transbank (publico.transbank.cl/tarifas) + Cola FEFO -->
        <div class="col-lg-5">
            <!-- Calculadora Interactiva Independiente de Tarifas Transbank -->
            <div class="card-pro mb-4 overflow-hidden">
                <div class="card-header d-flex justify-content-between align-items-center text-white"
                     style="background: linear-gradient(90deg, #D5006D 0%, #6B1D7A 100%);">
                    <div>
                        <i class="bi bi-Sliders me-1"></i>
                        <span>Calculadora Libre de Tarifas Transbank</span>
                    </div>
                    <a href="https://publico.transbank.cl/tarifas" target="_blank" rel="noopener" class="badge bg-white text-dark text-decoration-none">
                        publico.transbank.cl/tarifas <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-2">
                        Simula cualquier monto de venta y compara cuánto abona Transbank según el tipo de tarjeta (Comisión + 19% IVA):
                    </p>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Monto de Venta ($ CLP)</label>
                            <input type="number" id="simMontoInput" class="form-control form-control-sm font-mono fw-bold" value="25000" min="100">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Tipo de Tarjeta / Canal</label>
                            <select id="simTipoTarjeta" class="form-select form-select-sm fw-semibold">
                                <option value="1.49" data-plazo="24 horas hábiles">Débito Redcompra (1,49% + IVA)</option>
                                <option value="1.49" data-plazo="24 horas hábiles">Prepago (1,49% + IVA)</option>
                                <option value="2.29" data-plazo="48 horas hábiles">Crédito Nacional/Int. (2,29% + IVA)</option>
                            </select>
                        </div>
                    </div>
                    <div class="rounded-3 p-3 bg-light border small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Monto Venta Bruto:</span>
                            <strong id="simResBruto" class="font-mono">$25.000</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Comisión Transbank (<span id="simResTasa">1,49%</span>):</span>
                            <span id="simResComision" class="font-mono text-danger">-$373</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">IVA sobre Comisión (19%):</span>
                            <span id="simResIva" class="font-mono text-danger">-$71</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                            <span class="fw-semibold text-muted">Costo Total Transbank (Comisión + IVA):</span>
                            <strong id="simResCostoTotal" class="font-mono text-danger">-$444</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Recibes en tu cuenta (<span id="simResPlazo">24 horas hábiles</span>):</span>
                            <span id="simResLiquido" class="fs-5 fw-extrabold text-success font-mono">$24.556</span>
                        </div>
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

    // Elementos del Simulador Transbank Integrado en el POS
    const txtTbkTasaBadge = document.getElementById('txtTbkTasaBadge');
    const txtTbkPct = document.getElementById('txtTbkPct');
    const txtTbkComision = document.getElementById('txtTbkComision');
    const txtTbkIva = document.getElementById('txtTbkIva');
    const txtTbkDescuentoTotal = document.getElementById('txtTbkDescuentoTotal');
    const txtTbkAbonoLiquido = document.getElementById('txtTbkAbonoLiquido');
    const txtTbkPlazo = document.getElementById('txtTbkPlazo');

    const formatCLP = (val) => '$' + Math.round(val || 0).toLocaleString('es-CL');

    // Función oficial de cálculo Transbank (publico.transbank.cl/tarifas)
    function calcularTransbankJS(montoBruto, metodo) {
        let tasa = 0;
        let plazo = 'Inmediato en Caja (Sin Comisión TBK)';
        if (metodo === 'debito' || metodo === 'prepago') {
            tasa = 1.49;
            plazo = 'Abono en 24 hrs hábiles (Transbank)';
        } else if (metodo === 'credito') {
            tasa = 2.29;
            plazo = 'Abono en 48 hrs hábiles (Transbank)';
        }

        const comision = Math.round(montoBruto * (tasa / 100));
        const iva = Math.round(comision * 0.19);
        const totalDescuento = comision + iva;
        const liquido = Math.max(0, montoBruto - totalDescuento);

        return { tasa, comision, iva, totalDescuento, liquido, plazo };
    }

    function actualizarCalculosYPreview() {
        const opt = selectMed.options[selectMed.selectedIndex];
        const metodo = selectMetodo.value;

        if (!opt || !opt.value) {
            bloqueControlado.classList.add('d-none');
            badgeControladoHeader.classList.add('d-none');
            previewLotesFefo.innerHTML = 'Selecciona o escanea un medicamento para previsualizar qué lotes se descontarán.';
            txtSubtotal.textContent = '$0';
            txtDescuento.textContent = '-$0';
            txtTotal.textContent = '$0';
            const tbkZero = calcularTransbankJS(0, metodo);
            txtTbkPct.textContent = tbkZero.tasa.toFixed(2);
            txtTbkTasaBadge.textContent = tbkZero.tasa > 0 ? `Tasa: ${tbkZero.tasa}% + IVA` : 'Exento TBK (0%)';
            txtTbkComision.textContent = '-$0';
            txtTbkIva.textContent = '-$0';
            txtTbkDescuentoTotal.textContent = '-$0';
            txtTbkAbonoLiquido.textContent = '$0';
            txtTbkPlazo.textContent = tbkZero.plazo;
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

        // Actualizar Simulador Transbank de la venta actual
        const tbk = calcularTransbankJS(total, metodo);
        txtTbkPct.textContent = tbk.tasa.toFixed(2);
        txtTbkTasaBadge.textContent = tbk.tasa > 0 ? `Tasa: ${tbk.tasa}% + IVA` : 'Exento TBK (0%)';
        txtTbkComision.textContent = '-' + formatCLP(tbk.comision);
        txtTbkIva.textContent = '-' + formatCLP(tbk.iva);
        txtTbkDescuentoTotal.textContent = '-' + formatCLP(tbk.totalDescuento);
        txtTbkAbonoLiquido.textContent = formatCLP(tbk.liquido);
        txtTbkPlazo.textContent = tbk.plazo;

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

    // Calculadora Independiente de Tarifas Transbank (Columna Derecha)
    const simMontoInput = document.getElementById('simMontoInput');
    const simTipoTarjeta = document.getElementById('simTipoTarjeta');
    function actualizarCalculadoraLibreTbk() {
        const monto = Math.max(0, parseFloat(simMontoInput.value || '0'));
        const tasa = parseFloat(simTipoTarjeta.value || '1.49');
        const plazo = simTipoTarjeta.options[simTipoTarjeta.selectedIndex].dataset.plazo;

        const comision = Math.round(monto * (tasa / 100));
        const iva = Math.round(comision * 0.19);
        const costoTotal = comision + iva;
        const liquido = Math.max(0, monto - costoTotal);

        document.getElementById('simResBruto').textContent = formatCLP(monto);
        document.getElementById('simResTasa').textContent = tasa.toFixed(2).replace('.', ',') + '%';
        document.getElementById('simResComision').textContent = '-' + formatCLP(comision);
        document.getElementById('simResIva').textContent = '-' + formatCLP(iva);
        document.getElementById('simResCostoTotal').textContent = '-' + formatCLP(costoTotal);
        document.getElementById('simResPlazo').textContent = plazo;
        document.getElementById('simResLiquido').textContent = formatCLP(liquido);
    }
    simMontoInput.addEventListener('input', actualizarCalculadoraLibreTbk);
    simTipoTarjeta.addEventListener('change', actualizarCalculadoraLibreTbk);

    // Procesar un código de barras leído por pistola USB/Bluetooth o clic de prueba
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

    // Envío transaccional FEFO + Voucher Transbank
    document.getElementById('posForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const resultadoDiv = document.getElementById('resultadoVenta');
        resultadoDiv.className = 'mt-3 alert alert-info d-flex align-items-center gap-2';
        resultadoDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div><span>Comunicando con Pinpad Transbank y ejecutando descuento FEFO...</span>';

        const formData = new FormData(this);
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
                const lotesHtml = data.lotes_fefo.map(l =>
                    `<li>Lote <strong class="font-mono">${l.lote}</strong>: <strong>${l.cantidad} u.</strong> descontadas</li>`
                ).join('');

                const tbk = data.transbank || {};
                const tbkHtml = tbk.es_transbank ? `
                    <div class="mt-2 p-2 rounded-3 bg-white border small">
                        <div class="d-flex justify-content-between fw-bold text-dark border-bottom pb-1 mb-1">
                            <span><i class="bi bi-credit-card-2-front text-danger me-1"></i>VOUCHER TRANSBANK APROBADO</span>
                            <span class="font-mono badge bg-dark">${tbk.codigo_autorizacion_tbk}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Comisión TBK (${tbk.tasa_transbank}%) + IVA (19%):</span>
                            <span class="font-mono text-danger">-${formatCLP(tbk.total_descuento_tbk)}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold text-success">
                            <span>Abono Líquido a Cuenta (${tbk.plazo_abono}):</span>
                            <span class="font-mono">${formatCLP(tbk.abono_liquido)}</span>
                        </div>
                    </div>
                ` : '';

                resultadoDiv.className = 'mt-3 alert alert-success border-success shadow-sm';
                resultadoDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-check-circle-fill me-1"></i>¡Venta #${data.venta_id ?? ''} procesada con éxito!</strong>
                        <span class="badge bg-success fs-6">${formatCLP(data.total || 0)}</span>
                    </div>
                    <div class="mt-2 small">Lotes descontados por algoritmo FEFO:</div>
                    <ul class="mb-1 small">${lotesHtml}</ul>
                    ${tbkHtml}
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
    });
})();
</script>
@endpush
