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
        $permissions = [
            ['name' => 'Baixar conta a receber',  'slug' => 'financeiro.contas-receber.baixar',  'description' => 'Registrar recebimento (baixar) de conta a receber'],
            ['name' => 'Estornar conta a receber', 'slug' => 'financeiro.contas-receber.estornar', 'description' => 'Estornar baixa de conta a receber (desfaz o recebimento)'],
            ['name' => 'Baixar conta a pagar',     'slug' => 'financeiro.contas-pagar.baixar',    'description' => 'Registrar pagamento (baixar) de conta a pagar'],
            ['name' => 'Estornar conta a pagar',   'slug' => 'financeiro.contas-pagar.estornar',  'description' => 'Estornar baixa de conta a pagar (desfaz o pagamento)'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'financeiro.contas-receber.baixar',
            'financeiro.contas-receber.estornar',
            'financeiro.contas-pagar.baixar',
            'financeiro.contas-pagar.estornar',
        ])->delete();
    }
};
