@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold mb-0">Punto de Venta (POS) — Motor FEFO</h2>
        <p class="text-muted mb-0">El descuento de inventario asigna automáticamente los lotes con vencimiento más próximo (First Expired, First Out).</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Dispensación de Medicamento
                </div>
                <div class="card-body">
                    <form id="posForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Medicamento disponible en Vitrina</label>
                            <select name="medicamento_id" id="medicamento_id" class="form-select" required>
                                <option value="">-- Seleccione un medicamento --</option>
                                @foreach($medicamentos as $med)
                                    <option value="{{ $med->id }}"
                                            data-precio="{{ $med->precio_unitario }}"
                                            data-controlado="{{ $med->es_controlado ? '1' : '0' }}"
                                            data-stock="{{ $med->lotes->sum('stock_vitrina') }}">
                                        [{{ $med->codigo_barra }}] {{ $med->nombre_comercial }} ({{ $med->principio_activo }})
                                        — ${{ number_format($med->precio_unitario, 0, ',', '.') }}
                                        — Stock Vitrina: {{ $med->lotes->sum('stock_vitrina') }}
                                        {{ $med->es_controlado ? ' [CONTROLADO]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cantidad</label>
                                <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Convenio / Beneficio</label>
                                <select name="convenio_id" id="convenio_id" class="form-select">
                                    <option value="" data-descuento="0">Sin Convenio (0%)</option>
                                    @foreach($convenios as $conv)
                                        <option value="{{ $conv->id }}" data-descuento="{{ $conv->porcentaje_descuento }}">
                                            {{ $conv->nombre }} (-{{ $conv->porcentaje_descuento }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Método de Pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="debito">Tarjeta Débito</option>
                                    <option value="credito">Tarjeta Crédito</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </div>
                        </div>

                        <div id="bloqueControlado" class="border rounded p-3 mb-3 bg-warning-subtle d-none">
                            <h6 class="fw-bold text-dark mb-2">Registro Obligatorio — Libro de Medicamentos Controlados</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="rut_paciente" class="form-control form-control-sm" placeholder="RUT Paciente">
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="nombre_paciente" class="form-control form-control-sm" placeholder="Nombre Completo Paciente">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="rut_medico" class="form-control form-control-sm" placeholder="RUT Médico">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="nombre_medico" class="form-control form-control-sm" placeholder="Nombre Médico">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="numero_receta" class="form-control form-control-sm" placeholder="N° Receta Cheque/Retenida">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-verde-medico w-100 py-2 fw-bold">
                            Procesar Venta con Asignación FEFO
                        </button>
                    </form>

                    <div id="resultadoVenta" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    Cola de Lotes en Vitrina (Orden FEFO)
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Lote</th>
                                <th>Vence</th>
                                <th>Stock Vitrina</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($medicamentos as $med)
                                @foreach($med->lotes as $lote)
                                    <tr>
                                        <td>{{ $med->nombre_comercial }}</td>
                                        <td><code>{{ $lote->numero_lote }}</code></td>
                                        <td>{{ $lote->fecha_vencimiento->format('Y-m-d') }}</td>
                                        <td class="fw-bold">{{ $lote->stock_vitrina }}</td>
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
@endsection

@push('scripts')
<script>
document.getElementById('medicamento_id').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const esControlado = selected && selected.dataset.controlado === '1';
    document.getElementById('bloqueControlado').classList.toggle('d-none', !esControlado);
});

document.getElementById('posForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const resultadoDiv = document.getElementById('resultadoVenta');
    resultadoDiv.className = 'mt-3 alert alert-info';
    resultadoDiv.textContent = 'Procesando transacción con bloqueo FEFO...';

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
            const lotesHtml = data.lotes_fefo.map(l =>
                `<li>Lote <strong>${l.lote}</strong>: ${l.cantidad} u. descontadas</li>`
            ).join('');
            resultadoDiv.className = 'mt-3 alert alert-success';
            resultadoDiv.innerHTML = `
                <strong>¡Venta #${data.venta_id ?? ''} procesada con éxito!</strong>
                <div class="mt-1">Lotes asignados por algoritmo FEFO:</div>
                <ul class="mb-1">${lotesHtml}</ul>
                <small>Recargando stock en vitrina...</small>
            `;
            setTimeout(() => window.location.reload(), 1600);
        } else {
            resultadoDiv.className = 'mt-3 alert alert-danger';
            resultadoDiv.textContent = data.message || 'Error al procesar la venta.';
        }
    } catch (err) {
        resultadoDiv.className = 'mt-3 alert alert-danger';
        resultadoDiv.textContent = 'Error de red al conectar con el servidor POS.';
    }
});
</script>
@endpush
