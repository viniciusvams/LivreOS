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

class StatusOs extends Model
{
    protected $table = 'status_os';

    protected $fillable = [
        'nome',
        'cor',
        'ordem',
        'ativo',
        'sistema',
        'marca_inicio',
        'marca_conclusao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'sistema' => 'boolean',
        'marca_inicio' => 'boolean',
        'marca_conclusao' => 'boolean',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }
}
