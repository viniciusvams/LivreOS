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

use App\Http\Controllers\Plugin\AreaClienteController;
use AreaCliente\ChamadoController;
use AreaCliente\AdminChamadoController;
use AreaCliente\SettingsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Assets do plugin (Jodit etc.) – acesso público para carregar CSS/JS
Route::middleware(['web'])->get('assets/{path}', function (string $path) {
    $path = str_replace(['..', '\\'], ['', '/'], $path);
    $base = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
    $fullPath = realpath($base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    $baseReal = realpath($base);
    if (!$baseReal || !$fullPath || !str_starts_with($fullPath, $baseReal) || !File::isFile($fullPath)) {
        abort(404);
    }
    $mime = match (pathinfo($fullPath, PATHINFO_EXTENSION)) {
        'js' => 'application/javascript',
        'css' => 'text/css',
        'map' => 'application/json',
        default => File::mimeType($fullPath),
    };
    return response()->file($fullPath, ['Content-Type' => $mime]);
})->where('path', '.*')->name('assets');

Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function () {
    Route::get('/', [AreaClienteController::class, 'index'])->name('index');
    Route::get('/exportar', [AreaClienteController::class, 'exportCsv'])->name('exportar');
    Route::get('/perfil', [AreaClienteController::class, 'perfil'])->name('perfil');
    Route::get('/ajuda', [AreaClienteController::class, 'ajuda'])->name('ajuda');
    Route::post('/trocar-senha', [AreaClienteController::class, 'trocarSenha'])->name('trocar-senha');
    Route::get('/os/{ordemServico}', [AreaClienteController::class, 'show'])->name('os.show')->middleware('area-cliente:ordemServico');
    Route::post('/os/{ordemServico}/assinatura-aprovacao', [AreaClienteController::class, 'abrirAssinaturaAprovacao'])->name('os.assinatura-aprovacao')->middleware('area-cliente:ordemServico');
    Route::get('/os/{ordemServico}/pdf', [AreaClienteController::class, 'pdf'])->name('os.pdf')->middleware('area-cliente:ordemServico');
    Route::get('/os/{ordemServico}/anexo/{anexo}', [AreaClienteController::class, 'downloadAnexo'])->name('os.anexo')->middleware('area-cliente:ordemServico');

    Route::get('/chamados', [ChamadoController::class, 'index'])->name('chamados.index');
    Route::get('/chamados/novo', [ChamadoController::class, 'create'])->name('chamados.create');
    Route::post('/chamados', [ChamadoController::class, 'store'])->name('chamados.store');
    Route::get('/chamados/{chamado}', [ChamadoController::class, 'show'])->name('chamados.show');
    Route::post('/chamados/{chamado}/fechar', [ChamadoController::class, 'fechar'])->name('chamados.fechar');
    Route::post('/chamados/{chamado}/confirmar-solucao', [ChamadoController::class, 'confirmarSolucao'])->name('chamados.confirmar-solucao');
    Route::post('/chamados/{chamado}/reabrir', [ChamadoController::class, 'reabrir'])->name('chamados.reabrir');
    Route::post('/chamados/{chamado}/desistir', [ChamadoController::class, 'desistir'])->name('chamados.desistir');
    Route::post('/chamados/{chamado}/respostas', [ChamadoController::class, 'storeResposta'])->name('chamados.respostas.store');
    Route::post('/chamados/{chamado}/anexos', [ChamadoController::class, 'storeAnexo'])->name('chamados.anexos.store');
    Route::get('/chamados/{chamado}/anexo/{anexo}', [ChamadoController::class, 'downloadAnexo'])->name('chamados.anexo');

    // Admin: painel de chamados (usuários do sistema, não portal)
    Route::prefix('admin/chamados')->name('admin.chamados.')->group(function () {
        Route::get('/', [AdminChamadoController::class, 'index'])->name('index');
        Route::get('/{chamado}/anexo/{anexo}', [AdminChamadoController::class, 'downloadAnexo'])->name('anexo');
        Route::post('/{chamado}/respostas', [AdminChamadoController::class, 'storeResposta'])->name('respostas.store');
        Route::post('/{chamado}/anexos', [AdminChamadoController::class, 'storeAnexo'])->name('anexos.store');
        Route::post('/{chamado}/converter-os', [AdminChamadoController::class, 'convertToOs'])->name('converter-os');
        Route::match(['put', 'patch'], '/{chamado}', [AdminChamadoController::class, 'update'])->name('update');
        Route::get('/{chamado}', [AdminChamadoController::class, 'show'])->name('show');
    });

    // Configurações do plugin (export/import, clientes habilitados)
    Route::middleware(['permission:manage-plugins'])->prefix('configuracoes')->name('configuracoes.')->group(function () {
        Route::get('/clientes', [\AreaCliente\ConfigClientesController::class, 'index'])->name('clientes');
        Route::post('/clientes/{cliente}/portal-suporte', [\AreaCliente\ConfigClientesController::class, 'togglePortalSuporte'])->name('clientes.portal-suporte');
        Route::get('/cep/{cep}', function (string $cep) {
            $cep = preg_replace('/\D/', '', $cep);
            if (strlen($cep) !== 8) {
                return response()->json(['erro' => true], 400);
            }
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                return response()->json($response->json());
            } catch (\Throwable $e) {
                return response()->json(['erro' => true], 502);
            }
        })->where('cep', '[0-9]+')->name('cep.buscar');
        Route::get('/clientes/{cliente}/modal-usuario', [\AreaCliente\UsuarioClienteController::class, 'modal'])->name('clientes.usuarios.modal');
        Route::post('/clientes/{cliente}/usuarios', [\AreaCliente\UsuarioClienteController::class, 'store'])->name('clientes.usuarios.store');
        Route::put('/clientes/{cliente}/usuarios/{user}', [\AreaCliente\UsuarioClienteController::class, 'update'])->name('clientes.usuarios.update');
        Route::post('/clientes/{cliente}/usuarios/vincular', [\AreaCliente\UsuarioClienteController::class, 'vincular'])->name('clientes.usuarios.vincular');
        Route::post('/clientes/{cliente}/usuarios/{user}/desvincular', [\AreaCliente\UsuarioClienteController::class, 'desvincular'])->name('clientes.usuarios.desvincular');
        Route::get('/export', [SettingsController::class, 'export'])->name('export');
        Route::get('/import', [SettingsController::class, 'importForm'])->name('import');
        Route::post('/import', [SettingsController::class, 'importProcess'])->name('import.process');
    });
});
