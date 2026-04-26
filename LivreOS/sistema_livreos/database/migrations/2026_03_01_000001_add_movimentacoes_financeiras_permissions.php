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
     * Adiciona permissões de movimentações financeiras (nova movimentação e cancelar conciliação).
     * Administradores já têm acesso via is_admin; demais usuários precisam da permissão ou do role com a permissão.
     */
    public function up(): void
    {
        Permission::firstOrCreate(
            ['slug' => 'financeiro.movimentacoes.create'],
            [
                'name' => 'Criar movimentação (ajuste)',
                'description' => 'Criar nova movimentação manual de ajuste no financeiro',
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'financeiro.movimentacoes.desconciliar'],
            [
                'name' => 'Cancelar conciliação',
                'description' => 'Desconciliar movimentações financeiras',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('slug', [
            'financeiro.movimentacoes.create',
            'financeiro.movimentacoes.desconciliar',
        ])->delete();
    }
};
