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

class Equipamento extends Model
{
    use HasFactory;

    protected $table = 'equipamentos';

    protected $fillable = [
        'cliente_id',
        'marca',
        'modelo',
        'numero_serie',
        'numero_patrimonio',
        'acessorios',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordensServico()
    {
        return $this->hasMany(OrdemServico::class, 'equipamento_id');
    }
}
