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

/**
 * Pagamentos fixos recorrentes (contas a pagar): água, luz, aluguel, condomínio, etc.
 * Gera contas a pagar automaticamente via comando diário.
 */
class ContaPagarRecorrente extends Model
{
    protected $table = 'contas_pagar_recorrentes';

    protected $fillable = [
        'tenant_id',
        'fornecedor_id',
        'descricao',
        'tipo',
        'valor',
        'data_inicio',
        'data_fim',
        'frequencia',
        'gerar_ultimo_dia_mes',
        'proxima_geracao_em',
        'forma_pagamento_id',
        'conta_bancaria_id',
        'plano_conta_id',
        'ativo',
        'observacoes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'proxima_geracao_em' => 'date',
        'ativo' => 'boolean',
        'gerar_ultimo_dia_mes' => 'boolean',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Cliente::class, 'fornecedor_id');
    }

    public function formaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    public function planoConta()
    {
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Contas a pagar geradas por esta despesa recorrente.
     */
    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class, 'conta_pagar_recorrente_id');
    }

    /**
     * Calcula a próxima data de geração a partir de uma data base.
     */
    public function calcularProximaData(\Carbon\Carbon $dataBase): \Carbon\Carbon
    {
        $next = match ($this->frequencia) {
            'diaria' => $dataBase->copy()->addDay(),
            'semanal' => $dataBase->copy()->addWeek(),
            'quinzenal' => $dataBase->copy()->addWeeks(2),
            'mensal' => $dataBase->copy()->addMonth(),
            'bimestral' => $dataBase->copy()->addMonths(2),
            'trimestral' => $dataBase->copy()->addMonths(3),
            'semestral' => $dataBase->copy()->addMonths(6),
            'anual' => $dataBase->copy()->addYear(),
            default => $dataBase->copy()->addMonth(),
        };
        if ($this->frequencia === 'mensal' && $this->gerar_ultimo_dia_mes) {
            $next->endOfMonth();
        }
        return $next;
    }

    /** Labels de frequência para exibição */
    public static function frequenciaLabel(string $frequencia): string
    {
        return match ($frequencia) {
            'diaria' => 'Diária',
            'semanal' => 'Semanal',
            'quinzenal' => 'Quinzenal',
            'mensal' => 'Mensal',
            'bimestral' => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => ucfirst($frequencia),
        };
    }

    /** Labels de tipo para exibição (contas fixas: água, luz, etc.) */
    public static function tipoLabel(?string $tipo): string
    {
        return $tipo ? (match ($tipo) {
            'agua' => 'Água',
            'luz' => 'Luz',
            'gas' => 'Gás',
            'aluguel' => 'Aluguel',
            'condominio' => 'Condomínio',
            'telefone' => 'Telefone',
            'internet' => 'Internet',
            'outro' => 'Outro',
            default => ucfirst($tipo),
        }) : '-';
    }
}
