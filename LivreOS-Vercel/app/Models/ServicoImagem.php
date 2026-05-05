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

class ServicoImagem extends Model
{
    use HasFactory;

    protected $table = 'servico_imagens';

    protected $fillable = [
        'servico_id',
        'tipo',
        'caminho_local',
        'url_externa',
        'ordem',
    ];

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
