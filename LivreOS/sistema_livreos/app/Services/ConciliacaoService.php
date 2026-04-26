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

use App\Models\Adquirente;
use App\Models\FormaPagamento;
use Carbon\Carbon;

/**
 * Serviço responsável por determinar se uma movimentação
 * deve ser conciliada automaticamente no momento da baixa.
 *
 * Regras:
 *  - PIX, Dinheiro, Transferência → instantâneo → sempre conciliado
 *  - dias_recebimento = 0 → sempre conciliado
 *  - Boleto, Cheque → nunca auto-concilia (exceto dias_recebimento = 0)
 *  - Cartão crédito/débito:
 *      - Com adquirente tipo_antecipacao = 'antecipado' ou 'ambos'
 *        → usa dias_antecipacao do adquirente
 *      - Com adquirente tipo_antecipacao = 'fluxo'
 *        → usa dias_recebimento da forma de pagamento
 *      - Sem adquirente → usa dias_recebimento da forma de pagamento
 *      - Se data de liquidação <= hoje → concilia automaticamente
 *      - Se data de liquidação > hoje → fica pendente (em trânsito)
 */
class ConciliacaoService
{
    /** Tipos de pagamento que liquidam instantaneamente */
    private const TIPOS_INSTANTANEOS = ['pix', 'dinheiro', 'transferencia'];

    /** Tipos que nunca auto-conciliam (a menos que dias_recebimento = 0) */
    private const TIPOS_NUNCA_AUTO = ['boleto', 'cheque'];

    /**
     * Calcula os dados de conciliação para uma movimentação de baixa.
     *
     * @param  FormaPagamento  $formaPagamento  Forma de pagamento selecionada na baixa
     * @param  Adquirente|null $adquirente      Adquirente vinculado ao recebimento (cartão)
     * @param  string          $dataBaixa       Data da baixa (Y-m-d)
     *
     * @return array{
     *   conciliado: bool,
     *   data_conciliacao: string|null,
     *   data_prevista: string|null,
     *   obs_liquidacao: string|null
     * }
     */
    /**
     * @param  bool  $cartaoDebitoConciliarNaData  Quando true e a forma for cartão de débito, concilia na data da baixa
     *                                            (recebimento já liquidado na operação — ex.: lançamento na criação do título).
     */
    public static function calcular(
        FormaPagamento $formaPagamento,
        ?Adquirente $adquirente,
        string $dataBaixa,
        bool $cartaoDebitoConciliarNaData = false
    ): array {
        if ($cartaoDebitoConciliarNaData && strtolower((string) $formaPagamento->tipo) === 'cartao_debito') {
            return self::conciliado($dataBaixa);
        }

        // 1. Pagamentos instantâneos ou prazo zero
        if (
            in_array($formaPagamento->tipo, self::TIPOS_INSTANTANEOS)
            || (int) $formaPagamento->dias_recebimento === 0
        ) {
            return self::conciliado($dataBaixa);
        }

        // 2. Boleto e cheque: nunca auto-concilia
        if (in_array($formaPagamento->tipo, self::TIPOS_NUNCA_AUTO)) {
            $dataLiquidacao = Carbon::parse($dataBaixa)
                ->addDays((int) $formaPagamento->dias_recebimento);

            return self::pendente($dataLiquidacao, $formaPagamento->nome);
        }

        // 3. Cartão crédito / débito (ou qualquer outro tipo com prazo)
        return self::calcularCartao($formaPagamento, $adquirente, $dataBaixa);
    }

    /**
     * Lógica específica para cartão com ou sem adquirente configurado.
     */
    private static function calcularCartao(
        FormaPagamento $formaPagamento,
        ?Adquirente $adquirente,
        string $dataBaixa
    ): array {
        $diasRecebimento = (int) $formaPagamento->dias_recebimento;

        if ($adquirente) {
            $tipoAntecipacao = $adquirente->tipo_antecipacao;

            if (in_array($tipoAntecipacao, ['antecipado', 'ambos'])) {
                // Antecipação ativa: recebe em dias_antecipacao independente do tipo
                $diasRecebimento = (int) $adquirente->dias_antecipacao;
            }
            // 'fluxo': usa dias_recebimento da forma de pagamento (já definido acima)
        }

        $dataLiquidacao = Carbon::parse($dataBaixa)->addDays($diasRecebimento);

        // Se a data de liquidação é hoje ou já passou → concilia automaticamente
        if ($dataLiquidacao->lte(now()->endOfDay())) {
            return self::conciliado($dataLiquidacao->toDateString());
        }

        // Liquidação futura → fica pendente (em trânsito)
        $label = $adquirente
            ? "{$formaPagamento->nome} via {$adquirente->nome}"
            : $formaPagamento->nome;

        return self::pendente($dataLiquidacao, $label);
    }

    /**
     * Monta o resultado para movimento já conciliado.
     */
    private static function conciliado(string $dataConciliacao): array
    {
        return [
            'conciliado'      => true,
            'data_conciliacao' => $dataConciliacao,
            'data_prevista'   => $dataConciliacao,
            'obs_liquidacao'  => null,
        ];
    }

    /**
     * Monta o resultado para movimento pendente de conciliação.
     */
    private static function pendente(Carbon $dataLiquidacao, string $label): array
    {
        return [
            'conciliado'      => false,
            'data_conciliacao' => null,
            'data_prevista'   => $dataLiquidacao->toDateString(),
            'obs_liquidacao'  => sprintf(
                'Liquidação prevista em %s (%s)',
                $dataLiquidacao->format('d/m/Y'),
                $label
            ),
        ];
    }

    /**
     * Versão sem adquirente — atalho conveniente para ContaPagar e OS.
     */
    public static function calcularSemAdquirente(
        FormaPagamento $formaPagamento,
        string $dataBaixa
    ): array {
        return self::calcular($formaPagamento, null, $dataBaixa);
    }
}
