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
use App\Models\TermoGarantia;
use Illuminate\Http\Request;

class TermoGarantiaController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(
            TermoGarantia::with(['createdBy', 'updatedBy'])->orderBy('nome'),
            'termo_garantia'
        );
        $termos = $query->paginate(15);

        return erp_view('configuracoes_sistema.termos-garantia.index', [
            'title' => 'Termos de Garantia',
            'termos' => $termos,
        ]);
    }

    public function create()
    {
        return erp_view('configuracoes_sistema.termos-garantia.create', [
            'title' => 'Novo Termo de Garantia',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:200',
            'termo' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        TermoGarantia::create([
            'nome' => $validated['nome'],
            'termo' => $validated['termo'] ?? null,
            'ativo' => $request->has('ativo'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('configuracoes-sistema.termos-garantia.index')
            ->with('success', 'Termo de garantia criado com sucesso!');
    }

    public function edit(TermoGarantia $termoGarantia)
    {
        return erp_view('configuracoes_sistema.termos-garantia.edit', [
            'title' => 'Editar Termo de Garantia',
            'termo' => $termoGarantia,
        ]);
    }

    public function update(Request $request, TermoGarantia $termoGarantia)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:200',
            'termo' => 'nullable|string',
            'ativo' => 'boolean',
        ]);

        $termoGarantia->update([
            'nome' => $validated['nome'],
            'termo' => $validated['termo'] ?? null,
            'ativo' => $request->has('ativo'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('configuracoes-sistema.termos-garantia.index')
            ->with('success', 'Termo de garantia atualizado com sucesso!');
    }

    public function destroy(TermoGarantia $termoGarantia)
    {
        // Verificar se há OS usando este termo
        if ($termoGarantia->ordensServico()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir este termo de garantia pois existem ordens de serviço associadas a ele.');
        }

        $termoGarantia->delete();

        return redirect()->route('configuracoes-sistema.termos-garantia.index')
            ->with('success', 'Termo de garantia excluído com sucesso!');
    }
}
