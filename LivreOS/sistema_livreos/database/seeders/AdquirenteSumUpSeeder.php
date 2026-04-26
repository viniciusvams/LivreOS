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

namespace Database\Seeders;

use App\Models\Adquirente;
use App\Models\ContaBancaria;
use App\Models\FormaPagamento;
use App\Models\TaxaAdquirente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adquirente SumUp com taxas sem antecipação (fluxo).
 * Tabela informada: coluna 1 = Visa/Master, coluna 2 = Outros.
 */
class AdquirenteSumUpSeeder extends Seeder
{
    public function run(): void
    {
        $a = Adquirente::updateOrCreate(
            ['codigo' => 'SUMUP'],
            [
                'tenant_id' => null,
                'nome' => 'SumUp',
                'tipo_antecipacao' => 'fluxo',
                'dias_antecipacao' => 1,
                'permite_antecipacao_parcelado' => false,
                'observacoes' => 'Taxas de referência sem antecipação (recebimento em fluxo). Visa/Master vs Outros. Ajuste dias de liquidação conforme contrato SumUp.',
                'ativo' => true,
            ]
        );

        TaxaAdquirente::where('adquirente_id', $a->id)->delete();

        $base = [
            'taxa_por_parcela' => 0,
            'taxa_fixa' => 0,
            'usa_antecipacao' => false,
            'taxa_antecipacao_percentual' => null,
            'dias_recebimento_debito' => 1,
            'dias_recebimento_credito_vista' => 30,
            'dias_recebimento_parcela' => 30,
            'ativo' => true,
        ];

        $visaMaster = [
            'debito' => 1.69,
            'credito_vista' => 2.99,
            'parc_2_6' => 3.49,
            'parc_7_12' => 3.99,
        ];

        $outros = [
            'debito' => 2.59,
            'credito_vista' => 4.20,
            'parc_2_6' => 5.70,
            'parc_7_12' => 5.70,
        ];

        foreach (['visa', 'master'] as $bandeira) {
            $this->criarBlocoTaxas($a->id, $bandeira, $visaMaster, $base);
        }

        $this->criarBlocoTaxas($a->id, 'outros', $outros, $base);

        $this->vincularContaPadrao($a);
        $this->vincularFormasPagamentoCartao($a);
    }

    /**
     * Associa SumUp a Cartão de Crédito e Cartão de Débito (pivô forma_pagamento_adquirente).
     * Não remove outras adquirentes. Só marca como padrão se a forma ainda não tiver adquirente padrão.
     */
    public function vincularFormasPagamentoCartao(Adquirente $adquirente): void
    {
        foreach (['cartao_credito', 'cartao_debito'] as $tipo) {
            $forma = FormaPagamento::where('tipo', $tipo)->first();
            if (! $forma) {
                continue;
            }

            if ($adquirente->formasPagamento()->where('formas_pagamento.id', $forma->id)->exists()) {
                continue;
            }

            $maxOrdem = DB::table('forma_pagamento_adquirente')
                ->where('forma_pagamento_id', $forma->id)
                ->max('ordem');
            $ordem = $maxOrdem !== null ? (int) $maxOrdem + 1 : 0;

            $jaTemPadrao = DB::table('forma_pagamento_adquirente')
                ->where('forma_pagamento_id', $forma->id)
                ->where('padrao', true)
                ->exists();

            $now = now();
            $adquirente->formasPagamento()->attach($forma->id, [
                'padrao' => ! $jaTemPadrao,
                'ordem' => $ordem,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function criarBlocoTaxas(int $adquirenteId, string $bandeira, array $taxas, array $base): void
    {
        $rows = [
            [
                'bandeira' => $bandeira,
                'modalidade' => 'debito',
                'parcelas_min' => null,
                'parcelas_max' => null,
                'taxa_percentual' => $taxas['debito'],
                'observacoes' => 'Débito — SumUp (sem antecipação).',
            ],
            [
                'bandeira' => $bandeira,
                'modalidade' => 'credito_vista',
                'parcelas_min' => null,
                'parcelas_max' => null,
                'taxa_percentual' => $taxas['credito_vista'],
                'observacoes' => 'Crédito à vista — SumUp (sem antecipação).',
            ],
            [
                'bandeira' => $bandeira,
                'modalidade' => 'credito_parcelado',
                'parcelas_min' => 2,
                'parcelas_max' => 6,
                'taxa_percentual' => $taxas['parc_2_6'],
                'observacoes' => 'Crédito parcelado 2–6x — SumUp (sem antecipação).',
            ],
            [
                'bandeira' => $bandeira,
                'modalidade' => 'credito_parcelado',
                'parcelas_min' => 7,
                'parcelas_max' => 12,
                'taxa_percentual' => $taxas['parc_7_12'],
                'observacoes' => 'Crédito parcelado 7–12x — SumUp (sem antecipação).',
            ],
        ];

        foreach ($rows as $t) {
            TaxaAdquirente::create(array_merge($base, $t, ['adquirente_id' => $adquirenteId]));
        }
    }

    private function vincularContaPadrao(Adquirente $adquirente): void
    {
        $conta = ContaBancaria::updateOrCreate(
            ['nome' => 'Conta SumUp', 'tenant_id' => $adquirente->tenant_id],
            [
                'tipo' => 'conta_corrente',
                'banco' => null,
                'agencia' => null,
                'conta' => null,
                'digito' => null,
                'pix' => null,
                'saldo_inicial' => 0,
                'data_saldo_inicial' => null,
                'ativo' => true,
                'observacoes' => 'Conta bancária padrão para recebimentos SumUp.',
            ]
        );
        $adquirente->contas()->sync([$conta->id => ['padrao' => true, 'ordem' => 0]]);
    }
}
