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

namespace AreaCliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Resposta ou observação em um chamado.
 * Tabela: plugin_area_cliente_chamado_respostas
 */
class ChamadoResposta extends Model
{
    protected $table = 'plugin_area_cliente_chamado_respostas';

    protected $fillable = [
        'chamado_id',
        'user_id',
        'mensagem',
        'interno',
    ];

    protected $casts = [
        'chamado_id' => 'integer',
        'user_id' => 'integer',
        'interno' => 'boolean',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class, 'chamado_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(ChamadoAnexo::class, 'chamado_resposta_id');
    }
}
