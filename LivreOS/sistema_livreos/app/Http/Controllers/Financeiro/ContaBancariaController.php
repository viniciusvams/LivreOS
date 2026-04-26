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
use App\Models\ContaBancaria;
use Illuminate\Http\Request;

class ContaBancariaController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(
            ContaBancaria::with(['createdBy', 'updatedBy'])->orderBy('nome'),
            'conta_bancaria'
        );
        $contas = $query->paginate(15);

        return erp_view('financeiro.contas-bancarias.index', [
            'title' => 'Contas Bancárias',
            'contas' => $contas,
        ]);
    }

    public function create()
    {
        return erp_view('financeiro.contas-bancarias.create', [
            'title' => 'Nova Conta Bancária',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:conta_corrente,poupanca,caixa,carteira',
            'banco' => 'nullable|string|max:100',
            'agencia' => 'nullable|string|max:20',
            'conta' => 'nullable|string|max:30',
            'digito' => 'nullable|string|max:5',
            'pix' => 'nullable|string|max:100',
            'saldo_inicial' => 'nullable|numeric|min:0',
            'data_saldo_inicial' => 'nullable|date',
            'ativo' => 'boolean',
            'observacoes' => 'nullable|string',
        ]);

        ContaBancaria::create([
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'banco' => $validated['banco'] ?? null,
            'agencia' => $validated['agencia'] ?? null,
            'conta' => $validated['conta'] ?? null,
            'digito' => $validated['digito'] ?? null,
            'pix' => $validated['pix'] ?? null,
            'saldo_inicial' => $validated['saldo_inicial'] ?? 0,
            'data_saldo_inicial' => $validated['data_saldo_inicial'] ?? now(),
            'ativo' => $request->has('ativo'),
            'observacoes' => $validated['observacoes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.contas-bancarias.index')
            ->with('success', 'Conta bancária criada com sucesso!');
    }

    public function edit(ContaBancaria $contaBancaria)
    {
        return erp_view('financeiro.contas-bancarias.edit', [
            'title' => 'Editar Conta Bancária',
            'conta' => $contaBancaria,
        ]);
    }

    /**
     * Redireciona para a tela de movimentações (extrato) já filtrada por esta conta e período do mês.
     */
    public function extrato(ContaBancaria $contaBancaria)
    {
        $params = [
            'conta_bancaria_id' => $contaBancaria->id,
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->format('Y-m-d'),
        ];

        return redirect()->route('financeiro.movimentacoes.index', $params);
    }

    public function update(Request $request, ContaBancaria $contaBancaria)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:conta_corrente,poupanca,caixa,carteira',
            'banco' => 'nullable|string|max:100',
            'agencia' => 'nullable|string|max:20',
            'conta' => 'nullable|string|max:30',
            'digito' => 'nullable|string|max:5',
            'pix' => 'nullable|string|max:100',
            'saldo_inicial' => 'nullable|numeric|min:0',
            'data_saldo_inicial' => 'nullable|date',
            'ativo' => 'boolean',
            'observacoes' => 'nullable|string',
        ]);

        $contaBancaria->update([
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'banco' => $validated['banco'] ?? null,
            'agencia' => $validated['agencia'] ?? null,
            'conta' => $validated['conta'] ?? null,
            'digito' => $validated['digito'] ?? null,
            'pix' => $validated['pix'] ?? null,
            'saldo_inicial' => $validated['saldo_inicial'] ?? 0,
            'data_saldo_inicial' => $validated['data_saldo_inicial'] ?? now(),
            'ativo' => $request->has('ativo'),
            'observacoes' => $validated['observacoes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.contas-bancarias.index')
            ->with('success', 'Conta bancária atualizada com sucesso!');
    }
}
