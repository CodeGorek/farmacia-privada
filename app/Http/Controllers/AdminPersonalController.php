<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TurnoPersonal;
use App\Models\AusenciaReemplazo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminPersonalController extends Controller
{
    public function index()
    {
        $usuarios = User::with(['turnos', 'ausencias.reemplazante'])->orderBy('rol')->orderBy('name')->get();
        $turnos = TurnoPersonal::with('user')->orderBy('fecha_inicio', 'desc')->get();
        $ausencias = AusenciaReemplazo::with(['titular', 'reemplazante'])->orderBy('fecha_inicio', 'desc')->get();

        return view('admin.personal', compact('usuarios', 'turnos', 'ausencias'));
    }

    public function storeUsuario(Request $request)
    {
        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:users,rut',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'rol' => 'required|in:vendedor,qf,bodeguero,admin',
            'registro_isp' => 'nullable|string|max:40',
            'telefono' => 'nullable|string|max:30',
            'horario_habitual' => 'required|string|max:100',
            'password' => 'required|string|min:4',
        ]);

        User::create([
            'rut' => $validated['rut'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            'registro_isp' => $validated['registro_isp'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'horario_habitual' => $validated['horario_habitual'],
            'activo' => true,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.personal.index')->with('success', "Cuenta creada exitosamente para {$validated['name']} ({$validated['rol']}).");
    }

    public function toggleEstadoUsuario(User $user)
    {
        $user->activo = !$user->activo;
        $user->save();

        $estadoTxt = $user->activo ? 'HABILITADA' : 'DESHABILITADA';
        return redirect()->route('admin.personal.index')->with('success', "La cuenta de {$user->name} ha sido {$estadoTxt}.");
    }

    public function storeTurno(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo_turno' => 'required|string|max:40',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'hora_entrada' => 'required|string|max:10',
            'hora_salida' => 'required|string|max:10',
            'observaciones' => 'nullable|string|max:200',
        ]);

        $validated['es_nocturno'] = $validated['tipo_turno'] === 'NOCTURNO_GUARDIA' || $request->boolean('es_nocturno');

        TurnoPersonal::create($validated);

        return redirect()->route('admin.personal.index')->with('success', 'Turno asignado correctamente en el calendario de la farmacia.');
    }

    public function storeAusenciaReemplazo(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reemplazante_id' => 'nullable|exists:users,id|different:user_id',
            'tipo' => 'required|in:LICENCIA_MEDICA,DIA_LIBRE,VACACIONES,PERMISO_ADMINISTRATIVO',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo_folio' => 'nullable|string|max:150',
        ]);

        $validated['estado'] = 'VIGENTE';

        AusenciaReemplazo::create($validated);

        return redirect()->route('admin.personal.index')->with('success', 'Ausencia / Licencia y asignación de reemplazo registrados con éxito.');
    }
}
