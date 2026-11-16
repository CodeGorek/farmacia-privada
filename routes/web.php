<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\VisacionQfController;
use App\Http\Controllers\AdminPersonalController;
use App\Http\Controllers\OperacionesSanitariasController;

// Portal de Login Público (Sin menús ni vistas internas)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Todas las rutas internas requieren sesión iniciada (middleware auth)
Route::middleware('auth')->group(function () {
    // Dashboard BI (Redirige automáticamente a cada rol a su vista principal si corresponde)
    Route::get('/', [PosController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/realtime', [PosController::class, 'dashboardRealtime'])->name('dashboard.realtime');

    // Módulo Punto de Venta (POS) — Exclusivo Vendedor (y supervisable por Admin)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/validar-rut-convenio', [PosController::class, 'validarRutConvenio'])->name('validar-rut');
        Route::post('/solicitar-visacion-qf', [VisacionQfController::class, 'crearSolicitudDesdePos'])->name('solicitar-visacion');
        Route::get('/solicitud-visacion/{solicitud}', [VisacionQfController::class, 'estadoSolicitudPos'])->name('estado-visacion');
        Route::post('/procesar-venta', [PosController::class, 'procesarVenta'])->name('procesar');
        Route::post('/webpay/iniciar', [PosController::class, 'iniciarWebpay'])->name('webpay.iniciar');
        Route::match(['get', 'post'], '/webpay/retorno', [PosController::class, 'retornoWebpay'])->name('webpay.retorno');
    });

    // Módulo de Visación de Recetas — Exclusivo Químico Farmacéutico (D.T.)
    Route::prefix('visacion-qf')->name('visacion.')->group(function () {
        Route::get('/', [VisacionQfController::class, 'index'])->name('index');
        Route::get('/pendientes-json', [VisacionQfController::class, 'pendientesJson'])->name('pendientes.json');
        Route::post('/{solicitud}/aprobar', [VisacionQfController::class, 'aprobar'])->name('aprobar');
        Route::post('/{solicitud}/rechazar', [VisacionQfController::class, 'rechazar'])->name('rechazar');
    });

    // Módulo de Operaciones Sanitarias, Caja Z, Boleta SII, Cadena de Frío y Recall ISP
    Route::prefix('operaciones')->name('operaciones.')->group(function () {
        Route::get('/', [OperacionesSanitariasController::class, 'index'])->name('index');
        Route::get('/boleta-sii/{venta}', [OperacionesSanitariasController::class, 'verBoletaSii'])->name('boleta');
        Route::post('/venta/{venta}/anular-nc61', [OperacionesSanitariasController::class, 'anularVentaConNotaCredito'])->name('venta.anular');
        Route::post('/caja/abrir', [OperacionesSanitariasController::class, 'abrirCaja'])->name('caja.abrir');
        Route::post('/caja/{caja}/cerrar-z', [OperacionesSanitariasController::class, 'cerrarCajaZ'])->name('caja.cerrar');
        Route::post('/temperatura', [OperacionesSanitariasController::class, 'storeTemperatura'])->name('temperatura.store');
        Route::post('/lote/{lote}/recall-isp', [OperacionesSanitariasController::class, 'toggleRecallCuarentena'])->name('lote.recall');
        Route::post('/ordenes-compra', [OperacionesSanitariasController::class, 'storeOrdenCompra'])->name('oc.store');
        Route::post('/farmacovigilancia', [OperacionesSanitariasController::class, 'storeFarmacovigilancia'])->name('ram.store');
    });

    // Módulo de Administración de Personal, Turnos, Licencias y Reemplazos — EXCLUSIVO ADMINISTRADOR
    Route::prefix('admin/personal')->name('admin.personal.')->group(function () {
        Route::get('/', [AdminPersonalController::class, 'index'])->name('index');
        Route::post('/usuarios', [AdminPersonalController::class, 'storeUsuario'])->name('usuarios.store');
        Route::post('/usuarios/{user}/toggle', [AdminPersonalController::class, 'toggleEstadoUsuario'])->name('usuarios.toggle');
        Route::post('/turnos', [AdminPersonalController::class, 'storeTurno'])->name('turnos.store');
        Route::post('/ausencias', [AdminPersonalController::class, 'storeAusenciaReemplazo'])->name('ausencias.store');
    });

    // Módulo de Inventario y Lotes FEFO
    Route::prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/', [InventarioController::class, 'index'])->name('index');
        Route::post('/medicamentos', [InventarioController::class, 'storeMedicamento'])->name('medicamentos.store');
        Route::post('/lotes', [InventarioController::class, 'storeLote'])->name('lotes.store');
        Route::post('/lotes/{lote}/traspasar', [InventarioController::class, 'traspasarBodegaAVitrina'])->name('lotes.traspasar');
    });
});
