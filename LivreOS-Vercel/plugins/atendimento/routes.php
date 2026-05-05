<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

use Atendimento\AtendimentoController;
use Atendimento\AtendimentoSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Servir arquivos do disk local (evita 403 com storage privado)
    Route::get('/foto/{foto}/file', [AtendimentoController::class, 'servirFoto'])->name('foto.file');
    Route::get('/assinatura/{atendimento}/file', [AtendimentoController::class, 'servirAssinatura'])->name('assinatura.file');

    Route::get('/', [AtendimentoController::class, 'index'])->name('index');
    Route::get('/rota-do-dia/export/csv', [AtendimentoController::class, 'rotaDiaExportCsv'])->name('rota-do-dia.export.csv');
    Route::get('/rota-do-dia/export/pdf', [AtendimentoController::class, 'rotaDiaExportPdf'])->name('rota-do-dia.export.pdf');
    Route::get('/rota-do-dia/mapa', [AtendimentoController::class, 'rotaDiaMapa'])->name('rota-do-dia.mapa');
    Route::get('/rota-do-dia', [AtendimentoController::class, 'rotaDia'])->name('rota-do-dia');
    Route::get('/rota-do-mes/export/csv', [AtendimentoController::class, 'rotaMesExportCsv'])->name('rota-do-mes.export.csv');
    Route::get('/rota-do-mes/export/pdf', [AtendimentoController::class, 'rotaMesExportPdf'])->name('rota-do-mes.export.pdf');
    Route::get('/create', [AtendimentoController::class, 'create'])->name('create');
    Route::post('/', [AtendimentoController::class, 'store'])->name('store');
    Route::get('/{id}', [AtendimentoController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [AtendimentoController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AtendimentoController::class, 'update'])->name('update');
    Route::delete('/{id}', [AtendimentoController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/converter-os', [AtendimentoController::class, 'converterParaOs'])->name('converter-os');
});

Route::middleware(['web', 'auth', 'permission:manage-plugins'])->prefix('configuracoes')->name('configuracoes.')->group(function () {
    Route::post('/status', [AtendimentoSettingsController::class, 'storeStatus'])->name('status.store');
    Route::put('/status/{id}', [AtendimentoSettingsController::class, 'updateStatus'])->name('status.update');
    Route::delete('/status/{id}', [AtendimentoSettingsController::class, 'destroyStatus'])->name('status.destroy');
    Route::post('/prioridades', [AtendimentoSettingsController::class, 'storePrioridade'])->name('prioridades.store');
    Route::put('/prioridades/{id}', [AtendimentoSettingsController::class, 'updatePrioridade'])->name('prioridades.update');
    Route::delete('/prioridades/{id}', [AtendimentoSettingsController::class, 'destroyPrioridade'])->name('prioridades.destroy');
    Route::post('/tempos', [AtendimentoSettingsController::class, 'storeTempo'])->name('tempos.store');
    Route::put('/tempos/{id}', [AtendimentoSettingsController::class, 'updateTempo'])->name('tempos.update');
    Route::delete('/tempos/{id}', [AtendimentoSettingsController::class, 'destroyTempo'])->name('tempos.destroy');
    Route::post('/maps', [AtendimentoSettingsController::class, 'updateMaps'])->name('maps.update');
});
