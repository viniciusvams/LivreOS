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

namespace App\Services;

use App\Models\ContaReceber;
use App\Models\PagamentoRecorrente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gera contas a receber a partir de pagamentos recorrentes.
 * Usado pelo comando artisan e pelo catch-up ao salvar um recorrente.
 */
class GerarContasRecorrentesService
{
    /**
     * Gera uma única conta a receber para o recorrente, se estiver due.
     * Retorna 1 se gerou, 0 se não havia nada a gerar.
     */
    public function gerarUmaConta(PagamentoRecorrente $rec, ?Carbon $hoje = null): int
    {
        $hoje = $hoje ?? Carbon::today();
        $rec->loadMissing(['cliente', 'formaPagamento', 'contaBancaria']);

        if (!$rec->ativo) {
            return 0;
        }
        if ($rec->proxima_geracao_em > $hoje) {
            return 0;
        }
        if ($rec->data_fim && $rec->proxima_geracao_em > $rec->data_fim) {
            $rec->update(['ativo' => false, 'updated_by' => null]);
            return 0;
        }

        $dataVencimento = $rec->proxima_geracao_em;
        if ($rec->frequencia === 'mensal' && $rec->gerar_ultimo_dia_mes) {
            $dataVencimento = $dataVencimento->copy()->endOfMonth();
        }
        if ($dataVencimento > $hoje) {
            return 0;
        }
        if ($rec->data_fim && $dataVencimento > $rec->data_fim) {
            $rec->update(['ativo' => false, 'updated_by' => null]);
            return 0;
        }

        $contaBancariaId = $rec->conta_bancaria_id;
        if (!$contaBancariaId && $rec->forma_pagamento_id && $rec->formaPagamento) {
            $contaBancariaId = $rec->formaPagamento->conta_bancaria_id;
        }

        $descontoTaxa = 0.0;
        if ($rec->forma_pagamento_id && $rec->formaPagamento) {
            $descontoTaxa = (float) $rec->formaPagamento->calcularTaxa($rec->valor);
        }

        $observacoes = trim((string) ($rec->observacoes ?? ''));
        $rastreio = sprintf(
            '[Recorrente #%d - %s | Venc. gerado: %s | Gerado em %s]',
            $rec->id,
            $rec->descricao,
            $dataVencimento->format('d/m/Y'),
            now()->format('d/m/Y H:i')
        );
        $observacoes = $observacoes === '' ? $rastreio : $observacoes . "\n" . $rastreio;

        try {
            DB::beginTransaction();
            ContaReceber::create([
                'cliente_id' => $rec->cliente_id,
                'pagamento_recorrente_id' => $rec->id,
                'descricao' => $rec->descricao . ' (recorrente)',
                'valor' => $rec->valor,
                'valor_original' => $rec->valor,
                'desconto' => $descontoTaxa,
                'data_vencimento' => $dataVencimento,
                'forma_pagamento_id' => $rec->forma_pagamento_id,
                'conta_bancaria_id' => $contaBancariaId,
                'plano_conta_id' => $rec->plano_conta_id,
                'status' => 'aberto',
                'observacoes' => $observacoes,
                'created_by' => $rec->created_by,
            ]);

            $proxima = $rec->calcularProximaData(Carbon::parse($dataVencimento));
            if ($rec->data_fim && $proxima > $rec->data_fim) {
                $rec->update(['proxima_geracao_em' => $proxima, 'ativo' => false, 'updated_by' => null]);
            } else {
                $rec->update(['proxima_geracao_em' => $proxima, 'updated_by' => null]);
            }
            DB::commit();
            return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Gera todas as contas em atraso para um único recorrente (catch-up).
     * Use após criar ou ativar um recorrente com data de início no passado.
     */
    public function gerarCatchUpRecorrente(PagamentoRecorrente $rec, ?Carbon $ate = null): int
    {
        $ate = $ate ?? Carbon::today();
        $total = 0;
        $maxIter = 500; // evita loop infinito
        for ($i = 0; $i < $maxIter; $i++) {
            $n = $this->gerarUmaConta($rec, $ate);
            if ($n === 0) {
                break;
            }
            $total += $n;
            $rec->refresh();
        }
        return $total;
    }

    /**
     * Processa todos os recorrentes due. Para cada um, gera todas as parcelas em atraso (catch-up).
     * Retorna quantidade de contas geradas.
     */
    public function gerarPendentes(?Carbon $hoje = null): int
    {
        $hoje = $hoje ?? Carbon::today();
        $recorrentes = PagamentoRecorrente::with(['cliente', 'formaPagamento', 'contaBancaria'])
            ->where('ativo', true)
            ->where('proxima_geracao_em', '<=', $hoje)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_fim')->orWhere('data_fim', '>=', $hoje);
            })
            ->get();

        $geradas = 0;
        foreach ($recorrentes as $rec) {
            try {
                $geradas += $this->gerarCatchUpRecorrente($rec, $hoje);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return $geradas;
    }
}
