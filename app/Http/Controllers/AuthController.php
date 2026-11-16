<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AusenciaReemplazo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        $usuariosDemo = User::orderBy('id')->get();
        return view('auth.login', compact('usuariosDemo'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'identificador' => 'required|string',
            'password' => 'required|string',
        ]);

        $identificador = trim($request->identificador);

        $user = User::where('email', $identificador)
            ->orWhere('rut', $identificador)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'identificador' => 'Credenciales inválidas. Verifica tu correo/RUT y contraseña.',
            ])->withInput();
        }

        if (!$user->activo) {
            return back()->withErrors([
                'identificador' => "La cuenta de {$user->name} se encuentra DESHABILITADA por el Administrador.",
            ])->withInput();
        }

        // Verificar si tiene licencia médica vigente hoy
        $licenciaActiva = AusenciaReemplazo::with('reemplazante')
            ->where('user_id', $user->id)
            ->where('estado', 'VIGENTE')
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->first();

        if ($licenciaActiva && $licenciaActiva->tipo === 'LICENCIA_MEDICA') {
            $reemp = $licenciaActiva->reemplazante ? $licenciaActiva->reemplazante->name : 'Sin asignar';
            return back()->withErrors([
                'identificador' => "Acceso bloqueado por normativa laboral: {$user->name} registra Licencia Médica vigente hasta el " . $licenciaActiva->fecha_fin->format('d/m/Y') . ". Reemplazo activo: {$reemp}.",
            ])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return match ($user->rol) {
            'qf' => redirect()->route('visacion.index')->with('success', "Bienvenido(a) D.T. {$user->name} (Químico Farmacéutico)."),
            'admin' => redirect()->route('admin.personal.index')->with('success', "Bienvenido(a) {$user->name} (Administrador General)."),
            default => redirect()->route('pos.index')->with('success', "Turno iniciado en Caja POS: {$user->name}."),
        };
    }

    /**
     * Cambio rápido de usuario en 1 clic desde la barra superior para facilitar las pruebas de roles (Vendedor <-> QF <-> Admin)
     */
    public function quickSwitch(User $user)
    {
        if (!$user->activo) {
            return back()->with('webpay_error', "No se puede cambiar a {$user->name}: la cuenta está deshabilitada.");
        }

        Auth::login($user);

        return match ($user->rol) {
            'qf' => redirect()->route('visacion.index'),
            'admin' => redirect()->route('admin.personal.index'),
            default => redirect()->route('pos.index'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
