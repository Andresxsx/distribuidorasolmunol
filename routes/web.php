<?php

use App\Http\Controllers\AsistenteIaController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Reportes de Compras
    |--------------------------------------------------------------------------
    */

    Route::get('/reportes/compras/excel', [ReporteController::class, 'comprasExcel'])
        ->name('reportes.compras.excel');

    Route::get('/reportes/compras/pdf', [ReporteController::class, 'comprasPdf'])
        ->name('reportes.compras.pdf');

    /*
    |--------------------------------------------------------------------------
    | Reportes de Ventas
    |--------------------------------------------------------------------------
    */

    Route::get('/reportes/ventas/excel', [ReporteController::class, 'ventasExcel'])
        ->name('reportes.ventas.excel');

    Route::get('/reportes/ventas/pdf', [ReporteController::class, 'ventasPdf'])
        ->name('reportes.ventas.pdf');

    /*
    |--------------------------------------------------------------------------
    | Asistente IA flotante
    |--------------------------------------------------------------------------
    */

    Route::post('/ia/asistente', [AsistenteIaController::class, 'preguntar'])
        ->name('ia.asistente');
});