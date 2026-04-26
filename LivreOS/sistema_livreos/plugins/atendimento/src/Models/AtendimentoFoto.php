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

namespace Atendimento\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtendimentoFoto extends Model
{
    protected $table = 'plugin_atendimento_fotos';

    public const TIPO_ANTES = 'antes';
    public const TIPO_DEPOIS = 'depois';
    public const TIPO_EVIDENCIA = 'evidencia';

    protected $fillable = [
        'atendimento_id',
        'tipo',
        'legenda',
        'caminho',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class);
    }
}
