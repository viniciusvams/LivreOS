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

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Executa as tarefas agendadas quando chamado por um usuário autenticado,
 * sem expor o token. Usado quando "Executar tarefas ao acessar" está habilitado.
 * Roda no máximo uma vez por dia (cache por data).
 */
class RunTarefasInternoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (!auth()->check()) {
            return response('', 404);
        }

        if (!\App\Models\Configuracao::runTarefasAoAcessar()) {
            return response('', 200);
        }

        $hoje = now()->format('Y-m-d');
        $cacheKey = 'run_tarefas_interno_' . $hoje;

        if (Cache::has($cacheKey)) {
            return response('', 200);
        }

        try {
            // Chama os comandos diretamente: schedule:run só executa eventos "due" no minuto atual,
            // então daily() à 00:00 não rodaria às 15h. Assim garantimos geração em qualquer horário.
            Artisan::call('financeiro:gerar-recorrentes');
            Artisan::call('financeiro:gerar-contas-pagar-recorrentes');
            Artisan::call('financeiro:aplicar-reajuste-recorrentes');
            Cache::put($cacheKey, true, now()->endOfDay()->addMinute());
            return response('', 200);
        } catch (\Throwable $e) {
            Log::error('RunTarefasInterno: erro ao executar tarefas - ' . $e->getMessage());
            return response('', 200);
        }
    }
}
