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

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Criar Permissões
        $permissions = [
            ['name' => 'Ver Dashboard', 'slug' => 'view-dashboard'],
            ['name' => 'Gerenciar Usuários', 'slug' => 'manage-users'],
            ['name' => 'Gerenciar Roles', 'slug' => 'manage-roles'],
            ['name' => 'Gerenciar Permissões', 'slug' => 'manage-permissions'],
            ['name' => 'Criar Usuários', 'slug' => 'create-users'],
            ['name' => 'Editar Usuários', 'slug' => 'edit-users'],
            ['name' => 'Deletar Usuários', 'slug' => 'delete-users'],

            ['name' => 'Ver Produtos', 'slug' => 'view-products'],
            ['name' => 'Criar Produtos', 'slug' => 'create-products'],
            ['name' => 'Editar Produtos', 'slug' => 'edit-products'],
            ['name' => 'Deletar Produtos', 'slug' => 'delete-products'],

            ['name' => 'Ver Serviços', 'slug' => 'view-services'],
            ['name' => 'Criar Serviços', 'slug' => 'create-services'],
            ['name' => 'Editar Serviços', 'slug' => 'edit-services'],
            ['name' => 'Deletar Serviços', 'slug' => 'delete-services'],

            ['name' => 'Ver Clientes', 'slug' => 'view-clients'],
            ['name' => 'Criar Clientes', 'slug' => 'create-clients'],
            ['name' => 'Editar Clientes', 'slug' => 'edit-clients'],
            ['name' => 'Deletar Clientes', 'slug' => 'delete-clients'],

            ['name' => 'Ver Histórico de OS', 'slug' => 'view-os-history'],

            ['name' => 'Acessar Financeiro', 'slug' => 'access-financeiro', 'description' => 'Acessar módulo financeiro (contas, movimentações, relatórios)'],

            ['name' => 'Gerenciar Plugins', 'slug' => 'manage-plugins', 'description' => 'Instalar, ativar, desativar e excluir plugins'],

            ['name' => 'Criar movimentação (ajuste)', 'slug' => 'financeiro.movimentacoes.create', 'description' => 'Criar nova movimentação manual de ajuste no financeiro'],
            ['name' => 'Cancelar conciliação', 'slug' => 'financeiro.movimentacoes.desconciliar', 'description' => 'Desconciliar movimentações financeiras'],

            ['name' => 'Cancelar transferência entre contas', 'slug' => 'financeiro.transferencias.cancelar', 'description' => 'Cancelar transferência entre contas (com registro de motivo e responsável)'],

            ['name' => 'Vender com estoque zero', 'slug' => 'produtos.vender_estoque_zero', 'description' => 'Permite incluir produtos com estoque zero ou insuficiente na OS mesmo quando a regra de bloqueio está ativa'],
            ['name' => 'Cadastrar produto/serviço avulso na OS', 'slug' => 'ordem_servico.produto_servico_avulso', 'description' => 'Permite adicionar produto avulso e serviço avulso (itens sem vínculo com cadastro) na ordem de serviço'],

            ['name' => 'Ver pedidos de venda', 'slug' => 'view-pedidos-venda', 'description' => 'Visualizar a lista e detalhes de pedidos de venda'],
            ['name' => 'Criar pedidos de venda', 'slug' => 'create-pedidos-venda', 'description' => 'Criar novos pedidos de venda'],
            ['name' => 'Editar pedidos de venda', 'slug' => 'edit-pedidos-venda', 'description' => 'Editar pedidos de venda em rascunho ou confirmado'],
            ['name' => 'Excluir pedidos de venda', 'slug' => 'delete-pedidos-venda', 'description' => 'Excluir pedidos de venda em rascunho'],
            ['name' => 'Faturar pedido de venda', 'slug' => 'pedidos_venda.faturar', 'description' => 'Faturar pedido de venda (gera financeiro e baixa estoque)'],
            ['name' => 'Cancelar pedido de venda', 'slug' => 'pedidos_venda.cancelar', 'description' => 'Cancelar pedido de venda'],
            ['name' => 'Entregar pedido de venda', 'slug' => 'pedidos_venda.entregar', 'description' => 'Marcar pedido de venda como entregue'],
            ['name' => 'Aplicar desconto em pedidos de venda', 'slug' => 'pedidos_venda.aplicar_desconto', 'description' => 'Permite informar ou aumentar desconto por item e desconto global (sujeito a limites em Configurações → Limites de desconto).'],

            ['name' => 'Ver propostas comerciais', 'slug' => 'view-propostas-comerciais', 'description' => 'Listar e visualizar propostas / orçamentos comerciais'],
            ['name' => 'Criar propostas comerciais', 'slug' => 'create-propostas-comerciais', 'description' => 'Criar propostas e novas versões'],
            ['name' => 'Editar propostas comerciais', 'slug' => 'edit-propostas-comerciais', 'description' => 'Editar proposta em rascunho e converter em pedido'],
            ['name' => 'Excluir propostas comerciais', 'slug' => 'delete-propostas-comerciais', 'description' => 'Excluir proposta em rascunho'],
            ['name' => 'Gerir modelos de documento de propostas', 'slug' => 'manage-proposta-documento-modelos', 'description' => 'Cadastrar e editar modelos Word (.docx) e HTML (.html) para propostas comerciais'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        // Criar Roles
        $adminRole = Role::firstOrCreate([
            'slug' => 'admin',
        ], [
            'name' => 'Administrador',
            'description' => 'Acesso total ao sistema',
        ]);

        $managerRole = Role::firstOrCreate([
            'slug' => 'manager',
        ], [
            'name' => 'Gerente',
            'description' => 'Pode gerenciar usuários e visualizar relatórios',
        ]);

        $userRole = Role::firstOrCreate([
            'slug' => 'user',
        ], [
            'name' => 'Usuário',
            'description' => 'Acesso básico ao sistema',
        ]);

        $sellerRole = Role::firstOrCreate([
            'slug' => 'seller',
        ], [
            'name' => 'Vendedor',
            'description' => 'Acesso comercial a produtos, serviços e clientes',
        ]);

        $attendantRole = Role::firstOrCreate([
            'slug' => 'attendant',
        ], [
            'name' => 'Atendente',
            'description' => 'Atendimento e cadastro básico',
        ]);

        $technicianRole = Role::firstOrCreate([
            'slug' => 'technician',
        ], [
            'name' => 'Técnico',
            'description' => 'Acesso técnico e atualização de atendimentos',
        ]);

        // Atribuir todas as permissões ao role Admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id')->all());

        $managerSlugs = [
            'view-dashboard', 'manage-users', 'create-users', 'edit-users',
            'view-pedidos-venda', 'create-pedidos-venda', 'edit-pedidos-venda', 'delete-pedidos-venda',
            'pedidos_venda.faturar', 'pedidos_venda.cancelar', 'pedidos_venda.entregar', 'pedidos_venda.aplicar_desconto',
            'view-propostas-comerciais', 'create-propostas-comerciais', 'edit-propostas-comerciais', 'delete-propostas-comerciais',
            'manage-proposta-documento-modelos',
        ];
        $managerRole->permissions()->sync(
            Permission::whereIn('slug', $managerSlugs)->pluck('id')->all()
        );

        $sellerSlugs = [
            'view-products', 'create-products', 'edit-products',
            'view-services', 'create-services', 'edit-services',
            'view-clients', 'create-clients', 'edit-clients',
            'view-os-history', 'access-financeiro',
            'view-pedidos-venda', 'create-pedidos-venda', 'edit-pedidos-venda', 'pedidos_venda.faturar', 'pedidos_venda.entregar', 'pedidos_venda.aplicar_desconto',
            'view-propostas-comerciais', 'create-propostas-comerciais', 'edit-propostas-comerciais', 'delete-propostas-comerciais',
        ];
        $sellerRole->permissions()->sync(
            Permission::whereIn('slug', $sellerSlugs)->pluck('id')->all()
        );

        $attendantRole->permissions()->sync([
            Permission::where('slug', 'view-services')->first()->id,
            Permission::where('slug', 'create-services')->first()->id,
            Permission::where('slug', 'edit-services')->first()->id,
            Permission::where('slug', 'view-clients')->first()->id,
            Permission::where('slug', 'create-clients')->first()->id,
            Permission::where('slug', 'edit-clients')->first()->id,
            Permission::where('slug', 'view-os-history')->first()->id,
            Permission::where('slug', 'access-financeiro')->first()->id,
        ]);

        $technicianRole->permissions()->sync([
            Permission::where('slug', 'view-services')->first()->id,
            Permission::where('slug', 'edit-services')->first()->id,
            Permission::where('slug', 'view-clients')->first()->id,
            Permission::where('slug', 'view-os-history')->first()->id,
        ]);

        // Criar usuário admin padrão
        $admin = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $vendedor = User::firstOrCreate([
            'email' => 'vendedor@erp.test',
        ], [
            'name' => 'Vendedor',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        $vendedor->roles()->sync([$sellerRole->id]);

        $atendente = User::firstOrCreate([
            'email' => 'atendente@erp.test',
        ], [
            'name' => 'Atendente',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        $atendente->roles()->sync([$attendantRole->id]);

        $tecnico = User::firstOrCreate([
            'email' => 'tecnico@erp.test',
        ], [
            'name' => 'Técnico',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        $tecnico->roles()->sync([$technicianRole->id]);
    }
}
