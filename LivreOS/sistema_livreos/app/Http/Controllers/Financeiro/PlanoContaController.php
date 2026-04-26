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
use App\Models\PlanoConta;
use Illuminate\Http\Request;

class PlanoContaController extends Controller
{
    public function index()
    {
        $query = PlanoConta::with(['pai', 'createdBy'])
                ->withSum(['contasPagar as total_despesa_pago' => fn ($q) => $q->where('status', 'pago')], 'valor_pago')
                ->withSum(['contasPagar as total_despesa_aberto' => fn ($q) => $q->whereIn('status', ['aberto', 'parcial'])], 'valor')
                ->withSum(['contasReceber as total_receita_recebido' => fn ($q) => $q->where('status', 'pago')], 'valor_recebido')
                ->withSum(['contasReceber as total_receita_aberto' => fn ($q) => $q->whereIn('status', ['aberto', 'parcial'])], 'valor')
                ->whereNull('pai_id')
                ->orderBy('ordem')
                ->orderBy('nome');
        $contas = $query->get();

        // Carregar filhos com totais
        $contas->load([
            'filhos' => fn ($q) => $q
                ->withSum(['contasPagar as total_despesa_pago' => fn ($q2) => $q2->where('status', 'pago')], 'valor_pago')
                ->withSum(['contasPagar as total_despesa_aberto' => fn ($q2) => $q2->whereIn('status', ['aberto', 'parcial'])], 'valor')
                ->withSum(['contasReceber as total_receita_recebido' => fn ($q2) => $q2->where('status', 'pago')], 'valor_recebido')
                ->withSum(['contasReceber as total_receita_aberto' => fn ($q2) => $q2->whereIn('status', ['aberto', 'parcial'])], 'valor'),
        ]);

        return erp_view('financeiro.plano-contas.index', [
            'title' => 'Plano de Contas',
            'contas' => $contas,
        ]);
    }

    public function create()
    {
        $contasPai = PlanoConta::whereNull('pai_id')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return erp_view('financeiro.plano-contas.create', [
            'title' => 'Nova Conta',
            'contasPai' => $contasPai,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'pai_id' => 'nullable|exists:plano_contas,id',
            'codigo' => 'nullable|string|max:20',
            'nome' => 'required|string|max:200',
            'tipo' => 'required|in:receita,despesa',
            'categoria' => 'required|in:operacional,nao_operacional,financeira',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
            'ordem' => 'nullable|integer|min:0',
        ]);

        PlanoConta::create([
            'pai_id' => $validated['pai_id'] ?? null,
            'codigo' => $validated['codigo'] ?? null,
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'categoria' => $validated['categoria'],
            'descricao' => $validated['descricao'] ?? null,
            'ativo' => $request->has('ativo'),
            'ordem' => $validated['ordem'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.plano-contas.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    public function edit(PlanoConta $planoConta)
    {
        $contasPai = PlanoConta::whereNull('pai_id')
            ->where('id', '!=', $planoConta->id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return erp_view('financeiro.plano-contas.edit', [
            'title' => 'Editar Conta',
            'conta' => $planoConta,
            'contasPai' => $contasPai,
        ]);
    }

    public function update(Request $request, PlanoConta $planoConta)
    {
        $validated = $this->validateWithFilters($request, [
            'pai_id' => 'nullable|exists:plano_contas,id',
            'codigo' => 'nullable|string|max:20',
            'nome' => 'required|string|max:200',
            'tipo' => 'required|in:receita,despesa',
            'categoria' => 'required|in:operacional,nao_operacional,financeira',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $planoConta->update([
            'pai_id' => $validated['pai_id'] ?? null,
            'codigo' => $validated['codigo'] ?? null,
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'categoria' => $validated['categoria'],
            'descricao' => $validated['descricao'] ?? null,
            'ativo' => $request->has('ativo'),
            'ordem' => $validated['ordem'] ?? 0,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.plano-contas.index')
            ->with('success', 'Conta atualizada com sucesso!');
    }
}
