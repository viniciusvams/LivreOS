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

class ContaBancaria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contas_bancarias';

    protected $fillable = [
        'tenant_id',
        'nome',
        'tipo',
        'banco',
        'agencia',
        'conta',
        'digito',
        'pix',
        'saldo_inicial',
        'data_saldo_inicial',
        'ativo',
        'observacoes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'data_saldo_inicial' => 'date',
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

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoFinanceira::class);
    }

    public function contasReceber()
    {
        return $this->hasMany(ContaReceber::class);
    }

    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class);
    }

    public function adquirentes()
    {
        return $this->belongsToMany(Adquirente::class, 'adquirente_contas')
            ->withPivot('padrao', 'ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    public function getSaldoAtualAttribute()
    {
        $entradas = $this->movimentacoes()
            ->where('tipo', 'entrada')
            ->where('conciliado', true)
            ->whereNull('data_cancelamento')
            ->sum('valor');

        $saidas = $this->movimentacoes()
            ->where('tipo', 'saida')
            ->where('conciliado', true)
            ->whereNull('data_cancelamento')
            ->sum('valor');

        return $this->saldo_inicial + $entradas - $saidas;
    }
}
