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

class VendaPagamento extends Model
{
    protected $table = 'plugin_pdv_venda_pagamentos';

    protected $fillable = [
        'venda_id',
        'forma_pagamento_id',
        'valor',
        'parcelas',
        'conta_bancaria_id',
        'adquirente_id',
        'bandeira',
        'antecipado',
        'observacoes',
        'pix_chave_id',
        'pix_parcela_vencimentos',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'parcelas' => 'integer',
        'antecipado' => 'boolean',
        'pix_parcela_vencimentos' => 'array',
    ];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function formaPagamento(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FormaPagamento::class, 'forma_pagamento_id');
    }

    public function contaBancaria(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ContaBancaria::class, 'conta_bancaria_id');
    }

    public function adquirente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Adquirente::class, 'adquirente_id');
    }
}
