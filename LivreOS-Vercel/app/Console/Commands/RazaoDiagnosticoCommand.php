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
use App\Models\PlanoConta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico do relatório Razão: executa as mesmas queries em etapas
 * para comparar com o resultado do sistema.
 *
 * Uso: php artisan razao:diagnostico 151 2025-03-01 2026-03-03
 */
class RazaoDiagnosticoCommand extends Command
{
    protected $signature = 'razao:diagnostico
                            {plano_conta_id : ID do plano de conta (ex: 151)}
                            {data_inicio=2025-03-01 : Data início Y-m-d}
                            {data_fim=2026-03-03 : Data fim Y-m-d}';

    protected $description = 'Diagnóstico do Razão: executa queries em etapas e compara resultados';

    public function handle(): int
    {
        $planoContaId = (int) $this->argument('plano_conta_id');
        $dataInicio = $this->argument('data_inicio');
        $dataFim = $this->argument('data_fim');

        $this->info("=== Diagnóstico Razão ===");
        $this->info("Plano conta ID: {$planoContaId}");
        $this->info("Período: {$dataInicio} a {$dataFim}");
        $this->newLine();

        // 1) Conta existe?
        $conta = PlanoConta::find($planoContaId);
        if (!$conta) {
            $this->error("Plano de conta #{$planoContaId} não existe.");
            return 1;
        }
        $this->info("Conta: [{$conta->codigo}] {$conta->nome}");

        // 2) IDs da conta + descendentes (mesma lógica do controller)
        $ids = [$planoContaId];
        do {
            $newIds = PlanoConta::whereIn('pai_id', $ids)->pluck('id')->toArray();
            $ids = array_unique(array_merge($ids, $newIds));
            if (empty($newIds)) {
                break;
            }
        } while (true);
        $planoContaIds = array_values($ids);
        $this->info('IDs considerados (conta + filhas): ' . implode(', ', $planoContaIds));
        $this->newLine();

        $table = (new MovimentacaoFinanceira())->getTable();

        // 3) Total de movimentações no período (SEM filtro de plano, SEM tenant, SEM conciliado)
        $totalPeriodo = DB::table($table)
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('deleted_at')
            ->count();
        $this->info("[1] Total movimentações no período (sem plano/tenant): {$totalPeriodo}");

        // 4) Total com plano_conta_id no conjunto (direto na movimentação)
        $totalComPlanoNaMov = DB::table($table)
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->whereIn('plano_conta_id', $planoContaIds)
            ->whereNull('deleted_at')
            ->count();
        $this->info("[2] Movimentações com plano_conta_id IN (" . implode(',', $planoContaIds) . "): {$totalComPlanoNaMov}");

        // 5) Total com conta_receber.plano_conta_id no conjunto
        $totalViaCR = DB::table($table)
            ->join('contas_receber', 'contas_receber.id', '=', "{$table}.conta_receber_id")
            ->whereBetween("{$table}.data_movimentacao", [$dataInicio, $dataFim])
            ->whereIn('contas_receber.plano_conta_id', $planoContaIds)
            ->whereNull("{$table}.deleted_at")
            ->count();
        $this->info("[3] Movimentações via conta_receber com plano_conta_id no conjunto: {$totalViaCR}");

        // 6) Total com conta_pagar.plano_conta_id no conjunto
        $totalViaCP = DB::table($table)
            ->join('contas_pagar', 'contas_pagar.id', '=', "{$table}.conta_pagar_id")
            ->whereBetween("{$table}.data_movimentacao", [$dataInicio, $dataFim])
            ->whereIn('contas_pagar.plano_conta_id', $planoContaIds)
            ->whereNull("{$table}.deleted_at")
            ->count();
        $this->info("[4] Movimentações via conta_pagar com plano_conta_id no conjunto: {$totalViaCP}");

        // 7) Query combinada (OR dos três) - equivalente ao where do Razão, sem tenant
        $builderCombinado = DB::table($table)
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($table, $planoContaIds) {
                $q->whereIn("{$table}.plano_conta_id", $planoContaIds)
                    ->orWhereExists(function ($sub) use ($table, $planoContaIds) {
                        $sub->select(DB::raw(1))
                            ->from('contas_receber')
                            ->whereColumn('contas_receber.id', "{$table}.conta_receber_id")
                            ->whereIn('contas_receber.plano_conta_id', $planoContaIds);
                    })
                    ->orWhereExists(function ($sub) use ($table, $planoContaIds) {
                        $sub->select(DB::raw(1))
                            ->from('contas_pagar')
                            ->whereColumn('contas_pagar.id', "{$table}.conta_pagar_id")
                            ->whereIn('contas_pagar.plano_conta_id', $planoContaIds);
                    });
            });
        $totalCombinado = (clone $builderCombinado)->count();
        $this->info("[5] Total com filtro Razão (plano na mov OU no CR OU no CP): {$totalCombinado}");

        // 8) Se a tabela tem tenant_id: distribuição por tenant_id
        if (Schema::hasColumn($table, 'tenant_id')) {
            $porTenant = DB::table($table)
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->whereNull('deleted_at')
                ->selectRaw('tenant_id, count(*) as total')
                ->groupBy('tenant_id')
                ->get();
            $this->newLine();
            $this->info('[6] Movimentações no período por tenant_id:');
            foreach ($porTenant as $row) {
                $this->line("    tenant_id " . ($row->tenant_id ?? 'NULL') . " => {$row->total}");
            }

            $totalComTenantNull = DB::table($table)
                ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
                ->whereNull('deleted_at')
                ->whereNull('tenant_id')
                ->count();
            $this->info("    Total com tenant_id NULL: {$totalComTenantNull}");
        }

        // 9) Amostra de registros no período (primeiros 5) para inspecionar
        $this->newLine();
        $amostra = DB::table($table)
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('deleted_at')
            ->select('id', 'data_movimentacao', 'tipo', 'valor', 'plano_conta_id', 'conta_receber_id', 'conta_pagar_id', 'conciliado', 'tenant_id')
            ->orderBy('data_movimentacao')
            ->limit(5)
            ->get();
        $this->info('[7] Amostra de movimentações no período (5 primeiras):');
        if ($amostra->isEmpty()) {
            $this->warn('    Nenhuma movimentação no período.');
        } else {
            foreach ($amostra as $m) {
                $this->line(sprintf(
                    '    id=%s data=%s tipo=%s valor=%s plano_conta_id=%s conta_receber_id=%s conta_pagar_id=%s conciliado=%s tenant_id=%s',
                    $m->id,
                    $m->data_movimentacao,
                    $m->tipo,
                    $m->valor,
                    $m->plano_conta_id ?? 'NULL',
                    $m->conta_receber_id ?? 'NULL',
                    $m->conta_pagar_id ?? 'NULL',
                    $m->conciliado ? '1' : '0',
                    $m->tenant_id ?? 'NULL'
                ));
            }
        }

        // 10) Query Eloquent igual ao controller (sem applyEntityQuery para comparar)
        $this->newLine();
        $query = MovimentacaoFinanceira::with(['contaBancaria', 'contaReceber', 'contaPagar'])
            ->whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->where(function ($q) use ($planoContaIds) {
                $q->whereIn('plano_conta_id', $planoContaIds)
                    ->orWhereHas('contaReceber', fn ($q2) => $q2->whereIn('plano_conta_id', $planoContaIds))
                    ->orWhereHas('contaPagar', fn ($q2) => $q2->whereIn('plano_conta_id', $planoContaIds));
            });
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $this->info('[8] SQL Eloquent (sem tenant, sem applyEntityQuery):');
        $this->line($this->bindSql($sql, $bindings));
        $countEloquent = (clone $query)->count();
        $this->info("    Count: {$countEloquent}");

        $this->newLine();
        $this->info('--- Conclusão ---');
        if ($totalPeriodo === 0) {
            $this->warn('Não há movimentações no período. Cadastre movimentações com data_movimentacao entre ' . $dataInicio . ' e ' . $dataFim . '.');
        } elseif ($totalCombinado === 0) {
            $this->warn('Há movimentações no período, mas nenhuma está vinculada ao plano de conta ' . $planoContaId . ' (nem nas contas filhas).');
            $this->warn('Verifique se as movimentações (ou os títulos de CR/CP) têm plano_conta_id preenchido.');
        } else {
            $this->info("O filtro do Razão retornaria {$totalCombinado} registro(s). Se o sistema mostra 0, verifique filtro por tenant_id ou hook entity.index.query.");
        }

        return 0;
    }

    private function bindSql(string $sql, array $bindings): string
    {
        $s = $sql;
        foreach ($bindings as $b) {
            $b = is_string($b) ? "'" . addslashes($b) . "'" : ($b === null ? 'NULL' : $b);
            $s = preg_replace('/\?/', (string) $b, $s, 1);
        }
        return $s;
    }
}
