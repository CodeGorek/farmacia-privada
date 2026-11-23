<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TurnoPersonal;
use App\Models\AusenciaReemplazo;
use App\Models\AuditoriaSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminPersonalController extends Controller
{
    /**
     * Verifica que únicamente cuentas 'admin' o 'superadmin' puedan acceder al módulo de administración.
     */
    private function verificarSoloAdmin()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login')->withErrors([
                'identificador' => 'Inicia sesión con una cuenta de Administrador o Super Administrador.',
            ]);
        }

        if (!$user->isAdminOrSuper()) {
            $rutaDestino = $user->rol === 'qf' ? 'visacion.index' : 'pos.index';
            return redirect()->route($rutaDestino)->with(
                'rbac_warning',
                "Acceso Restringido (Rol actual: {$user->rol_nombre}): Únicamente el Administrador General o Super Administrador tiene permisos para gestionar personal, turnos, licencias y auditoría."
            );
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

        $seccion = $request->query('seccion', 'cuentas');
        if (!in_array($seccion, ['cuentas', 'turnos', 'licencias', 'auditoria'], true)) {
            $seccion = 'cuentas';
        }

        $usuarios = User::with(['turnos', 'ausencias.reemplazante'])
            ->orderByRaw("CASE rol WHEN 'superadmin' THEN 1 WHEN 'admin' THEN 2 WHEN 'qf' THEN 3 WHEN 'vendedor' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        $turnos = TurnoPersonal::with('user')->orderBy('fecha_inicio', 'desc')->get();
        $ausencias = AusenciaReemplazo::with(['titular', 'reemplazante'])->orderBy('fecha_inicio', 'desc')->get();

        $auditoriaQuery = AuditoriaSistema::latest();
        if ($qAudit = trim((string) $request->query('q_auditoria', ''))) {
            $auditoriaQuery->where(function ($q) use ($qAudit) {
                $q->where('usuario_nombre', 'like', "%{$qAudit}%")
                    ->orWhere('accion', 'like', "%{$qAudit}%")
                    ->orWhere('modulo', 'like', "%{$qAudit}%")
                    ->orWhere('detalle', 'like', "%{$qAudit}%")
                    ->orWhere('ip', 'like', "%{$qAudit}%")
                    ->orWhere('navegador', 'like', "%{$qAudit}%")
                    ->orWhere('sistema_operativo', 'like', "%{$qAudit}%");
            });
        }
        $auditorias = $auditoriaQuery->take(100)->get();

        return view('admin.personal', compact('seccion', 'usuarios', 'turnos', 'ausencias', 'auditorias'));
    }

    /**
     * Endpoint JSON en tiempo real para la bitácora de Auditoría del Sistema
     */
    public function auditoriaJson(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdminOrSuper()) {
            return response()->json(['status' => 'forbidden'], 403);
        }

        $query = AuditoriaSistema::latest('id');
        if ($qAudit = trim((string) $request->query('q_auditoria', ''))) {
            $query->where(function ($q) use ($qAudit) {
                $q->where('usuario_nombre', 'like', "%{$qAudit}%")
                    ->orWhere('accion', 'like', "%{$qAudit}%")
                    ->orWhere('modulo', 'like', "%{$qAudit}%")
                    ->orWhere('detalle', 'like', "%{$qAudit}%")
                    ->orWhere('ip', 'like', "%{$qAudit}%")
                    ->orWhere('navegador', 'like', "%{$qAudit}%")
                    ->orWhere('sistema_operativo', 'like', "%{$qAudit}%");
            });
        }

        $rows = $query->take(100)->get()->map(fn($a) => [
            'id' => $a->id,
            'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i:s') : '',
            'usuario_nombre' => $a->usuario_nombre,
            'usuario_rol' => strtoupper($a->usuario_rol ?? 'N/A'),
            'accion' => $a->accion,
            'modulo' => $a->modulo,
            'detalle' => $a->detalle,
            'ip' => $a->ip,
            'navegador' => $a->navegador,
            'sistema_operativo' => $a->sistema_operativo,
            'dispositivo' => $a->tipo_dispositivo,
        ]);

        return response()->json([
            'status' => 'ok',
            'hora_servidor' => now()->format('d/m/Y H:i:s'),
            'count' => $rows->count(),
            'auditorias' => $rows,
        ]);
    }

    public function storeUsuario(Request $request)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

        $actor = auth()->user();
        $rolesPermitidos = $actor->isSuperAdmin()
            ? 'vendedor,qf,bodeguero,admin,superadmin'
            : 'vendedor,qf,bodeguero';

        $validated = $request->validate([
            'rut' => 'required|string|max:20|unique:users,rut',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'rol' => "required|in:{$rolesPermitidos}",
            'registro_isp' => 'nullable|string|max:40',
            'telefono' => 'nullable|string|max:30',
            'horario_habitual' => 'required|string|max:100',
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
        ], [
            'password.min' => 'La contraseña debe tener al menos 10 caracteres.',
            'password.mixed' => 'La contraseña debe incluir letras mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un símbolo (ej. !@#$%).',
        ]);

        $nuevo = User::create([
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

        AuditoriaSistema::registrar(
            'CREAR_USUARIO',
            'Administración Personal',
            "Creó la cuenta {$nuevo->name} ({$nuevo->email}) con rol {$nuevo->rol_nombre}."
        );

        return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
            ->with('success', "Cuenta creada exitosamente para {$validated['name']} ({$nuevo->rol_nombre}).");
    }

    public function toggleEstadoUsuario(User $user)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

        $actor = auth()->user();

        if ($user->id === $actor->id) {
            return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
                ->with('rbac_warning', 'No puedes deshabilitar tu propia cuenta activa.');
        }

        if ($user->isAdminOrSuper() && !$actor->isSuperAdmin()) {
            return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
                ->with('rbac_warning', 'Solo el Super Administrador puede habilitar o deshabilitar cuentas de Administradores.');
        }

        $user->activo = !$user->activo;
        $user->save();

        $estadoTxt = $user->activo ? 'HABILITADA' : 'DESHABILITADA';
        AuditoriaSistema::registrar(
            'CAMBIO_ESTADO_USUARIO',
            'Administración Personal',
            "Cuenta {$user->name} ({$user->email}) fue marcada como {$estadoTxt}."
        );

        return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
            ->with('success', "La cuenta de {$user->name} ha sido {$estadoTxt}.");
    }

    public function resetPasswordUsuario(Request $request, User $user)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

        $actor = auth()->user();

        if ($user->isAdminOrSuper() && !$actor->isSuperAdmin() && $user->id !== $actor->id) {
            return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
                ->with('rbac_warning', 'Solo el Super Administrador puede restablecer contraseñas de otros Administradores.');
        }

        $validated = $request->validate([
            'nueva_password' => ['required', 'string', 'max:64', Password::min(10)->mixedCase()->numbers()->symbols()],
        ], [
            'nueva_password.min' => 'La nueva contraseña debe tener al menos 10 caracteres.',
            'nueva_password.mixed' => 'La nueva contraseña debe incluir letras mayúsculas y minúsculas.',
            'nueva_password.numbers' => 'La nueva contraseña debe incluir al menos un número.',
            'nueva_password.symbols' => 'La nueva contraseña debe incluir al menos un símbolo (ej. !@#$%).',
        ]);

        $user->password = Hash::make($validated['nueva_password']);
        $user->save();

        AuditoriaSistema::registrar(
            'RESTABLECER_PASSWORD',
            'Administración Personal',
            "Restableció la contraseña de la cuenta {$user->name} ({$user->email})."
        );

        return redirect()->route('admin.personal.index', ['seccion' => 'cuentas'])
            ->with('success', "Contraseña restablecida correctamente para {$user->name}.");
    }

    public function storeTurno(Request $request)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

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

        $turno = TurnoPersonal::create($validated);
        $emp = User::find($validated['user_id']);

        AuditoriaSistema::registrar(
            'ASIGNAR_TURNO',
            'Turnos y Guardias',
            "Asignó turno {$validated['tipo_turno']} ({$validated['fecha_inicio']} al {$validated['fecha_fin']}) a " . ($emp->name ?? 'Funcionario') . "."
        );

        return redirect()->route('admin.personal.index', ['seccion' => 'turnos'])
            ->with('success', 'Turno asignado correctamente en el calendario de la farmacia.');
    }

    public function storeAusenciaReemplazo(Request $request)
    {
        if ($redirect = $this->verificarSoloAdmin()) {
            return $redirect;
        }

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
        $emp = User::find($validated['user_id']);

        AuditoriaSistema::registrar(
            'REGISTRAR_AUSENCIA',
            'Licencias y Reemplazos',
            "Registró {$validated['tipo']} ({$validated['fecha_inicio']} al {$validated['fecha_fin']}) para " . ($emp->name ?? 'Funcionario') . "."
        );

        return redirect()->route('admin.personal.index', ['seccion' => 'licencias'])
            ->with('success', 'Ausencia / Licencia y asignación de reemplazo registrados con éxito.');
    }
}
