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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacaoUsuario extends Model
{
    use SoftDeletes;

    protected $table = 'notificacoes_usuarios';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensagem',
        'url',
        'icone',
        'lida',
        'referencia_tipo',
        'referencia_data',
    ];

    protected $casts = [
        'lida' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Retorna cor/classe CSS de acordo com o ícone */
    public function corIcone(): string
    {
        return match ($this->icone) {
            'sucesso' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900',
            'erro'    => 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900',
            'alerta'  => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900',
            default   => 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900',
        };
    }
}
