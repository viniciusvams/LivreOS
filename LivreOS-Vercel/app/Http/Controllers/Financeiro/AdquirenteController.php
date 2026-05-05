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

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Adquirente;
use App\Models\ContaBancaria;
use App\Models\FormaPagamento;
use Illuminate\Http\Request;

class AdquirenteController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(
            Adquirente::with(['createdBy', 'updatedBy', 'contas', 'taxas'])->orderBy('nome'),
            'adquirente'
        );
        $adquirentes = $query->paginate(15);

        return erp_view('financeiro.adquirentes.index', [
            'title' => 'Adquirentes',
            'adquirentes' => $adquirentes,
        ]);
    }

    public function create()
    {
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.adquirentes.create', [
            'title' => 'Nova Adquirente',
            'contasBancarias' => $contasBancarias,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'codigo' => 'nullable|string|max:50',
            'tipo_antecipacao' => 'required|in:fluxo,antecipado,ambos',
            'dias_antecipacao' => 'required|integer|min:1|max:30',
            'permite_antecipacao_parcelado' => 'boolean',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
            'contas' => 'nullable|array',
            'contas.*' => 'exists:contas_bancarias,id',
            'contas_padrao' => 'nullable|exists:contas_bancarias,id',
            'formas_pagamento' => 'nullable|array',
            'formas_pagamento.*' => 'exists:formas_pagamento,id',
            'formas_pagamento_padrao' => 'nullable|exists:formas_pagamento,id',
        ]);

        $adquirente = Adquirente::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'nome' => $validated['nome'],
            'codigo' => $validated['codigo'] ?? null,
            'tipo_antecipacao' => $validated['tipo_antecipacao'],
            'dias_antecipacao' => $validated['dias_antecipacao'],
            'permite_antecipacao_parcelado' => $request->has('permite_antecipacao_parcelado'),
            'observacoes' => $validated['observacoes'] ?? null,
            'ativo' => $request->has('ativo'),
            'created_by' => auth()->id(),
        ]);

        // Vincular contas bancárias
        if (!empty($validated['contas'])) {
            $contasData = [];
            foreach ($validated['contas'] as $index => $contaId) {
                $contasData[$contaId] = [
                    'padrao' => $contaId == ($validated['contas_padrao'] ?? null),
                    'ordem' => $index,
                ];
            }
            $adquirente->contas()->sync($contasData);
        }

        // Vincular formas de pagamento
        if (!empty($validated['formas_pagamento'])) {
            $formasData = [];
            foreach ($validated['formas_pagamento'] as $index => $formaId) {
                $formasData[$formaId] = [
                    'padrao' => $formaId == ($validated['formas_pagamento_padrao'] ?? null),
                    'ordem' => $index,
                ];
            }
            $adquirente->formasPagamento()->sync($formasData);
        }

        return redirect()->route('financeiro.adquirentes.index')
            ->with('success', 'Adquirente criada com sucesso!');
    }

    public function show(Adquirente $adquirente)
    {
        $adquirente->load(['contas', 'formasPagamento', 'taxas' => function($query) {
            $query->where('ativo', true)->orderBy('bandeira')->orderBy('modalidade');
        }]);

        return erp_view('financeiro.adquirentes.show', [
            'title' => $adquirente->nome,
            'adquirente' => $adquirente,
        ]);
    }

    public function edit(Adquirente $adquirente)
    {
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $formasPagamento = FormaPagamento::where('ativo', true)->orderBy('nome')->get();

        $adquirente->load(['contas', 'formasPagamento', 'taxas' => function ($q) {
            $q->orderBy('bandeira')->orderBy('modalidade')->orderBy('parcelas_min');
        }]);

        return erp_view('financeiro.adquirentes.edit', [
            'title' => 'Editar Adquirente',
            'adquirente' => $adquirente,
            'contasBancarias' => $contasBancarias,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function update(Request $request, Adquirente $adquirente)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'codigo' => 'nullable|string|max:50',
            'tipo_antecipacao' => 'required|in:fluxo,antecipado,ambos',
            'dias_antecipacao' => 'required|integer|min:1|max:30',
            'permite_antecipacao_parcelado' => 'boolean',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
            'contas' => 'nullable|array',
            'contas.*' => 'exists:contas_bancarias,id',
            'contas_padrao' => 'nullable|exists:contas_bancarias,id',
            'formas_pagamento' => 'nullable|array',
            'formas_pagamento.*' => 'exists:formas_pagamento,id',
            'formas_pagamento_padrao' => 'nullable|exists:formas_pagamento,id',
        ]);

        $adquirente->update([
            'nome' => $validated['nome'],
            'codigo' => $validated['codigo'] ?? null,
            'tipo_antecipacao' => $validated['tipo_antecipacao'],
            'dias_antecipacao' => $validated['dias_antecipacao'],
            'permite_antecipacao_parcelado' => $request->has('permite_antecipacao_parcelado'),
            'observacoes' => $validated['observacoes'] ?? null,
            'ativo' => $request->has('ativo'),
            'updated_by' => auth()->id(),
        ]);

        // Atualizar contas bancárias
        if (isset($validated['contas'])) {
            $contasData = [];
            foreach ($validated['contas'] as $index => $contaId) {
                $contasData[$contaId] = [
                    'padrao' => $contaId == ($validated['contas_padrao'] ?? null),
                    'ordem' => $index,
                ];
            }
            $adquirente->contas()->sync($contasData);
        } else {
            $adquirente->contas()->detach();
        }

        // Atualizar formas de pagamento
        if (isset($validated['formas_pagamento'])) {
            $formasData = [];
            foreach ($validated['formas_pagamento'] as $index => $formaId) {
                $formasData[$formaId] = [
                    'padrao' => $formaId == ($validated['formas_pagamento_padrao'] ?? null),
                    'ordem' => $index,
                ];
            }
            $adquirente->formasPagamento()->sync($formasData);
        } else {
            $adquirente->formasPagamento()->detach();
        }

        return redirect()->route('adquirentes.index')
            ->with('success', 'Adquirente atualizada com sucesso!');
    }

    public function destroy(Adquirente $adquirente)
    {
        $adquirente->delete();

        return redirect()->route('financeiro.adquirentes.index')
            ->with('success', 'Adquirente excluída com sucesso!');
    }
}
