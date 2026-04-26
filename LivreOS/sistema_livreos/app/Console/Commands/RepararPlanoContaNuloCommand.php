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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repara plano_conta_id nulo nas tabelas após um reset de plano de contas que
 * zerou as referências (via cascade de FK).
 *
 * Uso: php artisan financeiro:reparar-plano-conta-nulo
 */
class RepararPlanoContaNuloCommand extends Command
{
    protected $signature = 'financeiro:reparar-plano-conta-nulo';

    protected $description = 'Restaura plano_conta_id nulo em CR, CP, movimentações e recorrentes usando padrões por código';

    public function handle(): int
    {
        $this->info('Reparando plano_conta_id nulo nas tabelas financeiras...');
        $this->newLine();

        // Carrega mapa código -> id do plano de contas atual
        $planos = DB::table('plano_contas')
            ->whereNull('deleted_at')
            ->pluck('id', 'codigo')
            ->toArray();

        if (empty($planos)) {
            $this->error('Plano de contas vazio! Rode: php artisan db:seed --class=PlanoContaSeeder');
            return 1;
        }

        $this->info('Planos carregados: ' . count($planos));

        // IDs padrão úteis
        $planoReceita    = $planos['1.1'] ?? null; // Receita de Serviços
        $planoManutencao = $planos['1.3'] ?? null; // Receita de Manutenção
        $planoCusto      = $planos['3.1'] ?? null; // Custos de Serviços
        $planoDesp       = $planos['3.2'] ?? null; // Despesas Administrativas

        $this->line("  Plano padrão receita   : {$planoReceita} (1.1)");
        $this->line("  Plano padrão custo      : {$planoCusto} (3.1)");

        // 1) Contas a receber com plano_conta_id NULL → padrão 1.1 Receita de Serviços
        if ($planoReceita && Schema::hasColumn('contas_receber', 'plano_conta_id')) {
            $crFixadas = DB::table('contas_receber')
                ->whereNull('plano_conta_id')
                ->update(['plano_conta_id' => $planoReceita]);
            $this->info("Contas a receber corrigidas (→ 1.1): {$crFixadas}");
        }

        // 2) Contas a pagar com plano_conta_id NULL → padrão 3.1 Custos de Serviços
        if ($planoCusto && Schema::hasColumn('contas_pagar', 'plano_conta_id')) {
            $cpFixadas = DB::table('contas_pagar')
                ->whereNull('plano_conta_id')
                ->update(['plano_conta_id' => $planoCusto]);
            $this->info("Contas a pagar corrigidas (→ 3.1): {$cpFixadas}");
        }

        // 3) Pagamentos recorrentes com plano_conta_id NULL → padrão 1.1
        if ($planoReceita && Schema::hasColumn('pagamentos_recorrentes', 'plano_conta_id')) {
            $prFixados = DB::table('pagamentos_recorrentes')
                ->whereNull('plano_conta_id')
                ->update(['plano_conta_id' => $planoReceita]);
            $this->info("Pagamentos recorrentes corrigidos (→ 1.1): {$prFixados}");
        }

        // 4) Contas a pagar recorrentes com plano_conta_id NULL → padrão 3.1
        if ($planoCusto && Schema::hasColumn('contas_pagar_recorrentes', 'plano_conta_id')) {
            $cprFixadas = DB::table('contas_pagar_recorrentes')
                ->whereNull('plano_conta_id')
                ->update(['plano_conta_id' => $planoCusto]);
            $this->info("Contas a pagar recorrentes corrigidas (→ 3.1): {$cprFixadas}");
        }

        // 5) Movimentações com plano_conta_id NULL: usa plano da CR ou CP vinculada
        if (Schema::hasColumn('movimentacoes_financeiras', 'plano_conta_id')) {
            $movNulas = DB::table('movimentacoes_financeiras')
                ->whereNull('plano_conta_id')
                ->select('id', 'conta_receber_id', 'conta_pagar_id', 'tipo')
                ->get();

            $movFixadas = 0;
            foreach ($movNulas as $mov) {
                $planoId = null;

                if ($mov->conta_receber_id) {
                    $planoId = DB::table('contas_receber')
                        ->where('id', $mov->conta_receber_id)
                        ->value('plano_conta_id');
                }

                if (!$planoId && $mov->conta_pagar_id) {
                    $planoId = DB::table('contas_pagar')
                        ->where('id', $mov->conta_pagar_id)
                        ->value('plano_conta_id');
                }

                // Se ainda não achou, usa padrão por tipo
                if (!$planoId) {
                    $planoId = $mov->tipo === 'entrada' ? $planoReceita : $planoCusto;
                }

                if ($planoId) {
                    DB::table('movimentacoes_financeiras')
                        ->where('id', $mov->id)
                        ->update(['plano_conta_id' => $planoId]);
                    $movFixadas++;
                }
            }

            $this->info("Movimentações corrigidas: {$movFixadas} de {$movNulas->count()}");
        }

        $this->newLine();
        $this->info('Concluído. Verifique se os planos de despesas específicas (energia, aluguel, etc.) precisam de ajuste manual.');
        $this->line('  Para ajustar manualmente, edite a Conta a Pagar ou a Movimentação no sistema.');

        return 0;
    }
}
