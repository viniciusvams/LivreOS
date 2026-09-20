<?php

namespace Compras\Services;

use App\Models\Produto;
use Compras\Models\OrdemCompra;
use Compras\Models\OrdemCompraItem;
use Compras\Models\RecebimentoCompra;
use Compras\Models\RecebimentoCompraItem;
use Illuminate\Support\Facades\DB;

class RecebimentoCompraService
{
    public function registrarRecebimento(OrdemCompra $ordem, array $dados, ?int $usuarioId = null): RecebimentoCompra
    {
        return DB::transaction(function () use ($ordem, $dados, $usuarioId) {
            $recebimento = RecebimentoCompra::create([
                'ordem_compra_id' => $ordem->id,
                'data_recebimento' => $dados['data_recebimento'] ?? now()->toDateString(),
                'numero_documento' => $dados['numero_documento'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
                'usuario_id' => $usuarioId ?? auth()->id(),
                'status' => 'recebido',
                'valor_total' => 0,
            ]);

            $valorTotal = 0;
            foreach ($dados['itens'] ?? [] as $itemDados) {
                $ordemItemId = (int) ($itemDados['ordem_item_id'] ?? 0);
                $ordemItem = $ordemItemId ? OrdemCompraItem::find($ordemItemId) : null;
                if (! $ordemItem || $ordemItem->ordem_compra_id !== $ordem->id) {
                    continue;
                }

                $quantidade = (float) ($itemDados['quantidade_recebida'] ?? 0);
                if ($quantidade <= 0) {
                    continue;
                }

                $preco = (float) ($itemDados['preco_unitario'] ?? $ordemItem->preco_unitario ?? 0);
                $valorItem = $quantidade * $preco;
                $valorTotal += $valorItem;

                $recebimentoItem = RecebimentoCompraItem::create([
                    'recebimento_id' => $recebimento->id,
                    'ordem_item_id' => $ordemItem->id,
                    'produto_id' => $ordemItem->produto_id,
                    'quantidade_recebida' => $quantidade,
                    'unidade' => $ordemItem->unidade,
                    'preco_unitario' => $preco,
                    'valor_total' => $valorItem,
                    'lote' => $itemDados['lote'] ?? null,
                    'validade' => $itemDados['validade'] ?? null,
                    'observacoes' => $itemDados['observacoes'] ?? null,
                ]);

                $ordemItem->quantidade_recebida = (float) $ordemItem->quantidade_recebida + $quantidade;
                $ordemItem->status_item = $ordemItem->quantidade_recebida >= $ordemItem->quantidade ? 'recebido' : 'parcial';
                $ordemItem->save();

                if ($ordemItem->produto_id) {
                    $produto = Produto::find($ordemItem->produto_id);
                    if ($produto) {
                        EstoqueCompraService::receberProduto(
                            $produto,
                            $quantidade,
                            $preco,
                            $usuarioId ?? auth()->id(),
                            (int) $dados['deposito_id']
                        );
                    }
                }

                $recebimentoItem->load('produto');
            }

            $recebimento->valor_total = $valorTotal;
            $recebimento->status = 'recebido';
            $recebimento->save();

            $jaRecebeuTudo = $ordem->itens->every(fn ($item) => (float) ($item->quantidade_recebida ?? 0) >= (float) ($item->quantidade ?? 0));
            $ordem->status = $jaRecebeuTudo ? 'recebida' : 'parcialmente_recebida';
            $ordem->save();

            return $recebimento->fresh(['itens', 'ordemCompra']);
        });
    }
}
