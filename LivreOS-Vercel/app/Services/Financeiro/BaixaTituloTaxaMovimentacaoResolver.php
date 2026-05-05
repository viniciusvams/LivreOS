<?php

/**
 * Componente da aplicação LivreOS
 *
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace App\Services\Financeiro;

use App\Models\BaixaTitulo;
use App\Models\MovimentacaoFinanceira;

/**
 * Localiza a movimentação de saída da taxa vinculada à baixa do recebimento.
 * Prioriza {@see BaixaTitulo::$taxa_movimentacao_id}; senão usa ordem e metadados
 * (sem depender do texto da descrição, que pode ser editado).
 */
class BaixaTituloTaxaMovimentacaoResolver
{
    public static function findTaxaMovimentacao(BaixaTitulo $baixa, MovimentacaoFinanceira $movEntrada): ?MovimentacaoFinanceira
    {
        if ($baixa->tipo_titulo !== 'conta_receber') {
            return null;
        }

        if ($baixa->taxa_movimentacao_id) {
            $m = MovimentacaoFinanceira::query()->find($baixa->taxa_movimentacao_id);
            if ($m
                && $m->tipo === 'saida'
                && in_array($m->origem, ['outro', 'taxa_recebimento'], true)
                && (int) $m->conta_receber_id === (int) $movEntrada->conta_receber_id
            ) {
                return $m;
            }
        }

        return self::findTaxaPorEstrutura($baixa, $movEntrada);
    }

    /**
     * Primeira saída (taxa) registrada após a entrada da baixa, mesmo título, conta e data.
     */
    public static function findTaxaPorEstrutura(BaixaTitulo $baixa, MovimentacaoFinanceira $movEntrada): ?MovimentacaoFinanceira
    {
        return MovimentacaoFinanceira::query()
            ->where('conta_receber_id', $movEntrada->conta_receber_id)
            ->where('tipo', 'saida')
            ->whereIn('origem', ['outro', 'taxa_recebimento'])
            ->where('conta_bancaria_id', $movEntrada->conta_bancaria_id)
            ->whereDate('data_movimentacao', $baixa->data_baixa)
            ->where('id', '>', $movEntrada->id)
            ->orderBy('id')
            ->first();
    }
}
