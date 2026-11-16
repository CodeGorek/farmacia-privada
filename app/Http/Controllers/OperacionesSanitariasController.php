<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\CajaTurno;
use App\Models\BitacoraTemperatura;
use App\Models\OrdenCompra;
use App\Models\Farmacovigilancia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperacionesSanitariasController extends Controller
{
    /**
     * Centro de Operaciones Sanitarias, Caja Z, CENABAST, Cadena de Frío y Farmacovigilancia
     */
    public function index()
    {
        $cajaActual = CajaTurno::with('user')->where('estado', 'ABIERTA')->latest()->first();
        $historialCajas = CajaTurno::with('user')->orderBy('id', 'desc')->take(10)->get();

        // Totales reales del día para el Arqueo Z en tiempo real
        $ventasActivasHoy = Venta::whereDate('created_at', today())->where('anulada', false);
        $efectivoHoy = (float) (clone $ventasActivasHoy)->where('metodo_pago', 'efectivo')->sum('total');
        $debitoHoy = (float) (clone $ventasActivasHoy)->whereIn('metodo_pago', ['debito', 'prepago'])->sum('total');
        $creditoHoy = (float) (clone $ventasActivasHoy)->where('metodo_pago', 'credito')->sum('total');
        $descuentosHoy = (float) (clone $ventasActivasHoy)->sum('descuento');

        $ultimasVentas = Venta::with(['detalles.medicamento', 'convenio', 'user'])
            ->latest()
            ->take(12)
            ->get();

        $temperaturas = BitacoraTemperatura::with('user')->latest()->take(10)->get();
        $ordenesCompra = OrdenCompra::with('medicamento')->latest()->take(12)->get();
        $reportesRam = Farmacovigilancia::with(['medicamento', 'user'])->latest()->take(10)->get();
        $medicamentos = Medicamento::with('lotes')->orderBy('nombre_comercial')->get();
        $lotes = Lote::with('medicamento')->orderBy('fecha_vencimiento', 'asc')->get();

        return view('operaciones.index', compact(
            'cajaActual',
            'historialCajas',
            'efectivoHoy',
            'debitoHoy',
            'creditoHoy',
            'descuentosHoy',
            'ultimasVentas',
            'temperaturas',
            'ordenesCompra',
            'reportesRam',
            'medicamentos',
            'lotes'
        ));
    }

    /**
     * Vista de Impresión de Boleta Electrónica Térmica 80mm SII (DTE 39) + Voucher Transbank
     */
    public function verBoletaSii(Venta $venta)
    {
        $venta->load(['detalles.medicamento', 'convenio', 'user']);
        return view('operaciones.boleta_sii', compact('venta'));
    }

    /**
     * Emitir Nota de Crédito Electrónica SII (DTE 61) / Anular Venta y Reintegrar Stock a su Lote FEFO original
     */
    public function anularVentaConNotaCredito(Venta $venta)
    {
        if ($venta->anulada) {
            return back()->with('webpay_error', "La venta #{$venta->id} ya se encuentra anulada.");
        }

        DB::transaction(function () use ($venta) {
            $venta->load('detalles');
            foreach ($venta->detalles as $det) {
                if ($det->lote_id) {
                    $lote = Lote::lockForUpdate()->find($det->lote_id);
                    if ($lote) {
                        $lote->stock_vitrina += $det->cantidad;
                        $lote->save();
                    }
                }
            }

            $venta->update([
                'anulada' => true,
                'estado' => 'anulada_nc61',
                'tipo_dte' => 'NOTA_CREDITO_61',
            ]);
        });

        return back()->with('success', "Nota de Crédito Electrónica SII (DTE 61) emitida para Venta #{$venta->id}. Todas las unidades fueron reintegradas a sus lotes FEFO originales.");
    }

    /**
     * Abrir Turno de Caja con Fondo Fijo Inicial
     */
    public function abrirCaja(Request $request)
    {
        $request->validate([
            'fondo_fijo_inicial' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:200',
        ]);

        CajaTurno::where('estado', 'ABIERTA')->update(['estado' => 'CERRADA', 'cierre_at' => now()]);

        CajaTurno::create([
            'user_id' => auth()->id(),
            'estado' => 'ABIERTA',
            'fondo_fijo_inicial' => $request->fondo_fijo_inicial,
            'apertura_at' => now(),
            'observaciones' => $request->observaciones ?: 'Apertura normal de turno POS',
        ]);

        return back()->with('success', 'Apertura de Caja registrada correctamente con fondo fijo inicial.');
    }

    /**
     * Realizar Arqueo y Cierre de Caja Z (Cuadratura de Efectivo vs Sistema + Transbank)
     */
    public function cerrarCajaZ(Request $request, CajaTurno $caja)
    {
        $request->validate([
            'efectivo_fisico_declarado' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:200',
        ]);

        $ventasActivasHoy = Venta::whereDate('created_at', today())->where('anulada', false);
        $efectivoSistema = (float) (clone $ventasActivasHoy)->where('metodo_pago', 'efectivo')->sum('total');
        $debitoSistema = (float) (clone $ventasActivasHoy)->whereIn('metodo_pago', ['debito', 'prepago'])->sum('total');
        $creditoSistema = (float) (clone $ventasActivasHoy)->where('metodo_pago', 'credito')->sum('total');
        $descuentos = (float) (clone $ventasActivasHoy)->sum('descuento');

        $efectivoEsperadoEnGaveta = (float) $caja->fondo_fijo_inicial + $efectivoSistema;
        $declarado = (float) $request->efectivo_fisico_declarado;
        $diferencia = $declarado - $efectivoEsperadoEnGaveta;

        $caja->update([
            'estado' => 'CERRADA',
            'ventas_efectivo_sistema' => $efectivoSistema,
            'ventas_tbk_debito' => $debitoSistema,
            'ventas_tbk_credito' => $creditoSistema,
            'total_descuentos_convenio' => $descuentos,
            'efectivo_fisico_declarado' => $declarado,
            'diferencia_arqueo' => $diferencia,
            'cierre_at' => now(),
            'observaciones' => $request->observaciones ?: 'Cierre Z de turno conforme',
        ]);

        return back()->with('success', "Reporte Z de Cierre de Caja emitido. Diferencia de arqueo: $" . number_format($diferencia, 0, ',', '.'));
    }

    /**
     * Registrar medición oficial en la Bitácora de Temperatura y Cadena de Frío ISP (DS 466)
     */
    public function storeTemperatura(Request $request)
    {
        $validated = $request->validate([
            'jornada' => 'required|string|max:30',
            'temp_sala' => 'required|numeric',
            'humedad_sala' => 'required|integer|min:10|max:99',
            'temp_refrigerador' => 'required|numeric',
            'observaciones' => 'nullable|string|max:200',
        ]);

        $conforme = ($validated['temp_sala'] >= 18.0 && $validated['temp_sala'] <= 25.0)
            && ($validated['temp_refrigerador'] >= 2.0 && $validated['temp_refrigerador'] <= 8.0);

        BitacoraTemperatura::create([
            'user_id' => auth()->id(),
            'jornada' => $validated['jornada'],
            'temp_sala' => $validated['temp_sala'],
            'humedad_sala' => $validated['humedad_sala'],
            'temp_refrigerador' => $validated['temp_refrigerador'],
            'conforme_isp' => $conforme,
            'observaciones' => $validated['observaciones'] ?: ($conforme ? 'Parámetros dentro de norma ISP DS 466' : 'ALERTA TÉRMICA: Revisar equipo de frío'),
        ]);

        return back()->with('success', 'Registro de Temperatura de Sala y Cadena de Frío guardado en la bitácora sanitaria.');
    }

    /**
     * Bloquear / Desbloquear un Lote por Alerta Recall ISP o pasar unidades a Cuarentena de Vencidos
     */
    public function toggleRecallCuarentena(Request $request, Lote $lote)
    {
        $lote->bloqueado_recall_isp = !$lote->bloqueado_recall_isp;
        if ($lote->bloqueado_recall_isp) {
            $unidadesMovidas = $lote->stock_vitrina;
            $lote->stock_cuarentena += $unidadesMovidas;
            $lote->stock_vitrina = 0;
            $lote->motivo_bloqueo = $request->input('motivo_bloqueo') ?: 'Retiro preventivo por Alerta Sanitaria ISP / Cuarentena FEFO';
        } else {
            $lote->stock_vitrina += $lote->stock_cuarentena;
            $lote->stock_cuarentena = 0;
            $lote->motivo_bloqueo = null;
        }
        $lote->save();

        $msg = $lote->bloqueado_recall_isp
            ? "Lote {$lote->numero_lote} BLOQUEADO y movido a Cuarentena Sanitaria ISP. No podrá ser vendido en caja."
            : "Lote {$lote->numero_lote} liberado de cuarentena y reintegrado a vitrina.";

        return back()->with('success', $msg);
    }

    /**
     * Emitir Orden de Compra a Droguería / CENABAST y Recepcionar Stock automáticamente si se solicita
     */
    public function storeOrdenCompra(Request $request)
    {
        $validated = $request->validate([
            'proveedor' => 'required|string|max:100',
            'medicamento_id' => 'required|exists:medicamentos,id',
            'cantidad_solicitada' => 'required|integer|min:1',
            'costo_unitario_neto' => 'required|numeric|min:1',
        ]);

        $folio = 'OC-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $validated['proveedor']), 0, 4)) . '-' . mt_rand(1000, 9999);
        $totalNeto = (float) $validated['costo_unitario_neto'] * (int) $validated['cantidad_solicitada'];

        OrdenCompra::create([
            'folio_oc' => $folio,
            'proveedor' => $validated['proveedor'],
            'medicamento_id' => $validated['medicamento_id'],
            'cantidad_solicitada' => $validated['cantidad_solicitada'],
            'costo_unitario_neto' => $validated['costo_unitario_neto'],
            'total_neto' => $totalNeto,
            'estado' => 'EMITIDA',
        ]);

        return back()->with('success', "Orden de Compra {$folio} emitida a {$validated['proveedor']} por $" . number_format($totalNeto, 0, ',', '.'));
    }

    /**
     * Registrar Notificación de Farmacovigilancia (RAM ISP) o Reclamo Sanitario SEREMI
     */
    public function storeFarmacovigilancia(Request $request)
    {
        $validated = $request->validate([
            'tipo_registro' => 'required|in:RAM_ISP,RECLAMO_SEREMI,SUGERENCIA',
            'rut_paciente' => 'required|string|max:20',
            'nombre_paciente' => 'required|string|max:150',
            'medicamento_id' => 'nullable|exists:medicamentos,id',
            'numero_lote' => 'nullable|string|max:30',
            'severidad' => 'required|in:LEVE,MODERADA,GRAVE',
            'descripcion_clinica' => 'required|string|max:600',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['estado_reporte'] = $validated['tipo_registro'] === 'RAM_ISP' ? 'REPORTADO_RED_RAM_ISP' : 'REGISTRADO_LIBRO_SEREMI';

        Farmacovigilancia::create($validated);

        return back()->with('success', 'Reporte registrado oficialmente en el Libro Digital de Farmacovigilancia / SEREMI.');
    }
}
