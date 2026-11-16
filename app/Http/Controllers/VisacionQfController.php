<?php

namespace App\Http\Controllers;

use App\Models\SolicitudVisacion;
use App\Models\LibroControlado;
use App\Models\Medicamento;
use Illuminate\Http\Request;

class VisacionQfController extends Controller
{
    private function verificarPermisoQf()
    {
        $user = auth()->user();
        if ($user && $user->rol === 'vendedor') {
            return redirect()->route('pos.index')->with(
                'rbac_warning',
                "Acceso Restringido (Rol actual: {$user->rol_nombre}): Como Vendedor puedes enviar solicitudes desde tu Caja POS, pero únicamente el Químico Farmacéutico (Director Técnico) está facultado para visar y aprobar recetas médicas."
            );
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->verificarPermisoQf()) {
            return $redirect;
        }

        $pendientes = SolicitudVisacion::with('vendedor')
            ->where('estado', 'PENDIENTE')
            ->orderBy('created_at', 'asc')
            ->get();

        $historial = SolicitudVisacion::with(['vendedor', 'quimico'])
            ->whereIn('estado', ['APROBADA', 'RECHAZADA', 'PROCESADA'])
            ->latest()
            ->take(20)
            ->get();

        $libroControlados = LibroControlado::with(['medicamento', 'lote', 'venta'])
            ->latest()
            ->take(15)
            ->get();

        return view('visacion.index', compact('pendientes', 'historial', 'libroControlados'));
    }

    /**
     * Endpoint JSON en tiempo real para que el Químico Farmacéutico vea llegar solicitudes sin recargar
     */
    public function pendientesJson()
    {
        $pendientes = SolicitudVisacion::with('vendedor')
            ->where('estado', 'PENDIENTE')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'ok',
            'count' => $pendientes->count(),
            'pendientes' => $pendientes->map(fn($s) => [
                'id' => $s->id,
                'folio' => 'VIS-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                'vendedor' => $s->vendedor->name ?? 'Vendedor de Caja',
                'tipo_receta' => $s->tipo_receta,
                'rut_paciente' => $s->rut_paciente,
                'nombre_paciente' => $s->nombre_paciente,
                'rut_medico' => $s->rut_medico,
                'nombre_medico' => $s->nombre_medico,
                'numero_receta' => $s->numero_receta,
                'items' => $s->items_json,
                'monto_total' => (float) $s->monto_total,
                'hora' => $s->created_at->format('H:i:s'),
            ]),
        ]);
    }

    /**
     * El Vendedor desde /pos envía una solicitud de aprobación al Químico Farmacéutico
     */
    public function crearSolicitudDesdePos(Request $request)
    {
        $request->validate([
            'rut_paciente' => 'required|string|max:20',
            'nombre_paciente' => 'required|string|max:150',
            'rut_medico' => 'required|string|max:20',
            'nombre_medico' => 'required|string|max:150',
            'numero_receta' => 'required|string|max:50',
            'tipo_receta' => 'nullable|string|max:40',
            'items' => 'required|array|min:1',
            'monto_total' => 'required|numeric|min:0',
        ]);

        $itemsDetalle = [];
        foreach ($request->items as $item) {
            $med = Medicamento::find($item['medicamento_id'] ?? 0);
            if ($med) {
                $itemsDetalle[] = [
                    'medicamento_id' => $med->id,
                    'codigo_barra' => $med->codigo_barra,
                    'nombre_comercial' => $med->nombre_comercial,
                    'principio_activo' => $med->principio_activo,
                    'es_controlado' => (bool) $med->es_controlado,
                    'requiere_receta' => (bool) $med->requiere_receta,
                    'cantidad' => (int) ($item['cantidad'] ?? 1),
                    'precio_unitario' => (float) $med->precio_unitario,
                ];
            }
        }

        $solicitud = SolicitudVisacion::create([
            'vendedor_id' => auth()->id(),
            'estado' => 'PENDIENTE',
            'tipo_receta' => $request->input('tipo_receta', 'RECETA_RETENIDA'),
            'rut_paciente' => $request->rut_paciente,
            'nombre_paciente' => $request->nombre_paciente,
            'rut_medico' => $request->rut_medico,
            'nombre_medico' => $request->nombre_medico,
            'numero_receta' => $request->numero_receta,
            'items_json' => $itemsDetalle,
            'monto_total' => $request->monto_total,
        ]);

        return response()->json([
            'status' => 'ok',
            'solicitud_id' => $solicitud->id,
            'folio' => 'VIS-' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT),
            'estado' => $solicitud->estado,
            'mensaje' => 'Solicitud enviada a la bandeja del Químico Farmacéutico. Esperando visación...',
        ]);
    }

    /**
     * El POS del vendedor consulta en tiempo real si el QF ya aprobó o rechazó su solicitud
     */
    public function estadoSolicitudPos(SolicitudVisacion $solicitud)
    {
        return response()->json([
            'status' => 'ok',
            'solicitud_id' => $solicitud->id,
            'folio' => 'VIS-' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT),
            'estado' => $solicitud->estado,
            'qf_nombre' => $solicitud->quimico->name ?? 'Químico Farmacéutico de Turno',
            'codigo_firma_qf' => $solicitud->codigo_firma_qf,
            'observaciones_qf' => $solicitud->observaciones_qf,
            'aprobada_at' => $solicitud->aprobada_at ? $solicitud->aprobada_at->format('H:i:s') : null,
        ]);
    }

    /**
     * El Químico Farmacéutico aprueba la receta desde /visacion-qf
     */
    public function aprobar(Request $request, SolicitudVisacion $solicitud)
    {
        if ($redirect = $this->verificarPermisoQf()) {
            return $redirect;
        }

        $firma = 'QF-ISP-' . strtoupper(substr(md5($solicitud->id . microtime()), 0, 6));

        $solicitud->update([
            'qf_id' => auth()->id(),
            'estado' => 'APROBADA',
            'observaciones_qf' => $request->input('observaciones_qf') ?: 'Visación conforme a Decreto Supremo 466 / 404 MINSAL.',
            'codigo_firma_qf' => $firma,
            'aprobada_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'estado' => 'APROBADA',
                'codigo_firma_qf' => $firma,
            ]);
        }

        return redirect()->route('visacion.index')->with('success', "Solicitud VIS-{$solicitud->id} APROBADA con firma digital {$firma}. El vendedor ya tiene liberado el cobro en caja.");
    }

    /**
     * El Químico Farmacéutico rechaza la receta desde /visacion-qf
     */
    public function rechazar(Request $request, SolicitudVisacion $solicitud)
    {
        if ($redirect = $this->verificarPermisoQf()) {
            return $redirect;
        }

        $motivo = $request->input('observaciones_qf') ?: 'Receta rechazada por incumplimiento sanitario (enmendadura o vigencia > 30 días).';

        $solicitud->update([
            'qf_id' => auth()->id(),
            'estado' => 'RECHAZADA',
            'observaciones_qf' => $motivo,
            'aprobada_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'estado' => 'RECHAZADA',
            ]);
        }

        return redirect()->route('visacion.index')->with('success', "Solicitud VIS-{$solicitud->id} RECHAZADA. Se notificó inmediatamente a la caja del vendedor.");
    }
}
