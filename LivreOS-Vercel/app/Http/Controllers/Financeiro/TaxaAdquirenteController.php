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
use App\Models\TaxaAdquirente;
use Illuminate\Http\Request;

class TaxaAdquirenteController extends Controller
{
    public function index(Adquirente $adquirente)
    {
        $query = $this->applyEntityQuery(
            TaxaAdquirente::where('adquirente_id', $adquirente->id)
                ->with(['createdBy', 'updatedBy'])
                ->orderBy('bandeira')
                ->orderBy('modalidade')
                ->orderBy('parcelas_min'),
            'taxa_adquirente'
        );
        $taxas = $query->paginate(20);

        return erp_view('financeiro.taxas.index', [
            'title' => "Taxas - {$adquirente->nome}",
            'adquirente' => $adquirente,
            'taxas' => $taxas,
        ]);
    }

    public function create(Adquirente $adquirente)
    {
        return erp_view('financeiro.taxas.create', [
            'title' => 'Nova Taxa',
            'adquirente' => $adquirente,
        ]);
    }

    public function store(Request $request, Adquirente $adquirente)
    {
        $validated = $this->validateWithFilters($request, [
            'bandeira' => 'required|in:master,visa,elo,amex,hipercard,outros',
            'modalidade' => 'required|in:debito,credito_vista,credito_parcelado',
            'parcelas_min' => 'nullable|integer|min:1|max:120',
            'parcelas_max' => 'nullable|integer|min:1|max:120|gte:parcelas_min',
            'taxa_percentual' => 'required|numeric|min:0|max:100',
            'taxa_por_parcela' => 'nullable|numeric|min:0|max:100',
            'taxa_fixa' => 'nullable|numeric|min:0',
            'usa_antecipacao' => 'boolean',
            'taxa_antecipacao_percentual' => 'nullable|required_if:usa_antecipacao,1|numeric|min:0|max:100',
            'dias_recebimento_debito' => 'nullable|integer|min:0|max:365',
            'dias_recebimento_credito_vista' => 'nullable|integer|min:0|max:365',
            'dias_recebimento_parcela' => 'nullable|integer|min:0|max:365',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        TaxaAdquirente::create([
            'adquirente_id' => $adquirente->id,
            'bandeira' => $validated['bandeira'],
            'modalidade' => $validated['modalidade'],
            'parcelas_min' => $validated['parcelas_min'] ?? null,
            'parcelas_max' => $validated['parcelas_max'] ?? null,
            'taxa_percentual' => $validated['taxa_percentual'],
            'taxa_por_parcela' => $validated['taxa_por_parcela'] ?? 0,
            'taxa_fixa' => $validated['taxa_fixa'] ?? 0,
            'usa_antecipacao' => $request->has('usa_antecipacao'),
            'taxa_antecipacao_percentual' => $validated['taxa_antecipacao_percentual'] ?? null,
            'dias_recebimento_debito' => $validated['dias_recebimento_debito'] ?? 1,
            'dias_recebimento_credito_vista' => $validated['dias_recebimento_credito_vista'] ?? 30,
            'dias_recebimento_parcela' => $validated['dias_recebimento_parcela'] ?? 30,
            'data_inicio' => $validated['data_inicio'] ?? null,
            'data_fim' => $validated['data_fim'] ?? null,
            'observacoes' => $validated['observacoes'] ?? null,
            'ativo' => $request->has('ativo'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.taxas.index', $adquirente)
            ->with('success', 'Taxa criada com sucesso!');
    }

    public function edit(Adquirente $adquirente, TaxaAdquirente $taxa)
    {
        return erp_view('financeiro.taxas.edit', [
            'title' => 'Editar Taxa',
            'adquirente' => $adquirente,
            'taxa' => $taxa,
        ]);
    }

    public function update(Request $request, Adquirente $adquirente, TaxaAdquirente $taxa)
    {
        $validated = $this->validateWithFilters($request, [
            'bandeira' => 'required|in:master,visa,elo,amex,hipercard,outros',
            'modalidade' => 'required|in:debito,credito_vista,credito_parcelado',
            'parcelas_min' => 'nullable|integer|min:1|max:120',
            'parcelas_max' => 'nullable|integer|min:1|max:120|gte:parcelas_min',
            'taxa_percentual' => 'required|numeric|min:0|max:100',
            'taxa_por_parcela' => 'nullable|numeric|min:0|max:100',
            'taxa_fixa' => 'nullable|numeric|min:0',
            'usa_antecipacao' => 'boolean',
            'taxa_antecipacao_percentual' => 'nullable|required_if:usa_antecipacao,1|numeric|min:0|max:100',
            'dias_recebimento_debito' => 'nullable|integer|min:0|max:365',
            'dias_recebimento_credito_vista' => 'nullable|integer|min:0|max:365',
            'dias_recebimento_parcela' => 'nullable|integer|min:0|max:365',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'observacoes' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $taxa->update([
            'bandeira' => $validated['bandeira'],
            'modalidade' => $validated['modalidade'],
            'parcelas_min' => $validated['parcelas_min'] ?? null,
            'parcelas_max' => $validated['parcelas_max'] ?? null,
            'taxa_percentual' => $validated['taxa_percentual'],
            'taxa_por_parcela' => $validated['taxa_por_parcela'] ?? 0,
            'taxa_fixa' => $validated['taxa_fixa'] ?? 0,
            'usa_antecipacao' => $request->has('usa_antecipacao'),
            'taxa_antecipacao_percentual' => $validated['taxa_antecipacao_percentual'] ?? null,
            'dias_recebimento_debito' => $validated['dias_recebimento_debito'] ?? 1,
            'dias_recebimento_credito_vista' => $validated['dias_recebimento_credito_vista'] ?? 30,
            'dias_recebimento_parcela' => $validated['dias_recebimento_parcela'] ?? 30,
            'data_inicio' => $validated['data_inicio'] ?? null,
            'data_fim' => $validated['data_fim'] ?? null,
            'observacoes' => $validated['observacoes'] ?? null,
            'ativo' => $request->has('ativo'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.taxas.index', $adquirente)
            ->with('success', 'Taxa atualizada com sucesso!');
    }

    public function destroy(Adquirente $adquirente, TaxaAdquirente $taxa)
    {
        $taxa->delete();

        return redirect()->route('financeiro.taxas.index', $adquirente)
            ->with('success', 'Taxa excluída com sucesso!');
    }
}
