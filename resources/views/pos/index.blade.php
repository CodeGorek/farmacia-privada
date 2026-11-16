@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Encabezado del Módulo POS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-extrabold mb-1"><i class="bi bi-upc-scan text-primary me-2"></i>Terminal Punto de Venta (POS) — Motor FEFO</h2>
            <p class="text-muted mb-0">Escanea el código de barras con tu pistola lectora USB/Bluetooth o selecciona manualmente el medicamento.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <span class="scanner-dot me-2"></span>Escáner HID Global Escuchando
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna Izquierda: Lector de Código de Barras + Formulario de Dispensación -->
        <div class="col-lg-7">
            <!-- Módulo Lector de Código de Barras Real -->
            <div class="scanner-box p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="scanner-dot"></span>
                        <span class="fw-bold text-uppercase small tracking-wider text-success-emphasis" style="color: #34D399 !important;">
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
                        <strong>Modo Continuo:</strong> Escanear el mismo código varias veces suma <code>+1</code> a la cantidad automáticamente.
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
                                <label class="form-label fw-bold">Medio de Pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="debito">💳 Tarjeta Débito (Redcompra)</option>
                                    <option value="credito">💳 Tarjeta Crédito</option>
                                    <option value="transferencia">🏦 Transferencia Bancaria</option>
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

                        <!-- Resumen Financiero y Previsualización FEFO antes de cobrar -->
                        <div class="rounded-4 p-3 mb-4 border" style="background-color: #F8FAFC;">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <div class="small fw-bold text-uppercase text-muted mb-1">
                                        <i class="bi bi-layers-half text-primary me-1"></i>Simulación de Descuento por Lotes (FEFO):
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
                                        <span class="fw-bold">TOTAL A PAGAR:</span>
                                        <span id="txtTotal" class="fs-3 fw-extrabold text-success font-mono">$0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitVenta" class="btn btn-verde-medico w-100 py-3 rounded-3 fw-bold fs-5">
                            <i class="bi bi-check2-circle me-2"></i>Confirmar y Procesar Venta con Asignación FEFO
                        </button>
                    </form>

                    <div id="resultadoVenta" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Cola de Lotes en Vitrina Ordenada por FEFO -->
        <div class="col-lg-5">
            <div class="card-pro h-100">
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
    const scannerInput = document.getElementById('barcodeScannerInput');
    const feedbackBadge = document.getElementById('scannerFeedbackBadge');
    const bloqueControlado = document.getElementById('bloqueControlado');
    const badgeControladoHeader = document.getElementById('badgeControladoHeader');
    const previewLotesFefo = document.getElementById('previewLotesFefo');
    const txtSubtotal = document.getElementById('txtSubtotal');
    const txtDescuento = document.getElementById('txtDescuento');
    const txtTotal = document.getElementById('txtTotal');

    const formatCLP = (val) => '$' + Math.round(val).toLocaleString('es-CL');

    function actualizarCalculosYPreview() {
        const opt = selectMed.options[selectMed.selectedIndex];
        if (!opt || !opt.value) {
            bloqueControlado.classList.add('d-none');
            badgeControladoHeader.classList.add('d-none');
            previewLotesFefo.innerHTML = 'Selecciona o escanea un medicamento para previsualizar qué lotes se descontarán.';
            txtSubtotal.textContent = '$0';
            txtDescuento.textContent = '-$0';
            txtTotal.textContent = '$0';
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

        // Simulación en vivo de lotes FEFO
        try {
            const lotes = JSON.parse(opt.dataset.lotes || '[]');
            let restante = cantidad;
            const asignados = [];

            for (const l of lotes) {
                if (restante <= 0) break;
                if (l.stock <= 0) continue;
                const tomar = Math.min(l.stock, restante);
                asignados.push(`<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono me-1">${l.lote}: ${tomar} u. (vence ${l.vence})</span>`);
                restante -= tomar;
            }

            if (restante > 0) {
                previewLotesFefo.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-octagon-fill me-1"></i>Stock insuficiente en vitrina (faltan ${restante} u.). Traspasa stock desde Bodega en Inventario.</span>`;
            } else {
                previewLotesFefo.innerHTML = asignados.join(' ') || '<span class="text-danger">Sin lotes disponibles</span>';
            }
        } catch (e) {}
    }

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
            // Si ya estaba seleccionado el mismo producto, incrementamos +1 la cantidad (como en supermercado/farmacia)
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

    // 1. Escucha directa en el input del escáner (cuando el lector envía Enter o Tab al terminar de escanear)
    scannerInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            procesarCodigoDeBarras(this.value);
        }
    });

    // 2. Escucha GLOBAL de pistola lectora HID (por si el foco está en otro elemento de la pantalla)
    let hidBuffer = '';
    let lastKeyTime = 0;
    document.addEventListener('keydown', function (e) {
        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
            return; // Si ya está dentro de un input, que actúe el input correspondiente
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

    // Si viene ?scan=CODIGO desde el Dashboard, lo escanea automáticamente al cargar
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scan')) {
        procesarCodigoDeBarras(urlParams.get('scan'));
    }

    // Envío transaccional FEFO
    document.getElementById('posForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const resultadoDiv = document.getElementById('resultadoVenta');
        resultadoDiv.className = 'mt-3 alert alert-info d-flex align-items-center gap-2';
        resultadoDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div><span>Ejecutando bloqueo pesimista FEFO (lockForUpdate)...</span>';

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
                resultadoDiv.className = 'mt-3 alert alert-success border-success shadow-sm';
                resultadoDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-check-circle-fill me-1"></i>¡Venta #${data.venta_id ?? ''} registrada exitosamente!</strong>
                        <span class="badge bg-success fs-6">${formatCLP(data.total || 0)}</span>
                    </div>
                    <div class="mt-2 small">Desglose de salida aplicado por motor FEFO:</div>
                    <ul class="mb-1 small">${lotesHtml}</ul>
                    <small class="text-muted">Actualizando existencias en vitrina...</small>
                `;
                setTimeout(() => window.location.reload(), 1600);
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
