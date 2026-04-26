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
use App\Services\GerarContasPagarRecorrentesService;

class GerarContasPagarRecorrentes extends Command
{
    protected $signature = 'financeiro:gerar-contas-pagar-recorrentes';
    protected $description = 'Gera contas a pagar a partir das despesas recorrentes ativas (água, luz, aluguel, etc.)';

    public function handle(GerarContasPagarRecorrentesService $service): int
    {
        $geradas = $service->gerarPendentes(Carbon::today());
        if ($geradas > 0) {
            $this->info("Geradas {$geradas} conta(s) a pagar a partir de despesas recorrentes.");
        }
        return self::SUCCESS;
    }
}
