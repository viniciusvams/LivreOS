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

namespace App\Http\Controllers\Concerns;

use App\Models\Produto;
use App\Models\Servico;
use Illuminate\Support\Facades\DB;

trait BuscaCatalogoVendas
{
    /**
     * ID da tabela de preços vindo da query string (sempre inteiro &gt; 0 ou null).
     */
    protected function normalizarTabelaPrecoIdRequest(mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }
        if (is_array($raw)) {
            return null;
        }
        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    /**
     * Preços explícitos na pivot produto_tabela_preco, chave = produto_id (int).
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection  $produtoIds
     * @return \Illuminate\Support\Collection<int, float>
     */
    protected function mapaPrecosProdutoTabela($produtoIds, ?int $tabelaPrecoId): \Illuminate\Support\Collection
    {
        if ($tabelaPrecoId === null || $tabelaPrecoId <= 0 || $produtoIds->isEmpty()) {
            return collect();
        }

        $ids = $produtoIds->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = DB::table('produto_tabela_preco')
            ->whereIn('produto_id', $ids->all())
            ->where('tabela_preco_id', $tabelaPrecoId)
            ->whereNotNull('preco')
            ->get(['produto_id', 'preco']);

        $map = collect();
        foreach ($rows as $row) {
            $map[(int) $row->produto_id] = (float) $row->preco;
        }

        return $map;
    }

    /**
     * Uma linha do autocomplete de produto no formulário de pedido/proposta (e carga por produto_id).
     *
     * @param  \Illuminate\Support\Collection<int, float>  $precosTabelaMap
     * @param  ?float  $percentualAjusteTabela  percentual_ajuste da tabela quando não há preço na pivot
     */
    protected function mapearProdutoLinhaBuscaPedido(
        Produto $p,
        $precosTabelaMap,
        ?int $tabelaPrecoId,
        ?float $percentualAjusteTabela = null
    ): array {
        $pid = (int) $p->id;
        $preco = (float) $p->preco_venda;
        $precoTabela = false;

        if ($tabelaPrecoId !== null && $tabelaPrecoId > 0) {
            if ($precosTabelaMap->has($pid)) {
                $preco = (float) $precosTabelaMap->get($pid);
                $precoTabela = true;
            } elseif ($percentualAjusteTabela !== null && abs($percentualAjusteTabela) > 0.00001) {
                $preco = round($preco * (1 + $percentualAjusteTabela / 100), 2);
                $precoTabela = true;
            }
        }

        $variacoes = [];
        if (($p->formato ?? '') === 'variacao' && $p->relationLoaded('variacoes')) {
            foreach ($p->variacoes as $v) {
                $variacoes[] = [
                    'id' => $v->id,
                    'referencia_sku' => $v->referencia_sku ?? '',
                    'opcoes_valores' => $v->opcoes_valores ?? [],
                    'ean_variacao' => $v->ean_variacao ?? null,
                    'quantidade' => $v->quantidade !== null ? (float) $v->quantidade : null,
                ];
            }
        }

        $kitItens = [];
        if (($p->formato ?? '') === 'composicao' && $p->relationLoaded('composicoes')) {
            foreach ($p->composicoes as $comp) {
                $kitItens[] = [
                    'nome' => $comp->componente?->nome ?? 'Componente',
                    'quantidade' => format_quantity($comp->quantidade ?? 1),
                ];
            }
        }

        $kitComponentes = $this->kitComponentesDoProduto($p);

        return [
            'id' => $p->id,
            'descricao' => $p->nome,
            'preco' => $preco,
            'formato' => $p->formato,
            'estoque_quantidade' => $p->estoque_quantidade,
            'estoque_localizacao' => $p->estoque_localizacao,
            'preco_tabela' => $precoTabela,
            'variacoes' => $variacoes,
            'kit_itens' => $kitItens,
            'kit_componentes' => $kitComponentes,
        ];
    }

    /**
     * Lista legível da composição do kit (mesmo padrão do PDV: "Nome" ou "Nome (Nx)").
     */
    protected function kitComponentesDoProduto(?Produto $produto): array
    {
        if ($produto === null || ($produto->formato ?? '') !== 'composicao') {
            return [];
        }
        if (! $produto->relationLoaded('composicoes')) {
            $produto->load([
                'composicoes' => fn ($q) => $q->orderBy('id'),
                'composicoes.componente',
            ]);
        }
        $out = [];
        foreach ($produto->composicoes as $comp) {
            $qtd = (float) ($comp->quantidade ?? 1);
            $nomeComp = $comp->componente?->nome ?? 'Item';
            $out[] = abs($qtd - 1) > 0.00001 ? "{$nomeComp} ({$qtd}x)" : $nomeComp;
        }

        return $out;
    }
}
