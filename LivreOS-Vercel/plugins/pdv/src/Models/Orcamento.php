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

class Orcamento extends Model
{
    protected $table = 'plugin_pdv_orcamentos';

    protected $fillable = [
        'caixa_id',
        'cliente_id',
        'nome_cliente',
        'total',
        'status',
        'observacao',
        'observacao_interna',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_CONVERTIDO = 'convertido';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    public function caixa(): BelongsTo
    {
        return $this->belongsTo(Caixa::class, 'caixa_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
    }
}
