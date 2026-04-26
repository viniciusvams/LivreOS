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

class LimiteDesconto extends Model
{
    protected $table = 'limite_desconto';

    protected $fillable = [
        'limite_valor',
        'limite_percentual',
        'bloquear_alteracao_preco',
    ];

    protected $casts = [
        'limite_valor' => 'decimal:2',
        'limite_percentual' => 'decimal:2',
        'bloquear_alteracao_preco' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'limite_desconto_role')
            ->withTimestamps();
    }

    /**
     * Retorna o registro singleton de limites (sempre id 1).
     */
    public static function config(): self
    {
        $config = static::first();
        if (!$config) {
            $config = static::create([
                'limite_valor' => null,
                'limite_percentual' => null,
            ]);
        }
        return $config;
    }
}
