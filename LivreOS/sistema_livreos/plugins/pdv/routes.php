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

use Pdv\PdvController;
use Pdv\PdvApiController;
use Pdv\PdvRelatorioController;
use Pdv\PdvSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:120,1'])->group(function () {
    Route::get('/', [PdvController::class, 'index'])->name('index');
    Route::get('/caixas', [PdvController::class, 'caixas'])->name('caixas');
    Route::post('/aviso-quebra-sobra/{id}/ocultar', [PdvController::class, 'avisoQuebraSobraOcultar'])->name('aviso-quebra-sobra.ocultar');
    Route::get('/historico-vendas', [PdvController::class, 'historicoVendas'])->name('historico-vendas');
    Route::get('/historico-vendas/export-csv', [PdvController::class, 'historicoVendasExportCsv'])->name('historico-vendas.export-csv');
    Route::get('/historico-vendas/export-pdf', [PdvController::class, 'historicoVendasExportPdf'])->name('historico-vendas.export-pdf');
    Route::prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/',                  [PdvRelatorioController::class, 'index'])->name('index');
        Route::get('/vendas-periodo',    [PdvRelatorioController::class, 'vendasPeriodo'])->name('vendas-periodo');
        Route::get('/produtos-vendidos', [PdvRelatorioController::class, 'produtosVendidos'])->name('produtos-vendidos');
        Route::get('/resumo-caixas',     [PdvRelatorioController::class, 'resumoCaixas'])->name('resumo-caixas');
    });

    Route::get('/habilitar-caixa-usuarios', fn () => redirect()->route('configuracoes-sistema.plugins.show', 'pdv', 301))->name('habilitar-caixa-usuarios')->middleware('permission:manage-plugins');
    Route::post('/habilitar-caixa-usuarios', [PdvSettingsController::class, 'storeHabilitarCaixaUsuarios'])->name('habilitar-caixa-usuarios.store')->middleware('permission:manage-plugins');
    Route::post('/configuracoes', [PdvSettingsController::class, 'store'])->name('configuracoes.store')->middleware('permission:manage-plugins');
    Route::get('/configuracoes/export', [PdvSettingsController::class, 'export'])->name('configuracoes.export')->middleware('permission:manage-plugins');
    Route::get('/configuracoes/import', [PdvSettingsController::class, 'importForm'])->name('configuracoes.import')->middleware('permission:manage-plugins');
    Route::post('/configuracoes/import', [PdvSettingsController::class, 'importProcess'])->name('configuracoes.import.process')->middleware('permission:manage-plugins');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/caixa/status', [PdvApiController::class, 'caixaStatus'])->name('caixa.status');
        Route::post('/caixa/abrir', [PdvApiController::class, 'caixaAbrir'])->name('caixa.abrir');
        Route::post('/caixa/reforco', [PdvApiController::class, 'caixaReforco'])->name('caixa.reforco');
        Route::post('/caixa/sangria', [PdvApiController::class, 'caixaSangria'])->name('caixa.sangria');
        Route::get('/caixa/fechamento-formas', [PdvApiController::class, 'caixaFechamentoFormas'])->name('caixa.fechamento-formas');
        Route::post('/caixa/fechar', [PdvApiController::class, 'caixaFechar'])->name('caixa.fechar');
        Route::get('/caixa/historico', [PdvApiController::class, 'caixaHistorico'])->name('caixa.historico');

        Route::get('/imagem', [PdvApiController::class, 'imagem'])->name('imagem');
        Route::get('/busca/produtos', [PdvApiController::class, 'buscaProdutos'])->name('busca.produtos');
        Route::get('/busca/servicos', [PdvApiController::class, 'buscaServicos'])->name('busca.servicos');
        Route::get('/busca/clientes', [PdvApiController::class, 'buscaClientes'])->name('busca.clientes');
        Route::get('/formas-pagamento', [PdvApiController::class, 'formasPagamento'])->name('formas-pagamento');
        Route::get('/adquirentes', [PdvApiController::class, 'adquirentes'])->name('adquirentes');
        Route::get('/plano-contas-aporte', [PdvApiController::class, 'planoContasAporte'])->name('plano-contas-aporte');

        Route::get('/vendas', [PdvApiController::class, 'vendasList'])->name('vendas.list');
        Route::post('/vendas', [PdvApiController::class, 'vendaStore'])->name('vendas.store');
        Route::get('/vendas/{venda}', [PdvApiController::class, 'vendaShow'])->name('vendas.show');
        Route::get('/vendas/{venda}/comprovante-pdf', [PdvApiController::class, 'vendaComprovantePdf'])->name('vendas.comprovante-pdf');
        Route::post('/vendas/{venda}/cancelar', [PdvApiController::class, 'vendaCancelar'])->name('vendas.cancelar');
        Route::get('/historico-vendas', [PdvApiController::class, 'historicoVendasJson'])->name('historico-vendas.json');

        Route::get('/orcamentos', [PdvApiController::class, 'orcamentosList'])->name('orcamentos.list');
        Route::post('/orcamentos', [PdvApiController::class, 'orcamentoStore'])->name('orcamentos.store');
        Route::get('/orcamentos/{orcamento}', [PdvApiController::class, 'orcamentoShow'])->name('orcamentos.show');
        Route::post('/orcamentos/{orcamento}/importar-venda', [PdvApiController::class, 'orcamentoImportarParaVenda'])->name('orcamentos.importar-venda');
        Route::post('/orcamento/pdf', [PdvApiController::class, 'orcamentoPdf'])->name('orcamento.pdf');
        Route::post('/validar-senha-desconto', [PdvApiController::class, 'validarSenhaDesconto'])->name('validar-senha-desconto');
        Route::post('/validar-senha-autorizador', [PdvApiController::class, 'validarSenhaAutorizador'])->name('validar-senha-autorizador');
        Route::post('/validar-senha-estoque-zero', [PdvApiController::class, 'validarSenhaEstoqueZero'])->name('validar-senha-estoque-zero');
        Route::post('/check-estoque-carrinho', [PdvApiController::class, 'checkEstoqueCarrinho'])->name('check-estoque-carrinho');
    });
});
