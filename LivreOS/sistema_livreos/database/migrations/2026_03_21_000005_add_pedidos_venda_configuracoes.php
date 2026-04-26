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

use App\Models\Configuracao;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.estoque_modo'],
            ['valor' => 'faturar']
        );

        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.gerar_os_servico'],
            ['valor' => '0']
        );

        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.plano_conta_receita'],
            ['valor' => '']
        );

        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.centro_custo'],
            ['valor' => '']
        );
    }

    public function down(): void
    {
        Configuracao::whereIn('chave', [
            'pedidos_venda.estoque_modo',
            'pedidos_venda.gerar_os_servico',
            'pedidos_venda.plano_conta_receita',
            'pedidos_venda.centro_custo',
        ])->delete();
    }
};
