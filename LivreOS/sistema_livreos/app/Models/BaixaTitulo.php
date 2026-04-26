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

class BaixaTitulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'baixas_titulos';

    protected $fillable = [
        'tipo_titulo',
        'titulo_id',
        'movimentacao_id',
        'taxa_movimentacao_id',
        'valor_baixa',
        'data_baixa',
        'juros',
        'multa',
        'desconto',
        'observacoes',
        'estornado',
        'data_estorno',
        'estornado_por',
        'motivo_estorno',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valor_baixa' => 'decimal:2',
        'data_baixa' => 'date',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'desconto' => 'decimal:2',
        'estornado' => 'boolean',
        'data_estorno' => 'date',
    ];

    public function movimentacao()
    {
        return $this->belongsTo(MovimentacaoFinanceira::class);
    }

    public function taxaMovimentacao()
    {
        return $this->belongsTo(MovimentacaoFinanceira::class, 'taxa_movimentacao_id');
    }

    public function estornadoPor()
    {
        return $this->belongsTo(User::class, 'estornado_por');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contaReceber()
    {
        if ($this->tipo_titulo === 'conta_receber') {
            return ContaReceber::find($this->titulo_id);
        }
        return null;
    }

    public function contaPagar()
    {
        if ($this->tipo_titulo === 'conta_pagar') {
            return ContaPagar::find($this->titulo_id);
        }
        return null;
    }
}
