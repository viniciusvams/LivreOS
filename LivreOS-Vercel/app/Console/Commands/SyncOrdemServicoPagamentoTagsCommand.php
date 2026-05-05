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

use App\Services\OrdemServicoPagamentoTagSyncService;
use Illuminate\Console\Command;

class SyncOrdemServicoPagamentoTagsCommand extends Command
{
    protected $signature = 'ordens-servico:sync-tags-pagamento';

    protected $description = 'Atualiza tag "Pago" nas OS encerradas que ainda têm "Pagamento Pendente" mas sem título a receber em aberto';

    public function handle(): int
    {
        $result = OrdemServicoPagamentoTagSyncService::sincronizarTodasComTagPendente();

        foreach ($result['atualizadas'] as $row) {
            $this->line("Atualizada OS #{$row['os_id']}" . ($row['codigo'] ? " ({$row['codigo']})" : '') . ' → tag Pago');
        }

        foreach ($result['mantidas'] as $row) {
            $this->warn("Mantida OS #{$row['os_id']}" . ($row['codigo'] ? " ({$row['codigo']})" : '') . " — {$row['contas_abertas']} conta(s) em aberto");
        }

        $this->info('Concluído: ' . count($result['atualizadas']) . ' atualizada(s), ' . count($result['mantidas']) . ' mantida(s) com pendência.');

        return self::SUCCESS;
    }
}
