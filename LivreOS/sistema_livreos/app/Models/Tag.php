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

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'nome',
        'tipo',
        'escopo_financeiro',
        'descricao',
        'cor_fundo',
        'cor_fonte',
        'ativo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function ordensServico()
    {
        return $this->belongsToMany(OrdemServico::class, 'ordem_servico_tag');
    }

    public function contasReceber()
    {
        return $this->belongsToMany(ContaReceber::class, 'conta_receber_tag');
    }

    public function contasPagar()
    {
        return $this->belongsToMany(ContaPagar::class, 'conta_pagar_tag');
    }

    /** Tags usáveis em títulos financeiros deste escopo (inclui tags globais escopo_financeiro null). */
    public function scopeParaTituloFinanceiro($query, string $escopoFinanceiro)
    {
        return $query->where('ativo', true)->where(function ($q) use ($escopoFinanceiro) {
            $q->whereNull('escopo_financeiro')->orWhere('escopo_financeiro', $escopoFinanceiro);
        });
    }
}
