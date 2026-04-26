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

use App\Models\PedidoVendaItem;

/**
 * Devolução ao estoque ao cancelar (total ou parcial) itens de produto no pedido de venda faturado.
 */
class PedidoVendaEstoqueHelper
{
    public static function quantidadeLinhaAindaVendida(PedidoVendaItem $item): float
    {
        $q = (float) $item->quantidade;
        $c = (float) ($item->quantidade_cancelada ?? 0);

        return max(0, $q - $c);
    }

    public static function estornarItemQuantidade(PedidoVendaItem $item, float $quantidadeUnidades): void
    {
        if ($item->tipo !== 'produto' || $quantidadeUnidades <= 0) {
            return;
        }
        if (! $item->produto_id) {
            return;
        }
        $item->loadMissing(['produto.composicoes.componente']);
        $produto = $item->produto;
        if (! $produto) {
            return;
        }

        if (($produto->formato ?? '') === 'composicao') {
            $composicoes = $produto->composicoes ?? $produto->composicoes()->with('componente')->get();
            foreach ($composicoes as $comp) {
                $componente = $comp->componente;
                if (! $componente || $componente->estoque_quantidade === null) {
                    continue;
                }
                $qtdDevolve = (float) ($comp->quantidade ?? 0) * $quantidadeUnidades;
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
        $produto->estoque_quantidade = (float) $produto->estoque_quantidade + $quantidadeUnidades;
        $produto->save();
    }

    /**
     * Estorna quantidades ainda "em aberto" em cada linha (após cancelamentos parciais).
     */
    public static function estornarEstoquePedidoItensRestantes(iterable $itens): void
    {
        foreach ($itens as $item) {
            $q = self::quantidadeLinhaAindaVendida($item);
            if ($q <= 0) {
                continue;
            }
            self::estornarItemQuantidade($item, $q);
        }
    }
}
