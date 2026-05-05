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

class CaixaMovimentacao extends Model
{
    protected $table = 'plugin_pdv_caixa_movimentacoes';

    protected $fillable = [
        'caixa_id',
        'tipo',
        'valor',
        'plano_conta_id',
        'justificativa',
        'created_by',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public const TIPO_ABERTURA = 'abertura';
    public const TIPO_REFORCO = 'reforco';
    public const TIPO_SANGRIA = 'sangria';
    public const TIPO_FECHAMENTO = 'fechamento';

    public function caixa(): BelongsTo
    {
        return $this->belongsTo(Caixa::class, 'caixa_id');
    }

    public function planoConta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PlanoConta::class, 'plano_conta_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
