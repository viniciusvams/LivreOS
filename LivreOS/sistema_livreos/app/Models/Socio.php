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

class Socio extends Model
{
    use HasFactory;

    protected $table = 'socios';

    protected $fillable = [
        'cliente_id',
        'nome_completo',
        'cpf',
        'rg_orgao_emissor',
        'nacionalidade',
        'estado_civil',
        'profissao',
        'cep',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'uf',
        'socio_administrador',
        'tipo_assinatura',
        'percentual_participacao',
        'score_credito',
        'possui_restricoes',
        'patrimonio_estimado',
    ];

    protected $casts = [
        'socio_administrador' => 'boolean',
        'possui_restricoes' => 'boolean',
        'percentual_participacao' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
