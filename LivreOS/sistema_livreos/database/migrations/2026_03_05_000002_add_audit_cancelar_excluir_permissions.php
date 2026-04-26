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
    /**
     * Permissões para cancelar/excluir por módulo (admin sempre pode).
     * Usado pelo AuditCancelExcluirService para verificar acesso.
     */
    public function up(): void
    {
        $permissions = [
            ['name' => 'Cancelar conta a receber', 'slug' => 'financeiro.contas-receber.cancelar', 'description' => 'Cancelar contas a receber (com motivo registrado)'],
            ['name' => 'Excluir conta a receber', 'slug' => 'financeiro.contas-receber.excluir', 'description' => 'Excluir contas a receber (com motivo registrado)'],
            ['name' => 'Cancelar conta a pagar', 'slug' => 'financeiro.contas-pagar.cancelar', 'description' => 'Cancelar contas a pagar'],
            ['name' => 'Excluir conta a pagar', 'slug' => 'financeiro.contas-pagar.excluir', 'description' => 'Excluir contas a pagar'],
            ['name' => 'Excluir ordem de serviço', 'slug' => 'ordem-servico.excluir', 'description' => 'Excluir ordens de serviço'],
            ['name' => 'Excluir fornecedor', 'slug' => 'delete-fornecedores', 'description' => 'Excluir fornecedores/contatos'],
            ['name' => 'Excluir pagamento recorrente', 'slug' => 'financeiro.pagamentos-recorrentes.excluir', 'description' => 'Excluir pagamentos recorrentes'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'financeiro.contas-receber.cancelar',
            'financeiro.contas-receber.excluir',
            'financeiro.contas-pagar.cancelar',
            'financeiro.contas-pagar.excluir',
            'ordem-servico.excluir',
            'delete-fornecedores',
            'financeiro.pagamentos-recorrentes.excluir',
        ])->delete();
    }
};
