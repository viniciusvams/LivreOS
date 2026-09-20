<?php

namespace Compras\Services;

use App\Models\Produto;

class EstoqueCompraService
{
    public static function receberProduto(
        Produto $produto,
        float $quantidade,
        float $custoUnitario,
        ?int $usuarioId = null,
        ?int $depositoId = null
    ): void
    {
        if ($quantidade <= 0) {
            return;
        }

        $qAntes = (float) ($produto->estoque_quantidade ?? 0);
        $cAntes = (float) ($produto->estoque_preco_custo_unitario ?? 0);
        $qNova = $qAntes + $quantidade;

        $custoMedio = $qNova > 0
            ? (($qAntes * $cAntes) + ($quantidade * $custoUnitario)) / $qNova
            : $custoUnitario;

        $produto->estoque_quantidade = round($qNova, 4);
        $produto->estoque_preco_custo_unitario = round($custoMedio, 2);
        $produto->estoque_preco_compra_unitario = round($custoUnitario, 2);
        if ($depositoId !== null) {
            $produto->estoque_deposito_id = $depositoId;
        }
        $produto->updated_by = $usuarioId ?? $produto->updated_by;
        $produto->save();
    }
}
