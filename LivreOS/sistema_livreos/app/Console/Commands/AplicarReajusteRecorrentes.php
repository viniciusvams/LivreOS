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

use App\Models\PagamentoRecorrente;
use App\Services\IndiceEconomicoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AplicarReajusteRecorrentes extends Command
{
    protected $signature = 'financeiro:aplicar-reajuste-recorrentes
                            {--dry-run : Apenas simular, não gravar alterações}';
    protected $description = 'Aplica reajuste por índice (IPCA, INPC, IGP-M) nos pagamentos recorrentes ativos configurados para reajuste';

    public function handle(IndiceEconomicoService $indiceService): int
    {
        $dryRun = $this->option('dry-run');
        $hoje = Carbon::today();

        $recorrentes = PagamentoRecorrente::query()
            ->where('ativo', true)
            ->where('reajuste_habilitado', true)
            ->where('reajuste_tipo', 'indice')
            ->whereNotNull('reajuste_indice')
            ->whereNotNull('reajuste_periodo_meses')
            ->where('reajuste_periodo_meses', '>=', 1)
            ->get();

        if ($recorrentes->isEmpty()) {
            $this->info('Nenhum pagamento recorrente configurado para reajuste por índice.');
            return self::SUCCESS;
        }

        $aplicados = 0;
        $erros = 0;

        foreach ($recorrentes as $rec) {
            $deveReajustar = $this->deveAplicarReajuste($rec, $hoje);
            if (!$deveReajustar) {
                continue;
            }

            $meses = (int) $rec->reajuste_periodo_meses;
            $codigoIndice = strtoupper(trim((string) ($rec->reajuste_indice ?? '')));
            if ($codigoIndice === '') {
                $this->warn("Recorrente #{$rec->id} ({$rec->descricao}): índice não informado. Pulando.");
                $erros++;
                continue;
            }
            $variacao = $indiceService->variacaoAcumuladaMeses($codigoIndice, $meses);

            if ($variacao === null) {
                $this->warn("Recorrente #{$rec->id} ({$rec->descricao}): índice {$codigoIndice} indisponível. Pulando.");
                Log::warning("Reajuste recorrente #{$rec->id}: índice {$codigoIndice} não disponível.");
                $erros++;
                continue;
            }

            $descontoPct = (float) ($rec->reajuste_desconto_pct ?? 0);
            $fatorReajuste = 1 + ($variacao / 100) * (1 - $descontoPct / 100);
            $valorAnterior = (float) $rec->valor;
            $novoValor = round($valorAnterior * $fatorReajuste, 2);

            if ($novoValor <= 0) {
                $this->warn("Recorrente #{$rec->id}: novo valor calculado inválido ({$novoValor}). Mantendo valor atual.");
                continue;
            }

            if ($dryRun) {
                $this->line(" [DRY-RUN] #{$rec->id} {$rec->descricao}: R$ " . number_format($valorAnterior, 2, ',', '.') . " → R$ " . number_format($novoValor, 2, ',', '.') . " (variação {$variacao}%, desconto {$descontoPct}%)");
                $aplicados++;
                continue;
            }

            try {
                DB::beginTransaction();
                $rec->update([
                    'valor' => $novoValor,
                    'reajuste_ultima_data' => $hoje,
                    'updated_by' => null,
                ]);
                DB::commit();
                $this->info("Recorrente #{$rec->id} {$rec->descricao}: R$ " . number_format($valorAnterior, 2, ',', '.') . " → R$ " . number_format($novoValor, 2, ',', '.'));
                Log::info("Reajuste aplicado: PagamentoRecorrente #{$rec->id}, valor {$valorAnterior} → {$novoValor}, índice {$rec->reajuste_indice}, variação {$variacao}%");
                $aplicados++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Erro ao reajustar #{$rec->id}: " . $e->getMessage());
                Log::error("Erro ao aplicar reajuste no recorrente #{$rec->id}: " . $e->getMessage());
                $erros++;
            }
        }

        if ($aplicados > 0) {
            $this->info($dryRun ? "[DRY-RUN] Seriam aplicados {$aplicados} reajuste(s)." : "Aplicados {$aplicados} reajuste(s).");
        }
        if ($erros > 0) {
            $this->warn("{$erros} registro(s) com erro ou indisponibilidade.");
        }

        return self::SUCCESS;
    }

    /**
     * Verifica se o recorrente está due para reajuste (período decorrido desde última aplicação ou desde data_inicio).
     */
    protected function deveAplicarReajuste(PagamentoRecorrente $rec, Carbon $hoje): bool
    {
        $periodoMeses = (int) $rec->reajuste_periodo_meses;
        if ($periodoMeses < 1) {
            return false;
        }

        $dataReferencia = $rec->reajuste_ultima_data
            ? Carbon::parse($rec->reajuste_ultima_data)->startOfMonth()
            : Carbon::parse($rec->data_inicio)->startOfMonth();

        $proximoReajuste = $dataReferencia->copy()->addMonths($periodoMeses);
        $inicioMesAtual = $hoje->copy()->startOfMonth();

        return $proximoReajuste->lte($inicioMesAtual);
    }
}
