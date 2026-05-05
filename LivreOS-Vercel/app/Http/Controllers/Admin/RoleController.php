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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Services\AuditCancelExcluirService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(Role::with('permissions'), 'role');
        $roles = $query->paginate(25);
        $permissionGroupName = $this->getPermissionGroupNameMap();
        return erp_view('admin.roles.index', [
            'title' => 'Gerenciar Roles',
            'roles' => $roles,
            'permissionGroupName' => $permissionGroupName,
            'fullWidth' => true
        ]);
    }

    public function create()
    {
        Permission::ensureEstoqueAvulsoPermissions();
        $permissions = Permission::orderBy('name')->get();
        $permissionsGrouped = $this->groupPermissionsByModule($permissions);
        return erp_view('admin.roles.create', [
            'title' => 'Criar Role',
            'permissions' => $permissions,
            'permissionsGrouped' => $permissionsGrouped
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        $role = Role::create($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role criada com sucesso!');
    }

    public function edit(Role $role)
    {
        Permission::ensureEstoqueAvulsoPermissions();
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->get();
        $permissionsGrouped = $this->groupPermissionsByModule($permissions);
        return erp_view('admin.roles.edit', [
            'title' => 'Editar Role',
            'role' => $role,
            'permissions' => $permissions,
            'permissionsGrouped' => $permissionsGrouped
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validateWithFilters($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        $role->update($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role atualizada com sucesso!');
    }

    public function destroy(Request $request, Role $role, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'role')) {
            abort(403, 'Você não tem permissão para excluir roles.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $descricao = $role->name ?? $role->slug ?? 'Role #' . $role->id;
        $entityId = $role->id;

        $role->delete();

        $auditService->log('excluir', 'role', $entityId, $descricao, $request->motivo, $request);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deletada com sucesso!');
    }

    /**
     * Agrupa permissões por módulo/perfil para exibição.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Permission>  $permissions
     * @return array<string, \Illuminate\Support\Collection<int, \App\Models\Permission>>
     */
    protected function groupPermissionsByModule($permissions)
    {
        $groups = [];
        foreach ($permissions as $permission) {
            $group = $this->getPermissionGroupName($permission->slug);
            $groups[$group][$permission->id] = $permission;
        }

        foreach ($groups as $g => $items) {
            $groups[$g] = collect($items)->values();
        }

        return $groups;
    }

    /**
     * Retorna o nome do grupo/módulo de uma permissão a partir do slug.
     */
    protected function getPermissionGroupName(string $slug): string
    {
        $slug = strtolower($slug);
        $map = [
            'dashboard' => 'Dashboard',
            'users' => 'Usuários',
            'user' => 'Usuários',
            'roles' => 'Roles',
            'role' => 'Roles',
            'permissions' => 'Permissões',
            'permission' => 'Permissões',
            'products' => 'Produtos',
            'product' => 'Produtos',
            'produtos' => 'Produtos',
            'services' => 'Serviços',
            'service' => 'Serviços',
            'clients' => 'Clientes',
            'client' => 'Clientes',
            'plugins' => 'Plugins',
            'plugin' => 'Plugins',
            'financeiro' => 'Financeiro',
            'movimentacoes' => 'Financeiro - Movimentações',
            'transferencias' => 'Financeiro - Transferências',
            'os-history' => 'Ordens de Serviço',
            'os' => 'Ordens de Serviço',
            'ordem_servico' => 'Ordens de Serviço',
        ];

        if (str_contains($slug, '.')) {
            $prefix = explode('.', $slug)[0];
            return $map[$prefix] ?? ucfirst($prefix);
        }
        foreach ($map as $key => $label) {
            if ($key !== 'os' && $key !== 'os-history' && str_contains($slug, $key)) {
                return $label;
            }
            if (($key === 'os-history' || $key === 'os') && str_contains($slug, 'os')) {
                return $label;
            }
        }

        return 'Outros';
    }

    /**
     * Mapa permission_id => nome do grupo para uso na listagem.
     *
     * @return array<int, string>
     */
    protected function getPermissionGroupNameMap(): array
    {
        $permissions = Permission::all();
        $out = [];
        foreach ($permissions as $p) {
            $out[$p->id] = $this->getPermissionGroupName($p->slug);
        }
        return $out;
    }
}
