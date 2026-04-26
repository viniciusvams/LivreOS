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

use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\MovimentacaoFinanceira;
use App\Models\PlanoConta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reseta o plano de contas (apaga, recria com IDs a partir de 1) e atualiza
 * todas as referências em contas_receber, contas_pagar e movimentacoes_financeiras
 * para os novos IDs, usando o código da conta como chave.
 *
 * Uso: php artisan plano-contas:reset-e-atualizar-referencias
 */
class PlanoContasResetAtualizarReferenciasCommand extends Command
{
    protected $signature = 'plano-contas:reset-e-atualizar-referencias';

    protected $description = 'Reseta plano de contas (IDs a partir de 1) e atualiza referências em CR, CP e movimentações';

    public function handle(): int
    {
        $this->info('Plano de contas: reset e atualização de referências');
        $this->newLine();

        // 1) Mapeamento atual: id -> codigo (antes de apagar)
        $antigos = PlanoConta::orderBy('id')->get(['id', 'codigo']);
        $oldIdToCodigo = $antigos->pluck('codigo', 'id')->toArray();
        $this->info('Contas atuais no plano: ' . count($oldIdToCodigo));

        // 2) Apagar plano (quebrar pai_id primeiro) com forceDelete para limpar soft-deleted também
        PlanoConta::withTrashed()->update(['pai_id' => null]);
        PlanoConta::withTrashed()->forceDelete();
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'plano_contas'");
        } else {
            DB::statement('ALTER TABLE plano_contas AUTO_INCREMENT = 1');
        }

        // 3) Rodar o seeder para recriar com IDs 1, 2, 3, ...
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\PlanoContaSeeder']);
        $this->info('Plano de contas recriado (IDs a partir de 1).');

        // 4) Novo mapeamento: codigo -> novo id
        $novos = PlanoConta::orderBy('id')->get(['id', 'codigo']);
        $codigoToNewId = $novos->pluck('id', 'codigo')->toArray();

        // 5) Mapeamento old_id -> new_id (só onde o código existe no novo plano)
        $oldToNew = [];
        foreach ($oldIdToCodigo as $oldId => $codigo) {
            if (isset($codigoToNewId[$codigo])) {
                $oldToNew[(int) $oldId] = (int) $codigoToNewId[$codigo];
            }
        }
        $this->info('Referências a atualizar: ' . count($oldToNew) . ' IDs de plano.');
        if (empty($oldToNew)) {
            $this->warn('Nenhum código em comum; nada a atualizar.');
            return 0;
        }

        // 6) Atualizar tabelas que referenciam plano_contas
        $movimentacoes = 0;
        foreach ($oldToNew as $oldId => $newId) {
            if ($oldId === $newId) {
                continue;
            }
            $movimentacoes += MovimentacaoFinanceira::where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
        }
        $this->info('Movimentações atualizadas: ' . $movimentacoes);

        $cr = 0;
        foreach ($oldToNew as $oldId => $newId) {
            if ($oldId === $newId) continue;
            $cr += ContaReceber::where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
        }
        $this->info('Contas a receber atualizadas: ' . $cr);

        $cp = 0;
        foreach ($oldToNew as $oldId => $newId) {
            if ($oldId === $newId) continue;
            $cp += ContaPagar::where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
        }
        $this->info('Contas a pagar atualizadas: ' . $cp);

        if (class_exists(\App\Models\PagamentoRecorrente::class) && \Schema::hasColumn((new \App\Models\PagamentoRecorrente())->getTable(), 'plano_conta_id')) {
            $pr = 0;
            foreach ($oldToNew as $oldId => $newId) {
                if ($oldId === $newId) continue;
                $pr += \App\Models\PagamentoRecorrente::where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
            }
            $this->info('Pagamentos recorrentes atualizados: ' . $pr);
        }

        if (class_exists(\App\Models\ContaPagarRecorrente::class)) {
            $model = new \App\Models\ContaPagarRecorrente();
            if (\Schema::hasColumn($model->getTable(), 'plano_conta_id')) {
                $cpr = 0;
                foreach ($oldToNew as $oldId => $newId) {
                    if ($oldId === $newId) continue;
                    $cpr += \App\Models\ContaPagarRecorrente::where('plano_conta_id', $oldId)->update(['plano_conta_id' => $newId]);
                }
                $this->info('Contas a pagar recorrentes atualizadas: ' . $cpr);
            }
        }

        $this->newLine();
        $this->info('Concluído. Plano de contas começa em ID 1 e referências foram ajustadas.');
        return 0;
    }
}
