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

class PlanoConta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plano_contas';

    protected $fillable = [
        'tenant_id',
        'pai_id',
        'codigo',
        'nome',
        'tipo',
        'categoria',
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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pai()
    {
        return $this->belongsTo(PlanoConta::class, 'pai_id');
    }

    public function filhos()
    {
        return $this->hasMany(PlanoConta::class, 'pai_id')->orderBy('ordem');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contasReceber()
    {
        return $this->hasMany(ContaReceber::class, 'plano_conta_id');
    }

    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class, 'plano_conta_id');
    }
}
