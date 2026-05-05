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
use App\Models\StatusOs;
use App\Models\OrdemServico;
use Illuminate\Http\Request;

class StatusOsController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(
            StatusOs::orderBy('ordem')->orderBy('nome'),
            'status_os'
        );
        $statusList = $query->paginate(20);

        return erp_view('configuracoes_sistema.status-os.index', [
            'title' => 'Status de Ordem de Serviço',
            'statusList' => $statusList,
        ]);
    }

    public function create()
    {
        return erp_view('configuracoes_sistema.status-os.create', [
            'title' => 'Novo Status',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
            'marca_inicio' => 'boolean',
            'marca_conclusao' => 'boolean',
        ]);

        StatusOs::create([
            'nome' => $validated['nome'],
            'cor' => $validated['cor'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $request->boolean('ativo'),
            'sistema' => false,
            'marca_inicio' => $request->boolean('marca_inicio'),
            'marca_conclusao' => $request->boolean('marca_conclusao'),
        ]);

        return redirect()->route('configuracoes-sistema.status-os.index')
            ->with('success', 'Status criado com sucesso!');
    }

    public function edit(StatusOs $statusOs)
    {
        return erp_view('configuracoes_sistema.status-os.edit', [
            'title' => 'Editar Status',
            'status' => $statusOs,
        ]);
    }

    public function update(Request $request, StatusOs $statusOs)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:80',
            'cor' => 'nullable|string|max:20',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
            'marca_inicio' => 'boolean',
            'marca_conclusao' => 'boolean',
        ]);

        $update = [
            'nome' => $validated['nome'],
            'cor' => $validated['cor'] ?? null,
            'ordem' => $validated['ordem'] ?? 0,
            'ativo' => $request->boolean('ativo'),
            'marca_inicio' => $request->boolean('marca_inicio'),
            'marca_conclusao' => $request->boolean('marca_conclusao'),
        ];

        if ($statusOs->sistema) {
            unset($update['nome']);
        }

        $statusOs->update($update);

        return redirect()->route('configuracoes-sistema.status-os.index')
            ->with('success', 'Status atualizado com sucesso!');
    }

    public function destroy(StatusOs $statusOs)
    {
        if ($statusOs->sistema) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir status do sistema.');
        }

        $uso = OrdemServico::where('status', $statusOs->nome)->count();
        if ($uso > 0) {
            return redirect()->back()
                ->with('error', "Não é possível excluir este status pois existem {$uso} OS(ões) utilizando-o.");
        }

        $statusOs->delete();

        return redirect()->route('configuracoes-sistema.status-os.index')
            ->with('success', 'Status excluído com sucesso!');
    }
}
