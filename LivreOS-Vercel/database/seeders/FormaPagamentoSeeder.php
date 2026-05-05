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

use App\Models\ContaBancaria;
use App\Models\FormaPagamento;
use Illuminate\Database\Seeder;

class FormaPagamentoSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar contas bancárias padrão
        $contaCaixa = ContaBancaria::where('tipo', 'caixa')->where('nome', 'Caixa')->first();
        $contaCorrente = ContaBancaria::where('tipo', 'conta_corrente')
            ->where('nome', 'Conta Corrente - Pagamentos Eletrônicos')
            ->first();

        $formas = [
            [
                'nome' => 'Pix',
                'tipo' => 'pix',
                'taxa_percentual' => 0,
                'taxa_fixa' => 0,
                'dias_recebimento' => 0,
                'ativo' => true,
                'permite_parcela' => false,
                'conta_bancaria_id' => $contaCorrente?->id, // Eletrônico → Conta Corrente
            ],
            [
                'nome' => 'Boleto',
                'tipo' => 'boleto',
                'taxa_percentual' => 0,
                'taxa_fixa' => 2.50,
                'dias_recebimento' => 2,
                'ativo' => true,
                'permite_parcela' => false,
                'conta_bancaria_id' => $contaCorrente?->id, // Eletrônico → Conta Corrente
            ],
            [
                'nome' => 'Cartão de Crédito',
                'tipo' => 'cartao_credito',
                'taxa_percentual' => 3.99,
                'taxa_fixa' => 0,
                'dias_recebimento' => 30,
                'ativo' => true,
                'permite_parcela' => true,
                'max_parcelas' => 12,
                'conta_bancaria_id' => $contaCorrente?->id, // Eletrônico → Conta Corrente
            ],
            [
                'nome' => 'Cartão de Débito',
                'tipo' => 'cartao_debito',
                'taxa_percentual' => 1.99,
                'taxa_fixa' => 0,
                'dias_recebimento' => 1,
                'ativo' => true,
                'permite_parcela' => false,
                'conta_bancaria_id' => $contaCorrente?->id, // Eletrônico → Conta Corrente
            ],
            [
                'nome' => 'Dinheiro',
                'tipo' => 'dinheiro',
                'taxa_percentual' => 0,
                'taxa_fixa' => 0,
                'dias_recebimento' => 0,
                'ativo' => true,
                'permite_parcela' => false,
                'conta_bancaria_id' => $contaCaixa?->id, // Dinheiro → Caixa
            ],
            [
                'nome' => 'Transferência Bancária',
                'tipo' => 'transferencia',
                'taxa_percentual' => 0,
                'taxa_fixa' => 0,
                'dias_recebimento' => 1,
                'ativo' => true,
                'permite_parcela' => false,
                'conta_bancaria_id' => $contaCorrente?->id, // Eletrônico → Conta Corrente
            ],
        ];

        foreach ($formas as $forma) {
            $formaPagamento = FormaPagamento::firstOrCreate(
                ['tipo' => $forma['tipo']],
                $forma
            );
            
            // Se a forma já existia, atualizar a conta bancária se necessário
            if ($formaPagamento->wasRecentlyCreated === false && $forma['conta_bancaria_id'] && !$formaPagamento->conta_bancaria_id) {
                $formaPagamento->update(['conta_bancaria_id' => $forma['conta_bancaria_id']]);
            }
        }

        if ($contaCaixa && $contaCorrente) {
            $this->command->info('Formas de pagamento associadas às contas bancárias:');
            $this->command->info('- Dinheiro → Caixa');
            $this->command->info('- Pix, Boleto, Cartões, Transferência → Conta Corrente');
        } else {
            $this->command->warn('Contas bancárias padrão não encontradas. Execute ContaBancariaSeeder primeiro.');
        }
    }
}
