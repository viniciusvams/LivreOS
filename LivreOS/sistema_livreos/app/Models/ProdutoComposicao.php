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

class ProdutoComposicao extends Model
{
    use HasFactory;

    protected $table = 'produto_composicoes';

    protected $fillable = [
        'produto_id',
        'componente_id',
        'quantidade',
        'custo_unitario',
        'custo_total',
    ];

    protected $casts = [
        'custo_unitario' => 'decimal:2',
        'custo_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::retrieved(function (self $model): void {
            foreach (['custo_unitario', 'custo_total'] as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
        });
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function componente()
    {
        return $this->belongsTo(Produto::class, 'componente_id');
    }
}
