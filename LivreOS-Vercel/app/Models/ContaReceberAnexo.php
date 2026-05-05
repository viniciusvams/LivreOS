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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaReceberAnexo extends Model
{
    protected $table = 'conta_receber_anexos';

    protected $fillable = [
        'conta_receber_id',
        'nome_arquivo',
        'caminho_arquivo',
        'tipo_mime',
        'tamanho',
        'created_by',
    ];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class);
    }
}
