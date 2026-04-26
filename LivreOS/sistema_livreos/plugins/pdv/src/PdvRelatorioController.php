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

namespace Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pdv\Models\Caixa;
use Pdv\Models\CaixaMovimentacao;
use Pdv\Models\Venda;
use Pdv\Models\VendaItem;
use Pdv\Models\VendaPagamento;

class PdvRelatorioController
{
    private function verificarAcesso(): void
    {
        if (!function_exists('auth') || !auth()->check()) {
            abort(403, 'Acesso não autorizado.');
        }
        $user = auth()->user();
        if (method_exists($user, 'canAccessOperational') && !$user->canAccessOperational()) {
            abort(403, 'Sem permissão para acessar relatórios do PDV.');
        }
    }

    /**
     * Hub: lista os relatórios disponíveis.
     */
    public function index()
    {
        $this->verificarAcesso();
        return view('pdv::relatorios.index', ['title' => 'Relatórios PDV']);
    }

    /**
     * Relatório 1: Vendas por período, com resumo e breakdown por forma de pagamento.
     */
    public function vendasPeriodo(Request $request)
    {
        $this->verificarAcesso();

        $dataInicio = $request->input('data_de', now()->startOfMonth()->toDateString());
        $dataFim    = $request->input('data_ate', now()->toDateString());
        $status     = $request->input('status', 'finalizada');
        $caixaId    = $request->input('caixa_id');

        $query = Venda::with(['cliente', 'caixa.user', 'pagamentos.formaPagamento'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$dataInicio, $dataFim]);

        if ($status) {
            $query->where('status', $status);
        }
        if ($caixaId) {
            $query->where('caixa_id', $caixaId);
        }

        $vendas = $query->orderByDesc('created_at')->get();

        // Métricas gerais (apenas sobre vendas finalizadas no período)
        $vendaFinalizadas = $vendas->where('status', 'finalizada');
        $totalVendas      = $vendaFinalizadas->count();
        $totalValor       = $vendaFinalizadas->sum('total_final');
        $ticketMedio      = $totalVendas > 0 ? $totalValor / $totalVendas : 0;
        $totalDescontos   = $vendaFinalizadas->sum('desconto');
        $totalCanceladas  = $vendas->where('status', 'cancelada')->count();

        // Breakdown por forma de pagamento (sobre as finalizadas do período)
        $pagamentos = VendaPagamento::with('formaPagamento')
            ->whereHas('venda', function ($q) use ($dataInicio, $dataFim, $caixaId) {
                $q->where('status', Venda::STATUS_FINALIZADA)
                  ->whereBetween(DB::raw('DATE(created_at)'), [$dataInicio, $dataFim]);
                if ($caixaId) {
                    $q->where('caixa_id', $caixaId);
                }
            })
            ->selectRaw('forma_pagamento_id, SUM(valor) as total, COUNT(*) as qtd')
            ->groupBy('forma_pagamento_id')
            ->get()
            ->map(function ($row) {
                $row->nome_forma = $row->formaPagamento->nome ?? 'Desconhecida';
                return $row;
            })
            ->sortByDesc('total');

        // Caixas para filtro
        $caixasParaFiltro = Caixa::with('user')
            ->whereBetween(DB::raw('DATE(opened_at)'), [$dataInicio, $dataFim])
            ->orderByDesc('opened_at')
            ->get();

        return view('pdv::relatorios.vendas-periodo', compact(
            'vendas', 'totalVendas', 'totalValor', 'ticketMedio',
            'totalDescontos', 'totalCanceladas', 'pagamentos',
            'caixasParaFiltro', 'dataInicio', 'dataFim', 'status', 'caixaId'
        ) + ['title' => 'Relatório de Vendas por Período']);
    }

    /**
     * Relatório 2: Produtos e serviços mais vendidos no período.
     */
    public function produtosVendidos(Request $request)
    {
        $this->verificarAcesso();

        $dataInicio = $request->input('data_de', now()->startOfMonth()->toDateString());
        $dataFim    = $request->input('data_ate', now()->toDateString());
        $tipo       = $request->input('tipo', '');

        $query = VendaItem::with(['produto', 'servico'])
            ->whereHas('venda', function ($q) use ($dataInicio, $dataFim) {
                $q->where('status', Venda::STATUS_FINALIZADA)
                  ->whereBetween(DB::raw('DATE(created_at)'), [$dataInicio, $dataFim]);
            })
            ->selectRaw('
                tipo,
                produto_id,
                servico_id,
                descricao,
                SUM(quantidade) as qtd_total,
                SUM(total) as valor_total,
                COUNT(DISTINCT venda_id) as qtd_vendas,
                AVG(preco_unitario) as preco_medio
            ')
            ->groupBy('tipo', 'produto_id', 'servico_id', 'descricao');

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        $itens = $query->orderByDesc('valor_total')->limit(100)->get();

        // Totalizadores
        $totalItensVendidos = $itens->sum('qtd_total');
        $totalValorItens    = $itens->sum('valor_total');

        return view('pdv::relatorios.produtos-vendidos', compact(
            'itens', 'totalItensVendidos', 'totalValorItens',
            'dataInicio', 'dataFim', 'tipo'
        ) + ['title' => 'Produtos/Serviços Mais Vendidos']);
    }

    /**
     * Relatório 3: Resumo de caixas com totais, sangrias, reforços e diferença.
     */
    public function resumoCaixas(Request $request)
    {
        $this->verificarAcesso();

        $dataInicio = $request->input('data_de', now()->startOfMonth()->toDateString());
        $dataFim    = $request->input('data_ate', now()->toDateString());
        $statusCaixa = $request->input('status_caixa', '');
        $userId     = $request->input('user_id');

        $query = Caixa::with(['user', 'movimentacoes', 'fechamentoQuebraSobra'])
            ->whereBetween(DB::raw('DATE(opened_at)'), [$dataInicio, $dataFim]);

        if ($statusCaixa) {
            $query->where('status', $statusCaixa);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $caixas = $query->orderByDesc('opened_at')->get();

        // Para cada caixa, calcula totais de vendas e movimentações
        $caixas->each(function ($caixa) {
            $caixa->total_vendas_count = Venda::where('caixa_id', $caixa->id)
                ->where('status', Venda::STATUS_FINALIZADA)
                ->count();
            $caixa->total_vendas_valor = Venda::where('caixa_id', $caixa->id)
                ->where('status', Venda::STATUS_FINALIZADA)
                ->sum('total_final');
            $caixa->total_reforcos = $caixa->movimentacoes
                ->where('tipo', CaixaMovimentacao::TIPO_REFORCO)
                ->sum('valor');
            $caixa->total_sangrias = $caixa->movimentacoes
                ->where('tipo', CaixaMovimentacao::TIPO_SANGRIA)
                ->sum('valor');
            $caixa->diferenca = $caixa->valor_fechamento_informado !== null
                ? $caixa->valor_fechamento_informado - $caixa->valor_fechamento_sistema
                : null;
        });

        // Operadores para filtro
        $operadoresParaFiltro = \App\Models\User::whereIn(
            'id',
            Caixa::whereBetween(DB::raw('DATE(opened_at)'), [$dataInicio, $dataFim])
                ->pluck('user_id')
                ->unique()
        )->get(['id', 'name']);

        // Totalizadores gerais
        $resumoTotal = [
            'caixas'   => $caixas->count(),
            'vendas'   => $caixas->sum('total_vendas_count'),
            'valor'    => $caixas->sum('total_vendas_valor'),
            'reforcos' => $caixas->sum('total_reforcos'),
            'sangrias' => $caixas->sum('total_sangrias'),
        ];

        return view('pdv::relatorios.resumo-caixas', compact(
            'caixas', 'operadoresParaFiltro', 'resumoTotal',
            'dataInicio', 'dataFim', 'statusCaixa', 'userId'
        ) + ['title' => 'Resumo de Caixas']);
    }
}
