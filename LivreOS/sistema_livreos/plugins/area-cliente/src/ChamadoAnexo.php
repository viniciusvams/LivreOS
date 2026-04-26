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

/**
 * Anexo de um chamado (foto, documento).
 * Tabela: plugin_area_cliente_chamado_anexos
 */
class ChamadoAnexo extends Model
{
    protected $table = 'plugin_area_cliente_chamado_anexos';

    protected $fillable = [
        'chamado_id',
        'chamado_resposta_id',
        'path',
        'nome_original',
    ];

    protected $casts = [
        'chamado_id' => 'integer',
        'chamado_resposta_id' => 'integer',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class, 'chamado_id');
    }

    public function resposta(): BelongsTo
    {
        return $this->belongsTo(ChamadoResposta::class, 'chamado_resposta_id');
    }
}
