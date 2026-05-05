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

use App\Models\Equipamento;
use Illuminate\Http\Request;

class EquipamentoController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'numero_patrimonio' => 'nullable|string|max:100',
            'acessorios' => 'nullable|string|max:2000',
        ]);

        $equipamento = Equipamento::create($data);

        return response()->json([
            'id' => $equipamento->id,
            'cliente_id' => $equipamento->cliente_id,
            'label' => trim(($equipamento->marca ?? '') . ' ' . ($equipamento->modelo ?? '') . ' ' . ($equipamento->numero_serie ?? '')) ?: 'Equipamento #' . $equipamento->id,
        ]);
    }

    public function edit(Equipamento $equipamento)
    {
        $equipamento->load('cliente');

        return erp_view('equipamentos.edit', [
            'title' => 'Editar Equipamento',
            'equipamento' => $equipamento,
        ]);
    }

    public function update(Request $request, Equipamento $equipamento)
    {
        $data = $this->validateWithFilters($request, [
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'numero_patrimonio' => 'nullable|string|max:100',
            'acessorios' => 'nullable|string|max:2000',
        ]);

        $equipamento->update($data);

        return redirect()->route('clientes.show', $equipamento->cliente_id)
            ->with('success', 'Equipamento atualizado com sucesso.');
    }
}
