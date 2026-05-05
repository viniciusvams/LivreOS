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
use App\Models\Permission;
use App\Services\AuditCancelExcluirService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        Permission::ensureEstoqueAvulsoPermissions();
        $query = $this->applyEntityQuery(Permission::query(), 'permission');
        $permissions = $query->paginate(15);
        return erp_view('admin.permissions.index', [
            'title' => 'Gerenciar Permissões',
            'permissions' => $permissions
        ]);
    }

    public function create()
    {
        return erp_view('admin.permissions.create', [
            'title' => 'Criar Permissão'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions',
            'description' => 'nullable|string',
        ]);

        Permission::create($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão criada com sucesso!');
    }

    public function edit(Permission $permission)
    {
        return erp_view('admin.permissions.edit', [
            'title' => 'Editar Permissão',
            'permission' => $permission
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $this->validateWithFilters($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $permission->id,
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão atualizada com sucesso!');
    }

    public function destroy(Request $request, Permission $permission, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'permission')) {
            abort(403, 'Você não tem permissão para excluir permissões.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $descricao = $permission->name ?? $permission->slug ?? 'Permissão #' . $permission->id;
        $entityId = $permission->id;

        $permission->delete();

        $auditService->log('excluir', 'permission', $entityId, $descricao, $request->motivo, $request);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissão deletada com sucesso!');
    }
}
