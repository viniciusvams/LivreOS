<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

use Compras\ComprasController;
use Compras\Http\Controllers\OrdemCompraController;
use Compras\Models\CompraImportacao;
use Compras\Models\OrdemCompra;
use Illuminate\Support\Facades\Route;

Route::bind('compra_importacao', static fn (string $value) => CompraImportacao::findOrFail($value));
Route::bind('ordem_compra', static fn (string $value) => OrdemCompra::findOrFail($value));

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

        Route::get('/ordens', [OrdemCompraController::class, 'index'])->name('ordens.index');
        Route::post('/ordens/criar', [OrdemCompraController::class, 'criar'])->name('ordens.criar');
        Route::get('/ordens/{ordem_compra}', [OrdemCompraController::class, 'mostrar'])->name('ordens.show');
        Route::post('/ordens/{ordem_compra}/aprovar', [OrdemCompraController::class, 'aprovar'])->name('ordens.aprovar');
        Route::post('/ordens/{ordem_compra}/rejeitar', [OrdemCompraController::class, 'rejeitar'])->name('ordens.rejeitar');
        Route::get('/ordens/{ordem_compra}/receber', [OrdemCompraController::class, 'recebimento'])->name('ordens.receber');
        Route::post('/ordens/{ordem_compra}/receber', [OrdemCompraController::class, 'registrarRecebimento'])->name('ordens.registrar-recebimento');
    });
