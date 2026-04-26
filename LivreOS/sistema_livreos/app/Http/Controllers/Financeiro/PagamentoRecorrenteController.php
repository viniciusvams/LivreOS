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
use App\Models\PagamentoRecorrente;
use App\Models\Cliente;
use App\Models\FormaPagamento;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Services\GerarContasRecorrentesService;
use App\Services\AuditCancelExcluirService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PagamentoRecorrenteController extends Controller
{
    public function index(Request $request)
    {
        $query = PagamentoRecorrente::with(['cliente', 'formaPagamento', 'contaBancaria']);
        $query = $this->applyEntityQuery($query, 'pagamento_recorrente');

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
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
        $clientes = Cliente::orderBy('nome')->get(['id', 'nome']);

        return erp_view('financeiro.pagamentos-recorrentes.index', [
            'title' => 'Pagamentos Recorrentes',
            'recorrentes' => $recorrentes,
            'clientes' => $clientes,
        ]);
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nome')->select('id', 'nome', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get();
        $formasPagamento = FormaPagamento::with('contaBancaria')->where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'receita')->where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.pagamentos-recorrentes.create', [
            'title' => 'Novo Pagamento Recorrente',
            'clientes' => $clientes,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWithFilters($request, [
            'cliente_id' => 'required|exists:clientes,id',
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
            'controle_origem' => 'nullable|string|max:60',
            'controle_entidade_tipo' => 'nullable|string|max:80',
            'controle_entidade_id' => 'nullable|string|max:120',
            'controle_referencia' => 'nullable|string|max:120',
            'tipo' => 'nullable|string|in:aluguel,contrato,assinatura,manutencao,outro',
            'reajuste_habilitado' => 'boolean',
            'reajuste_periodo_meses' => 'nullable|integer|min:0|max:120',
            'reajuste_tipo' => 'nullable|string|in:nenhum,indice,manual',
            'reajuste_indice' => 'nullable|string|max:30',
            'reajuste_desconto_pct' => 'nullable|numeric|min:0|max:100',
            'reajuste_manual_pct' => 'nullable|numeric|min:0|max:999.99',
        ]);

        $dataInicio = Carbon::parse($validated['data_inicio'])->startOfDay()->toDateString();
        $rec = PagamentoRecorrente::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'cliente_id' => $validated['cliente_id'],
            'descricao' => $validated['descricao'],
            'tipo' => $validated['tipo'] ?? null,
            'valor' => $validated['valor'],
            'data_inicio' => $dataInicio,
            'data_fim' => $validated['data_fim'] ?? null,
            'frequencia' => $validated['frequencia'],
            'gerar_ultimo_dia_mes' => $request->boolean('gerar_ultimo_dia_mes', false),
            // Próxima geração = data de início (primeiro vencimento); depois do catch-up será avançada pela frequência
            'proxima_geracao_em' => $dataInicio,
            'forma_pagamento_id' => $validated['forma_pagamento_id'] ?? null,
            'conta_bancaria_id' => $validated['conta_bancaria_id'] ?? null,
            'plano_conta_id' => $validated['plano_conta_id'] ?? null,
            'ativo' => $request->boolean('ativo', true),
            'observacoes' => $validated['observacoes'] ?? null,
            'controle_origem' => $validated['controle_origem'] ?? null,
            'controle_entidade_tipo' => $validated['controle_entidade_tipo'] ?? null,
            'controle_entidade_id' => $validated['controle_entidade_id'] ?? null,
            'controle_referencia' => $validated['controle_referencia'] ?? null,
            'reajuste_habilitado' => $request->boolean('reajuste_habilitado', false),
            'reajuste_periodo_meses' => $validated['reajuste_periodo_meses'] ?? null,
            'reajuste_tipo' => $validated['reajuste_tipo'] ?? null,
            'reajuste_indice' => $validated['reajuste_indice'] ?? null,
            'reajuste_desconto_pct' => $validated['reajuste_desconto_pct'] ?? null,
            'reajuste_manual_pct' => $validated['reajuste_manual_pct'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($rec->ativo && $rec->proxima_geracao_em && $rec->proxima_geracao_em->lte(Carbon::today())) {
            // Gera imediatamente tudo que estiver vencido até hoje (inclui o vencimento do dia atual).
            $ateRetroativo = Carbon::today();
            $geradas = app(GerarContasRecorrentesService::class)->gerarCatchUpRecorrente($rec, $ateRetroativo);
            if ($geradas > 0) {
                return redirect()->to(request('voltar', route('financeiro.pagamentos-recorrentes.index')))
                    ->with('success', "Pagamento recorrente cadastrado. Foram geradas {$geradas} conta(s) a receber (vencidas/atuais). As próximas serão geradas automaticamente (diariamente via cron ou URL de tarefas).");
            }
        }

        return redirect()->to(request('voltar', route('financeiro.pagamentos-recorrentes.index')))
            ->with('success', 'Pagamento recorrente cadastrado. As contas a receber serão geradas automaticamente conforme o agendamento diário.');
    }

    public function edit(PagamentoRecorrente $pagamentoRecorrente)
    {
        $clientes = Cliente::orderBy('nome')->select('id', 'nome', 'tipo_pessoa', 'cpf', 'cnpj', 'razao_social', 'grupo_economico_id')->get();
        $formasPagamento = FormaPagamento::with('contaBancaria')->where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $planoContas = PlanoConta::where('tipo', 'receita')->where('ativo', true)->orderBy('nome')->get();

        return erp_view('financeiro.pagamentos-recorrentes.edit', [
            'title' => 'Editar Pagamento Recorrente',
            'recorrente' => $pagamentoRecorrente,
            'clientes' => $clientes,
            'formasPagamento' => $formasPagamento,
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
        ]);
    }

    public function update(Request $request, PagamentoRecorrente $pagamentoRecorrente)
    {
        $validated = $this->validateWithFilters($request, [
            'cliente_id' => 'required|exists:clientes,id',
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
            'controle_origem' => 'nullable|string|max:60',
            'controle_entidade_tipo' => 'nullable|string|max:80',
            'controle_entidade_id' => 'nullable|string|max:120',
            'controle_referencia' => 'nullable|string|max:120',
            'tipo' => 'nullable|string|in:aluguel,contrato,assinatura,manutencao,outro',
            'reajuste_habilitado' => 'boolean',
            'reajuste_periodo_meses' => 'nullable|integer|min:0|max:120',
            'reajuste_tipo' => 'nullable|string|in:nenhum,indice,manual',
            'reajuste_indice' => 'nullable|string|max:30',
            'reajuste_desconto_pct' => 'nullable|numeric|min:0|max:100',
            'reajuste_manual_pct' => 'nullable|numeric|min:0|max:999.99',
        ]);

        $dataInicioNovo = Carbon::parse($validated['data_inicio'])->startOfDay()->toDateString();
        $dataInicioAlterada = $dataInicioNovo !== Carbon::parse($pagamentoRecorrente->getOriginal('data_inicio'))->toDateString();
        $frequenciaAlterada = $validated['frequencia'] !== $pagamentoRecorrente->getOriginal('frequencia');
        $ultimoDiaMesAlterado = $request->boolean('gerar_ultimo_dia_mes', false) !== (bool) $pagamentoRecorrente->getOriginal('gerar_ultimo_dia_mes');

        $payload = [
            'cliente_id' => $validated['cliente_id'],
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
            'controle_origem' => $validated['controle_origem'] ?? null,
            'controle_entidade_tipo' => $validated['controle_entidade_tipo'] ?? null,
            'controle_entidade_id' => $validated['controle_entidade_id'] ?? null,
            'controle_referencia' => $validated['controle_referencia'] ?? null,
            'reajuste_habilitado' => $request->boolean('reajuste_habilitado', false),
            'reajuste_periodo_meses' => $validated['reajuste_periodo_meses'] ?? null,
            'reajuste_tipo' => $validated['reajuste_tipo'] ?? null,
            'reajuste_indice' => $validated['reajuste_indice'] ?? null,
            'reajuste_desconto_pct' => $validated['reajuste_desconto_pct'] ?? null,
            'reajuste_manual_pct' => $validated['reajuste_manual_pct'] ?? null,
            'updated_by' => auth()->id(),
        ];
        if ($dataInicioAlterada || $frequenciaAlterada || $ultimoDiaMesAlterado) {
            $payload['proxima_geracao_em'] = $dataInicioNovo;
        }
        $pagamentoRecorrente->update($payload);

        $rec = $pagamentoRecorrente->fresh();
        if ($rec->ativo && $rec->proxima_geracao_em && $rec->proxima_geracao_em->lte(Carbon::today())) {
            // Gera imediatamente tudo que estiver vencido até hoje (inclui o vencimento do dia atual).
            $ateRetroativo = Carbon::today();
            $geradas = app(GerarContasRecorrentesService::class)->gerarCatchUpRecorrente($rec, $ateRetroativo);
            if ($geradas > 0) {
                return redirect()->to(request('voltar', route('financeiro.pagamentos-recorrentes.index')))
                    ->with('success', "Pagamento recorrente atualizado. Foram geradas {$geradas} conta(s) a receber (vencidas/atuais).");
            }
        }

        return redirect()->to(request('voltar', route('financeiro.pagamentos-recorrentes.index')))
            ->with('success', 'Pagamento recorrente atualizado.');
    }

    public function destroy(Request $request, PagamentoRecorrente $pagamentoRecorrente, AuditCancelExcluirService $auditService)
    {
        if (!$auditService->canExcluir(auth()->user(), 'pagamento_recorrente')) {
            abort(403, 'Você não tem permissão para excluir pagamentos recorrentes.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $descricao = $pagamentoRecorrente->descricao ?? 'Recorrente #' . $pagamentoRecorrente->id;
        $entityId = $pagamentoRecorrente->id;

        $pagamentoRecorrente->delete();

        $auditService->log('excluir', 'pagamento_recorrente', $entityId, $descricao, $request->motivo, $request);

        return redirect()->to(request('voltar', route('financeiro.pagamentos-recorrentes.index')))
            ->with('success', 'Pagamento recorrente excluído.');
    }
}
