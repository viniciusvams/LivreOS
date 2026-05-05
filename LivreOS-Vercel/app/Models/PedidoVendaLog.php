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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoVendaLog extends Model
{
    public $timestamps = false;

    protected $table = 'pedido_venda_logs';

    protected $fillable = [
        'pedido_venda_id',
        'user_id',
        'status_anterior',
        'status_novo',
        'acao',
        'descricao',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function pedidoVenda(): BelongsTo
    {
        return $this->belongsTo(PedidoVenda::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
