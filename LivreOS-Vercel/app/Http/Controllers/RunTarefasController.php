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
use Illuminate\Support\Facades\Log;

/**
 * Permite executar as tarefas agendadas (recorrentes, reajuste) e a fila de jobs via requisição HTTP.
 * Útil em hospedagem compartilhada onde não há acesso ao cron nem ao comando queue:work.
 *
 * Configure CRON_SECRET_TOKEN no .env e chame esta URL periodicamente (ex.: a cada 1–5 min).
 * Ex.: GET /run-tarefas?token=seu_token_secreto
 *
 * Quando QUEUE_CONNECTION=database, esta URL também processa até 30 jobs da fila (ex.: envio de e-mails).
 */
class RunTarefasController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = $request->query('token') ?? $request->header('X-Cron-Token');
        $expected = config('app.cron_secret_token');

        if (empty($expected)) {
            Log::warning('RunTarefas: CRON_SECRET_TOKEN não configurado. Configure em .env para usar execução via URL.');
            return response('Token não configurado. Defina CRON_SECRET_TOKEN no .env.', 503);
        }

        if (!hash_equals((string) $expected, (string) $token)) {
            return response('Não autorizado.', 403);
        }

        $output = [];
        try {
            Artisan::call('schedule:run');
            $output[] = trim(Artisan::output());
        } catch (\Throwable $e) {
            Log::error('RunTarefas: erro ao executar schedule:run - ' . $e->getMessage());
            $output[] = 'Erro: ' . $e->getMessage();
        }

        // Em hospedagem compartilhada, processar a fila via URL (quando driver for database)
        if (config('queue.default') === 'database') {
            try {
                Artisan::call('queue:work', [
                    '--stop-when-empty' => true,
                    '--max-jobs' => 30,
                ]);
                $queueOutput = trim(Artisan::output());
                if ($queueOutput !== '') {
                    $output[] = 'Fila: ' . $queueOutput;
                }
            } catch (\Throwable $e) {
                Log::error('RunTarefas: erro ao processar fila - ' . $e->getMessage());
                $output[] = 'Fila: Erro - ' . $e->getMessage();
            }
        }

        return response(implode("\n", $output), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
