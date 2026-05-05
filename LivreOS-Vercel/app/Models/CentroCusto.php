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
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroCusto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centros_custos';

    protected $fillable = [
        'tenant_id',
        'codigo',
        'nome',
        'descricao',
        'ativo',
        'ordem',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function contasReceber()
    {
        return $this->hasMany(ContaReceber::class, 'centro_custo_id');
    }

    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class, 'centro_custo_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoFinanceira::class, 'centro_custo_id');
    }
}
