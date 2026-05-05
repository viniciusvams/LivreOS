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
            ['name' => 'Ver pedidos de venda',     'slug' => 'view-pedidos-venda',           'description' => 'Visualizar a lista e detalhes de pedidos de venda'],
            ['name' => 'Criar pedidos de venda',   'slug' => 'create-pedidos-venda',         'description' => 'Criar novos pedidos de venda'],
            ['name' => 'Editar pedidos de venda',  'slug' => 'edit-pedidos-venda',           'description' => 'Editar pedidos de venda em rascunho ou confirmado'],
            ['name' => 'Excluir pedidos de venda', 'slug' => 'delete-pedidos-venda',         'description' => 'Excluir pedidos de venda em rascunho'],
            ['name' => 'Faturar pedido de venda',  'slug' => 'pedidos_venda.faturar',        'description' => 'Faturar pedido de venda (gera financeiro e baixa estoque)'],
            ['name' => 'Cancelar pedido de venda', 'slug' => 'pedidos_venda.cancelar',       'description' => 'Cancelar pedido de venda'],
            ['name' => 'Entregar pedido de venda', 'slug' => 'pedidos_venda.entregar',       'description' => 'Marcar pedido de venda como entregue'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Atribuir todas as permissões ao role admin
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $ids = Permission::whereIn('slug', array_column($permissions, 'slug'))->pluck('id');
            $admin->permissions()->syncWithoutDetaching($ids);
        }

        // Seller: ver, criar, editar, faturar, entregar
        $seller = Role::where('slug', 'seller')->first();
        if ($seller) {
            $sellerSlugs = ['view-pedidos-venda', 'create-pedidos-venda', 'edit-pedidos-venda', 'pedidos_venda.faturar', 'pedidos_venda.entregar'];
            $ids = Permission::whereIn('slug', $sellerSlugs)->pluck('id');
            $seller->permissions()->syncWithoutDetaching($ids);
        }

        // Manager: todas as permissões
        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $ids = Permission::whereIn('slug', array_column($permissions, 'slug'))->pluck('id');
            $manager->permissions()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'view-pedidos-venda',
            'create-pedidos-venda',
            'edit-pedidos-venda',
            'delete-pedidos-venda',
            'pedidos_venda.faturar',
            'pedidos_venda.cancelar',
            'pedidos_venda.entregar',
        ])->delete();
    }
};
