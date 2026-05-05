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
use App\Models\MovimentacaoFinanceira;
use App\Models\ContaBancaria;
use App\Models\PlanoConta;
use App\Models\CentroCusto;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimentacaoController extends Controller
{
    /**
     * Monta a query base com todos os filtros (reutilizada em index, export e totais).
     */
    protected function queryComFiltros(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = optional(auth()->user())->tenant_id;
        $query = MovimentacaoFinanceira::query()
            ->whereNull('data_cancelamento')
            ->with(['contaBancaria', 'contaReceber', 'contaPagar', 'planoConta', 'centroCusto', 'createdBy', 'conciliadoPor']);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $contaId = $request->query('conta_bancaria_id');
        if ($contaId !== null && $contaId !== '') {
            $query->where('conta_bancaria_id', (int) $contaId);
        }

        $tipo = $request->query('tipo');
        if ($tipo !== null && $tipo !== '') {
            $query->where('tipo', $tipo);
        }

        $conciliado = $request->query('conciliado');
        if ($conciliado !== null && $conciliado !== '') {
            $query->where('conciliado', $conciliado === '1' || $conciliado === 'true');
        }

        $origem = $request->query('origem');
        if ($origem !== null && $origem !== '') {
            $query->where('origem', $origem);
        }

        $dataInicio = $request->query('data_inicio');
        if ($dataInicio !== null && $dataInicio !== '') {
            $query->whereDate('data_movimentacao', '>=', $dataInicio);
        }

        $dataFim = $request->query('data_fim');
        if ($dataFim !== null && $dataFim !== '') {
            $query->whereDate('data_movimentacao', '<=', $dataFim);
        }

        $busca = $request->query('q');
        if ($busca !== null && trim($busca) !== '') {
            $termo = '%' . trim($busca) . '%';
            $query->where(function ($q) use ($termo) {
                $q->where('descricao', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo);
            });
        }

        $valorMin = $request->query('valor_min');
        if ($valorMin !== null && $valorMin !== '') {
            $query->where('valor', '>=', (float) str_replace(',', '.', $valorMin));
        }
        $valorMax = $request->query('valor_max');
        if ($valorMax !== null && $valorMax !== '') {
            $query->where('valor', '<=', (float) str_replace(',', '.', $valorMax));
        }

        $dataConciliacaoInicio = $request->query('data_conciliacao_inicio');
        if ($dataConciliacaoInicio !== null && $dataConciliacaoInicio !== '') {
            $query->where('data_conciliacao', '>=', $dataConciliacaoInicio);
        }
        $dataConciliacaoFim = $request->query('data_conciliacao_fim');
        if ($dataConciliacaoFim !== null && $dataConciliacaoFim !== '') {
            $query->where('data_conciliacao', '<=', $dataConciliacaoFim);
        }

        $ordenar = $request->query('ordenar', 'data_movimentacao');
        if (!in_array($ordenar, ['data_movimentacao', 'valor', 'descricao', 'created_at'], true)) {
            $ordenar = 'data_movimentacao';
        }
        $direcao = strtolower($request->query('ordenar_direcao', 'desc'));
        if (!in_array($direcao, ['asc', 'desc'], true)) {
            $direcao = 'desc';
        }
        $query->orderBy($ordenar, $direcao)->orderBy('id', $direcao);

        return $query;
    }

    /**
     * Totais contábeis (soma de linhas) + visão gerencial do extrato (vendas, ajustes, taxas).
     *
     * @return array<string, float|int>
     */
    protected function totaisExtratoGerencial(\Illuminate\Database\Eloquent\Builder $queryTotais): array
    {
        $q = (clone $queryTotais)->reorder();

        $totalEntradasContabil = (float) (clone $q)->where('tipo', 'entrada')->sum('valor');
        $totalSaidasContabil = (float) (clone $q)->where('tipo', 'saida')->sum('valor');

        $recebimentosVendas = (float) (clone $q)->where('tipo', 'entrada')->where('origem', 'conta_receber')->sum('valor');
        $ajustesCreditoEntrada = (float) (clone $q)->where('tipo', 'entrada')->where('origem', 'ajuste')->sum('valor');
        $outrasEntradas = (float) (clone $q)->where('tipo', 'entrada')
            ->whereNotIn('origem', ['conta_receber', 'ajuste'])
            ->sum('valor');

        $pagamentosContaPagar = (float) (clone $q)->where('tipo', 'saida')->where('origem', 'conta_pagar')->sum('valor');
        $ajustesDebitoSaida = (float) (clone $q)->where('tipo', 'saida')->where('origem', 'ajuste')->sum('valor');

        $taxasSaidaBruto = (float) (clone $q)->where('tipo', 'saida')->where(function ($w) {
            $w->where('origem', 'taxa_recebimento')
                ->orWhere(function ($w2) {
                    $w2->where('origem', 'outro')->whereNotNull('conta_receber_id');
                });
        })->sum('valor');

        $estornoTaxaCreditoAjuste = (float) (clone $q)->where('tipo', 'entrada')->where('origem', 'ajuste')
            ->where(function ($w) {
                $w->where('descricao', 'like', 'Estorno:%Taxa%')
                    ->orWhere('descricao', 'like', 'Estorno: Taxa%');
            })->sum('valor');

        $custosTaxasLiquido = $taxasSaidaBruto - $estornoTaxaCreditoAjuste;

        $faturamentoBruto = $recebimentosVendas;
        $faturamentoLiquido = $faturamentoBruto - $ajustesDebitoSaida - $custosTaxasLiquido;

        return [
            'total_entradas_contabil' => $totalEntradasContabil,
            'total_saidas_contabil' => $totalSaidasContabil,
            'saldo_periodo_contabil' => $totalEntradasContabil - $totalSaidasContabil,
            'recebimentos_vendas' => $recebimentosVendas,
            'ajustes_credito_entrada' => $ajustesCreditoEntrada,
            'outras_entradas' => $outrasEntradas,
            'pagamentos_conta_pagar' => $pagamentosContaPagar,
            'ajustes_debito_saida' => $ajustesDebitoSaida,
            'taxas_saida_bruto' => $taxasSaidaBruto,
            'estorno_taxa_credito_ajuste' => $estornoTaxaCreditoAjuste,
            'custos_taxas_liquido' => $custosTaxasLiquido,
            'faturamento_bruto' => $faturamentoBruto,
            'faturamento_liquido' => $faturamentoLiquido,
        ];
    }

    public function index(Request $request)
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ], [
            'data_fim.after_or_equal' => 'A data fim deve ser igual ou posterior à data início.',
        ]);

        $req = $request->duplicate();
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        if (($dataInicio === null || $dataInicio === '') && ($dataFim === null || $dataFim === '')) {
            $inicio = now()->startOfMonth()->format('Y-m-d');
            $fim = now()->format('Y-m-d');
            $req->query->set('data_inicio', $inicio);
            $req->query->set('data_fim', $fim);
        }

        $query = $this->queryComFiltros($req);
        $query = $this->applyEntityQuery($query, 'movimentacao_financeira');

        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }
        $movimentacoes = $query->paginate($perPage)->withQueryString();

        $queryTotais = $this->queryComFiltros($req);
        $queryTotais = $this->applyEntityQuery($queryTotais, 'movimentacao_financeira');
        $extratoGerencial = $this->totaisExtratoGerencial($queryTotais);
        $totalEntradas = $extratoGerencial['total_entradas_contabil'];
        $totalSaidas = $extratoGerencial['total_saidas_contabil'];
        $quantidade = (clone $queryTotais)->reorder()->count();
        $pendentesConciliacao = (clone $queryTotais)->reorder()->where('conciliado', false)->count();

        $tenantId = optional(auth()->user())->tenant_id;
        $contasBancarias = ContaBancaria::where('ativo', true)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nome')
            ->get();

        return erp_view('financeiro.movimentacoes.index', [
            'title' => 'Movimentações Financeiras',
            'movimentacoes' => $movimentacoes,
            'contasBancarias' => $contasBancarias,
            'contaExtrato' => $req->query('conta_bancaria_id') ? ContaBancaria::find($req->query('conta_bancaria_id')) : null,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'quantidade' => $quantidade,
            'pendentesConciliacao' => $pendentesConciliacao,
            'extratoGerencial' => $extratoGerencial,
            'filtrosAtivos' => $this->filtrosAtivos($req),
            'dataInicioEfetivo' => $req->query('data_inicio'),
            'dataFimEfetivo' => $req->query('data_fim'),
        ]);
    }

    protected function filtrosAtivos(Request $request): array
    {
        $ativos = [];
        if ($request->query('conta_bancaria_id')) $ativos['conta_bancaria_id'] = $request->query('conta_bancaria_id');
        if ($request->query('tipo')) $ativos['tipo'] = $request->query('tipo');
        if ($request->query('conciliado') !== null && $request->query('conciliado') !== '') $ativos['conciliado'] = $request->query('conciliado');
        if ($request->query('origem')) $ativos['origem'] = $request->query('origem');
        if ($request->query('data_inicio')) $ativos['data_inicio'] = $request->query('data_inicio');
        if ($request->query('data_fim')) $ativos['data_fim'] = $request->query('data_fim');
        if ($request->query('q')) $ativos['q'] = $request->query('q');
        if ($request->query('valor_min')) $ativos['valor_min'] = $request->query('valor_min');
        if ($request->query('valor_max')) $ativos['valor_max'] = $request->query('valor_max');
        if ($request->query('data_conciliacao_inicio')) $ativos['data_conciliacao_inicio'] = $request->query('data_conciliacao_inicio');
        if ($request->query('data_conciliacao_fim')) $ativos['data_conciliacao_fim'] = $request->query('data_conciliacao_fim');
        return $ativos;
    }

    public function show(Request $request, MovimentacaoFinanceira $movimentacao)
    {
        $movimentacao->load(['contaBancaria', 'contaReceber', 'contaPagar', 'createdBy', 'conciliadoPor', 'desconciliadoPor', 'transferencia']);
        if ($request->wantsJson()) {
            return response()->json([
                'id' => $movimentacao->id,
                'data_movimentacao' => $movimentacao->data_movimentacao->format('d/m/Y'),
                'descricao' => $movimentacao->descricao,
                'observacoes' => $movimentacao->observacoes,
                'tipo' => $movimentacao->tipo,
                'valor' => $movimentacao->valor,
                'origem' => $movimentacao->origem,
                'conciliado' => $movimentacao->conciliado,
                'data_conciliacao' => $movimentacao->data_conciliacao?->format('d/m/Y'),
                'conta_bancaria' => $movimentacao->contaBancaria?->nome,
                'conta_receber_id' => $movimentacao->conta_receber_id,
                'conta_pagar_id' => $movimentacao->conta_pagar_id,
                'conta_receber_url' => $movimentacao->conta_receber_id ? route('financeiro.contas-receber.edit', $movimentacao->conta_receber_id) : null,
                'conta_pagar_url' => $movimentacao->conta_pagar_id ? route('financeiro.contas-pagar.edit', $movimentacao->conta_pagar_id) : null,
                'created_at' => $movimentacao->created_at->format('d/m/Y H:i'),
                'conciliado_por' => $movimentacao->conciliadoPor?->name,
                'motivo_desconciliacao' => $movimentacao->motivo_desconciliacao,
                'data_desconciliacao' => $movimentacao->data_desconciliacao?->format('d/m/Y H:i'),
                'desconciliado_por' => $movimentacao->desconciliadoPor?->name,
            ]);
        }
        return redirect()->route('financeiro.movimentacoes.index');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->queryComFiltros($request);
        $query = $this->applyEntityQuery($query, 'movimentacao_financeira');
        $movimentacoes = $query->limit(10000)->get();

        $filename = 'movimentacoes_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($movimentacoes) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Data', 'Descrição', 'Conta', 'Origem', 'Tipo', 'Valor', 'Conciliado', 'Data Conciliação'], ';');
            foreach ($movimentacoes as $m) {
                fputcsv($out, [
                    $m->data_movimentacao->format('d/m/Y'),
                    $m->descricao,
                    $m->contaBancaria->nome ?? '',
                    $m->origem ?? '',
                    $m->tipo,
                    number_format($m->valor, 2, ',', '.'),
                    $m->conciliado ? 'Sim' : 'Não',
                    $m->data_conciliacao ? $m->data_conciliacao->format('d/m/Y') : '',
                ], ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $req = $request->duplicate();
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        if (($dataInicio === null || $dataInicio === '') && ($dataFim === null || $dataFim === '')) {
            $inicio = now()->startOfMonth()->format('Y-m-d');
            $fim = now()->format('Y-m-d');
            $req->query->set('data_inicio', $inicio);
            $req->query->set('data_fim', $fim);
        }

        $query = $this->queryComFiltros($req);
        $query = $this->applyEntityQuery($query, 'movimentacao_financeira');
        $movimentacoes = $query->limit(500)->get();

        $queryTotais = $this->queryComFiltros($req);
        $queryTotais = $this->applyEntityQuery($queryTotais, 'movimentacao_financeira');
        $extratoGerencial = $this->totaisExtratoGerencial($queryTotais);
        $totalEntradas = $extratoGerencial['total_entradas_contabil'];
        $totalSaidas = $extratoGerencial['total_saidas_contabil'];

        $contaExtrato = $req->query('conta_bancaria_id') ? ContaBancaria::find($req->query('conta_bancaria_id')) : null;

        $html = view('financeiro.movimentacoes.pdf', [
            'movimentacoes' => $movimentacoes,
            'totalEntradas' => $totalEntradas,
            'totalSaidas' => $totalSaidas,
            'extratoGerencial' => $extratoGerencial,
            'filtros' => $this->filtrosAtivos($req),
            'contaExtrato' => $contaExtrato,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');
        $filename = 'movimentacoes_' . now()->format('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function conciliar(Request $request, MovimentacaoFinanceira $movimentacao)
    {
        if ($movimentacao->conciliado) {
            return redirect()->back()->with('error', 'Movimentação já está conciliada!');
        }

        $movimentacao->update([
            'conciliado' => true,
            'data_conciliacao' => now(),
            'conciliado_por' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Movimentação conciliada com sucesso!');
    }

    public function conciliarLote(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = array_filter([$ids]);
        }
        $ids = array_map('intval', array_filter($ids));

        $tenantId = optional(auth()->user())->tenant_id;
        $query = MovimentacaoFinanceira::whereIn('id', $ids)->where('conciliado', false);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        $count = $query->update([
            'conciliado' => true,
            'data_conciliacao' => now(),
            'conciliado_por' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $msg = $count === 0
            ? 'Nenhuma movimentação elegível para conciliar.'
            : $count . ' movimentação(ões) conciliada(s) com sucesso!';
        return redirect()->back()->with($count > 0 ? 'success' : 'info', $msg);
    }

    public function desconciliar(Request $request, MovimentacaoFinanceira $movimentacao)
    {
        $user = auth()->user();
        if (!$user->is_admin && !$user->hasPermission('financeiro.movimentacoes.desconciliar')) {
            abort(403, 'Você não tem permissão para desconciliar movimentações.');
        }

        if (!$movimentacao->conciliado) {
            return redirect()->back()->with('error', 'Movimentação não está conciliada!');
        }

        $validated = $request->validate([
            'motivo_desconciliacao' => 'required|string|max:2000',
        ], [
            'motivo_desconciliacao.required' => 'Informe o motivo da desconciliação.',
        ]);

        $movimentacao->update([
            'conciliado' => false,
            'data_conciliacao' => null,
            'conciliado_por' => null,
            'motivo_desconciliacao' => $validated['motivo_desconciliacao'],
            'desconciliado_por' => auth()->id(),
            'data_desconciliacao' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Movimentação desconciliada.');
    }

    public function create()
    {
        $tenantId = optional(auth()->user())->tenant_id;
        $contasBancarias = ContaBancaria::where('ativo', true)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nome')
            ->get();

        $planoContas = PlanoConta::where('ativo', true)
            ->whereIn('tipo', ['receita', 'despesa'])
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();

        return erp_view('financeiro.movimentacoes.create', [
            'title' => 'Nova Movimentação (Ajuste)',
            'contasBancarias' => $contasBancarias,
            'planoContas' => $planoContas,
            'centrosCusto' => $centrosCusto,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->is_admin && !$user->hasPermission('financeiro.movimentacoes.create')) {
            abort(403, 'Você não tem permissão para criar movimentações manuais.');
        }

        $validated = $request->validate([
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'tipo' => 'required|in:entrada,saida',
            'valor' => 'required|numeric|min:0.01',
            'data_movimentacao' => 'required|date',
            'descricao' => 'required|string|max:255',
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custos,id',
            'observacoes' => 'nullable|string|max:5000',
        ]);

        $plano = \App\Models\PlanoConta::find($validated['plano_conta_id']);
        $tipoEsperado = $validated['tipo'] === 'entrada' ? 'receita' : 'despesa';
        if (!$plano || $plano->tipo !== $tipoEsperado) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['plano_conta_id' => 'O plano de contas deve ser do tipo ' . ($tipoEsperado === 'receita' ? 'Receita' : 'Despesa') . ' para movimentação do tipo ' . $validated['tipo'] . '.']);
        }

        $tenantId = optional(auth()->user())->tenant_id;

        MovimentacaoFinanceira::create([
            'tenant_id' => $tenantId,
            'conta_bancaria_id' => $validated['conta_bancaria_id'],
            'tipo' => $validated['tipo'],
            'origem' => 'ajuste',
            'plano_conta_id' => $validated['plano_conta_id'],
            'centro_custo_id' => $validated['centro_custo_id'] ?? null,
            'valor' => $validated['valor'],
            'data_movimentacao' => $validated['data_movimentacao'],
            'descricao' => $validated['descricao'],
            'observacoes' => $validated['observacoes'] ?? null,
            'conciliado' => true,
            'data_conciliacao' => now(),
            'conciliado_por' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('financeiro.movimentacoes.index')->with('success', 'Movimentação de ajuste criada com sucesso!');
    }
}
