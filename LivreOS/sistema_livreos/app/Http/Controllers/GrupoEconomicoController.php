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

namespace App\Http\Controllers;

use App\Models\GrupoEconomico;
use Illuminate\Http\Request;

class GrupoEconomicoController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(GrupoEconomico::query()->orderBy('nome'), 'grupo_economico');
        return erp_view('grupos_economicos.index', [
            'grupos' => $query->get(),
        ]);
    }

    public function create()
    {
        return erp_view('grupos_economicos.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'cnpj_matriz' => 'nullable|string|max:18',
            'ativo' => 'nullable|boolean',
        ]);

        GrupoEconomico::create([
            'nome' => $data['nome'],
            'cnpj_matriz' => $data['cnpj_matriz'] ?? null,
            'ativo' => !empty($data['ativo']),
        ]);

        return redirect()
            ->route('grupos-economicos.index')
            ->with('success', 'Grupo econômico cadastrado com sucesso!');
    }

    public function edit(GrupoEconomico $grupoEconomico)
    {
        return erp_view('grupos_economicos.edit', [
            'grupo' => $grupoEconomico,
        ]);
    }

    public function update(Request $request, GrupoEconomico $grupoEconomico)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'cnpj_matriz' => 'nullable|string|max:18',
            'ativo' => 'nullable|boolean',
        ]);

        $grupoEconomico->update([
            'nome' => $data['nome'],
            'cnpj_matriz' => $data['cnpj_matriz'] ?? null,
            'ativo' => !empty($data['ativo']),
        ]);

        return redirect()
            ->route('grupos-economicos.index')
            ->with('success', 'Grupo econômico atualizado com sucesso!');
    }

    public function destroy(GrupoEconomico $grupoEconomico)
    {
        $grupoEconomico->delete();

        return redirect()
            ->route('grupos-economicos.index')
            ->with('success', 'Grupo econômico excluído com sucesso!');
    }
}
