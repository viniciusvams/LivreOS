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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_venda_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('pedido_venda_itens', 'quantidade_cancelada')) {
                $table->decimal('quantidade_cancelada', 12, 4)->default(0)->after('quantidade');
            }
            if (! Schema::hasColumn('pedido_venda_itens', 'desconto_inicial')) {
                $table->decimal('desconto_inicial', 10, 2)->nullable()->after('desconto');
            }
        });

        if (Schema::hasColumn('pedido_venda_itens', 'desconto_inicial')) {
            DB::table('pedido_venda_itens')->whereNull('desconto_inicial')->update(['desconto_inicial' => DB::raw('desconto')]);
        }

        $permissions = [
            ['name' => 'Pedido venda: cancelar (total)', 'slug' => 'pedidos_venda.cancelar_total', 'description' => 'Cancelar pedido por completo (incl. faturado: estorno)'],
            ['name' => 'Pedido venda: cancelar (parcial)', 'slug' => 'pedidos_venda.cancelar_parcial', 'description' => 'Cancelar itens parcialmente em pedido faturado'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $legacy = Permission::where('slug', 'pedidos_venda.cancelar')->first();
        $newSlugs = ['pedidos_venda.cancelar_total', 'pedidos_venda.cancelar_parcial'];
        $newIds = Permission::whereIn('slug', $newSlugs)->pluck('id');

        foreach (['admin', 'manager'] as $roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching($newIds);
            }
        }

        // Quem tinha pedidos_venda.cancelar recebe as duas novas
        if ($legacy) {
            $rolesComLegado = Role::whereHas('permissions', fn ($q) => $q->where('slug', 'pedidos_venda.cancelar'))->get();
            foreach ($rolesComLegado as $role) {
                $role->permissions()->syncWithoutDetaching($newIds);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pedido_venda_itens', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_venda_itens', 'quantidade_cancelada')) {
                $table->dropColumn('quantidade_cancelada');
            }
            if (Schema::hasColumn('pedido_venda_itens', 'desconto_inicial')) {
                $table->dropColumn('desconto_inicial');
            }
        });

        Permission::whereIn('slug', ['pedidos_venda.cancelar_total', 'pedidos_venda.cancelar_parcial'])->delete();
    }
};
