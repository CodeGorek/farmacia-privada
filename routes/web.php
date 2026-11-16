<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosController;
use App\Http\Controllers\InventarioController;

Route::get('/', [PosController::class, 'dashboard'])->name('dashboard');
Route::get('/dashboard/realtime', [PosController::class, 'dashboardRealtime'])->name('dashboard.realtime');

Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::post('/validar-rut-convenio', [PosController::class, 'validarRutConvenio'])->name('validar-rut');
    Route::post('/procesar-venta', [PosController::class, 'procesarVenta'])->name('procesar');
    Route::post('/webpay/iniciar', [PosController::class, 'iniciarWebpay'])->name('webpay.iniciar');
    Route::match(['get', 'post'], '/webpay/retorno', [PosController::class, 'retornoWebpay'])->name('webpay.retorno');
});

Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::get('/', [InventarioController::class, 'index'])->name('index');
    Route::post('/medicamentos', [InventarioController::class, 'storeMedicamento'])->name('medicamentos.store');
    Route::post('/lotes', [InventarioController::class, 'storeLote'])->name('lotes.store');
    Route::post('/lotes/{lote}/traspasar', [InventarioController::class, 'traspasarBodegaAVitrina'])->name('lotes.traspasar');
});
