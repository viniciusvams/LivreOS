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

class TaxaAdquirente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxas_adquirente';

    protected $fillable = [
        'adquirente_id',
        'bandeira',
        'modalidade',
        'parcelas_min',
        'parcelas_max',
        'taxa_percentual',
        'taxa_por_parcela',
        'taxa_fixa',
        'usa_antecipacao',
        'taxa_antecipacao_percentual',
        'dias_recebimento_debito',
        'dias_recebimento_credito_vista',
        'dias_recebimento_parcela',
        'data_inicio',
        'data_fim',
        'ativo',
        'observacoes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'parcelas_min' => 'integer',
        'parcelas_max' => 'integer',
        'taxa_percentual' => 'decimal:4',
        'taxa_por_parcela' => 'decimal:4',
        'taxa_fixa' => 'decimal:2',
        'usa_antecipacao' => 'boolean',
        'taxa_antecipacao_percentual' => 'decimal:4',
        'dias_recebimento_debito' => 'integer',
        'dias_recebimento_credito_vista' => 'integer',
        'dias_recebimento_parcela' => 'integer',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'ativo' => 'boolean',
    ];

    /**
     * Normaliza atributos que podem vir como string vazia do banco (ex.: após importação)
     * para evitar MathException ao fazer cast para decimal/integer/boolean/date.
     */
    protected static function booted(): void
    {
        static::retrieved(function (self $model): void {
            $decimalKeys = ['taxa_percentual', 'taxa_por_parcela', 'taxa_fixa', 'taxa_antecipacao_percentual'];
            $intKeys = ['parcelas_min', 'parcelas_max', 'dias_recebimento_debito', 'dias_recebimento_credito_vista', 'dias_recebimento_parcela'];
            $boolKeys = ['usa_antecipacao', 'ativo'];
            $dateKeys = ['data_inicio', 'data_fim'];
            foreach (array_merge($decimalKeys, $intKeys, $dateKeys) as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
            foreach ($boolKeys as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
        });
    }

    public function adquirente()
    {
        return $this->belongsTo(Adquirente::class);
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
     * Verifica se a taxa se aplica a um número específico de parcelas
     */
    public function aplicaAParcelas($numParcelas)
    {
        if ($this->modalidade !== 'credito_parcelado') {
            return false;
        }

        $minOk = $this->parcelas_min === null || $numParcelas >= $this->parcelas_min;
        $maxOk = $this->parcelas_max === null || $numParcelas <= $this->parcelas_max;

        return $minOk && $maxOk;
    }

    /**
     * Verifica se a taxa está vigente na data especificada
     */
    public function estaVigente($data = null)
    {
        $data = $data ?? now();

        $inicioOk = $this->data_inicio === null || $this->data_inicio <= $data;
        $fimOk = $this->data_fim === null || $this->data_fim >= $data;

        return $inicioOk && $fimOk && $this->ativo;
    }
}
