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

use App\Http\Controllers\Api\AdquirenteApiController;
use App\Http\Controllers\Api\ApiInfoController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CategoriaProdutoApiController;
use App\Http\Controllers\Api\CategoriaServicoApiController;
use App\Http\Controllers\Api\CentroCustoApiController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\Api\ContaBancariaApiController;
use App\Http\Controllers\Api\ContaPagarApiController;
use App\Http\Controllers\Api\ContaReceberApiController;
use App\Http\Controllers\Api\FormaPagamentoApiController;
use App\Http\Controllers\Api\FornecedorApiController;
use App\Http\Controllers\Api\GrupoEconomicoApiController;
use App\Http\Controllers\Api\MovimentacaoApiController;
use App\Http\Controllers\Api\NotificacaoApiController;
use App\Http\Controllers\Api\OrdemServicoApiController;
use App\Http\Controllers\Api\PlanoContaApiController;
use App\Http\Controllers\Api\ProdutoApiController;
use App\Http\Controllers\Api\ServicoApiController;
use App\Http\Controllers\Api\StatusOsApiController;
use App\Http\Controllers\Api\TagApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — prefixo /api (configurado em bootstrap/app.php)
|--------------------------------------------------------------------------
| Autenticação: Bearer token (campo api_token do usuário).
| Obtenha o token via POST /api/auth/login (email + password).
| Todas as rotas protegidas exigem header: Authorization: Bearer {token}
|--------------------------------------------------------------------------
*/

// Rotas públicas (sem token)
Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');
Route::get('/', [ApiInfoController::class, 'index'])->name('api.info');

// Rotas protegidas (token + acesso operacional + permissões por recurso)
Route::middleware(['api.auth', 'api.operational'])->group(function () {
    Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthApiController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/alterar-senha', [AuthApiController::class, 'alterarSenha'])->name('api.auth.alterar-senha');

    // Clientes — exige view/create/edit/delete-clients
    Route::apiResource('clientes', ClienteApiController::class)->names('api.clientes');

    // Produtos — exige view/create/edit/delete-products
    Route::apiResource('produtos', ProdutoApiController::class)->names('api.produtos');

    // Serviços — exige view/create/edit/delete-services
    Route::apiResource('servicos', ServicoApiController::class)->names('api.servicos');

    // Ordens de Serviço — exige view-os-history (apenas listar e exibir)
    Route::get('/ordens-servico', [OrdemServicoApiController::class, 'index'])->name('api.ordens-servico.index');
    Route::get('/ordens-servico/{id}', [OrdemServicoApiController::class, 'show'])->name('api.ordens-servico.show');

    // Categorias (para selects no app)
    Route::get('/categorias-produtos', [CategoriaProdutoApiController::class, 'index'])->name('api.categorias-produtos.index');
    Route::get('/categorias-servicos', [CategoriaServicoApiController::class, 'index'])->name('api.categorias-servicos.index');

    // Fornecedores — exige view-products
    Route::get('/fornecedores', [FornecedorApiController::class, 'index'])->name('api.fornecedores.index');
    Route::get('/fornecedores/{id}', [FornecedorApiController::class, 'show'])->name('api.fornecedores.show');

    // Grupos econômicos — exige view-clients
    Route::get('/grupos-economicos', [GrupoEconomicoApiController::class, 'index'])->name('api.grupos-economicos.index');
    Route::get('/grupos-economicos/{id}', [GrupoEconomicoApiController::class, 'show'])->name('api.grupos-economicos.show');

    // Status OS — exige view-os-history
    Route::get('/status-os', [StatusOsApiController::class, 'index'])->name('api.status-os.index');

    // Tags (usadas em OS) — exige view-os-history
    Route::get('/tags', [TagApiController::class, 'index'])->name('api.tags.index');

    // Notificações do usuário (qualquer operacional)
    Route::get('/notificacoes', [NotificacaoApiController::class, 'index'])->name('api.notificacoes.index');
    Route::post('/notificacoes/{id}/ler', [NotificacaoApiController::class, 'marcarLida'])->name('api.notificacoes.ler');
    Route::post('/notificacoes/ler-todas', [NotificacaoApiController::class, 'marcarTodasLidas'])->name('api.notificacoes.ler-todas');
    Route::delete('/notificacoes/{id}', [NotificacaoApiController::class, 'destroy'])->name('api.notificacoes.destroy');

    // Financeiro — exige access-financeiro
    Route::get('/contas-receber', [ContaReceberApiController::class, 'index'])->name('api.contas-receber.index');
    Route::get('/contas-receber/{id}', [ContaReceberApiController::class, 'show'])->name('api.contas-receber.show');
    Route::get('/contas-pagar', [ContaPagarApiController::class, 'index'])->name('api.contas-pagar.index');
    Route::get('/contas-pagar/{id}', [ContaPagarApiController::class, 'show'])->name('api.contas-pagar.show');
    Route::get('/contas-bancarias', [ContaBancariaApiController::class, 'index'])->name('api.contas-bancarias.index');
    Route::get('/contas-bancarias/{id}', [ContaBancariaApiController::class, 'show'])->name('api.contas-bancarias.show');
    Route::get('/formas-pagamento', [FormaPagamentoApiController::class, 'index'])->name('api.formas-pagamento.index');
    Route::get('/formas-pagamento/{id}', [FormaPagamentoApiController::class, 'show'])->name('api.formas-pagamento.show');
    Route::get('/plano-contas', [PlanoContaApiController::class, 'index'])->name('api.plano-contas.index');
    Route::get('/plano-contas/{id}', [PlanoContaApiController::class, 'show'])->name('api.plano-contas.show');
    Route::get('/centros-custo', [CentroCustoApiController::class, 'index'])->name('api.centros-custo.index');
    Route::get('/centros-custo/{id}', [CentroCustoApiController::class, 'show'])->name('api.centros-custo.show');
    Route::get('/movimentacoes', [MovimentacaoApiController::class, 'index'])->name('api.movimentacoes.index');
    Route::get('/movimentacoes/{id}', [MovimentacaoApiController::class, 'show'])->name('api.movimentacoes.show');
    Route::get('/adquirentes', [AdquirenteApiController::class, 'index'])->name('api.adquirentes.index');
    Route::get('/adquirentes/{id}', [AdquirenteApiController::class, 'show'])->name('api.adquirentes.show');
});

// Hook para plugins registrarem rotas API adicionais
if (function_exists('do_action')) {
    do_action('api.routes.register', Route::getFacadeRoot());
}
