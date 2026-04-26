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

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Services\GerarContasRecorrentesService;
use App\Services\IndiceEconomicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Tela para testar manualmente a geração de contas recorrentes e o reajuste por índice.
 */
class TarefasRecorrentesController extends Controller
{
    public function index()
    {
        return erp_view('financeiro.tarefas-recorrentes.index', [
            'title' => 'Testar recorrência e reajuste',
        ]);
    }

    /** Gera contas a receber a partir dos recorrentes due (igual ao comando diário). Aplica reajuste por índice antes, se due. */
    public function gerarRecorrentes(Request $request, GerarContasRecorrentesService $service)
    {
        $request->validate(['_token' => 'required']);
        // Aplica reajuste por índice nos recorrentes due antes de gerar, para as contas saírem com valor corrigido
        Artisan::call('financeiro:aplicar-reajuste-recorrentes');
        $geradas = $service->gerarPendentes();
        return redirect()->route('financeiro.tarefas-recorrentes.index')
            ->with('result_gerar', $geradas > 0
                ? "Foram geradas {$geradas} conta(s) a receber."
                : 'Nenhuma conta a receber pendente para gerar no momento.');
    }

    /** Simula o reajuste por índice (dry-run), mostra o que seria alterado. */
    public function reajusteSimular(Request $request)
    {
        $request->validate(['_token' => 'required']);
        Artisan::call('financeiro:aplicar-reajuste-recorrentes', ['--dry-run' => true]);
        $output = trim(Artisan::output());
        return redirect()->route('financeiro.tarefas-recorrentes.index')
            ->with('result_reajuste_simular', $output ?: 'Nenhum recorrente due para reajuste por índice.');
    }

    /** Aplica o reajuste por índice (altera os valores). */
    public function reajusteAplicar(Request $request)
    {
        $request->validate(['_token' => 'required']);
        Artisan::call('financeiro:aplicar-reajuste-recorrentes');
        $output = trim(Artisan::output());
        return redirect()->route('financeiro.tarefas-recorrentes.index')
            ->with('result_reajuste_aplicar', $output ?: 'Nenhum reajuste aplicado.');
    }

    /** Testa a API de índice (ex.: IPCA últimos 12 meses). */
    public function testarIndice(Request $request, IndiceEconomicoService $indiceService)
    {
        $request->validate(['_token' => 'required']);
        $codigo = $request->get('indice', 'IPCA');
        $meses = (int) $request->get('meses', 12);
        $variacao = $indiceService->variacaoAcumuladaMeses($codigo, $meses);
        $msg = $variacao !== null
            ? "Índice {$codigo} (últimos {$meses} meses): variação acumulada = " . number_format($variacao, 2, ',', '.') . '%'
            : "Não foi possível obter o índice {$codigo} (verifique a conexão ou se o código é suportado).";
        return redirect()->route('financeiro.tarefas-recorrentes.index')
            ->with('result_indice', $msg);
    }
}
