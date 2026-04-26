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

namespace App\Services;

use App\Models\ContaReceber;
use App\Models\OrdemServico;
use App\Models\OrdemServicoProduto;
use App\Models\OrdemServicoServico;
use App\Models\PedidoVenda;
use App\Models\PedidoVendaItem;
use Illuminate\Support\Collection;

class RelatorioVendasServicoProdutoService
{
    /** Status que contam como OS “fechada” no modo operacional (PDF / produção), exceto canceladas. */
    private const OS_STATUS_OPERACIONAL_FECHADA_LOWER = [
        'encerrada',
        'encerrado',
        'concluída',
        'concluida',
        'concluído',
        'concluido',
        'finalizado',
        'finalizada',
        'faturado',
        'faturada',
        'fechado',
        'fechada',
        'entregue',
    ];

    /**
     * Quantidade efetiva para faturamento na OS: linhas só com qtd (ou horas / pacote fixo) entram no relatório.
     * Itens só com valor e quantidade 0 (ex.: orçamento de referência na mesma OS) ficam de fora.
     */
    protected function quantidadeEfetivaServicoOs(OrdemServicoServico $row): float
    {
        $tipo = strtolower((string) ($row->cobranca_tipo ?? 'unidade'));
        if (in_array($tipo, ['horas', 'tempo'], true)) {
            return max(0.0, (float) ($row->quantidade_horas ?? 0));
        }
        if ($tipo === 'fixo') {
            $q = (float) ($row->quantidade ?? 0);

            return $q > 0 ? $q : ((float) ($row->total ?? 0) > 0 ? 1.0 : 0.0);
        }

        return max(0.0, (float) ($row->quantidade ?? 0));
    }

    protected function linhaProdutoOsContaParaFaturamento(OrdemServicoProduto $row): bool
    {
        return (float) ($row->quantidade ?? 0) > 0 && (float) ($row->total ?? 0) > 0;
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string): mixed  $applyEntityQuery
     * @param  bool  $incluirPedidosOperacional  Incluir pedidos (data_pedido) no modo operacional; padrão false = só OS.
     * @return array{servicos: Collection, produtos: Collection}
     */
    public function aggregate(
        string $dataInicio,
        string $dataFim,
        callable $applyEntityQuery,
        string $criterio = 'financeiro',
        bool $incluirPedidosOperacional = false,
        ?int $tecnicoId = null,
        ?string $slaFiltro = null,
    ): array {
        return $criterio === 'operacional'
            ? $this->aggregateOperacional($dataInicio, $dataFim, $applyEntityQuery, $incluirPedidosOperacional, $tecnicoId, $slaFiltro)
            : $this->aggregateFinanceiro($dataInicio, $dataFim, $applyEntityQuery, $tecnicoId, $slaFiltro);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\OrdemServico>  $osQ
     */
    protected function applyOsFiltros($osQ, ?int $tecnicoId = null, ?string $slaFiltro = null): void
    {
        if ($tecnicoId !== null && $tecnicoId > 0) {
            $osQ->where(function ($q) use ($tecnicoId) {
                $q->whereHas('tecnicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId))
                    ->orWhereHas('servicos', fn ($sq) => $sq->where('tecnico_id', $tecnicoId));
            });
        }

        if ($slaFiltro === 'cumprido') {
            $osQ->where('sla_cumprido', true);
        } elseif ($slaFiltro === 'nao_cumprido') {
            $osQ->where('sla_cumprido', false);
        } elseif ($slaFiltro === 'com_sla') {
            $osQ->whereNotNull('sla_valor');
        } elseif ($slaFiltro === 'sem_sla') {
            $osQ->whereNull('sla_valor');
        }
    }

    /**
     * Aplica filtro de OS fechada para o relatório operacional (case-insensitive, sem depender só de Encerrada).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\OrdemServico>  $osQ
     */
    protected function scopeOrdemServicoOperacionalFechada($osQ): void
    {
        $placeholders = implode(',', array_fill(0, count(self::OS_STATUS_OPERACIONAL_FECHADA_LOWER), '?'));
        $expr = 'LOWER(TRIM(COALESCE(status, \'\')))';
        $exatos = [
            'Encerrada', 'Encerrado',
            'Concluída', 'Concluida', 'Concluído', 'Concluido',
            'Finalizado', 'Finalizada',
            'Faturado', 'Faturada',
            'Feito', 'Fechado', 'Fechada', 'Entregue',
        ];

        $osQ->whereNotNull('status')
            ->whereRaw("{$expr} NOT IN (?,?)", ['cancelada', 'cancelado'])
            ->where(function ($q) use ($exatos, $expr, $placeholders) {
                $q->whereIn('status', $exatos)
                    ->orWhereRaw("{$expr} IN ({$placeholders})", self::OS_STATUS_OPERACIONAL_FECHADA_LOWER);
            });
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string): mixed  $applyEntityQuery
     * @return array{servicos: Collection, produtos: Collection}
     */
    protected function aggregateFinanceiro(string $dataInicio, string $dataFim, callable $applyEntityQuery, ?int $tecnicoId = null, ?string $slaFiltro = null): array
    {
        $servBuckets = [];
        $prodBuckets = [];
        [$addServ, $addProd] = $this->makeBucketAdders($servBuckets, $prodBuckets);

        $contasQ = ContaReceber::query()
            ->whereIn('status', ['pago', 'parcial'])
            ->whereBetween('data_recebimento', [$dataInicio, $dataFim])
            ->where(function ($w) {
                $w->whereNull('origem_tipo')
                    ->orWhere('origem_tipo', '!=', 'adiantamento_os');
            });
        $contasQ = $applyEntityQuery($contasQ, 'conta_receber');
        $contas = $contasQ->get();

        /** @var array<int, float> */
        $receiveByPedido = [];
        /** @var array<int, float> */
        $receiveByOs = [];

        foreach ($contas as $cr) {
            $val = (float) ($cr->valor_recebido ?? $cr->valor ?? 0);
            if ($val <= 0) {
                continue;
            }
            if ($cr->entidade_tipo === 'pedido_venda' && $cr->entidade_id) {
                $pid = (int) $cr->entidade_id;
                $receiveByPedido[$pid] = ($receiveByPedido[$pid] ?? 0) + $val;

                continue;
            }

            $osId = null;
            if ($cr->entidade_tipo === 'ordem_servico' && $cr->entidade_id) {
                $osId = (int) $cr->entidade_id;
            } elseif ($cr->ordem_servico_id) {
                $osId = (int) $cr->ordem_servico_id;
            }
            if ($osId) {
                $receiveByOs[$osId] = ($receiveByOs[$osId] ?? 0) + $val;
            }
        }

        if ($receiveByPedido !== []) {
            $pedidosQ = PedidoVenda::query()
                ->with(['itens.servico', 'itens.produto'])
                ->whereIn('id', array_keys($receiveByPedido));
            $pedidosQ = $applyEntityQuery($pedidosQ, 'pedido_venda');
            $pedidos = $pedidosQ->get()->keyBy('id');

            foreach ($receiveByPedido as $pid => $received) {
                $pedido = $pedidos->get($pid);
                if (! $pedido) {
                    continue;
                }
                if (! in_array($pedido->status, ['confirmado', 'faturado', 'entregue'], true)) {
                    continue;
                }
                $itemsSum = 0.0;
                foreach ($pedido->itens as $it) {
                    $qEff = max(0, (float) $it->quantidade - (float) ($it->quantidade_cancelada ?? 0));
                    if ($qEff <= 0) {
                        continue;
                    }
                    $itemsSum += (float) $it->total;
                }
                $pedidoTotal = max((float) $pedido->total_geral, $itemsSum, 0.00001);
                $factor = min(1.0, $received / $pedidoTotal);

                foreach ($pedido->itens as $it) {
                    $qEff = max(0, (float) $it->quantidade - (float) ($it->quantidade_cancelada ?? 0));
                    if ($qEff <= 0) {
                        continue;
                    }
                    if ($it->tipo === 'servico') {
                        $sid = $it->servico_id;
                        $nome = $it->servico->nome ?? $it->descricao ?? 'Serviço não informado';
                        $key = $sid ? 's:'.$sid : 's:0:'.md5($nome);
                        $addServ($key, $nome, $qEff * $factor, (float) $it->total * $factor);
                    } elseif ($it->tipo === 'produto') {
                        $pidItem = $it->produto_id;
                        $nome = $it->produto->nome ?? $it->descricao ?? 'Produto não informado';
                        $key = $pidItem ? 'p:'.$pidItem : 'p:0:'.md5($nome);
                        $addProd($key, $nome, $qEff * $factor, (float) $it->total * $factor);
                    }
                }
            }
        }

        if ($receiveByOs !== []) {
            $osQ = OrdemServico::query()->whereIn('id', array_keys($receiveByOs));
            $this->applyOsFiltros($osQ, $tecnicoId, $slaFiltro);
            $osQ = $applyEntityQuery($osQ, 'ordem_servico');
            $ordens = $osQ->get()->keyBy('id');

            $osIds = $ordens->keys()->all();
            if ($osIds !== []) {
                $linhasServ = OrdemServicoServico::with('servico')->whereIn('ordem_servico_id', $osIds)->get()
                    ->groupBy(fn ($r) => (int) $r->ordem_servico_id);
                $linhasProd = OrdemServicoProduto::with('produto')->whereIn('ordem_servico_id', $osIds)->get()
                    ->groupBy(fn ($r) => (int) $r->ordem_servico_id);
            } else {
                $linhasServ = collect();
                $linhasProd = collect();
            }

            foreach ($receiveByOs as $osId => $received) {
                $os = $ordens->get($osId);
                if (! $os) {
                    continue;
                }
                $rowsS = $linhasServ->get($osId, collect())->filter(
                    fn (OrdemServicoServico $row) => $this->quantidadeEfetivaServicoOs($row) > 0
                );
                $rowsP = $linhasProd->get($osId, collect())->filter(
                    fn (OrdemServicoProduto $row) => $this->linhaProdutoOsContaParaFaturamento($row)
                );
                $linesSum = (float) $rowsS->sum('total') + (float) $rowsP->sum('total');
                if ($linesSum <= 0) {
                    continue;
                }
                $factor = min(1.0, $received / $linesSum);

                foreach ($rowsS as $row) {
                    $qEff = $this->quantidadeEfetivaServicoOs($row);
                    $sid = $row->servico_id;
                    $nome = $row->servico->nome ?? $row->descricao ?? 'Serviço não informado';
                    $key = $sid ? 's:'.$sid : 's:0:'.md5($nome);
                    $addServ($key, $nome, $qEff * $factor, (float) $row->total * $factor);
                }
                foreach ($rowsP as $row) {
                    $pidRow = $row->produto_id;
                    $nome = $row->produto->nome ?? $row->descricao ?? 'Produto não informado';
                    $key = $pidRow ? 'p:'.$pidRow : 'p:0:'.md5($nome);
                    $addProd($key, $nome, (float) $row->quantidade * $factor, (float) $row->total * $factor);
                }
            }
        }

        return $this->finalizeBuckets($servBuckets, $prodBuckets);
    }

    /**
     * OS fechada (vários status + rótulos de sistema externo) com COALESCE(real_conclusao, updated_at) no período.
     * Pedidos só se $incluirPedidosOperacional. OS com os_gerada_id em pedido ficam fora da agregação por OS.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder, string): mixed  $applyEntityQuery
     * @return array{servicos: Collection, produtos: Collection}
     */
    protected function aggregateOperacional(
        string $dataInicio,
        string $dataFim,
        callable $applyEntityQuery,
        bool $incluirPedidosOperacional = false,
        ?int $tecnicoId = null,
        ?string $slaFiltro = null
    ): array
    {
        $servBuckets = [];
        $prodBuckets = [];
        [$addServ, $addProd] = $this->makeBucketAdders($servBuckets, $prodBuckets);

        $inicioTs = $dataInicio.' 00:00:00';
        $fimTs = $dataFim.' 23:59:59';

        $osIdsFromPedido = PedidoVendaItem::query()
            ->whereNotNull('os_gerada_id')
            ->whereHas('pedidoVenda', function ($q) use ($applyEntityQuery) {
                $applyEntityQuery($q, 'pedido_venda');
            })
            ->distinct()
            ->pluck('os_gerada_id');

        $osQ = OrdemServico::query();
        $this->scopeOrdemServicoOperacionalFechada($osQ);
        $osQ->whereRaw('COALESCE(real_conclusao, updated_at) BETWEEN ? AND ?', [$inicioTs, $fimTs]);
        if ($osIdsFromPedido->isNotEmpty()) {
            $osQ->whereNotIn('id', $osIdsFromPedido);
        }
        $this->applyOsFiltros($osQ, $tecnicoId, $slaFiltro);
        $osQ = $applyEntityQuery($osQ, 'ordem_servico');
        $osIds = $osQ->pluck('id')->all();

        if ($osIds !== []) {
            $linhasServ = OrdemServicoServico::with('servico')->whereIn('ordem_servico_id', $osIds)->get()
                ->groupBy(fn ($r) => (int) $r->ordem_servico_id);
            $linhasProd = OrdemServicoProduto::with('produto')->whereIn('ordem_servico_id', $osIds)->get()
                ->groupBy(fn ($r) => (int) $r->ordem_servico_id);

            foreach ($osIds as $osId) {
                $rowsS = $linhasServ->get($osId, collect())->filter(
                    fn (OrdemServicoServico $row) => $this->quantidadeEfetivaServicoOs($row) > 0
                );
                $rowsP = $linhasProd->get($osId, collect())->filter(
                    fn (OrdemServicoProduto $row) => $this->linhaProdutoOsContaParaFaturamento($row)
                );
                foreach ($rowsS as $row) {
                    $qEff = $this->quantidadeEfetivaServicoOs($row);
                    $sid = $row->servico_id;
                    $nome = $row->servico->nome ?? $row->descricao ?? 'Serviço não informado';
                    $key = $sid ? 's:'.$sid : 's:0:'.md5($nome);
                    $addServ($key, $nome, $qEff, (float) $row->total);
                }
                foreach ($rowsP as $row) {
                    $pidRow = $row->produto_id;
                    $nome = $row->produto->nome ?? $row->descricao ?? 'Produto não informado';
                    $key = $pidRow ? 'p:'.$pidRow : 'p:0:'.md5($nome);
                    $addProd($key, $nome, (float) $row->quantidade, (float) $row->total);
                }
            }
        }

        if ($incluirPedidosOperacional) {
            $pedidosQ = PedidoVenda::query()
                ->with(['itens.servico', 'itens.produto'])
                ->whereIn('status', ['confirmado', 'faturado', 'entregue'])
                ->whereBetween('data_pedido', [$dataInicio, $dataFim]);
            $pedidosQ = $applyEntityQuery($pedidosQ, 'pedido_venda');

            foreach ($pedidosQ->get() as $pedido) {
                foreach ($pedido->itens as $it) {
                    $qEff = max(0, (float) $it->quantidade - (float) ($it->quantidade_cancelada ?? 0));
                    if ($qEff <= 0) {
                        continue;
                    }
                    if ($it->tipo === 'servico') {
                        $sid = $it->servico_id;
                        $nome = $it->servico->nome ?? $it->descricao ?? 'Serviço não informado';
                        $key = $sid ? 's:'.$sid : 's:0:'.md5($nome);
                        $addServ($key, $nome, $qEff, (float) $it->total);
                    } elseif ($it->tipo === 'produto') {
                        $pidItem = $it->produto_id;
                        $nome = $it->produto->nome ?? $it->descricao ?? 'Produto não informado';
                        $key = $pidItem ? 'p:'.$pidItem : 'p:0:'.md5($nome);
                        $addProd($key, $nome, $qEff, (float) $it->total);
                    }
                }
            }
        }

        return $this->finalizeBuckets($servBuckets, $prodBuckets);
    }

    /**
     * @param  array<string, array{nome: string, total: float, quantidade: float}>  $servBuckets
     * @param  array<string, array{nome: string, total: float, quantidade: float}>  $prodBuckets
     * @return array{0: callable, 1: callable}
     */
    protected function makeBucketAdders(array &$servBuckets, array &$prodBuckets): array
    {
        $addServ = function (string $key, string $nome, float $qtd, float $total) use (&$servBuckets): void {
            if (! isset($servBuckets[$key])) {
                $servBuckets[$key] = ['nome' => $nome, 'total' => 0.0, 'quantidade' => 0.0];
            }
            $servBuckets[$key]['total'] += $total;
            $servBuckets[$key]['quantidade'] += $qtd;
        };

        $addProd = function (string $key, string $nome, float $qtd, float $total) use (&$prodBuckets): void {
            if (! isset($prodBuckets[$key])) {
                $prodBuckets[$key] = ['nome' => $nome, 'total' => 0.0, 'quantidade' => 0.0];
            }
            $prodBuckets[$key]['total'] += $total;
            $prodBuckets[$key]['quantidade'] += $qtd;
        };

        return [$addServ, $addProd];
    }

    /**
     * @param  array<string, array{nome: string, total: float, quantidade: float}>  $servBuckets
     * @param  array<string, array{nome: string, total: float, quantidade: float}>  $prodBuckets
     * @return array{servicos: Collection, produtos: Collection}
     */
    protected function finalizeBuckets(array $servBuckets, array $prodBuckets): array
    {
        $servicos = collect($servBuckets)->map(fn ($r) => [
            'nome' => $r['nome'],
            'total' => $r['total'],
            'quantidade' => $r['quantidade'],
        ])->sortByDesc('total')->values();

        $produtos = collect($prodBuckets)->map(fn ($r) => [
            'nome' => $r['nome'],
            'total' => $r['total'],
            'quantidade' => $r['quantidade'],
        ])->sortByDesc('total')->values();

        return ['servicos' => $servicos, 'produtos' => $produtos];
    }
}
