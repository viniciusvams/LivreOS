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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoEconomico extends Model
{
    use HasFactory;

    protected $table = 'grupos_economicos';

    protected $fillable = [
        'nome',
        'cnpj_matriz',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'grupo_economico_id');
    }
}
