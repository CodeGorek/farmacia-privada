@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 mx-auto" style="max-width: 420px;">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-verde-medico rounded-pill px-4 fw-bold">
            <i class="bi bi-printer-fill me-1"></i>Imprimir Boleta Térmica 80mm
        </button>
    </div>

    <!-- Boleta Electrónica Formato Térmico 80mm SII Chile -->
    <div class="bg-white p-4 mx-auto shadow border rounded-3 font-mono" style="max-width: 420px; color: #000; font-size: 0.84rem;">
        <!-- Recuadro Rojo SII -->
        <div class="border border-danger border-3 p-2 text-center text-danger fw-bold mb-3">
            <div>R.U.T.: 76.890.123-4</div>
            <div class="fs-6">{{ $venta->anulada ? 'NOTA DE CRÉDITO ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}</div>
            <div>N° {{ $venta->folio_dte_sii ?? ('DTE39-0010' . $venta->id) }}</div>
            <div class="small">S.I.I. - SANTIAGO CENTRO</div>
        </div>

        <div class="text-center mb-3">
            <div class="fw-bold fs-6">FARMACIA PRIVADA PHARMACORE S.p.A.</div>
            <div class="small">Giro: Farmacia, Perfumería y Artículos Médicos</div>
            <div class="small">Av. Providencia 1450, Santiago, Chile</div>
            <div class="small">D.T.: Dr. Sebastián Morales (Q.F. Reg. SIS-QF-48291)</div>
            <div class="small text-muted">Resol. Ex. SII N° 80 del 22-08-2025</div>
        </div>

        <hr class="border-dark border-dashed my-2">

        <div class="small mb-2">
            <div class="d-flex justify-content-between">
                <span>FECHA EMISIÓN:</span>
                <strong>{{ $venta->created_at->format('d/m/Y H:i:s') }}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>CAJERO(A):</span>
                <span>{{ $venta->user->name ?? 'Camila Muñoz (Caja #1)' }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>RUT CLIENTE:</span>
                <span>{{ $venta->rut_cliente ?: '66.666.666-6 (Particular)' }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>CONVENIO:</span>
                <span>{{ $venta->convenio->nombre ?? 'Sin Convenio (Particular)' }}</span>
            </div>
        </div>

        <hr class="border-dark my-2">

        <!-- Detalle de Ítems y Lotes FEFO -->
        <div class="fw-bold d-flex justify-content-between small border-bottom pb-1 mb-2">
            <span>DESCRIPCIÓN / LOTE FEFO</span>
            <span>TOTAL</span>
        </div>

        @foreach($venta->detalles as $det)
            <div class="mb-2 small">
                <div class="fw-bold">{{ $det->medicamento->nombre_comercial ?? 'Medicamento' }}</div>
                <div class="d-flex justify-content-between text-muted">
                    <span>{{ $det->cantidad }} x ${{ number_format($det->precio_unitario, 0, ',', '.') }} [Lote: {{ $det->numero_lote }}]</span>
                    <strong class="text-dark">${{ number_format($det->subtotal, 0, ',', '.') }}</strong>
                </div>
            </div>
        @endforeach

        <hr class="border-dark my-2">

        @php
            $montoNeto = round($venta->total / 1.19);
            $montoIva = $venta->total - $montoNeto;
        @endphp

        <div class="small">
            <div class="d-flex justify-content-between">
                <span>SUBTOTAL BRUTO:</span>
                <span>${{ number_format($venta->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($venta->descuento > 0)
                <div class="d-flex justify-content-between text-success fw-bold">
                    <span>BONIFICACIÓN CONVENIO:</span>
                    <span>-${{ number_format($venta->descuento, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="d-flex justify-content-between text-muted">
                <span>MONTO NETO:</span>
                <span>${{ number_format($montoNeto, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between text-muted">
                <span>I.V.A. (19%):</span>
                <span>${{ number_format($montoIva, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold mt-1 pt-1 border-top border-dark">
                <span>TOTAL PAGADO:</span>
                <span>${{ number_format($venta->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Comprobante Transbank adjunto -->
        @if($venta->codigo_autorizacion_tbk)
            <div class="mt-3 p-2 border border-dark rounded-2 small bg-light">
                <div class="text-center fw-bold border-bottom pb-1 mb-1">COMPROBANTE PAGO CON TARJETA TRANSBANK</div>
                <div class="d-flex justify-content-between">
                    <span>MEDIO DE PAGO:</span>
                    <strong class="text-uppercase">{{ $venta->metodo_pago }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>CÓD. AUTORIZACIÓN:</span>
                    <strong>{{ $venta->codigo_autorizacion_tbk }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>ESTADO:</span>
                    <strong>APROBADO (RESPUESTA 00)</strong>
                </div>
            </div>
        @endif

        <!-- Representación visual del Timbre Electrónico PDF417 SII -->
        <div class="text-center mt-3 pt-2 border-top">
            <div class="mx-auto p-2 border bg-light" style="width: 100%; height: 64px; background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 5px, #000 5px, #000 6px, #fff 6px, #fff 9px);"></div>
            <div class="fw-bold mt-1" style="font-size: 0.72rem;">Timbre Electrónico SII</div>
            <div class="text-muted" style="font-size: 0.68rem;">Verifique documento en www.sii.cl</div>
        </div>
    </div>
</div>
@endsection
