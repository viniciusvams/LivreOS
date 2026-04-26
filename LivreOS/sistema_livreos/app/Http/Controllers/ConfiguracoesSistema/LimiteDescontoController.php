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

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\LimiteDesconto;
use App\Models\Role;
use App\Services\LimiteDescontoService;
use Illuminate\Http\Request;

class LimiteDescontoController extends Controller
{
    public function edit()
    {
        $config = LimiteDesconto::config()->load('roles');
        $roles = Role::orderBy('name')->get();

        return erp_view('configuracoes_sistema.limites-desconto.edit', [
            'title' => 'Limites de Desconto',
            'config' => $config,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, LimiteDescontoService $limiteService)
    {
        $validated = $this->validateWithFilters($request, [
            'limite_valor' => 'nullable|numeric|min:0',
            'limite_percentual' => 'nullable|numeric|min:0|max:100',
            'bloquear_alteracao_preco' => 'nullable|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $config = LimiteDesconto::config();
        $config->limite_valor = $validated['limite_valor'] !== null && $validated['limite_valor'] !== '' ? $validated['limite_valor'] : null;
        $config->limite_percentual = $validated['limite_percentual'] !== null && $validated['limite_percentual'] !== '' ? $validated['limite_percentual'] : null;
        $config->bloquear_alteracao_preco = $request->boolean('bloquear_alteracao_preco');
        $config->save();

        $roleIds = $request->input('role_ids', []);
        if (!is_array($roleIds)) {
            $roleIds = [];
        }
        $roleIds = array_filter(array_map('intval', $roleIds));
        $config->roles()->sync($roleIds);

        $limiteService->limparCache();

        return redirect()->route('configuracoes-sistema.limites-desconto.edit')
            ->with('success', 'Limites de desconto atualizados com sucesso!');
    }
}
