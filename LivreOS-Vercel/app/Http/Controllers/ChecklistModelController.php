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

use App\Models\ChecklistModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChecklistModelController extends Controller
{
    public function index(Request $request)
    {
        $query = ChecklistModel::query();

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        $ordenarPor = $request->input('ordenar_por', 'nome');
        $direcao = $request->input('direcao', 'asc') === 'desc' ? 'desc' : 'asc';
        $permitidos = ['nome', 'created_at', 'updated_at'];
        if (!in_array($ordenarPor, $permitidos, true)) {
            $ordenarPor = 'nome';
        }

        $porPagina = (int) $request->input('por_pagina', 15);
        if (!in_array($porPagina, [15, 30, 50, 100], true)) {
            $porPagina = 15;
        }

        $query = $this->applyEntityQuery($query->orderBy($ordenarPor, $direcao), 'checklist_model');
        $checklistModels = $query->paginate($porPagina)->withQueryString();

        return erp_view('checklist_models.index', [
            'title' => 'Modelos de Checklist',
            'checklistModels' => $checklistModels,
        ]);
    }

    public function create()
    {
        return erp_view('checklist_models.create', [
            'title' => 'Novo Modelo de Checklist',
            'checklistModel' => new ChecklistModel(),
        ]);
    }

    public function store(Request $request)
    {
        // Decodificar campos JSON se vier como string
        $campos = $request->input('campos');
        if (is_string($campos)) {
            $campos = json_decode($campos, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Erro ao processar campos do checklist. Verifique o formato JSON.');
            }
            $request->merge(['campos' => $campos]);
        }

        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'campos' => 'required|array|min:1',
            'campos.*.label' => 'required|string|max:255',
            'campos.*.tipo' => 'required|in:texto,numero,selecao,checkbox,data',
            'campos.*.obrigatorio' => 'nullable|boolean',
            'campos.*.opcoes' => 'required_if:campos.*.tipo,selecao|array',
            'ativo' => 'nullable|boolean',
        ], [
            'nome.required' => 'O nome do checklist é obrigatório.',
            'campos.required' => 'É necessário adicionar pelo menos um campo ao checklist.',
            'campos.min' => 'É necessário adicionar pelo menos um campo ao checklist.',
            'campos.*.label.required' => 'O label do campo é obrigatório.',
            'campos.*.tipo.required' => 'O tipo do campo é obrigatório.',
            'campos.*.tipo.in' => 'O tipo do campo deve ser: texto, numero, selecao, checkbox ou data.',
            'campos.*.opcoes.required_if' => 'Campos do tipo seleção devem ter opções definidas.',
        ]);

        try {
            DB::beginTransaction();

            $checklistModel = ChecklistModel::create([
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'campos' => $validated['campos'],
                'ativo' => $validated['ativo'] ?? true,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('checklist-models.index')
                ->with('success', 'Modelo de checklist criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar modelo de checklist: ' . $e->getMessage());
        }
    }

    public function show(ChecklistModel $checklistModel)
    {
        return erp_view('checklist_models.show', [
            'title' => 'Detalhes do Modelo de Checklist',
            'checklistModel' => $checklistModel,
        ]);
    }

    public function edit(ChecklistModel $checklistModel)
    {
        return erp_view('checklist_models.edit', [
            'title' => 'Editar Modelo de Checklist',
            'checklistModel' => $checklistModel,
        ]);
    }

    public function update(Request $request, ChecklistModel $checklistModel)
    {
        // Decodificar campos JSON se vier como string
        $campos = $request->input('campos');
        if (is_string($campos)) {
            $campos = json_decode($campos, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Erro ao processar campos do checklist. Verifique o formato JSON.');
            }
            $request->merge(['campos' => $campos]);
        }

        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'campos' => 'required|array|min:1',
            'campos.*.label' => 'required|string|max:255',
            'campos.*.tipo' => 'required|in:texto,numero,selecao,checkbox,data',
            'campos.*.obrigatorio' => 'nullable|boolean',
            'campos.*.opcoes' => 'required_if:campos.*.tipo,selecao|array',
            'ativo' => 'nullable|boolean',
        ], [
            'nome.required' => 'O nome do checklist é obrigatório.',
            'campos.required' => 'É necessário adicionar pelo menos um campo ao checklist.',
            'campos.min' => 'É necessário adicionar pelo menos um campo ao checklist.',
            'campos.*.label.required' => 'O label do campo é obrigatório.',
            'campos.*.tipo.required' => 'O tipo do campo é obrigatório.',
            'campos.*.tipo.in' => 'O tipo do campo deve ser: texto, numero, selecao, checkbox ou data.',
            'campos.*.opcoes.required_if' => 'Campos do tipo seleção devem ter opções definidas.',
        ]);

        try {
            DB::beginTransaction();

            $checklistModel->update([
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'campos' => $validated['campos'],
                'ativo' => $validated['ativo'] ?? true,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('checklist-models.index')
                ->with('success', 'Modelo de checklist atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar modelo de checklist: ' . $e->getMessage());
        }
    }

    public function destroy(ChecklistModel $checklistModel)
    {
        try {
            // Verificar se há respostas vinculadas
            if ($checklistModel->answers()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Não é possível excluir este modelo pois existem checklists preenchidos vinculados a ele.');
            }

            $checklistModel->delete();

            return redirect()->route('checklist-models.index')
                ->with('success', 'Modelo de checklist excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir modelo de checklist: ' . $e->getMessage());
        }
    }
}
