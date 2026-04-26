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

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Services\GerarContasRecorrentesService;

class GerarContasRecorrentes extends Command
{
    protected $signature = 'financeiro:gerar-recorrentes';
    protected $description = 'Gera contas a receber a partir dos pagamentos recorrentes ativos (executar diariamente)';

    public function handle(GerarContasRecorrentesService $service): int
    {
        // Aplica reajuste por índice nos recorrentes due antes de gerar
        Artisan::call('financeiro:aplicar-reajuste-recorrentes');
        $geradas = $service->gerarPendentes(Carbon::today());
        if ($geradas > 0) {
            $this->info("Geradas {$geradas} conta(s) a receber a partir de pagamentos recorrentes.");
        }
        return self::SUCCESS;
    }
}
