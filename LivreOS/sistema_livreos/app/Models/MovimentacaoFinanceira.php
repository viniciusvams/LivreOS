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

class MovimentacaoFinanceira extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'movimentacoes_financeiras';

    protected $fillable = [
        'tenant_id',
        'conta_bancaria_id',
        'tipo',
        'origem',
        'conta_receber_id',
        'conta_pagar_id',
        'transferencia_id',
        'plano_conta_id',
        'centro_custo_id',
        'valor',
        'data_movimentacao',
        'descricao',
        'observacoes',
        'conciliado',
        'data_conciliacao',
        'conciliado_por',
        'motivo_desconciliacao',
        'desconciliado_por',
        'data_desconciliacao',
        'motivo_cancelamento_transferencia',
        'cancelado_por',
        'data_cancelamento',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_movimentacao' => 'date',
        'conciliado' => 'boolean',
        'data_conciliacao' => 'date',
        'data_desconciliacao' => 'datetime',
        'data_cancelamento' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function contaReceber()
    {
        return $this->belongsTo(ContaReceber::class);
    }

    public function contaPagar()
    {
        return $this->belongsTo(ContaPagar::class);
    }

    public function planoConta()
    {
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }

    public function centroCusto()
    {
        return $this->belongsTo(CentroCusto::class, 'centro_custo_id');
    }

    public function transferencia()
    {
        return $this->belongsTo(MovimentacaoFinanceira::class, 'transferencia_id');
    }

    /** Movimentação de entrada vinculada a esta saída (quando esta é a saída da transferência). */
    public function transferenciaEntrada()
    {
        return $this->hasOne(MovimentacaoFinanceira::class, 'transferencia_id');
    }

    /** Retorna o par completo da transferência [saída, entrada]. Retorna [] se não for transferência. */
    public function getParTransferencia(): array
    {
        if ($this->origem !== 'transferencia') {
            return [];
        }
        if ($this->transferencia_id !== null) {
            $saida = $this->transferencia;
            return $saida ? [$saida, $this] : [];
        }
        $entrada = $this->transferenciaEntrada;
        return $entrada ? [$this, $entrada] : [];
    }

    public function conciliadoPor()
    {
        return $this->belongsTo(User::class, 'conciliado_por');
    }

    public function desconciliadoPor()
    {
        return $this->belongsTo(User::class, 'desconciliado_por');
    }

    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function baixas()
    {
        return $this->hasMany(BaixaTitulo::class);
    }

    /**
     * Filtra por centro de custo: movimento ou título vinculado (CR/CP) com o centro informado.
     */
    public function scopePorCentroCusto($query, $centroCustoId)
    {
        if ($centroCustoId === null || $centroCustoId === '') {
            return $query;
        }
        return $query->where(function ($q) use ($centroCustoId) {
            $q->where('centro_custo_id', $centroCustoId)
                ->orWhereHas('contaReceber', fn ($q2) => $q2->where('centro_custo_id', $centroCustoId))
                ->orWhereHas('contaPagar', fn ($q2) => $q2->where('centro_custo_id', $centroCustoId));
        });
    }
}
