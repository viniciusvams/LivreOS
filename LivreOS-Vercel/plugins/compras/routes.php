<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

use Compras\ComprasController;
use Compras\Models\CompraImportacao;
use Illuminate\Support\Facades\Route;

Route::bind('compra_importacao', static fn (string $value) => CompraImportacao::findOrFail($value));

Route::middleware(['web', 'auth', 'operational', 'permission:access-financeiro', 'permission:compras.gerenciar'])
    ->group(function () {
        Route::get('/', [ComprasController::class, 'index'])->name('index');
        Route::post('/upload', [ComprasController::class, 'upload'])->name('upload');
        Route::get('/importacao/{compra_importacao}', [ComprasController::class, 'revisar'])->name('revisar');
        Route::post('/importacao/{compra_importacao}/mapear', [ComprasController::class, 'salvarMapeamento'])->name('mapear');
        Route::post('/importacao/{compra_importacao}/confirmar', [ComprasController::class, 'confirmar'])->name('confirmar');
        Route::get('/importacao/{compra_importacao}/desfazer', [ComprasController::class, 'desfazerForm'])->name('desfazer.form');
        Route::post('/importacao/{compra_importacao}/desfazer', [ComprasController::class, 'desfazer'])->name('desfazer');
        Route::post('/importacao/{compra_importacao}/excluir', [ComprasController::class, 'excluirImportacao'])->name('excluir');
    });
