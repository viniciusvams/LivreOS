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
use App\Models\ContaPagarRecorrente;
use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Services\GerarContasPagarRecorrentesService;
use App\Services\AuditCancelExcluirService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ContaPagarRecorrenteController extends Controller
{
    public function index(Request $request)
    {
        $query = ContaPagarRecorrente::with(['fornecedor', 'formaPagamento', 'contaBancaria']);
        $query = $this->applyEntityQuery($query, 'conta_pagar_recorrente');

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }
        if ($request->filled('ativo')) {
            if ($request->ativo === '1') {
                $query->where('ativo', true);
            } elseif ($request->ativo === '0') {
                $query->where('ativo', false);
            }
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('frequencia')) {
            $query->where('frequencia', $request->frequencia);
        }

        $recorrentes = $query->orderBy('proxima_geracao_em')->paginate(15)->withQueryString();
        $fornecedores = Cliente::orderBy('nome')->get(['id', 'nome']);

        return erp_view('financeiro.contas-pagar-recorrentes.index', [
            'title' => 'Pagamentos fixos recorrentes (Contas a pagar)',
            'recorrentes' => $recorrentes,
            'fornecedores' => $fornecedores,
        ]);
    }

    public function create()
    {
        $fornecedores = Cliente::orderBy('nome')->get(['id', 'nome']);
        $formasPagamento = FormaPagamento::with('contaBancaria')->where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'despesa')->where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.contas-pagar-recorrentes.create', [
            'title' => 'Nova despesa recorrente',
            'fornecedores' => $fornecedores,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'fornecedor_id' => 'nullable|exists:clientes,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'frequencia' => 'required|in:diaria,semanal,quinzenal,mensal,bimestral,trimestral,semestral,anual',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'ativo' => 'boolean',
            'gerar_ultimo_dia_mes' => 'boolean',
            'observacoes' => 'nullable|string',
            'tipo' => 'nullable|string|in:agua,luz,gas,aluguel,condominio,telefone,internet,outro',
        ]);

        $dataInicio = Carbon::parse($validated['data_inicio'])->startOfDay()->toDateString();
        $rec = ContaPagarRecorrente::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
            'descricao' => $validated['descricao'],
            'tipo' => $validated['tipo'] ?? null,
            'valor' => $validated['valor'],
            'data_inicio' => $dataInicio,
            'data_fim' => $validated['data_fim'] ?? null,
            'frequencia' => $validated['frequencia'],
            'gerar_ultimo_dia_mes' => $request->boolean('gerar_ultimo_dia_mes', false),
            'proxima_geracao_em' => $dataInicio,
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'ativo' => $request->boolean('ativo', true),
            'observacoes' => $validated['observacoes'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($rec->ativo && $rec->proxima_geracao_em && $rec->proxima_geracao_em->lte(Carbon::today())) {
            // Gera imediatamente tudo que estiver vencido até hoje (inclui o vencimento do dia atual).
            $ateRetroativo = Carbon::today();
            $geradas = app(GerarContasPagarRecorrentesService::class)->gerarCatchUpRecorrente($rec, $ateRetroativo);
            if ($geradas > 0) {
                return redirect()->to(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])))
                    ->with('success', "Pagamento fixo cadastrado. Foram geradas {$geradas} conta(s) a pagar (vencidas/atuais). As próximas serão geradas automaticamente.");
            }
        }

        return redirect()->to(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])))
            ->with('success', 'Pagamento fixo recorrente cadastrado. As contas a pagar serão geradas automaticamente.');
    }

    public function edit(ContaPagarRecorrente $contaPagarRecorrente)
    {
        $fornecedores = Cliente::orderBy('nome')->get(['id', 'nome']);
        $formasPagamento = FormaPagamento::with('contaBancaria')->where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'despesa')->where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.contas-pagar-recorrentes.edit', [
            'title' => 'Editar despesa recorrente',
            'recorrente' => $contaPagarRecorrente,
            'fornecedores' => $fornecedores,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
        ]);
    }

    public function update(Request $request, ContaPagarRecorrente $contaPagarRecorrente)
    {
        $validated = $this->validateWithFilters($request, [
            'fornecedor_id' => 'nullable|exists:clientes,id',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'frequencia' => 'required|in:diaria,semanal,quinzenal,mensal,bimestral,trimestral,semestral,anual',
            'forma_pagamento_id' => 'nullable|exists:formas_pagamento,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'plano_conta_id' => 'nullable|exists:plano_contas,id',
            'ativo' => 'boolean',
            'gerar_ultimo_dia_mes' => 'boolean',
            'observacoes' => 'nullable|string',
            'tipo' => 'nullable|string|in:agua,luz,gas,aluguel,condominio,telefone,internet,outro',
        ]);

        $dataInicioNovo = Carbon::parse($validated['data_inicio'])->startOfDay()->toDateString();
        $dataInicioAlterada = $dataInicioNovo !== Carbon::parse($contaPagarRecorrente->getOriginal('data_inicio'))->toDateString();
        $frequenciaAlterada = $validated['frequencia'] !== $contaPagarRecorrente->getOriginal('frequencia');
        $ultimoDiaMesAlterado = $request->boolean('gerar_ultimo_dia_mes', false) !== (bool) $contaPagarRecorrente->getOriginal('gerar_ultimo_dia_mes');

        $payload = [
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
            'descricao' => $validated['descricao'],
            'tipo' => $validated['tipo'] ?? null,
            'valor' => $validated['valor'],
            'data_inicio' => $dataInicioNovo,
            'data_fim' => $validated['data_fim'] ?? null,
            'frequencia' => $validated['frequencia'],
            'gerar_ultimo_dia_mes' => $request->boolean('gerar_ultimo_dia_mes', false),
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'ativo' => $request->boolean('ativo', true),
            'observacoes' => $validated['observacoes'] ?? null,
            'updated_by' => auth()->id(),
        ];
        if ($dataInicioAlterada || $frequenciaAlterada || $ultimoDiaMesAlterado) {
            $payload['proxima_geracao_em'] = $dataInicioNovo;
        }
        $contaPagarRecorrente->update($payload);

        $rec = $contaPagarRecorrente->fresh();
        if ($rec->ativo && $rec->proxima_geracao_em && $rec->proxima_geracao_em->lte(Carbon::today())) {
            // Gera imediatamente tudo que estiver vencido até hoje (inclui o vencimento do dia atual).
            $ateRetroativo = Carbon::today();
            $geradas = app(GerarContasPagarRecorrentesService::class)->gerarCatchUpRecorrente($rec, $ateRetroativo);
            if ($geradas > 0) {
                return redirect()->to(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])))
                    ->with('success', "Pagamento fixo atualizado. Foram geradas {$geradas} conta(s) a pagar (vencidas/atuais).");
            }
        }

        return redirect()->to(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])))
            ->with('success', 'Pagamento fixo recorrente atualizado.');
    }

    public function destroy(Request $request, ContaPagarRecorrente $contaPagarRecorrente, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'conta_pagar_recorrente')) {
            abort(403, 'Você não tem permissão para excluir despesas recorrentes.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $descricao = $contaPagarRecorrente->descricao ?? 'Recorrente CP #' . $contaPagarRecorrente->id;
        $entityId = $contaPagarRecorrente->id;

        $contaPagarRecorrente->delete();

        $auditService->log('excluir', 'conta_pagar_recorrente', $entityId, $descricao, $request->motivo, $request);

        return redirect()->to(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])))
            ->with('success', 'Pagamento fixo recorrente excluído.');
    }
}
