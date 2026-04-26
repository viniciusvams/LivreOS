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
use App\Models\TaxaAdquirente;
use Illuminate\Database\Seeder;

class AdquirenteSeeder extends Seeder
{
    /**
     * Cadastra adquirentes Stone e Infinity Pay com taxas de referência.
     * Infinity Pay: taxas com antecipação incluída (compensa em 1 dia). Visa/Master e Elo/Amex.
     * Stone: taxas médias de mercado (não há tabela pública; conferir com a Stone).
     */
    public function run(): void
    {
        $this->seedInfinityPay();
        $this->seedStone();
    }

    private function seedInfinityPay(): void
    {
        $a = Adquirente::updateOrCreate(
            ['codigo' => 'INFINITYPAY'],
            [
                'tenant_id' => null,
                'nome' => 'Infinity Pay',
                'tipo_antecipacao' => 'antecipado',
                'dias_antecipacao' => 1,
                'permite_antecipacao_parcelado' => true,
                'observacoes' => 'Taxas únicas sobre a venda, já com antecipação de todas as parcelas incluída. Compensa em 1 dia único o total já descontado as taxas. Pix: grátis.',
                'ativo' => true,
            ]
        );

        TaxaAdquirente::where('adquirente_id', $a->id)->delete();

        $baseAttrs = [
            'taxa_por_parcela' => 0,
            'taxa_fixa' => 0,
            'usa_antecipacao' => true,
            'taxa_antecipacao_percentual' => null,
            'dias_recebimento_debito' => 1,
            'dias_recebimento_credito_vista' => 1,
            'dias_recebimento_parcela' => 0,
        ];

        $visaMaster = [
            'debito' => 1.37,
            'credito_vista' => 3.15,
            'parcelado' => [2 => 5.39, 3 => 6.12, 4 => 6.85, 5 => 7.57, 6 => 8.28, 7 => 8.99, 8 => 9.69, 9 => 10.38, 10 => 11.06, 11 => 11.74, 12 => 12.40],
        ];

        $eloAmex = [
            'debito' => 2.58,
            'credito_vista' => 4.91,
            'parcelado' => [2 => 6.47, 3 => 7.20, 4 => 7.92, 5 => 8.63, 6 => 9.33, 7 => 10.03, 8 => 10.72, 9 => 11.41, 10 => 12.08, 11 => 12.75, 12 => 13.41],
        ];

        foreach (['master', 'visa'] as $bandeira) {
            $this->createTaxasPorBandeira($a->id, $bandeira, $visaMaster, $baseAttrs);
        }
        foreach (['elo', 'amex'] as $bandeira) {
            $this->createTaxasPorBandeira($a->id, $bandeira, $eloAmex, $baseAttrs);
        }

        $this->vincularContaPadraoAdquirente($a, 'Conta Infinity Pay', 'Conta bancária padrão para recebimentos da adquirente Infinity Pay.');
    }

    private function vincularContaPadraoAdquirente(Adquirente $adquirente, string $nomeConta, string $observacoes = ''): void
    {
        $conta = ContaBancaria::updateOrCreate(
            ['nome' => $nomeConta, 'tenant_id' => $adquirente->tenant_id],
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
                'observacoes' => $observacoes,
            ]
        );
        $adquirente->contas()->sync([$conta->id => ['padrao' => true, 'ordem' => 0]]);
    }

    private function createTaxasPorBandeira(int $adquirenteId, string $bandeira, array $dados, array $baseAttrs): void
    {
        $rows = [
            [
                'bandeira' => $bandeira,
                'modalidade' => 'debito',
                'parcelas_min' => null,
                'parcelas_max' => null,
                'taxa_percentual' => $dados['debito'],
                'observacoes' => 'Débito (antecipado, D+1).',
            ],
            [
                'bandeira' => $bandeira,
                'modalidade' => 'credito_vista',
                'parcelas_min' => null,
                'parcelas_max' => null,
                'taxa_percentual' => $dados['credito_vista'],
                'observacoes' => 'Crédito à vista (antecipado, D+1).',
            ],
        ];
        foreach ($dados['parcelado'] as $parcelas => $taxa) {
            $rows[] = [
                'bandeira' => $bandeira,
                'modalidade' => 'credito_parcelado',
                'parcelas_min' => $parcelas,
                'parcelas_max' => $parcelas,
                'taxa_percentual' => $taxa,
                'observacoes' => "Crédito {$parcelas}x (antecipado, D+1).",
            ];
        }
        foreach ($rows as $t) {
            TaxaAdquirente::create(array_merge($t, $baseAttrs, [
                'adquirente_id' => $adquirenteId,
                'ativo' => true,
            ]));
        }
    }

    private function seedStone(): void
    {
        $a = Adquirente::updateOrCreate(
            ['codigo' => 'STONE'],
            [
                'tenant_id' => null,
                'nome' => 'Stone',
                'tipo_antecipacao' => 'antecipado',
                'dias_antecipacao' => 1,
                'permite_antecipacao_parcelado' => true,
                'observacoes' => 'Taxas antecipadas em 1 dia útil. Master/Visa e Elo/Amex conforme tabela Stone.',
                'ativo' => true,
            ]
        );

        TaxaAdquirente::where('adquirente_id', $a->id)->delete();

        $baseAttrs = [
            'taxa_por_parcela' => 0,
            'taxa_fixa' => 0,
            'usa_antecipacao' => true,
            'taxa_antecipacao_percentual' => null,
            'dias_recebimento_debito' => 1,
            'dias_recebimento_credito_vista' => 1,
            'dias_recebimento_parcela' => 0,
        ];

        $visaMaster = [
            'debito' => 0.89,
            'credito_vista' => 2.99,
            'parcelado' => [2 => 5.79, 3 => 6.99, 4 => 7.99, 5 => 8.99, 6 => 9.99, 7 => 10.99, 8 => 11.79, 9 => 11.99, 10 => 12.29, 11 => 12.29, 12 => 12.29],
        ];

        $eloAmex = [
            'debito' => 2.08,
            'credito_vista' => 4.18,
            'parcelado' => [2 => 7.18, 3 => 8.38, 4 => 9.38, 5 => 10.38, 6 => 11.38, 7 => 12.38, 8 => 13.18, 9 => 13.38, 10 => 13.68, 11 => 13.68, 12 => 13.68],
        ];

        foreach (['master', 'visa'] as $bandeira) {
            $this->createTaxasPorBandeira($a->id, $bandeira, $visaMaster, $baseAttrs);
        }
        foreach (['elo', 'amex'] as $bandeira) {
            $this->createTaxasPorBandeira($a->id, $bandeira, $eloAmex, $baseAttrs);
        }

        $this->vincularContaPadraoAdquirente($a, 'Conta Stone', 'Conta bancária padrão para recebimentos da adquirente Stone.');
    }
}

