<?php

namespace Compras\Services;

use App\Models\Contato;
use App\Models\Produto;
use Compras\Models\OrdemCompra;
use Compras\Models\OrdemCompraItem;
use Illuminate\Support\Str;

class OrdemCompraService
{
    public function criar(array $dados, ?int $usuarioId = null): OrdemCompra
    {
        $fornecedorId = (int) ($dados['fornecedor_id'] ?? 0);
        $fornecedor = $fornecedorId ? Contato::find($fornecedorId) : null;
        if (! $fornecedor || ! $fornecedor->is_fornecedor) {
            throw new \InvalidArgumentException('Fornecedor inválido.');
        }

        $ordem = OrdemCompra::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_ordem' => $this->gerarNumero(),
            'status' => 'rascunho',
            'data_emissao' => now()->toDateString(),
            'data_previsao' => $dados['data_previsao'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
            'valor_total' => 0,
            'created_by' => $usuarioId ?? auth()->id(),
        ]);

        $valorTotal = 0;
        foreach ($dados['itens'] ?? [] as $itemDados) {
            $produtoId = (int) ($itemDados['produto_id'] ?? 0);
            $produto = $produtoId ? Produto::find($produtoId) : null;
            if (! $produto) {
                continue;
            }

            $quantidade = (float) ($itemDados['quantidade'] ?? 0);
            $preco = (float) ($itemDados['preco_unitario'] ?? 0);
            $valorItem = $quantidade * $preco;
            $valorTotal += $valorItem;

            OrdemCompraItem::create([
                'ordem_compra_id' => $ordem->id,
                'produto_id' => $produto->id,
                'fornecedor_codigo' => $itemDados['fornecedor_codigo'] ?? null,
                'descricao' => $produto->nome,
                'quantidade' => $quantidade,
                'unidade' => $produto->unidade ?? 'un',
                'preco_unitario' => $preco,
                'valor_total' => $valorItem,
                'quantidade_recebida' => 0,
                'status_item' => 'pendente',
                'observacoes' => $itemDados['observacoes'] ?? null,
            ]);
        }

        $ordem->valor_total = $valorTotal;
        $ordem->save();

        return $ordem->fresh(['itens', 'fornecedor']);
    }

    public function aprovar(OrdemCompra $ordem, ?int $usuarioId = null): OrdemCompra
    {
        if (! in_array($ordem->status, ['rascunho', 'pendente_aprovacao'], true)) {
            throw new \RuntimeException('Essa ordem não pode ser aprovada no momento.');
        }

        $ordem->status = 'aprovada';
        $ordem->data_aprovacao = now();
        $ordem->approved_by = $usuarioId ?? auth()->id();
        $ordem->save();

        foreach ($ordem->itens as $item) {
            $item->status_item = 'aprovado';
            $item->save();
        }

        return $ordem->fresh(['itens', 'fornecedor']);
    }

    public function rejeitar(OrdemCompra $ordem, string $motivo, ?int $usuarioId = null): OrdemCompra
    {
        $ordem->status = 'rejeitada';
        $ordem->approved_by = $usuarioId ?? auth()->id();
        $ordem->motivo_rejeicao = $motivo;
        $ordem->save();

        foreach ($ordem->itens as $item) {
            $item->status_item = 'rejeitado';
            $item->save();
        }

        return $ordem->fresh(['itens', 'fornecedor']);
    }

    protected function gerarNumero(): string
    {
        return 'OC-' . strtoupper(Str::random(6)) . '-' . now()->format('Ymd');
    }
}
