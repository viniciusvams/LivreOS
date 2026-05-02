<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'operational'])->group(function () {
    // Configurações do plugin
    Route::get('settings', [\OsTermicaSettingsController::class, 'index'])->name('settings');
    Route::post('settings', [\OsTermicaSettingsController::class, 'store'])->name('settings.store');

    // Impressão Térmica
    Route::get('imprimir/{id}', [\OsTermicaPrintController::class, 'imprimirOs'])->name('imprimir');
    Route::get('imprimir-comprovante/{id}', [\OsTermicaPrintController::class, 'imprimirComprovante'])->name('imprimir_comprovante');
});
