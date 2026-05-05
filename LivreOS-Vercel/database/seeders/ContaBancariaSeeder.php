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
use App\Models\User;
use Illuminate\Database\Seeder;

class ContaBancariaSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar usuário para created_by
        $user = User::first();
        if (!$user) {
            $this->command->warn('Nenhum usuário encontrado. Crie um usuário primeiro.');
            return;
        }

        // Verificar se já existem contas cadastradas
        if (ContaBancaria::count() > 0) {
            $this->command->info('Contas bancárias já existem. Pulando criação...');
            return;
        }

        // 1. Conta Caixa (para recebimentos em dinheiro)
        ContaBancaria::create([
            'nome' => 'Caixa',
            'tipo' => 'caixa',
            'banco' => null,
            'agencia' => null,
            'conta' => null,
            'digito' => null,
            'pix' => null,
            'saldo_inicial' => 0,
            'data_saldo_inicial' => now(),
            'ativo' => true,
            'observacoes' => 'Conta padrão para recebimentos em dinheiro',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // 2. Conta Corrente (para pagamentos eletrônicos)
        ContaBancaria::create([
            'nome' => 'Conta Corrente - Pagamentos Eletrônicos',
            'tipo' => 'conta_corrente',
            'banco' => null, // Pode ser preenchido posteriormente
            'agencia' => null,
            'conta' => null,
            'digito' => null,
            'pix' => null,
            'saldo_inicial' => 0,
            'data_saldo_inicial' => now(),
            'ativo' => true,
            'observacoes' => 'Conta padrão para recebimentos de pagamentos eletrônicos (PIX, cartão, boleto, etc.)',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->command->info('2 contas bancárias padrão criadas com sucesso!');
        $this->command->info('- Caixa (tipo: caixa)');
        $this->command->info('- Conta Corrente - Pagamentos Eletrônicos (tipo: conta_corrente)');
    }
}
