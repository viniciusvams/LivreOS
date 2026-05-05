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

class TabelaPreco extends Model
{
    use HasFactory;

    protected $table = 'tabelas_precos';

    protected $fillable = [
        'nome',
        'percentual_ajuste',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'percentual_ajuste' => 'decimal:2',
    ];

    public function produtos()
    {
        return $this->belongsToMany(Produto::class, 'produto_tabela_preco')
            ->withPivot(['preco'])
            ->withTimestamps();
    }
}
