<?php

namespace Compras\Models;

use App\Models\Contato;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemCompra extends Model
{
    protected $table = 'plugin_compras_ordens';

    protected $fillable = [
        'fornecedor_id',
        'numero_ordem',
        'status',
        'data_emissao',
        'data_previsao',
        'data_aprovacao',
        'observacoes',
        'valor_total',
        'created_by',
        'approved_by',
        'motivo_rejeicao',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_previsao' => 'date',
        'data_aprovacao' => 'datetime',
        'valor_total' => 'decimal:2',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Contato::class, 'fornecedor_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemCompraItem::class, 'ordem_compra_id');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(RecebimentoCompra::class, 'ordem_compra_id');
    }
}
