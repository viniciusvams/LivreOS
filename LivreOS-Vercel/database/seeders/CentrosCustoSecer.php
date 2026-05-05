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

use App\Models\CentroCusto;
use App\Models\User;
use Illuminate\Database\Seeder;

class CentrosCustoSecer extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $userId = $user?->id;

        if (CentroCusto::count() > 0) {
            $this->command->info('Centros de custo já existem. Pulando criação...');
            return;
        }

        $itens = [
            ['codigo' => 'ADM', 'nome' => 'Administrativo', 'descricao' => 'Despesas e receitas da área administrativa', 'ordem' => 1],
            ['codigo' => 'COM', 'nome' => 'Comercial', 'descricao' => 'Vendas, marketing e relacionamento com clientes', 'ordem' => 2],
            ['codigo' => 'OPE', 'nome' => 'Operacional', 'descricao' => 'Atividades operacionais e prestação de serviços', 'ordem' => 3],
            ['codigo' => 'FIN', 'nome' => 'Financeiro', 'descricao' => 'Tesouraria, investimentos e custos financeiros', 'ordem' => 4],
            ['codigo' => 'RH', 'nome' => 'Recursos Humanos', 'descricao' => 'Folha de pagamento, treinamentos e benefícios', 'ordem' => 5],
            ['codigo' => 'TI', 'nome' => 'Tecnologia', 'descricao' => 'Infraestrutura de TI e sistemas', 'ordem' => 6],
        ];

        foreach ($itens as $item) {
            CentroCusto::create([
                'tenant_id' => $user?->tenant_id,
                'codigo' => $item['codigo'],
                'nome' => $item['nome'],
                'descricao' => $item['descricao'],
                'ativo' => true,
                'ordem' => $item['ordem'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        $this->command->info(count($itens) . ' centro(s) de custo criado(s) com sucesso.');
    }
}
