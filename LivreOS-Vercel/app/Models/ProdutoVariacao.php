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

class ProdutoVariacao extends Model
{
    use HasFactory;

    protected $table = 'produto_variacoes';

    protected $fillable = [
        'produto_id',
        'referencia_sku',
        'opcoes_valores',
        'ean_variacao',
        'quantidade',
        'herdar_do_pai',
    ];

    protected $casts = [
        'opcoes_valores' => 'array',
        'quantidade' => 'decimal:6',
        'herdar_do_pai' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::retrieved(function (self $model): void {
            foreach (['quantidade'] as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
            if (isset($model->attributes['herdar_do_pai']) && $model->attributes['herdar_do_pai'] === '') {
                $model->attributes['herdar_do_pai'] = null;
            }
        });
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
