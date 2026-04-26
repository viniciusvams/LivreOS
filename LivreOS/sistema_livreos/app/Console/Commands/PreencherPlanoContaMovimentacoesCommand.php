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

use App\Models\MovimentacaoFinanceira;
use Illuminate\Console\Command;

/**
 * Preenche plano_conta_id nas movimentações que vieram de CR/CP e estão vazias.
 * Uso: php artisan movimentacoes:preencher-plano-conta
 */
class PreencherPlanoContaMovimentacoesCommand extends Command
{
    protected $signature = 'movimentacoes:preencher-plano-conta';

    protected $description = 'Preenche plano_conta_id nas movimentações a partir do título (CR/CP) vinculado';

    public function handle(): int
    {
        $movimentacoes = MovimentacaoFinanceira::with(['contaReceber', 'contaPagar'])
            ->whereNull('plano_conta_id')
            ->get();

        $atualizadas = 0;
        foreach ($movimentacoes as $mov) {
            $planoId = $mov->contaReceber?->plano_conta_id ?? $mov->contaPagar?->plano_conta_id;
            if ($planoId !== null) {
                $mov->plano_conta_id = $planoId;
                $mov->save();
                $atualizadas++;
            }
        }

        $this->info("Movimentações com plano_conta_id vazio: {$movimentacoes->count()}");
        $this->info("Atualizadas com plano do título (CR/CP): {$atualizadas}");
        return 0;
    }
}
