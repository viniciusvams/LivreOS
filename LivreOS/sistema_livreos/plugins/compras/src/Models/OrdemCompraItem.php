<?php

namespace Compras\Models;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemCompraItem extends Model
{
    protected $table = 'plugin_compras_ordens_itens';

    protected $fillable = [
        'ordem_compra_id',
        'produto_id',
        'fornecedor_codigo',
        'descricao',
        'quantidade',
        'unidade',
        'preco_unitario',
        'valor_total',
        'quantidade_recebida',
        'status_item',
        'observacoes',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'quantidade_recebida' => 'decimal:4',
    ];

    public function ordemCompra(): BelongsTo
    {
        return $this->belongsTo(OrdemCompra::class, 'ordem_compra_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(RecebimentoCompraItem::class, 'ordem_item_id');
    }
}
