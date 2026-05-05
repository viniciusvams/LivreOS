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

class VendaItem extends Model
{
    protected $table = 'plugin_pdv_venda_itens';

    protected $fillable = [
        'venda_id',
        'tipo',
        'produto_id',
        'servico_id',
        'descricao',
        'quantidade',
        'quantidade_cancelada',
        'preco_unitario',
        'total',
        'serial',
        'kit_componentes',
        'observacao',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'quantidade_cancelada' => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'kit_componentes' => 'array',
    ];

    public const TIPO_PRODUTO = 'produto';
    public const TIPO_SERVICO = 'servico';

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Produto::class, 'produto_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Servico::class, 'servico_id');
    }
}
