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

use App\Models\ContaPagar;
use App\Models\ContaPagarRecorrente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gera contas a pagar a partir de despesas recorrentes (contas_pagar_recorrentes).
 * Usado pelo comando artisan e pelo catch-up ao salvar um recorrente.
 */
class GerarContasPagarRecorrentesService
{
    /**
     * Gera uma única conta a pagar para o recorrente, se estiver due.
     * Retorna 1 se gerou, 0 se não havia nada a gerar.
     */
    public function gerarUmaConta(ContaPagarRecorrente $rec, ?Carbon $hoje = null): int
    {
        $hoje = $hoje ?? Carbon::today();
        $rec->loadMissing(['fornecedor', 'formaPagamento', 'contaBancaria']);

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

        $observacoes = trim((string) ($rec->observacoes ?? ''));
        $rastreio = sprintf(
            '[Recorrente CP #%d - %s | Venc. gerado: %s | Gerado em %s]',
            $rec->id,
            $rec->descricao,
            $dataVencimento->format('d/m/Y'),
            now()->format('d/m/Y H:i')
        );
        $observacoes = $observacoes === '' ? $rastreio : $observacoes . "\n" . $rastreio;

        try {
            DB::beginTransaction();
            ContaPagar::create([
                'tenant_id' => $rec->tenant_id,
                'fornecedor_id' => $rec->fornecedor_id,
                'ordem_servico_id' => null,
                'conta_pagar_recorrente_id' => $rec->id,
                'plano_conta_id' => $rec->plano_conta_id,
                'descricao' => $rec->descricao . ' (recorrente)',
                'numero_documento' => null,
                'valor' => $rec->valor,
                'valor_original' => $rec->valor,
                'valor_pago' => 0,
                'data_vencimento' => $dataVencimento,
                'data_pagamento' => null,
                'forma_pagamento_id' => $rec->forma_pagamento_id,
                'conta_bancaria_id' => $contaBancariaId,
                'tipo' => 'operacional',
                'status' => 'aberto',
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0,
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
     */
    public function gerarCatchUpRecorrente(ContaPagarRecorrente $rec, ?Carbon $ate = null): int
    {
        $ate = $ate ?? Carbon::today();
        $total = 0;
        $maxIter = 500;
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
        $recorrentes = ContaPagarRecorrente::with(['fornecedor', 'formaPagamento', 'contaBancaria'])
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
