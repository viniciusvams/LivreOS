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

class PrioridadeAtendimento extends Model
{
    protected $table = 'plugin_atendimento_prioridades';

    protected $fillable = ['nome', 'cor', 'ordem'];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function atendimentos()
    {
        return $this->hasMany(Atendimento::class, 'prioridade_id');
    }
}
