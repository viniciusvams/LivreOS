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
            ['name' => 'PDV: cancelar venda (total)', 'slug' => 'pdv.cancelar_venda_total', 'description' => 'Cancelar venda no PDV por completo (estorno, conta a pagar, estoque)'],
            ['name' => 'PDV: cancelar venda (parcial)', 'slug' => 'pdv.cancelar_venda_parcial', 'description' => 'Cancelar parcialmente itens de venda finalizada no PDV'],
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
            // Vendedor: só parcial por padrão (total fica com gerência)
            $parcialId = Permission::where('slug', 'pdv.cancelar_venda_parcial')->value('id');
            if ($parcialId) {
                $seller->permissions()->syncWithoutDetaching([$parcialId]);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['pdv.cancelar_venda_total', 'pdv.cancelar_venda_parcial'])->delete();
    }
};
