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

use App\Models\Deposito;
use Illuminate\Http\Request;

class DepositoController extends Controller
{
    public function index(Request $request)
    {
        $query = Deposito::query();
        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }
        $query = $this->applyEntityQuery($query->orderBy('nome'), 'deposito');
        $depositos = $query->paginate(15);

        return erp_view('depositos.index', [
            'title' => 'Depósitos',
            'depositos' => $depositos,
        ]);
    }

    public function create()
    {
        return erp_view('depositos.create', [
            'title' => 'Novo Depósito',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'localizacao' => 'nullable|string|max:150',
            'ativo' => 'boolean',
        ]);

        $data['ativo'] = !empty($data['ativo']);
        Deposito::create($data);

        return redirect()->route('depositos.index')
            ->with('success', 'Depósito cadastrado com sucesso!');
    }

    public function edit(Deposito $deposito)
    {
        return erp_view('depositos.edit', [
            'title' => 'Editar Depósito',
            'deposito' => $deposito,
        ]);
    }

    public function update(Request $request, Deposito $deposito)
    {
        $data = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'localizacao' => 'nullable|string|max:150',
            'ativo' => 'boolean',
        ]);

        $data['ativo'] = !empty($data['ativo']);
        $deposito->update($data);

        return redirect()->route('depositos.index')
            ->with('success', 'Depósito atualizado com sucesso!');
    }

    public function destroy(Deposito $deposito)
    {
        $deposito->delete();

        return redirect()->route('depositos.index')
            ->with('success', 'Depósito excluído com sucesso!');
    }
}
