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

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'produtos.vender_estoque_zero'],
            [
                'name' => 'Vender com estoque zero',
                'description' => 'Permite incluir produtos com estoque zero ou insuficiente na OS mesmo quando a regra de bloqueio está ativa. O administrador sempre pode.',
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'ordem_servico.produto_servico_avulso'],
            [
                'name' => 'Cadastrar produto/serviço avulso na OS',
                'description' => 'Permite adicionar produto avulso e serviço avulso (itens sem vínculo com cadastro) na ordem de serviço. O administrador sempre pode.',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('slug', 'produtos.vender_estoque_zero')->delete();
        Permission::where('slug', 'ordem_servico.produto_servico_avulso')->delete();
    }
};
