<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta Térmica 50×30 mm — {{ $medicamento->nombre_comercial }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: 50mm 30mm;
            margin: 0;
        }

        body {
            background: #F1F5F9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
        }

        .toolbar {
            background: #0F172A;
            color: #fff;
            padding: 14px 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .preview-stage {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            padding: 32px 16px;
        }

        .etiqueta-50x30 {
            width: 50mm;
            height: 30mm;
            background: #ffffff;
            border: 1px dashed #94A3B8;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
            box-sizing: border-box;
            padding: 1.8mm 2.2mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .etq-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5mm;
            border-bottom: 0.3mm solid #0F172A;
            padding-bottom: 0.8mm;
        }

        .etq-nombre {
            font-size: 7.2pt;
            font-weight: 800;
            line-height: 1.1;
            color: #000;
            max-height: 6.2mm;
            overflow: hidden;
        }

        .etq-precio {
            font-size: 9.5pt;
            font-weight: 900;
            color: #000;
            white-space: nowrap;
            line-height: 1;
        }

        .etq-meta {
            display: flex;
            justify-content: space-between;
            font-size: 5.5pt;
            color: #1E293B;
            font-weight: 600;
            margin-top: 0.5mm;
        }

        .etq-barcode-wrap {
            text-align: center;
            margin-top: 0.3mm;
            line-height: 1;
        }

        .etq-barcode-wrap svg {
            width: 44mm;
            height: 11.5mm;
            display: block;
            margin: 0 auto;
        }

        .etq-footer {
            display: flex;
            justify-content: space-between;
            font-size: 5pt;
            color: #334155;
            font-weight: 700;
        }

        @media print {
            body {
                background: #fff !important;
            }
            .no-print {
                display: none !important;
            }
            .preview-stage {
                display: block;
                padding: 0;
                margin: 0;
            }
            .etiqueta-50x30 {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Volver a Inventario
            </a>
            <div>
                <div class="fw-bold">Impresión de Etiqueta Térmica 50 × 30 mm</div>
                <div class="small text-white-50">{{ $medicamento->nombre_comercial }} • EAN: {{ $medicamento->codigo_barra }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" class="d-flex align-items-center gap-2 m-0">
                @if($lote)
                    <input type="hidden" name="lote_id" value="{{ $lote->id }}">
                @endif
                <label class="small text-white-50 mb-0">Copias:</label>
                <input type="number" name="copias" value="{{ $copias }}" min="1" max="30" class="form-control form-control-sm text-center fw-bold" style="width: 70px;">
                <button type="submit" class="btn btn-sm btn-secondary">Aplicar</button>
            </form>
            <button onclick="window.print()" class="btn btn-sm btn-success fw-bold px-4 rounded-pill">
                <i class="bi bi-printer-fill me-1"></i>Imprimir 50×30 mm
            </button>
        </div>
    </div>

    <div class="preview-stage">
        @for($i = 0; $i < $copias; $i++)
            <div class="etiqueta-50x30">
                <div class="etq-header">
                    <div class="etq-nombre">{{ $medicamento->nombre_comercial }}</div>
                    <div class="etq-precio">${{ number_format($medicamento->precio_unitario, 0, ',', '.') }}</div>
                </div>
                <div class="etq-meta">
                    <span>{{ \Illuminate\Support\Str::limit($medicamento->laboratorio ?? $medicamento->categoria_producto, 20) }}</span>
                    @if($lote)
                        <span>Vence: {{ $lote->fecha_vencimiento->format('m/Y') }}</span>
                    @endif
                </div>
                <div class="etq-barcode-wrap">
                    <svg class="barcode-svg" data-code="{{ $medicamento->codigo_barra }}"></svg>
                </div>
                <div class="etq-footer">
                    <span>PharmaCore ERP</span>
                    <span>{{ $lote ? 'Lote: ' . $lote->numero_lote : $medicamento->categoria_producto }}</span>
                </div>
            </div>
        @endfor
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.querySelectorAll('.barcode-svg').forEach(function(svg) {
            const code = svg.dataset.code || '780000000000';
            if (typeof JsBarcode !== 'undefined') {
                JsBarcode(svg, code, {
                    format: 'CODE128',
                    width: 1.25,
                    height: 28,
                    displayValue: true,
                    fontSize: 9,
                    fontOptions: 'bold',
                    margin: 0
                });
            }
        });
    </script>
</body>
</html>
