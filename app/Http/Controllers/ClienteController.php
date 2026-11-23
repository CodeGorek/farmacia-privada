<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Convenio;
use App\Models\AfiliadoConvenio;
use App\Models\AuditoriaSistema;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $clientesQuery = Cliente::with('convenio')->orderBy('nombre_completo');

        if ($q !== '') {
            $rutNorm = Cliente::normalizarRut($q);
            $clientesQuery->where(function ($builder) use ($q, $rutNorm) {
                $builder->where('nombre_completo', 'like', "%{$q}%")
                    ->orWhere('rut', 'like', "%{$q}%")
                    ->orWhere('rut', 'like', "%{$rutNorm}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%");
            });
        }

        $clientes = $clientesQuery->get();
        $convenios = Convenio::where('activo', true)->orderBy('nombre')->get();

        return view('clientes.index', compact('clientes', 'convenios', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rut' => 'required|string|max:20',
            'nombre_completo' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'direccion' => 'nullable|string|max:200',
            'convenio_id' => 'nullable|exists:convenios,id',
            'observaciones' => 'nullable|string|max:250',
        ]);

        $rutNorm = Cliente::normalizarRut($validated['rut']);
        if (Cliente::where('rut', $rutNorm)->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Ya existe un cliente registrado con este RUT.'], 422);
            }
            return back()->withErrors(['rut' => 'Ya existe un cliente registrado con este RUT.'])->withInput();
        }

        $cliente = Cliente::create([
            'rut' => $rutNorm,
            'nombre_completo' => $validated['nombre_completo'],
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'convenio_id' => $validated['convenio_id'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'activo' => true,
        ]);

        // Sincronizar con padrón de convenios si el cliente seleccionó convenio
        if (!empty($validated['convenio_id'])) {
            AfiliadoConvenio::updateOrCreate(
                ['rut' => $rutNorm],
                [
                    'nombre_afiliado' => $validated['nombre_completo'],
                    'convenio_id' => $validated['convenio_id'],
                    'estado' => 'ACTIVO',
                ]
            );
        }

        AuditoriaSistema::registrar(
            'REGISTRAR_CLIENTE',
            'Clientes',
            "Registró al cliente {$cliente->nombre_completo} (RUT: " . Cliente::formatearRut($rutNorm) . ")."
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'cliente' => $cliente->load('convenio'),
                'rut_formateado' => Cliente::formatearRut($rutNorm),
                'message' => "Cliente {$cliente->nombre_completo} registrado exitosamente.",
            ]);
        }

        return redirect()->route('clientes.index')->with('success', "Cliente {$cliente->nombre_completo} registrado correctamente.");
    }
}
