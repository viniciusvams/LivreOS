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

class DiagnosticoRazaoCommand extends Command
{
    protected $signature = 'diagnostico:razao
                            {--plano_conta_id=151 : ID do plano de conta}
                            {--data_inicio=2025-03-01 : Data início}
                            {--data_fim=2026-03-03 : Data fim}
                            {--tenant_id= : Se informado, aplica mesmo filtro do usuário logado}';

    protected $description = 'Diagnóstico: executa as mesmas queries do relatório Razão e compara resultados.';

    public function handle(): int
    {
        $planoContaId = (int) $this->option('plano_conta_id');
        $dataInicio = $this->option('data_inicio');
        $dataFim = $this->option('data_fim');
        $tenantId = $this->option('tenant_id') !== null ? (int) $this->option('tenant_id') : null;

        $this->info('=== Diagnóstico Razão ===');
        $this->info("Plano conta ID: {$planoContaId} | Período: {$dataInicio} a {$dataFim}".($tenantId !== null ? " | tenant_id: {$tenantId}" : ' | (sem filtro tenant)'));
        $this->newLine();

        // 1) IDs da conta e descendentes (mesma lógica do controller)
        $planoContaIds = $this->idsContaEDescendentes($planoContaId);
        $this->info('1) IDs conta + descendentes: '.implode(', ', $planoContaIds));
        $conta = PlanoConta::find($planoContaId);
        if ($conta) {
            $this->line("   Conta: {$conta->codigo} - {$conta->nome}");
        }
        $this->newLine();

        // 2) Total de movimentações no período (sem filtro de plano)
        $totalNoPeriodo = MovimentacaoFinanceira::whereBetween('data_movimentacao', [$dataInicio, $dataFim])->count();
        $this->info("2) Total movimentações no período (qualquer plano): {$totalNoPeriodo}");

        // 2b) Por tenant_id (se existir coluna e houver mais de um valor)
        $porTenant = MovimentacaoFinanceira::whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->selectRaw('tenant_id, COUNT(*) as qtd')
            ->groupBy('tenant_id')
            ->get();
        if ($porTenant->isNotEmpty()) {
            $this->info('2b) Movimentações no período por tenant_id:');
            foreach ($porTenant as $r) {
                $this->line('   tenant_id='.($r->tenant_id ?? 'NULL')." => {$r->qtd} movimentações");
            }
        }

        if ($totalNoPeriodo === 0) {
            $this->warn('   Nenhuma movimentação no período. Verifique data_movimentacao na tabela movimentacoes_financeiras.');

            return 0;
        }

        // 3) Amostra: primeiras 5 movimentações do período
        $amostra = MovimentacaoFinanceira::whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->orderBy('data_movimentacao')->orderBy('id')
            ->take(5)
            ->get(['id', 'data_movimentacao', 'tipo', 'valor', 'plano_conta_id', 'conta_receber_id', 'conta_pagar_id', 'conciliado']);
        $this->info('3) Amostra (5 primeiras movimentações do período):');
        foreach ($amostra as $m) {
            $this->line(sprintf(
                '   id=%s data=%s tipo=%s valor=%s plano_conta_id=%s conta_receber_id=%s conta_pagar_id=%s conciliado=%s',
                $m->id,
                $m->data_movimentacao?->format('Y-m-d'),
                $m->tipo,
                $m->valor,
                $m->plano_conta_id ?? 'NULL',
                $m->conta_receber_id ?? 'NULL',
                $m->conta_pagar_id ?? 'NULL',
                $m->conciliado ? '1' : '0'
            ));
        }
        $this->newLine();

        // 4) Contagem por critério (query do Razão, sem applyEntityQuery)
        $qBase = MovimentacaoFinanceira::whereBetween('data_movimentacao', [$dataInicio, $dataFim]);
        if ($tenantId !== null) {
            $qBase->where('tenant_id', $tenantId);
        }

        $porPlanoMov = (clone $qBase)->whereIn('plano_conta_id', $planoContaIds)->count();
        $this->info('4a) Movimentações com plano_conta_id IN ('.implode(',', $planoContaIds)."): {$porPlanoMov}");

        $porCR = (clone $qBase)->whereHas('contaReceber', fn ($q) => $q->whereIn('plano_conta_id', $planoContaIds))->count();
        $this->info("4b) Movimentações com ContaReceber.plano_conta_id no conjunto: {$porCR}");

        $porCP = (clone $qBase)->whereHas('contaPagar', fn ($q) => $q->whereIn('plano_conta_id', $planoContaIds))->count();
        $this->info("4c) Movimentações com ContaPagar.plano_conta_id no conjunto: {$porCP}");

        $queryCompleta = (clone $qBase)->where(function ($q) use ($planoContaIds) {
            $q->whereIn('plano_conta_id', $planoContaIds)
                ->orWhereHas('contaReceber', fn ($q2) => $q2->whereIn('plano_conta_id', $planoContaIds))
                ->orWhereHas('contaPagar', fn ($q2) => $q2->whereIn('plano_conta_id', $planoContaIds));
        });
        $totalQueryCompleta = $queryCompleta->count();
        $this->info("4d) Total com query completa (plano OU CR.plano OU CP.plano): {$totalQueryCompleta}");
        $this->newLine();

        // 5) SQL bruto para conferência
        $idsStr = implode(',', array_map('intval', $planoContaIds));
        $params = [$dataInicio, $dataFim];
        $tenantCond = '';
        if ($tenantId !== null) {
            $tenantCond = ' AND m.tenant_id = ?';
            $params[] = $tenantId;
        }
        $sql = "
            SELECT COUNT(*) as total
            FROM movimentacoes_financeiras m
            LEFT JOIN contas_receber cr ON cr.id = m.conta_receber_id
            LEFT JOIN contas_pagar cp ON cp.id = m.conta_pagar_id
            WHERE m.data_movimentacao BETWEEN ? AND ?
            AND m.deleted_at IS NULL
            {$tenantCond}
            AND (
                m.plano_conta_id IN ({$idsStr})
                OR (m.conta_receber_id IS NOT NULL AND cr.plano_conta_id IN ({$idsStr}))
                OR (m.conta_pagar_id IS NOT NULL AND cp.plano_conta_id IN ({$idsStr}))
            )
        ";
        $totalSql = (int) (DB::selectOne($sql, $params)->total ?? 0);
        $this->info("5) Mesma lógica via SQL bruto (COUNT): {$totalSql}");
        $this->newLine();

        // 6) Se ainda 0, listar plano_conta_id distintos no período
        $distintosPlanos = MovimentacaoFinanceira::whereBetween('data_movimentacao', [$dataInicio, $dataFim])
            ->selectRaw('plano_conta_id, COUNT(*) as qtd')
            ->groupBy('plano_conta_id')
            ->get();
        $this->info('6) Plano de conta nas movimentações do período (mov.plano_conta_id):');
        foreach ($distintosPlanos as $r) {
            $this->line('   plano_conta_id='.($r->plano_conta_id ?? 'NULL')." => {$r->qtd} movimentações");
        }

        $crPlanos = DB::table('movimentacoes_financeiras as m')
            ->join('contas_receber as cr', 'cr.id', '=', 'm.conta_receber_id')
            ->whereBetween('m.data_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('m.deleted_at')
            ->whereNotNull('m.conta_receber_id')
            ->selectRaw('cr.plano_conta_id, COUNT(*) as qtd')
            ->groupBy('cr.plano_conta_id')
            ->get();
        $this->info('7) Plano de conta via CR no período (cr.plano_conta_id):');
        foreach ($crPlanos as $r) {
            $this->line('   plano_conta_id='.($r->plano_conta_id ?? 'NULL')." => {$r->qtd} movimentações");
        }

        $cpPlanos = DB::table('movimentacoes_financeiras as m')
            ->join('contas_pagar as cp', 'cp.id', '=', 'm.conta_pagar_id')
            ->whereBetween('m.data_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('m.deleted_at')
            ->whereNotNull('m.conta_pagar_id')
            ->selectRaw('cp.plano_conta_id, COUNT(*) as qtd')
            ->groupBy('cp.plano_conta_id')
            ->get();
        $this->info('8) Plano de conta via CP no período (cp.plano_conta_id):');
        foreach ($cpPlanos as $r) {
            $this->line('   plano_conta_id='.($r->plano_conta_id ?? 'NULL')." => {$r->qtd} movimentações");
        }

        $this->newLine();
        $this->comment('Se no sistema você está logado com tenant_id preenchido, o Razão filtra por esse tenant.');
        $this->comment('Rode: php artisan diagnostico:razao --plano_conta_id=151 --data_inicio=2025-03-01 --data_fim=2026-03-03');
        $this->comment('Para simular tenant: php artisan diagnostico:razao --tenant_id=1 --plano_conta_id=151 --data_inicio=2025-03-01 --data_fim=2026-03-03');

        return 0;
    }

    private function idsContaEDescendentes(int $planoContaId): array
    {
        $ids = [$planoContaId];
        do {
            $newIds = PlanoConta::whereIn('pai_id', $ids)->pluck('id')->toArray();
            $ids = array_unique(array_merge($ids, $newIds));
            if (empty($newIds)) {
                break;
            }
        } while (true);

        return array_values($ids);
    }
}
