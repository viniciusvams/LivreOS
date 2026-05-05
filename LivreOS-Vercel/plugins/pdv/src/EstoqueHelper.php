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

use Pdv\Models\Venda;
use Pdv\Models\VendaItem;

/**
 * Sincronização de estoque: baixa nos produtos ao finalizar venda no PDV.
 * Compatível com produto simples, variação e kit (composição).
 */
class EstoqueHelper
{
    /**
     * Verifica se os itens da venda respeitam o estoque disponível.
     * Retorna null se OK; ou array de itens com estoque insuficiente [['produto_id' => id, 'nome' => ..., 'estoque_disponivel' => float, 'quantidade_solicitada' => float], ...].
     *
     * @param array $itens Array de itens com tipo, produto_id, quantidade (e servico_id para tipo servico)
     * @return array|null
     */
    public static function verificarEstoqueVenda(array $itens): ?array
    {
        $produtosAgregados = [];
        foreach ($itens as $item) {
            if (($item['tipo'] ?? '') !== 'produto' || empty($item['produto_id'])) {
                continue;
            }
            $pid = (int) $item['produto_id'];
            $qtd = (float) ($item['quantidade'] ?? 0);
            if ($qtd <= 0) {
                continue;
            }
            if (!isset($produtosAgregados[$pid])) {
                $produtosAgregados[$pid] = ['produto_id' => $pid, 'quantidade_solicitada' => 0];
            }
            $produtosAgregados[$pid]['quantidade_solicitada'] += $qtd;
        }
        if (empty($produtosAgregados)) {
            return null;
        }
        $produtos = \App\Models\Produto::with(['composicoes.componente'])->whereIn('id', array_keys($produtosAgregados))->get()->keyBy('id');
        $insuficientes = [];
        foreach ($produtosAgregados as $pid => $agg) {
            $produto = $produtos->get($pid);
            if (!$produto) {
                continue;
            }
            $qtdSolicitada = (float) $agg['quantidade_solicitada'];

            if (($produto->formato ?? '') === 'composicao') {
                $composicoes = $produto->composicoes ?? $produto->composicoes()->with('componente')->get();
                foreach ($composicoes as $comp) {
                    $componente = $comp->componente;
                    if (!$componente || $componente->estoque_quantidade === null) {
                        continue;
                    }
                    $necessario = (float) ($comp->quantidade ?? 0) * $qtdSolicitada;
                    $disponivel = (float) $componente->estoque_quantidade;
                    if ($necessario > $disponivel) {
                        $insuficientes[] = [
                            'produto_id' => $pid,
                            'nome' => $produto->nome . ' (componente: ' . ($componente->nome ?? '') . ')',
                            'estoque_disponivel' => $disponivel,
                            'quantidade_solicitada' => $necessario,
                        ];
                        break;
                    }
                }
                continue;
            }

            $disponivel = $produto->estoque_quantidade !== null ? (float) $produto->estoque_quantidade : 0.0;
            if ($qtdSolicitada > $disponivel) {
                $insuficientes[] = [
                    'produto_id' => $pid,
                    'nome' => $produto->nome,
                    'estoque_disponivel' => $disponivel,
                    'quantidade_solicitada' => $qtdSolicitada,
                ];
            }
        }
        return $insuficientes ?: null;
    }

    public static function baixarEstoqueVenda(Venda $venda): void
    {
        foreach ($venda->itens()->with(['produto.composicoes.componente', 'servico'])->get() as $item) {
            if ($item->tipo === 'servico') {
                continue;
            }
            if (!$item->produto_id || !$item->produto) {
                continue;
            }
            $produto = $item->produto;
            $qtdItem = (float) $item->quantidade;

            // Kit/composição: baixa nos componentes
            if (($produto->formato ?? '') === 'composicao') {
                $composicoes = $produto->composicoes ?? $produto->composicoes()->with('componente')->get();
                foreach ($composicoes as $comp) {
                    $componente = $comp->componente;
                    if (!$componente || $componente->estoque_quantidade === null) {
                        continue;
                    }
                    $qtdBaixa = (float) ($comp->quantidade ?? 0) * $qtdItem;
                    if ($qtdBaixa <= 0) {
                        continue;
                    }
                    $novaQuantidade = (float) $componente->estoque_quantidade - $qtdBaixa;
                    $componente->estoque_quantidade = max($novaQuantidade, 0);
                    $componente->save();
                }
                continue;
            }

            // Produto unitário
            if ($produto->estoque_quantidade === null) {
                continue;
            }
            $novaQuantidade = (float) $produto->estoque_quantidade - $qtdItem;
            $produto->estoque_quantidade = max($novaQuantidade, 0);
            $produto->save();
        }
    }

    /**
     * Estorna estoque ao cancelar uma venda (devolve as quantidades ainda "em aberto" na linha).
     * Usa (quantidade - quantidade_cancelada) para não duplicar após cancelamentos parciais.
     */
    public static function estornarEstoqueVenda(Venda $venda): void
    {
        foreach ($venda->itens()->with(['produto.composicoes.componente', 'servico'])->get() as $item) {
            $qtdRestante = self::quantidadeLinhaAindaVendida($item);
            if ($qtdRestante <= 0) {
                continue;
            }
            self::estornarEstoqueItemQuantidade($item, $qtdRestante);
        }
    }

    /**
     * Quantidade da linha que ainda conta como vendida (não cancelada).
     */
    public static function quantidadeLinhaAindaVendida(VendaItem $item): float
    {
        $q = (float) $item->quantidade;
        $c = (float) ($item->quantidade_cancelada ?? 0);
        return max(0, $q - $c);
    }

    /**
     * Devolve ao estoque uma quantidade específica de unidades vendidas nesta linha (produto ou kit).
     */
    public static function estornarEstoqueItemQuantidade(VendaItem $item, float $quantidadeUnidades): void
    {
        if ($item->tipo === 'servico' || $quantidadeUnidades <= 0) {
            return;
        }
        if (!$item->produto_id || !$item->produto) {
            return;
        }
        $item->loadMissing(['produto.composicoes.componente']);
        $produto = $item->produto;
        $qtdItem = $quantidadeUnidades;

        if (($produto->formato ?? '') === 'composicao') {
            $composicoes = $produto->composicoes ?? $produto->composicoes()->with('componente')->get();
            foreach ($composicoes as $comp) {
                $componente = $comp->componente;
                if (!$componente || $componente->estoque_quantidade === null) {
                    continue;
                }
                $qtdDevolve = (float) ($comp->quantidade ?? 0) * $qtdItem;
                if ($qtdDevolve <= 0) {
                    continue;
                }
                $componente->estoque_quantidade = (float) $componente->estoque_quantidade + $qtdDevolve;
                $componente->save();
            }

            return;
        }

        if ($produto->estoque_quantidade === null) {
            return;
        }
        $produto->estoque_quantidade = (float) $produto->estoque_quantidade + $qtdItem;
        $produto->save();
    }
}
