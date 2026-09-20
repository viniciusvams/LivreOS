<?php

namespace Compras\Models;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecebimentoCompraItem extends Model
{
    protected $table = 'plugin_compras_recebimento_itens';

    protected $fillable = [
        'recebimento_id',
        'ordem_item_id',
        'produto_id',
        'quantidade_recebida',
        'unidade',
        'preco_unitario',
        'valor_total',
        'lote',
        'validade',
        'observacoes',
    ];

    protected $casts = [
        'quantidade_recebida' => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'validade' => 'date',
    ];

    public function recebimento(): BelongsTo
    {
        return $this->belongsTo(RecebimentoCompra::class, 'recebimento_id');
    }

    public function ordemItem(): BelongsTo
    {
        return $this->belongsTo(OrdemCompraItem::class, 'ordem_item_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
