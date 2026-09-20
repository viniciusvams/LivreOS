<?php

namespace Compras\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecebimentoCompra extends Model
{
    protected $table = 'plugin_compras_recebimentos';

    protected $fillable = [
        'ordem_compra_id',
        'data_recebimento',
        'numero_documento',
        'observacoes',
        'usuario_id',
        'status',
        'valor_total',
    ];

    protected $casts = [
        'data_recebimento' => 'date',
        'valor_total' => 'decimal:2',
    ];

    public function ordemCompra(): BelongsTo
    {
        return $this->belongsTo(OrdemCompra::class, 'ordem_compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(RecebimentoCompraItem::class, 'recebimento_id');
    }
}
