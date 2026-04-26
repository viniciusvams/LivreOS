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

namespace Pdv\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venda extends Model
{
    protected $table = 'plugin_pdv_vendas';

    protected $fillable = [
        'caixa_id',
        'cliente_id',
        'tipo',
        'status',
        'total',
        'desconto',
        'desconto_autorizado_por_user_id',
        'cliente_bloqueado_autorizado_por_user_id',
        'limite_credito_autorizado_por_user_id',
        'estoque_zerado_autorizado_por_user_id',
        'total_final',
        'observacoes',
        'observacao_interna',
        'motivo_cancelamento',
        'cancelado_por_user_id',
        'cancelamento_autorizado_por_user_id',
        'canceled_at',
        'seriais_garantia',
        'synced_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total_final' => 'decimal:2',
        'seriais_garantia' => 'array',
        'synced_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public const TIPO_VENDA = 'venda';
    public const TIPO_ORCAMENTO = 'orcamento';
    public const STATUS_ABERTA = 'aberta';
    public const STATUS_FINALIZADA = 'finalizada';
    public const STATUS_CANCELADA = 'cancelada';

    public function caixa(): BelongsTo
    {
        return $this->belongsTo(Caixa::class, 'caixa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(VendaItem::class, 'venda_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(VendaPagamento::class, 'venda_id');
    }

    public function descontoAutorizadoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'desconto_autorizado_por_user_id');
    }

    public function canceladoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelado_por_user_id');
    }

    public function cancelamentoAutorizadoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelamento_autorizado_por_user_id');
    }

    public function clienteBloqueadoAutorizadoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cliente_bloqueado_autorizado_por_user_id');
    }

    public function limiteCreditoAutorizadoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'limite_credito_autorizado_por_user_id');
    }

    public function estoqueZeradoAutorizadoPorUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'estoque_zerado_autorizado_por_user_id');
    }
}
