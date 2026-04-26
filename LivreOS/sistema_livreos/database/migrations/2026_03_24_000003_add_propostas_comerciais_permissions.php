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
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'Ver propostas comerciais', 'slug' => 'view-propostas-comerciais', 'description' => 'Listar e visualizar propostas / orçamentos comerciais (versões)'],
            ['name' => 'Criar propostas comerciais', 'slug' => 'create-propostas-comerciais', 'description' => 'Criar propostas e novas versões'],
            ['name' => 'Editar propostas comerciais', 'slug' => 'edit-propostas-comerciais', 'description' => 'Editar proposta em rascunho e converter em pedido'],
            ['name' => 'Excluir propostas comerciais', 'slug' => 'delete-propostas-comerciais', 'description' => 'Excluir proposta em rascunho (versão atual)'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $slugs = array_column($permissions, 'slug');
        $ids = Permission::whereIn('slug', $slugs)->pluck('id');

        foreach (['admin', 'manager'] as $roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching($ids);
            }
        }

        $seller = Role::where('slug', 'seller')->first();
        if ($seller) {
            $seller->permissions()->syncWithoutDetaching(
                Permission::whereIn('slug', $slugs)->pluck('id')
            );
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'view-propostas-comerciais',
            'create-propostas-comerciais',
            'edit-propostas-comerciais',
            'delete-propostas-comerciais',
        ])->delete();
    }
};
