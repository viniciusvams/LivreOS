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

class Adquirente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'adquirentes';

    protected $fillable = [
        'tenant_id',
        'nome',
        'codigo',
        'tipo_antecipacao',
        'dias_antecipacao',
        'permite_antecipacao_parcelado',
        'observacoes',
        'ativo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dias_antecipacao' => 'integer',
        'permite_antecipacao_parcelado' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contas()
    {
        return $this->belongsToMany(ContaBancaria::class, 'adquirente_contas')
            ->withPivot('padrao', 'ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    public function contaPadrao()
    {
        return $this->contas()->wherePivot('padrao', true)->first();
    }

    public function formasPagamento()
    {
        return $this->belongsToMany(FormaPagamento::class, 'forma_pagamento_adquirente')
            ->withPivot('padrao', 'ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    public function taxas()
    {
        return $this->hasMany(TaxaAdquirente::class);
    }

    public function taxasAtivas()
    {
        return $this->taxas()
            ->where('ativo', true)
            ->where(function($query) {
                $query->whereNull('data_fim')
                    ->orWhere('data_fim', '>=', now());
            })
            ->where(function($query) {
                $query->whereNull('data_inicio')
                    ->orWhere('data_inicio', '<=', now());
            });
    }
}
