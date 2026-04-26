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

/**
 * Registro de quebra/sobra de caixa no fechamento (um por fechamento).
 * Rastreável: valor_quebra_sobra, quem aprovou (se excedeu limite), dados por forma.
 */
class CaixaFechamentoQuebraSobra extends Model
{
    protected $table = 'plugin_pdv_caixa_fechamento_quebra_sobra';

    protected $fillable = [
        'caixa_id',
        'valor_quebra_sobra',
        'aprovado_por_user_id',
        'dados',
    ];

    protected $casts = [
        'valor_quebra_sobra' => 'decimal:2',
        'dados' => 'array',
    ];

    public function caixa(): BelongsTo
    {
        return $this->belongsTo(Caixa::class, 'caixa_id');
    }

    public function aprovadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'aprovado_por_user_id');
    }
}
