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

use App\Http\Controllers\Admin\AuditAcessoClienteController;
use App\Http\Controllers\Admin\AuditCancelExcluirController;
use App\Http\Controllers\Admin\AuditFinanceiroController;
use App\Http\Controllers\Admin\AuditLoginController;
use App\Http\Controllers\Admin\CategoriaDocumentoController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AjudaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaProdutoController;
use App\Http\Controllers\CategoriaServicoController;
use App\Http\Controllers\ChecklistModelController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracoesSistema\FilaNotificacoesController;
use App\Http\Controllers\ConfiguracoesSistema\LimiteDescontoController;
use App\Http\Controllers\ConfiguracoesSistema\NotificacoesOsController;
use App\Http\Controllers\ConfiguracoesSistema\PedidosVendaConfigController;
use App\Http\Controllers\ConfiguracoesSistema\PluginController;
use App\Http\Controllers\ConfiguracoesSistema\ProdutosEstoqueController;
use App\Http\Controllers\ConfiguracoesSistema\PropostasComerciaisConfigController;
use App\Http\Controllers\ConfiguracoesSistema\StatusOsController;
use App\Http\Controllers\ConfiguracoesSistema\TermoGarantiaController;
use App\Http\Controllers\ConfiguracoesSistema\UtilidadesController;
use App\Http\Controllers\ConfiguracoesSistemaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\EquipamentoPublicoController;
use App\Http\Controllers\Financeiro\AdquirenteController;
use App\Http\Controllers\Financeiro\CentroCustoController;
use App\Http\Controllers\Financeiro\ConfiguracoesFinanceiroController;
use App\Http\Controllers\Financeiro\ContaBancariaController;
use App\Http\Controllers\Financeiro\ContaPagarController;
use App\Http\Controllers\Financeiro\ContaPagarRecorrenteController;
use App\Http\Controllers\Financeiro\ContaReceberController;
use App\Http\Controllers\Financeiro\FinanceiroCategoriaTagController;
use App\Http\Controllers\Financeiro\FormaPagamentoController;
use App\Http\Controllers\Financeiro\MovimentacaoController;
use App\Http\Controllers\Financeiro\PagamentoRecorrenteController;
use App\Http\Controllers\Financeiro\PlanoContaController;
use App\Http\Controllers\Financeiro\RelatorioController;
use App\Http\Controllers\Financeiro\SimuladorController;
use App\Http\Controllers\Financeiro\TarefasRecorrentesController;
use App\Http\Controllers\Financeiro\TaxaAdquirenteController;
use App\Http\Controllers\Financeiro\TransferenciaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\GrupoEconomicoController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\OrdemServicoAssinaturaController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\PedidoVendaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PropostaComercialController;
use App\Http\Controllers\PropostaDocumentoModeloController;
use App\Http\Controllers\RunTarefasController;
use App\Http\Controllers\RunTarefasInternoController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Dashboard principal (apenas usuários operacionais)
Route::get('/', [DashboardController::class, 'index'])->middleware('operational')->name('dashboard');
Route::get('/dashboard/agenda', [DashboardController::class, 'agendaApi'])->middleware('operational')->name('dashboard.agenda');

// Ajuda ao usuário (manual em linguagem simples)
Route::get('/ajuda', [AjudaController::class, 'index'])->middleware('operational')->name('ajuda.index');
Route::get('/ajuda/plugins', [AjudaController::class, 'plugins'])->middleware('operational')->name('ajuda.plugins');

// calender pages
Route::get('/calendar', function () {
    return erp_view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// profile pages
Route::get('/profile', function () {
    return erp_view('pages.profile', ['title' => 'Profile']);
})->name('profile');

// form pages
Route::get('/form-elements', function () {
    return erp_view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return erp_view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return erp_view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return erp_view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return erp_view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return erp_view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');

// authentication pages
Route::get('/signin', [AuthController::class, 'showLoginForm'])->name('signin');
Route::post('/signin', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/alterar-senha', [AuthController::class, 'showAlterarSenhaForm'])->name('alterar-senha');
    Route::post('/alterar-senha', [AuthController::class, 'updatePassword'])->name('alterar-senha.update');

    // Notificações do usuário
    Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/ler-todas', [NotificacaoController::class, 'marcarTodasLidas'])->name('notificacoes.ler-todas');
    Route::delete('/notificacoes/excluir-todas', [NotificacaoController::class, 'destroyAll'])->name('notificacoes.excluir-todas');
    Route::post('/notificacoes/{id}/ler', [NotificacaoController::class, 'marcarLida'])->name('notificacoes.ler');
    Route::delete('/notificacoes/{id}', [NotificacaoController::class, 'destroy'])->name('notificacoes.destroy');
});

Route::get('/login', function () {
    return redirect()->route('signin');
});

Route::get('/signup', function () {
    return erp_view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// Execução de tarefas agendadas via URL (para servidor sem cron). Token em .env: CRON_SECRET_TOKEN
Route::get('/run-tarefas', RunTarefasController::class)->name('run-tarefas');
Route::get('/run-tarefas-interno', RunTarefasInternoController::class)->name('run-tarefas-interno')->middleware('auth');

// ui elements pages
Route::get('/alerts', function () {
    return erp_view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return erp_view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return erp_view('pages.ui-elements.badges', ['title' => 'Badges']);
})->name('badges');

Route::get('/buttons', function () {
    return erp_view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/image', function () {
    return erp_view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return erp_view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view-dashboard');
    Route::get('/audit-cancelar-excluir', [AuditCancelExcluirController::class, 'index'])->name('audit-cancelar-excluir.index')->middleware('admin');
    Route::get('/audit-login', [AuditLoginController::class, 'index'])->name('audit-login.index')->middleware('admin');
    Route::post('/audit-login/clear', [AuditLoginController::class, 'clear'])->name('audit-login.clear')->middleware('admin');
    Route::get('/audit-acesso-clientes', [AuditAcessoClienteController::class, 'index'])->name('audit-acesso-clientes.index')->middleware('admin');
    Route::post('/audit-acesso-clientes/clear', [AuditAcessoClienteController::class, 'clear'])->name('audit-acesso-clientes.clear')->middleware('admin');
    Route::get('/audit-financeiro', [AuditFinanceiroController::class, 'index'])->name('audit-financeiro.index')->middleware('admin');
    Route::resource('users', UserController::class)->middleware('permission:manage-users');
    Route::post('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore')->middleware('permission:manage-users');
    Route::resource('roles', RoleController::class)->middleware('permission:manage-roles');
    Route::resource('permissions', PermissionController::class)->middleware('permission:manage-permissions');
    Route::resource('categorias-documentos', CategoriaDocumentoController::class)->middleware('admin');
});

// Clientes Routes — controller exige view/create/edit/delete-clients
Route::prefix('clientes')->name('clientes.')->middleware('operational')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::post('/bulk-destroy', [ClienteController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [ClienteController::class, 'export'])->name('export');
    Route::get('/export-pdf', [ClienteController::class, 'exportPdf'])->name('export-pdf');
    Route::get('/create', [ClienteController::class, 'create'])->name('create');
    Route::post('/', [ClienteController::class, 'store'])->name('store');
    Route::post('/quick-store', [ClienteController::class, 'quickStore'])->name('quick-store');
    Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::patch('/{cliente}', [ClienteController::class, 'update'])->name('update');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');

    // Documentos
    Route::get('/documentos/{documento}/download', [ClienteController::class, 'downloadDocumento'])->name('documentos.download');
    Route::delete('/documentos/{documento}', [ClienteController::class, 'deletarDocumento'])->name('documentos.destroy');
});

// Produtos Routes
Route::prefix('produtos')->name('produtos.')->middleware('operational')->group(function () {
    Route::get('/', [ProdutoController::class, 'index'])->name('index');
    Route::post('/bulk-destroy', [ProdutoController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [ProdutoController::class, 'export'])->name('export');
    Route::get('/export-pdf', [ProdutoController::class, 'exportPdf'])->name('export-pdf');
    Route::get('/create', [ProdutoController::class, 'create'])->name('create');
    Route::post('/', [ProdutoController::class, 'store'])->name('store');
    Route::get('/imagem/{produtoImagem}', [ProdutoController::class, 'imagem'])->name('imagem');
    Route::get('/{produto}/edit', [ProdutoController::class, 'edit'])->name('edit');
    Route::put('/{produto}', [ProdutoController::class, 'update'])->name('update');
    Route::patch('/{produto}', [ProdutoController::class, 'update'])->name('update');
    Route::get('/{produto}', [ProdutoController::class, 'show'])->name('show');
    Route::delete('/{produto}', [ProdutoController::class, 'destroy'])->name('destroy');
});

// Serviços Routes
Route::prefix('servicos')->name('servicos.')->middleware('operational')->group(function () {
    Route::get('/', [ServicoController::class, 'index'])->name('index');
    Route::post('/bulk-destroy', [ServicoController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [ServicoController::class, 'export'])->name('export');
    Route::get('/export-pdf', [ServicoController::class, 'exportPdf'])->name('export-pdf');
    Route::get('/create', [ServicoController::class, 'create'])->name('create');
    Route::post('/', [ServicoController::class, 'store'])->name('store');
    Route::post('/categorias', [CategoriaServicoController::class, 'store'])->name('categorias.store');
    Route::get('/imagem/{servicoImagem}', [ServicoController::class, 'imagem'])->name('imagem');
    Route::get('/{servico}/edit', [ServicoController::class, 'edit'])->name('edit');
    Route::put('/{servico}', [ServicoController::class, 'update'])->name('update');
    Route::patch('/{servico}', [ServicoController::class, 'update'])->name('update');
    Route::get('/{servico}', [ServicoController::class, 'show'])->name('show');
    Route::delete('/{servico}', [ServicoController::class, 'destroy'])->name('destroy');
});

// Checklist Models Routes — exige view-os-history
Route::prefix('checklist-models')->name('checklist-models.')->middleware(['operational', 'permission:view-os-history'])->group(function () {
    Route::get('/', [ChecklistModelController::class, 'index'])->name('index');
    Route::get('/create', [ChecklistModelController::class, 'create'])->name('create');
    Route::post('/', [ChecklistModelController::class, 'store'])->name('store');
    Route::get('/{checklistModel}', [ChecklistModelController::class, 'show'])->name('show');
    Route::get('/{checklistModel}/edit', [ChecklistModelController::class, 'edit'])->name('edit');
    Route::put('/{checklistModel}', [ChecklistModelController::class, 'update'])->name('update');
    Route::delete('/{checklistModel}', [ChecklistModelController::class, 'destroy'])->name('destroy');
});

Route::prefix('categorias-servicos')->name('categorias-servicos.')->middleware(['operational', 'permission:view-services'])->group(function () {
    Route::get('/', [CategoriaServicoController::class, 'index'])->name('index');
    Route::get('/create', [CategoriaServicoController::class, 'create'])->name('create');
    Route::post('/', [CategoriaServicoController::class, 'store'])->name('store');
    Route::get('/{categoriaServico}/edit', [CategoriaServicoController::class, 'edit'])->name('edit');
    Route::put('/{categoriaServico}', [CategoriaServicoController::class, 'update'])->name('update');
    Route::patch('/{categoriaServico}/status', [CategoriaServicoController::class, 'updateStatus'])->name('status');
    Route::patch('/{categoriaServico}', [CategoriaServicoController::class, 'update'])->name('update');
    Route::delete('/{categoriaServico}', [CategoriaServicoController::class, 'destroy'])->name('destroy');
});

// Fornecedores Routes — exige view-products
Route::prefix('fornecedores')->name('fornecedores.')->middleware(['operational', 'permission:view-products'])->group(function () {
    Route::get('/', [FornecedorController::class, 'index'])->name('index');
    Route::get('/create', [FornecedorController::class, 'create'])->name('create');
    Route::post('/', [FornecedorController::class, 'store'])->name('store');
    Route::get('/{fornecedor}/edit', [FornecedorController::class, 'edit'])->name('edit');
    Route::put('/{fornecedor}', [FornecedorController::class, 'update'])->name('update');
    Route::patch('/{fornecedor}', [FornecedorController::class, 'update'])->name('update');
    Route::delete('/{fornecedor}', [FornecedorController::class, 'destroy'])->name('destroy');
});

// Categorias de Produtos Routes — exige view-products
Route::prefix('categorias-produtos')->name('categorias-produtos.')->middleware(['operational', 'permission:view-products'])->group(function () {
    Route::get('/', [CategoriaProdutoController::class, 'index'])->name('index');
    Route::get('/create', [CategoriaProdutoController::class, 'create'])->name('create');
    Route::post('/', [CategoriaProdutoController::class, 'store'])->name('store');
    Route::get('/{categoriaProduto}/edit', [CategoriaProdutoController::class, 'edit'])->name('edit');
    Route::put('/{categoriaProduto}', [CategoriaProdutoController::class, 'update'])->name('update');
    Route::patch('/{categoriaProduto}', [CategoriaProdutoController::class, 'update'])->name('update');
    Route::delete('/{categoriaProduto}', [CategoriaProdutoController::class, 'destroy'])->name('destroy');
});

// Depósitos Routes
Route::prefix('depositos')->name('depositos.')->middleware('admin')->group(function () {
    Route::get('/', [DepositoController::class, 'index'])->name('index');
    Route::get('/create', [DepositoController::class, 'create'])->name('create');
    Route::post('/', [DepositoController::class, 'store'])->name('store');
    Route::get('/{deposito}/edit', [DepositoController::class, 'edit'])->name('edit');
    Route::put('/{deposito}', [DepositoController::class, 'update'])->name('update');
    Route::patch('/{deposito}', [DepositoController::class, 'update'])->name('update');
    Route::delete('/{deposito}', [DepositoController::class, 'destroy'])->name('destroy');
});

// Configurações do Sistema (operacional; Limites de Desconto só admin)
Route::prefix('configuracoes-sistema')->name('configuracoes-sistema.')->middleware('operational')->group(function () {
    Route::get('/', [ConfiguracoesSistemaController::class, 'index'])->name('index');
    Route::get('/empresa', [ConfiguracoesSistemaController::class, 'empresa'])->name('empresa');
    Route::put('/empresa', [ConfiguracoesSistemaController::class, 'atualizarEmpresa'])->name('empresa.update');

    // Tarefas agendadas (executar ao acessar o sistema, sem cron) — apenas admin
    Route::get('/tarefas-agendadas', [ConfiguracoesSistemaController::class, 'tarefasAgendadas'])->name('tarefas-agendadas')->middleware('admin');
    Route::put('/tarefas-agendadas', [ConfiguracoesSistemaController::class, 'atualizarTarefasAgendadas'])->name('tarefas-agendadas.update')->middleware('admin');
    Route::post('/tarefas-agendadas/executar-agora', [ConfiguracoesSistemaController::class, 'executarTarefasAgora'])->name('tarefas-agendadas.executar-agora')->middleware('admin');

    // Limites de Desconto (apenas admin pode acessar)
    Route::get('/limites-desconto', [LimiteDescontoController::class, 'edit'])->name('limites-desconto.edit')->middleware('admin');
    Route::put('/limites-desconto', [LimiteDescontoController::class, 'update'])->name('limites-desconto.update')->middleware('admin');

    // Produtos e Estoque (apenas admin pode acessar)
    Route::get('/produtos-estoque', [ProdutosEstoqueController::class, 'edit'])->name('produtos-estoque.edit')->middleware('admin');
    Route::put('/produtos-estoque', [ProdutosEstoqueController::class, 'update'])->name('produtos-estoque.update')->middleware('admin');

    // Plano de Contas - lançamentos do encerramento de OS
    Route::get('/plano-conta-os', [ConfiguracoesSistemaController::class, 'planoContaOsEncerramento'])->name('plano-conta-os');
    Route::put('/plano-conta-os', [ConfiguracoesSistemaController::class, 'atualizarPlanoContaOsEncerramento'])->name('plano-conta-os.update');

    // Configurações de Pedidos de Venda
    Route::get('/pedidos-venda', [PedidosVendaConfigController::class, 'edit'])->name('pedidos-venda.edit');
    Route::put('/pedidos-venda', [PedidosVendaConfigController::class, 'update'])->name('pedidos-venda.update');

    // Numeração das propostas comerciais (grupo; versões partilham o mesmo código)
    Route::get('/propostas-comerciais', [PropostasComerciaisConfigController::class, 'edit'])->name('propostas-comerciais.edit');
    Route::put('/propostas-comerciais', [PropostasComerciaisConfigController::class, 'update'])->name('propostas-comerciais.update');
    Route::get('/numeracao-os', [ConfiguracoesSistemaController::class, 'numeracaoOs'])->name('numeracao-os')->middleware('admin');
    Route::put('/numeracao-os', [ConfiguracoesSistemaController::class, 'atualizarNumeracaoOs'])->name('numeracao-os.update')->middleware('admin');

    // Notificações de OS (e-mail, WhatsApp)
    Route::get('/notificacoes-os', [NotificacoesOsController::class, 'index'])->name('notificacoes-os.index');
    Route::put('/notificacoes-os', [NotificacoesOsController::class, 'update'])->name('notificacoes-os.update');
    Route::post('/notificacoes-os/test-smtp', [NotificacoesOsController::class, 'testSmtp'])->name('notificacoes-os.test-smtp');

    // Fila de e-mails e notificações
    Route::get('/fila-notificacoes', [FilaNotificacoesController::class, 'index'])->name('fila-notificacoes.index');
    Route::delete('/fila-notificacoes/{envio}', [FilaNotificacoesController::class, 'destroy'])->name('fila-notificacoes.destroy');

    // Status das Ordens de Serviço
    Route::get('/status-os', [StatusOsController::class, 'index'])->name('status-os.index');
    Route::get('/status-os/create', [StatusOsController::class, 'create'])->name('status-os.create');
    Route::post('/status-os', [StatusOsController::class, 'store'])->name('status-os.store');
    Route::get('/status-os/{statusOs}/edit', [StatusOsController::class, 'edit'])->name('status-os.edit');
    Route::put('/status-os/{statusOs}', [StatusOsController::class, 'update'])->name('status-os.update');
    Route::delete('/status-os/{statusOs}', [StatusOsController::class, 'destroy'])->name('status-os.destroy');

    // Plugins (admin ou permissão manage-plugins) — rotas genéricas; cada plugin registra suas próprias rotas de config
    Route::middleware('permission:manage-plugins')->group(function () {
        Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');
        Route::get('/plugins/{slug}', [PluginController::class, 'show'])->name('plugins.show');
        Route::post('/plugins/upload', [PluginController::class, 'upload'])->name('plugins.upload');
        Route::post('/plugins/bulk-action', [PluginController::class, 'bulkAction'])->name('plugins.bulk-action');
        Route::post('/plugins/{slug}/activate', [PluginController::class, 'activate'])->name('plugins.activate');
        Route::post('/plugins/{slug}/deactivate', [PluginController::class, 'deactivate'])->name('plugins.deactivate');
        Route::delete('/plugins/{slug}', [PluginController::class, 'destroy'])->name('plugins.destroy');
    });

    // Utilidades — exportar/importar completo, banco, anexos, zerar fábrica (apenas admin)
    Route::middleware('admin')->group(function () {
        Route::get('/utilidades', [UtilidadesController::class, 'index'])->name('utilidades.index');
        Route::get('/utilidades/exportar/{tipo}', [UtilidadesController::class, 'export'])->name('utilidades.export');
        Route::get('/utilidades/exportar-partes/{set}/{arquivo}', [UtilidadesController::class, 'downloadExportPart'])->name('utilidades.export-part.download');
        Route::post('/utilidades/importar', [UtilidadesController::class, 'importProcess'])->name('utilidades.import');
        Route::post('/utilidades/zerar-fabrica', [UtilidadesController::class, 'zerarFabrica'])->name('utilidades.zerar-fabrica');
        Route::post('/utilidades/restaurar-backup', [UtilidadesController::class, 'restaurarBackup'])->name('utilidades.restaurar-backup');
        Route::post('/utilidades/excluir-backup', [UtilidadesController::class, 'excluirBackup'])->name('utilidades.excluir-backup');
    });

    // Termos de Garantia
    Route::get('/impressao-os', [ConfiguracoesSistemaController::class, 'impressaoOrdemServico'])->name('impressao-os');
    Route::put('/impressao-os', [ConfiguracoesSistemaController::class, 'atualizarImpressaoOrdemServico'])->name('impressao-os.update');

    Route::get('/termos-garantia', [TermoGarantiaController::class, 'index'])->name('termos-garantia.index');
    Route::get('/termos-garantia/create', [TermoGarantiaController::class, 'create'])->name('termos-garantia.create');
    Route::post('/termos-garantia', [TermoGarantiaController::class, 'store'])->name('termos-garantia.store');
    Route::get('/termos-garantia/{termoGarantia}/edit', [TermoGarantiaController::class, 'edit'])->name('termos-garantia.edit');
    Route::put('/termos-garantia/{termoGarantia}', [TermoGarantiaController::class, 'update'])->name('termos-garantia.update');
    Route::delete('/termos-garantia/{termoGarantia}', [TermoGarantiaController::class, 'destroy'])->name('termos-garantia.destroy');
});

// Tags
Route::prefix('tags')->name('tags.')->middleware('operational')->group(function () {
    Route::get('/', [TagController::class, 'index'])->name('index');
    Route::get('/create', [TagController::class, 'create'])->name('create');
    Route::post('/', [TagController::class, 'store'])->name('store');
    Route::get('/{tag}/edit', [TagController::class, 'edit'])->name('edit');
    Route::put('/{tag}', [TagController::class, 'update'])->name('update');
    Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
});

// Grupos Econômicos Routes — exige view-clients
Route::prefix('grupos-economicos')->name('grupos-economicos.')->middleware(['operational', 'permission:view-clients'])->group(function () {
    Route::get('/', [GrupoEconomicoController::class, 'index'])->name('index');
    Route::get('/create', [GrupoEconomicoController::class, 'create'])->name('create');
    Route::post('/', [GrupoEconomicoController::class, 'store'])->name('store');
    Route::get('/{grupoEconomico}/edit', [GrupoEconomicoController::class, 'edit'])->name('edit');
    Route::put('/{grupoEconomico}', [GrupoEconomicoController::class, 'update'])->name('update');
    Route::patch('/{grupoEconomico}', [GrupoEconomicoController::class, 'update'])->name('update');
    Route::delete('/{grupoEconomico}', [GrupoEconomicoController::class, 'destroy'])->name('destroy');
});

// Pedidos de Venda
Route::prefix('pedidos-venda')->name('pedidos-venda.')->middleware(['operational'])->group(function () {
    Route::get('/', [PedidoVendaController::class, 'index'])->name('index');
    Route::get('/create', [PedidoVendaController::class, 'create'])->name('create');
    Route::post('/', [PedidoVendaController::class, 'store'])->name('store');
    Route::get('/buscar-produto', [PedidoVendaController::class, 'buscarProduto'])->name('buscar-produto');
    Route::get('/listar-orcamentos', [PedidoVendaController::class, 'listarOrcamentos'])->name('listar-orcamentos');
    Route::get('/{pedidosVenda}/itens-importacao', [PedidoVendaController::class, 'itensImportacao'])->name('itens-importacao');
    Route::get('/{pedidosVenda}/edit', [PedidoVendaController::class, 'edit'])->name('edit');
    Route::get('/{pedidosVenda}/pdf', [PedidoVendaController::class, 'exportPdf'])->name('pdf');
    Route::get('/{pedidosVenda}', [PedidoVendaController::class, 'show'])->name('show');
    Route::put('/{pedidosVenda}', [PedidoVendaController::class, 'update'])->name('update');
    Route::patch('/{pedidosVenda}', [PedidoVendaController::class, 'update']);
    Route::delete('/{pedidosVenda}', [PedidoVendaController::class, 'destroy'])->name('destroy');
    Route::post('/{pedidosVenda}/confirmar', [PedidoVendaController::class, 'confirmar'])->name('confirmar');
    Route::post('/{pedidosVenda}/faturar', [PedidoVendaController::class, 'faturar'])->name('faturar');
    Route::post('/{pedidosVenda}/entregar', [PedidoVendaController::class, 'entregar'])->name('entregar');
    Route::post('/{pedidosVenda}/cancelar', [PedidoVendaController::class, 'cancelar'])->name('cancelar');
    Route::post('/{pedidosVenda}/duplicar', [PedidoVendaController::class, 'duplicar'])->name('duplicar');
});

// Propostas comerciais (orçamento formal, versões — diferente do PDV)
Route::prefix('propostas-comerciais')->name('propostas-comerciais.')->middleware(['operational'])->group(function () {
    Route::get('/', [PropostaComercialController::class, 'index'])->name('index');
    Route::get('/create', [PropostaComercialController::class, 'create'])->name('create');
    Route::post('/', [PropostaComercialController::class, 'store'])->name('store');
    Route::get('/buscar-produto', [PropostaComercialController::class, 'buscarProduto'])->name('buscar-produto');

    Route::prefix('modelos-documento')->name('modelos-documento.')->group(function () {
        Route::get('/', [PropostaDocumentoModeloController::class, 'index'])->name('index');
        Route::get('/create', [PropostaDocumentoModeloController::class, 'create'])->name('create');
        Route::post('/', [PropostaDocumentoModeloController::class, 'store'])->name('store');
        Route::get('/{propostaDocumentoModelo}/edit', [PropostaDocumentoModeloController::class, 'edit'])->name('edit');
        Route::put('/{propostaDocumentoModelo}', [PropostaDocumentoModeloController::class, 'update'])->name('update');
        Route::delete('/{propostaDocumentoModelo}', [PropostaDocumentoModeloController::class, 'destroy'])->name('destroy');
    });

    Route::post('/{propostaComercial}/nova-versao', [PropostaComercialController::class, 'novaVersao'])->name('nova-versao');
    Route::post('/{propostaComercial}/converter-pedido', [PropostaComercialController::class, 'converterPedido'])->name('converter-pedido');
    Route::get('/{propostaComercial}/pdf', [PropostaComercialController::class, 'exportPdf'])->name('pdf');
    Route::get('/{propostaComercial}/impressao', [PropostaComercialController::class, 'verImpressaoPadrao'])->name('impressao');
    Route::get('/{propostaComercial}/docx', [PropostaComercialController::class, 'exportDocx'])->name('docx');
    Route::get('/{propostaComercial}/modelo-html', [PropostaComercialController::class, 'verModeloHtml'])->name('modelo-html');
    Route::get('/{propostaComercial}/edit', [PropostaComercialController::class, 'edit'])->name('edit');
    Route::put('/{propostaComercial}', [PropostaComercialController::class, 'update'])->name('update');
    Route::patch('/{propostaComercial}', [PropostaComercialController::class, 'update']);
    Route::patch('/{propostaComercial}/cliente', [PropostaComercialController::class, 'updateCliente'])->name('cliente');
    Route::delete('/{propostaComercial}', [PropostaComercialController::class, 'destroy'])->name('destroy');
    Route::get('/{propostaComercial}', [PropostaComercialController::class, 'show'])->name('show');
});

// Ordens de Serviço Routes — exige view-os-history (ou is_admin)
Route::prefix('ordens-servico')->name('ordens-servico.')->middleware(['operational', 'permission:view-os-history'])->group(function () {
    Route::get('/', [OrdemServicoController::class, 'index'])->name('index');
    Route::post('/bulk-status-prioridade', [OrdemServicoController::class, 'bulkStatusPrioridade'])->name('bulk-status-prioridade');
    Route::post('/bulk-destroy', [OrdemServicoController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [OrdemServicoController::class, 'export'])->name('export');
    Route::get('/export-pdf', [OrdemServicoController::class, 'exportPdf'])->name('export-pdf');
    Route::get('/create', [OrdemServicoController::class, 'create'])->name('create');
    Route::get('/{ordemServico}/print', [OrdemServicoController::class, 'print'])->name('print');
    Route::post('/{ordemServico}/enviar-email', [OrdemServicoController::class, 'enviarEmail'])->name('enviar-email');
    Route::post('/{ordemServico}/enviar-whatsapp', [OrdemServicoController::class, 'enviarWhatsApp'])->name('enviar-whatsapp');
    Route::get('/{ordemServico}/comprovante', [OrdemServicoController::class, 'comprovante'])->name('comprovante');
    Route::get('/{ordemServico}/equipamento-etiqueta', [OrdemServicoController::class, 'equipamentoEtiqueta'])->name('equipamento-etiqueta');
    Route::post('/', [OrdemServicoController::class, 'store'])->name('store');
    Route::post('/{ordemServico}/encerrar', [OrdemServicoController::class, 'encerrar'])->name('encerrar');
    Route::post('/{ordemServico}/checklist', [OrdemServicoController::class, 'salvarChecklist'])->name('checklist.store');
    Route::delete('/{ordemServico}/checklist/{checklistAnswer}', [OrdemServicoController::class, 'removerChecklist'])->name('checklist.destroy');
    Route::get('/{ordemServico}/edit', [OrdemServicoController::class, 'edit'])->name('edit');
    Route::patch('/{ordemServico}/tags', [OrdemServicoController::class, 'updateTags'])->name('tags.update');
    Route::put('/{ordemServico}', [OrdemServicoController::class, 'update'])->name('update');
    Route::patch('/{ordemServico}', [OrdemServicoController::class, 'update'])->name('update');
    Route::patch('/{ordemServico}/equipamento', [OrdemServicoController::class, 'updateEquipamento'])->name('equipamento');
    Route::patch('/{ordemServico}/cliente', [OrdemServicoController::class, 'updateCliente'])->name('cliente');
    Route::patch('/{ordemServico}/status', [OrdemServicoController::class, 'updateStatus'])->name('status');
    Route::post('/{ordemServico}/duplicate', [OrdemServicoController::class, 'duplicate'])->name('duplicate');
    Route::get('/{ordemServico}/notificacao-preview', [OrdemServicoController::class, 'notificacaoPreview'])->name('notificacao-preview');
    Route::post('/{ordemServico}/adiantamentos', [OrdemServicoController::class, 'storeAdiantamento'])->name('adiantamentos.store');
    Route::post('/{ordemServico}/adiantamentos/estornar/{contaReceber}', [OrdemServicoController::class, 'estornarAdiantamento'])->name('adiantamentos.estornar');
    Route::post('/{ordemServico}/anexos', [OrdemServicoController::class, 'storeAnexos'])->name('anexos.store');
    Route::patch('/{ordemServico}/anexos/{anexo}', [OrdemServicoController::class, 'updateAnexo'])->name('anexos.update');
    Route::delete('/{ordemServico}/anexos/{anexo}', [OrdemServicoController::class, 'destroyAnexo'])->name('anexos.destroy');
    Route::get('/{ordemServico}', [OrdemServicoController::class, 'show'])->name('show');
    Route::delete('/{ordemServico}', [OrdemServicoController::class, 'destroy'])->name('destroy');
});

// Anexo de OS - servir arquivo (evita problemas com storage:link no Windows)
Route::get('/anexo-os/{anexo}/file', [OrdemServicoController::class, 'servirAnexo'])
    ->middleware('operational')
    ->name('ordens-servico.anexos.file');

// API para busca de clientes (autocomplete)
Route::get('/api/ordens-servico/buscar-clientes', [OrdemServicoController::class, 'buscarClientes'])
    ->middleware('operational')
    ->name('api.ordens-servico.buscar-clientes');

// Equipamento - página pública via QR Code (token ou id para compatibilidade)
Route::get('/e/{token}', [EquipamentoPublicoController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('equipamento.publico');

// Assinaturas (cliente - público)
Route::get('/assinaturas/cliente/{token}', [OrdemServicoAssinaturaController::class, 'showCliente'])
    ->name('assinaturas.cliente');
Route::post('/assinaturas/cliente/{token}', [OrdemServicoAssinaturaController::class, 'storeCliente'])
    ->name('assinaturas.cliente.store');
Route::post('/assinaturas/event', [OrdemServicoAssinaturaController::class, 'logEventPublic'])
    ->name('assinaturas.event');
// Imagem da assinatura (acesso via token na URL ou usuário autenticado; não expõe arquivo direto)
Route::get('/assinaturas/imagem/{assinatura}', [OrdemServicoAssinaturaController::class, 'servirImagem'])
    ->name('assinaturas.imagem');

// Assinaturas (técnico e gestão - operacional)
Route::prefix('assinaturas')->name('assinaturas.')->middleware('operational')->group(function () {
    Route::get('/os/{ordemServico}/tecnico', [OrdemServicoAssinaturaController::class, 'showTecnico'])->name('tecnico');
    Route::post('/os/{ordemServico}/tecnico', [OrdemServicoAssinaturaController::class, 'storeTecnico'])->name('tecnico.store');
    Route::post('/os/{ordemServico}/tecnico/usar-salva', [OrdemServicoAssinaturaController::class, 'aplicarAssinaturaUsuario'])->name('tecnico.aplicar');
    Route::delete('/os/{ordemServico}/tecnico', [OrdemServicoAssinaturaController::class, 'deleteAssinaturaTecnico'])->name('tecnico.delete');
    Route::delete('/os/{ordemServico}/cliente', [OrdemServicoAssinaturaController::class, 'deleteAssinaturaCliente'])->name('cliente.delete');
    Route::post('/os/{ordemServico}/cliente/token', [OrdemServicoAssinaturaController::class, 'gerarTokenCliente'])->name('cliente.token');
    Route::delete('/usuario', [OrdemServicoAssinaturaController::class, 'deleteAssinaturaUsuario'])->name('usuario.delete');
    Route::get('/documento/{id}/download', [OrdemServicoAssinaturaController::class, 'downloadDocumento'])->name('documento.download');
    Route::get('/documento/{id}/verificar', [OrdemServicoAssinaturaController::class, 'verificarIntegridadeDocumento'])->name('documento.verificar');
    Route::delete('/documento/{id}', [OrdemServicoAssinaturaController::class, 'deleteDocumento'])->name('documento.delete');
});

Route::prefix('equipamentos')->name('equipamentos.')->middleware('operational')->group(function () {
    Route::post('/', [EquipamentoController::class, 'store'])->name('store');
    Route::get('/{equipamento}/edit', [EquipamentoController::class, 'edit'])->name('edit');
    Route::put('/{equipamento}', [EquipamentoController::class, 'update'])->name('update');
});

// Financeiro Routes — exige access-financeiro (ou is_admin)
Route::prefix('financeiro')->name('financeiro.')->middleware(['operational', 'permission:access-financeiro'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Financeiro\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/categorias-financeiras/opcoes', [FinanceiroCategoriaTagController::class, 'categoriasOpcoes'])->name('categorias-financeiras.opcoes');
    Route::get('/categorias-financeiras', [FinanceiroCategoriaTagController::class, 'categoriasIndex'])->name('categorias-financeiras.index');
    Route::post('/categorias-financeiras', [FinanceiroCategoriaTagController::class, 'categoriasStore'])->name('categorias-financeiras.store');
    Route::put('/categorias-financeiras/{categoriaFinanceira}', [FinanceiroCategoriaTagController::class, 'categoriasUpdate'])->name('categorias-financeiras.update');
    Route::delete('/categorias-financeiras/{categoriaFinanceira}', [FinanceiroCategoriaTagController::class, 'categoriasDestroy'])->name('categorias-financeiras.destroy');
    Route::get('/titulos-tags/autocomplete', [FinanceiroCategoriaTagController::class, 'tagsAutocomplete'])->name('titulos-tags.autocomplete');
    Route::post('/titulos-tags/rapido', [FinanceiroCategoriaTagController::class, 'tagsStoreRapido'])->name('titulos-tags.rapido');

    // Contas a Receber
    Route::get('/contas-receber', [ContaReceberController::class, 'index'])->name('contas-receber.index');
    Route::get('/contas-receber/lancamentos-recorrentes-cliente', [ContaReceberController::class, 'lancamentosRecorrentesCliente'])->name('contas-receber.lancamentos-recorrentes-cliente');
    Route::get('/contas-receber/pdf', [ContaReceberController::class, 'exportarPdf'])->name('contas-receber.pdf');
    Route::get('/contas-receber/create', [ContaReceberController::class, 'create'])->name('contas-receber.create');
    Route::post('/contas-receber', [ContaReceberController::class, 'store'])->name('contas-receber.store');
    Route::post('/contas-receber/preview-taxa-cartao', [ContaReceberController::class, 'previewTaxaCartao'])->name('contas-receber.preview-taxa-cartao');
    // Rotas de ações em massa (devem vir antes das rotas com parâmetros)
    Route::post('/contas-receber/baixar-massa', [ContaReceberController::class, 'baixarMassa'])->name('contas-receber.baixar-massa');
    Route::post('/contas-receber/cancelar-massa', [ContaReceberController::class, 'cancelarMassa'])->name('contas-receber.cancelar-massa');
    Route::delete('/contas-receber/excluir-massa', [ContaReceberController::class, 'excluirMassa'])->name('contas-receber.excluir-massa');
    Route::post('/contas-receber/agrupar', [ContaReceberController::class, 'agrupar'])->name('contas-receber.agrupar');
    Route::get('/contas-receber/anexos/{anexo}/file', [ContaReceberController::class, 'downloadAnexo'])->name('contas-receber.anexos.file');
    // Rotas com parâmetros (devem vir depois das rotas específicas)
    Route::get('/contas-receber/{contaReceber}/recibo', [ContaReceberController::class, 'recibo'])->name('contas-receber.recibo');
    Route::get('/contas-receber/{contaReceber}/edit', [ContaReceberController::class, 'edit'])->name('contas-receber.edit');
    Route::put('/contas-receber/{contaReceber}', [ContaReceberController::class, 'update'])->name('contas-receber.update');
    Route::post('/contas-receber/{contaReceber}', [ContaReceberController::class, 'update'])->name('contas-receber.update.post');
    Route::delete('/contas-receber/{contaReceber}', [ContaReceberController::class, 'destroy'])->name('contas-receber.destroy');
    Route::post('/contas-receber/{contaReceber}/anexos', [ContaReceberController::class, 'storeAnexos'])->name('contas-receber.anexos.store');
    Route::delete('/contas-receber/{contaReceber}/anexos/{anexo}', [ContaReceberController::class, 'destroyAnexo'])->name('contas-receber.anexos.destroy');
    Route::post('/contas-receber/{contaReceber}/anexos/{anexo}/excluir', [ContaReceberController::class, 'destroyAnexo'])->name('contas-receber.anexos.destroy.post');
    Route::post('/contas-receber/{contaReceber}/baixar', [ContaReceberController::class, 'baixar'])->name('contas-receber.baixar');
    Route::post('/contas-receber/{contaReceber}/desmembrar', [ContaReceberController::class, 'desmembrar'])->name('contas-receber.desmembrar');
    Route::post('/contas-receber/{contaReceber}/desagrupar', [ContaReceberController::class, 'desagrupar'])->name('contas-receber.desagrupar');
    Route::post('/contas-receber/{contaReceber}/cancelar', [ContaReceberController::class, 'cancelar'])->name('contas-receber.cancelar');
    Route::post('/contas-receber/{contaReceber}/estornar', [ContaReceberController::class, 'estornar'])->name('contas-receber.estornar');
    Route::post('/contas-receber/{contaReceber}/baixas/{baixa}/corrigir-datas', [ContaReceberController::class, 'corrigirDatasBaixa'])
        ->name('contas-receber.corrigir-datas-baixa')
        ->middleware('permission:financeiro.contas-receber.corrigir_datas_baixa');

    // Pagamentos Recorrentes (contas a receber)
    Route::get('/pagamentos-recorrentes', [PagamentoRecorrenteController::class, 'index'])->name('pagamentos-recorrentes.index');
    Route::get('/pagamentos-recorrentes/create', [PagamentoRecorrenteController::class, 'create'])->name('pagamentos-recorrentes.create');
    Route::post('/pagamentos-recorrentes', [PagamentoRecorrenteController::class, 'store'])->name('pagamentos-recorrentes.store');
    Route::get('/pagamentos-recorrentes/{pagamentoRecorrente}/edit', [PagamentoRecorrenteController::class, 'edit'])->name('pagamentos-recorrentes.edit');
    Route::put('/pagamentos-recorrentes/{pagamentoRecorrente}', [PagamentoRecorrenteController::class, 'update'])->name('pagamentos-recorrentes.update');
    Route::delete('/pagamentos-recorrentes/{pagamentoRecorrente}', [PagamentoRecorrenteController::class, 'destroy'])->name('pagamentos-recorrentes.destroy');

    // Testar recorrência e reajuste (ferramentas)
    Route::get('/tarefas-recorrentes', [TarefasRecorrentesController::class, 'index'])->name('tarefas-recorrentes.index');
    Route::post('/tarefas-recorrentes/gerar', [TarefasRecorrentesController::class, 'gerarRecorrentes'])->name('tarefas-recorrentes.gerar');
    Route::post('/tarefas-recorrentes/reajuste-simular', [TarefasRecorrentesController::class, 'reajusteSimular'])->name('tarefas-recorrentes.reajuste-simular');
    Route::post('/tarefas-recorrentes/reajuste-aplicar', [TarefasRecorrentesController::class, 'reajusteAplicar'])->name('tarefas-recorrentes.reajuste-aplicar');
    Route::post('/tarefas-recorrentes/testar-indice', [TarefasRecorrentesController::class, 'testarIndice'])->name('tarefas-recorrentes.testar-indice');

    // Contas a Pagar
    Route::get('/contas-pagar', [ContaPagarController::class, 'index'])->name('contas-pagar.index');
    Route::get('/contas-pagar/create', [ContaPagarController::class, 'create'])->name('contas-pagar.create');
    Route::get('/contas-pagar/pdf', [ContaPagarController::class, 'exportarPdf'])->name('contas-pagar.pdf');
    Route::get('/contas-pagar/lancamentos-recorrentes-recorrente', [ContaPagarController::class, 'lancamentosRecorrentesRecorrente'])->name('contas-pagar.lancamentos-recorrentes-recorrente');
    Route::post('/contas-pagar', [ContaPagarController::class, 'store'])->name('contas-pagar.store');
    Route::post('/contas-pagar/agrupar', [ContaPagarController::class, 'agrupar'])->name('contas-pagar.agrupar');
    Route::get('/contas-pagar/anexos/{anexo}/file', [ContaPagarController::class, 'downloadAnexo'])->name('contas-pagar.anexos.file');
    // Contas a Pagar (despesas recorrentes ficam na aba "Despesas recorrentes" do index)
    Route::get('/contas-pagar-recorrentes', fn () => redirect()->route('financeiro.contas-pagar.index', ['aba' => 'recorrentes']))->name('contas-pagar-recorrentes.index');
    Route::get('/contas-pagar-recorrentes/create', [ContaPagarRecorrenteController::class, 'create'])->name('contas-pagar-recorrentes.create');
    Route::post('/contas-pagar-recorrentes', [ContaPagarRecorrenteController::class, 'store'])->name('contas-pagar-recorrentes.store');
    Route::get('/contas-pagar-recorrentes/{contaPagarRecorrente}/edit', [ContaPagarRecorrenteController::class, 'edit'])->name('contas-pagar-recorrentes.edit');
    Route::put('/contas-pagar-recorrentes/{contaPagarRecorrente}', [ContaPagarRecorrenteController::class, 'update'])->name('contas-pagar-recorrentes.update');
    Route::delete('/contas-pagar-recorrentes/{contaPagarRecorrente}', [ContaPagarRecorrenteController::class, 'destroy'])->name('contas-pagar-recorrentes.destroy');
    Route::get('/contas-pagar/{contaPagar}/edit', [ContaPagarController::class, 'edit'])->name('contas-pagar.edit');
    Route::put('/contas-pagar/{contaPagar}', [ContaPagarController::class, 'update'])->name('contas-pagar.update');
    Route::post('/contas-pagar/{contaPagar}', [ContaPagarController::class, 'update'])->name('contas-pagar.update.post');
    Route::post('/contas-pagar/{contaPagar}/anexos', [ContaPagarController::class, 'storeAnexos'])->name('contas-pagar.anexos.store');
    Route::delete('/contas-pagar/{contaPagar}/anexos/{anexo}', [ContaPagarController::class, 'destroyAnexo'])->name('contas-pagar.anexos.destroy');
    Route::post('/contas-pagar/{contaPagar}/anexos/{anexo}/excluir', [ContaPagarController::class, 'destroyAnexo'])->name('contas-pagar.anexos.destroy.post');
    Route::post('/contas-pagar/{contaPagar}/baixar', [ContaPagarController::class, 'baixar'])->name('contas-pagar.baixar');
    Route::post('/contas-pagar/{contaPagar}/desmembrar', [ContaPagarController::class, 'desmembrar'])->name('contas-pagar.desmembrar');
    Route::post('/contas-pagar/{contaPagar}/desagrupar', [ContaPagarController::class, 'desagrupar'])->name('contas-pagar.desagrupar');
    Route::post('/contas-pagar/{contaPagar}/estornar', [ContaPagarController::class, 'estornar'])->name('contas-pagar.estornar');
    Route::post('/contas-pagar/{contaPagar}/baixas/{baixa}/corrigir-datas', [ContaPagarController::class, 'corrigirDatasBaixa'])
        ->name('contas-pagar.corrigir-datas-baixa')
        ->middleware('permission:financeiro.contas-pagar.corrigir_datas_baixa');
    Route::post('/contas-pagar/{contaPagar}/cancelar', [ContaPagarController::class, 'cancelar'])->name('contas-pagar.cancelar');
    Route::delete('/contas-pagar/{contaPagar}', [ContaPagarController::class, 'destroy'])->name('contas-pagar.destroy');

    // Movimentações
    Route::get('/movimentacoes', [MovimentacaoController::class, 'index'])->name('movimentacoes.index');
    Route::get('/movimentacoes/export', [MovimentacaoController::class, 'export'])->name('movimentacoes.export');
    Route::get('/movimentacoes/export-pdf', [MovimentacaoController::class, 'exportPdf'])->name('movimentacoes.export-pdf');
    Route::get('/movimentacoes/create', [MovimentacaoController::class, 'create'])->name('movimentacoes.create')->middleware('permission:financeiro.movimentacoes.create');
    Route::post('/movimentacoes', [MovimentacaoController::class, 'store'])->name('movimentacoes.store')->middleware('permission:financeiro.movimentacoes.create');
    Route::get('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'show'])->name('movimentacoes.show');
    Route::post('/movimentacoes/conciliar-lote', [MovimentacaoController::class, 'conciliarLote'])->name('movimentacoes.conciliar-lote');
    Route::post('/movimentacoes/{movimentacao}/conciliar', [MovimentacaoController::class, 'conciliar'])->name('movimentacoes.conciliar');
    Route::post('/movimentacoes/{movimentacao}/desconciliar', [MovimentacaoController::class, 'desconciliar'])->name('movimentacoes.desconciliar')->middleware('permission:financeiro.movimentacoes.desconciliar');

    // Transferência entre contas
    Route::get('/transferencias', [TransferenciaController::class, 'index'])->name('transferencias.index');
    Route::get('/transferencias/create', [TransferenciaController::class, 'create'])->name('transferencias.create');
    Route::post('/transferencias', [TransferenciaController::class, 'store'])->name('transferencias.store');
    Route::post('/transferencias/{movimentacao}/cancelar', [TransferenciaController::class, 'cancelar'])->name('transferencias.cancelar')->middleware('permission:financeiro.transferencias.cancelar');

    // Contas Bancárias
    Route::get('/contas-bancarias', [ContaBancariaController::class, 'index'])->name('contas-bancarias.index');
    Route::get('/contas-bancarias/create', [ContaBancariaController::class, 'create'])->name('contas-bancarias.create');
    Route::post('/contas-bancarias', [ContaBancariaController::class, 'store'])->name('contas-bancarias.store');
    Route::get('/contas-bancarias/{contaBancaria}/edit', [ContaBancariaController::class, 'edit'])->name('contas-bancarias.edit');
    Route::get('/contas-bancarias/{contaBancaria}/extrato', [ContaBancariaController::class, 'extrato'])->name('contas-bancarias.extrato');
    Route::put('/contas-bancarias/{contaBancaria}', [ContaBancariaController::class, 'update'])->name('contas-bancarias.update');

    // Formas de Pagamento
    Route::get('/formas-pagamento', [FormaPagamentoController::class, 'index'])->name('formas-pagamento.index');
    Route::get('/formas-pagamento/create', [FormaPagamentoController::class, 'create'])->name('formas-pagamento.create');
    Route::post('/formas-pagamento', [FormaPagamentoController::class, 'store'])->name('formas-pagamento.store');
    Route::get('/formas-pagamento/{formaPagamento}/edit', [FormaPagamentoController::class, 'edit'])->name('formas-pagamento.edit');
    Route::put('/formas-pagamento/{formaPagamento}', [FormaPagamentoController::class, 'update'])->name('formas-pagamento.update');

    // Plano de Contas
    Route::get('/plano-contas', [PlanoContaController::class, 'index'])->name('plano-contas.index');
    Route::get('/plano-contas/create', [PlanoContaController::class, 'create'])->name('plano-contas.create');
    Route::post('/plano-contas', [PlanoContaController::class, 'store'])->name('plano-contas.store');

    // Adquirentes
    Route::get('/adquirentes', [AdquirenteController::class, 'index'])->name('adquirentes.index');
    Route::get('/adquirentes/create', [AdquirenteController::class, 'create'])->name('adquirentes.create');
    Route::post('/adquirentes', [AdquirenteController::class, 'store'])->name('adquirentes.store');
    Route::get('/adquirentes/{adquirente}', [AdquirenteController::class, 'show'])->name('adquirentes.show');
    Route::get('/adquirentes/{adquirente}/edit', [AdquirenteController::class, 'edit'])->name('adquirentes.edit');
    Route::put('/adquirentes/{adquirente}', [AdquirenteController::class, 'update'])->name('adquirentes.update');
    Route::delete('/adquirentes/{adquirente}', [AdquirenteController::class, 'destroy'])->name('adquirentes.destroy');

    // Taxas de Adquirentes
    Route::get('/adquirentes/{adquirente}/taxas', [TaxaAdquirenteController::class, 'index'])->name('taxas.index');
    Route::get('/adquirentes/{adquirente}/taxas/create', [TaxaAdquirenteController::class, 'create'])->name('taxas.create');
    Route::post('/adquirentes/{adquirente}/taxas', [TaxaAdquirenteController::class, 'store'])->name('taxas.store');
    Route::get('/adquirentes/{adquirente}/taxas/{taxa}/edit', [TaxaAdquirenteController::class, 'edit'])->name('taxas.edit');
    Route::put('/adquirentes/{adquirente}/taxas/{taxa}', [TaxaAdquirenteController::class, 'update'])->name('taxas.update');
    Route::delete('/adquirentes/{adquirente}/taxas/{taxa}', [TaxaAdquirenteController::class, 'destroy'])->name('taxas.destroy');

    // Simulador
    Route::get('/simulador', [SimuladorController::class, 'index'])->name('simulador.index');
    Route::post('/simulador/simular', [SimuladorController::class, 'simular'])->name('simulador.simular');
    Route::post('/simulador/comparar', [SimuladorController::class, 'comparar'])->name('simulador.comparar');
    Route::get('/plano-contas/{planoConta}/edit', [PlanoContaController::class, 'edit'])->name('plano-contas.edit');
    Route::put('/plano-contas/{planoConta}', [PlanoContaController::class, 'update'])->name('plano-contas.update');

    // Relatórios
    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('/fluxo-caixa', [RelatorioController::class, 'fluxoCaixa'])->name('fluxo-caixa');
    Route::get('/fluxo-caixa/pdf', [RelatorioController::class, 'fluxoCaixaPdf'])->name('fluxo-caixa.pdf');
    Route::get('/relatorios/balancete', [RelatorioController::class, 'balancete'])->name('relatorios.balancete');
    Route::get('/relatorios/balancete/pdf', [RelatorioController::class, 'balancetePdf'])->name('relatorios.balancete.pdf');
    Route::get('/relatorios/razao', [RelatorioController::class, 'razao'])->name('relatorios.razao');
    Route::get('/relatorios/razao/pdf', [RelatorioController::class, 'razaoPdf'])->name('relatorios.razao.pdf');
    Route::get('/dre', [RelatorioController::class, 'dre'])->name('dre');
    Route::get('/dre/pdf', [RelatorioController::class, 'drePdf'])->name('dre.pdf');
    Route::get('/relatorios/contas-receber/pdf', [RelatorioController::class, 'contasReceberPdf'])->name('relatorios.contas-receber.pdf');
    Route::get('/relatorios/contas-pagar/pdf', [RelatorioController::class, 'contasPagarPdf'])->name('relatorios.contas-pagar.pdf');
    Route::get('/relatorios/movimentacoes/pdf', [RelatorioController::class, 'movimentacoesPdf'])->name('relatorios.movimentacoes.pdf');
    Route::get('/relatorios/receitas-despesas/pdf', [RelatorioController::class, 'receitasDespesasPdf'])->name('relatorios.receitas-despesas.pdf');
    Route::get('/relatorios/inadimplencia', [RelatorioController::class, 'inadimplencia'])->name('relatorios.inadimplencia');
    Route::get('/relatorios/inadimplencia/pdf', [RelatorioController::class, 'inadimplenciaPdf'])->name('relatorios.inadimplencia.pdf');
    Route::get('/relatorios/curva-abc-clientes', [RelatorioController::class, 'curvaAbcClientes'])->name('relatorios.curva-abc-clientes');
    Route::get('/relatorios/curva-abc-clientes/pdf', [RelatorioController::class, 'curvaAbcClientesPdf'])->name('relatorios.curva-abc-clientes.pdf');
    Route::get('/relatorios/mrr', [RelatorioController::class, 'mrr'])->name('relatorios.mrr');
    Route::get('/relatorios/mrr/pdf', [RelatorioController::class, 'mrrPdf'])->name('relatorios.mrr.pdf');
    Route::get('/relatorios/vendas-vendedor', [RelatorioController::class, 'vendasVendedor'])->name('relatorios.vendas-vendedor');
    Route::get('/relatorios/vendas-vendedor/pdf', [RelatorioController::class, 'vendasVendedorPdf'])->name('relatorios.vendas-vendedor.pdf');
    Route::get('/relatorios/vendas-servico-produto', [RelatorioController::class, 'vendasServicoProduto'])->name('relatorios.vendas-servico-produto');
    Route::get('/relatorios/vendas-servico-produto/pdf', [RelatorioController::class, 'vendasServicoProdutoPdf'])->name('relatorios.vendas-servico-produto.pdf');
    Route::get('/relatorios/estoque-posicao', [RelatorioController::class, 'estoquePosicao'])->name('relatorios.estoque-posicao');
    Route::get('/relatorios/estoque-posicao/pdf', [RelatorioController::class, 'estoquePosicaoPdf'])->name('relatorios.estoque-posicao.pdf');

    // Centros de Custo
    Route::get('/centros-custo', [CentroCustoController::class, 'index'])->name('centros-custo.index');
    Route::get('/centros-custo/create', [CentroCustoController::class, 'create'])->name('centros-custo.create');
    Route::post('/centros-custo', [CentroCustoController::class, 'store'])->name('centros-custo.store');
    Route::get('/centros-custo/{centroCusto}/edit', [CentroCustoController::class, 'edit'])->name('centros-custo.edit');
    Route::put('/centros-custo/{centroCusto}', [CentroCustoController::class, 'update'])->name('centros-custo.update');
    Route::delete('/centros-custo/{centroCusto}', [CentroCustoController::class, 'destroy'])->name('centros-custo.destroy');

    // Configurações Financeiro (juros, multa, carência)
    Route::get('/configuracoes', [ConfiguracoesFinanceiroController::class, 'index'])->name('configuracoes.index');
    Route::put('/configuracoes', [ConfiguracoesFinanceiroController::class, 'update'])->name('configuracoes.update');
});
