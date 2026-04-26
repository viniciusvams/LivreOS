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

class FormaPagamentoController extends Controller
{
    public function index()
    {
        $query = $this->applyEntityQuery(
            FormaPagamento::with(['createdBy', 'updatedBy'])->orderBy('nome'),
            'forma_pagamento'
        );
        $formas = $query->paginate(15);

        return erp_view('financeiro.formas-pagamento.index', [
            'title' => 'Formas de Pagamento',
            'formas' => $formas,
        ]);
    }

    public function create()
    {
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $adquirentes = Adquirente::where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.formas-pagamento.create', [
            'title' => 'Nova Forma de Pagamento',
            'contasBancarias' => $contasBancarias,
            'adquirentes' => $adquirentes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:pix,boleto,cartao_credito,cartao_debito,dinheiro,transferencia,cheque',
            'taxa_percentual' => 'nullable|numeric|min:0|max:100',
            'taxa_fixa' => 'nullable|numeric|min:0',
            'dias_recebimento' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
            'permite_parcela' => 'boolean',
            'max_parcelas' => 'nullable|integer|min:1|max:120',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pix_chaves' => 'nullable|array',
            'pix_chaves.*.id' => 'nullable|string|max:40',
            'pix_chaves.*.nome' => 'nullable|string|max:100',
            'pix_chaves.*.chave' => 'nullable|string|max:255',
            'pix_chaves.*.tipo' => 'nullable|string|max:30',
            'pix_chaves.*.conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pix_chaves.*.taxa_percentual' => 'nullable|numeric|min:0|max:100',
            'pix_chaves.*.taxa_fixa' => 'nullable|numeric|min:0',
            'pix_chaves.*.ativo' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
            'adquirente_ids' => 'nullable|array',
            'adquirente_ids.*' => 'exists:adquirentes,id',
            'adquirente_padrao_id' => 'nullable|exists:adquirentes,id',
        ]);

        $forma = FormaPagamento::create([
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'taxa_percentual' => $validated['taxa_percentual'] ?? 0,
            'taxa_fixa' => $validated['taxa_fixa'] ?? 0,
            'dias_recebimento' => $validated['dias_recebimento'] ?? 0,
            'ativo' => $request->has('ativo'),
            'permite_parcela' => $request->has('permite_parcela'),
            'max_parcelas' => $validated['max_parcelas'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'pix_chaves' => $this->normalizarPixChaves($request),
            'observacoes' => $validated['observacoes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->syncAdquirentes($forma, $request);

        return redirect()->route('financeiro.formas-pagamento.index')
            ->with('success', 'Forma de pagamento criada com sucesso!');
    }

    public function edit(FormaPagamento $formaPagamento)
    {
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $adquirentes = Adquirente::where('ativo', true)->orderBy('nome')->get();
        $formaPagamento->load('adquirentes');

        return erp_view('financeiro.formas-pagamento.edit', [
            'title' => 'Editar Forma de Pagamento',
            'forma' => $formaPagamento,
            'contasBancarias' => $contasBancarias,
            'adquirentes' => $adquirentes,
        ]);
    }

    public function update(Request $request, FormaPagamento $formaPagamento)
    {
        $validated = $this->validateWithFilters($request, [
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:pix,boleto,cartao_credito,cartao_debito,dinheiro,transferencia,cheque',
            'taxa_percentual' => 'nullable|numeric|min:0|max:100',
            'taxa_fixa' => 'nullable|numeric|min:0',
            'dias_recebimento' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
            'permite_parcela' => 'boolean',
            'max_parcelas' => 'nullable|integer|min:1|max:120',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pix_chaves' => 'nullable|array',
            'pix_chaves.*.id' => 'nullable|string|max:40',
            'pix_chaves.*.nome' => 'nullable|string|max:100',
            'pix_chaves.*.chave' => 'nullable|string|max:255',
            'pix_chaves.*.tipo' => 'nullable|string|max:30',
            'pix_chaves.*.conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'pix_chaves.*.taxa_percentual' => 'nullable|numeric|min:0|max:100',
            'pix_chaves.*.taxa_fixa' => 'nullable|numeric|min:0',
            'pix_chaves.*.ativo' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
            'adquirente_ids' => 'nullable|array',
            'adquirente_ids.*' => 'exists:adquirentes,id',
            'adquirente_padrao_id' => 'nullable|exists:adquirentes,id',
        ]);

        $formaPagamento->update([
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'taxa_percentual' => $validated['taxa_percentual'] ?? 0,
            'taxa_fixa' => $validated['taxa_fixa'] ?? 0,
            'dias_recebimento' => $validated['dias_recebimento'] ?? 0,
            'ativo' => $request->has('ativo'),
            'permite_parcela' => $request->has('permite_parcela'),
            'max_parcelas' => $validated['max_parcelas'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'pix_chaves' => $this->normalizarPixChaves($request),
            'observacoes' => $validated['observacoes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $this->syncAdquirentes($formaPagamento, $request);

        return redirect()->route('financeiro.formas-pagamento.index')
            ->with('success', 'Forma de pagamento atualizada com sucesso!');
    }

    /**
     * Sincroniza adquirentes associados à forma de pagamento (cartão crédito/débito).
     */
    private function syncAdquirentes(FormaPagamento $forma, Request $request): void
    {
        $tipo = $forma->tipo;
        if (!in_array($tipo, ['cartao_credito', 'cartao_debito'])) {
            $forma->adquirentes()->detach();
            return;
        }

        $adquirenteIds = $request->input('adquirente_ids', []);
        if (!is_array($adquirenteIds)) {
            $adquirenteIds = [];
        }
        $adquirenteIds = array_filter(array_map('intval', $adquirenteIds));
        $padraoId = (int) $request->input('adquirente_padrao_id', 0);

        $sync = [];
        $ordem = 0;
        foreach ($adquirenteIds as $id) {
            $sync[$id] = ['padrao' => ($id === $padraoId), 'ordem' => $ordem++];
        }
        if ($padraoId && !isset($sync[$padraoId])) {
            $sync[$padraoId] = ['padrao' => true, 'ordem' => $ordem++];
        }
        $forma->adquirentes()->sync($sync);
    }

    private function normalizarPixChaves(Request $request): array
    {
        $tipo = (string) $request->input('tipo', '');
        if ($tipo !== 'pix') {
            return [];
        }
        $linhas = $request->input('pix_chaves', []);
        if (!is_array($linhas)) {
            return [];
        }
        $out = [];
        foreach ($linhas as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $chave = trim((string) ($row['chave'] ?? ''));
            $nome = trim((string) ($row['nome'] ?? ''));
            if ($chave === '' && $nome === '') {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                $id = 'pix_' . substr(md5((string) microtime(true) . '_' . $i . '_' . $chave), 0, 12);
            }
            $out[] = [
                'id' => $id,
                'nome' => $nome !== '' ? $nome : ('Chave ' . (count($out) + 1)),
                'chave' => $chave,
                'tipo' => trim((string) ($row['tipo'] ?? '')) ?: 'pix',
                'conta_bancaria_id' => !empty($row['conta_bancaria_id']) ? (int) $row['conta_bancaria_id'] : null,
                'taxa_percentual' => isset($row['taxa_percentual']) ? (float) $row['taxa_percentual'] : 0.0,
                'taxa_fixa' => isset($row['taxa_fixa']) ? (float) $row['taxa_fixa'] : 0.0,
                'ativo' => array_key_exists('ativo', $row) ? (bool) $row['ativo'] : true,
            ];
        }
        return $out;
    }
}
