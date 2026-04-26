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
use Illuminate\Support\Facades\Auth;

class ContaPagar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contas_pagar';

    protected $fillable = [
        'parent_id',
        'estrutura_tipo',
        'status_estrutura',
        'lote_uuid',
        'ordem_no_lote',
        'tenant_id',
        'fornecedor_id',
        'ordem_servico_id',
        'origem_tipo',
        'entidade_tipo',
        'entidade_id',
        'conta_pagar_recorrente_id',
        'plano_conta_id',
        'centro_custo_id',
        'categoria_financeira_id',
        'descricao',
        'numero_documento',
        'valor',
        'valor_original',
        'valor_pago',
        'data_vencimento',
        'data_pagamento',
        'forma_pagamento_id',
        'conta_bancaria_id',
        'tipo',
        'status',
        'juros',
        'multa',
        'desconto',
        'observacoes',
        'metadata',
        'referencia_externa',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_original' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'desconto' => 'decimal:2',
        'entidade_id' => 'integer',
        'ordem_no_lote' => 'integer',
        'metadata' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Cliente::class, 'fornecedor_id');
    }

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function contaPagarRecorrente()
    {
        return $this->belongsTo(ContaPagarRecorrente::class);
    }

    public function planoConta()
    {
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }

    public function centroCusto()
    {
        return $this->belongsTo(CentroCusto::class, 'centro_custo_id');
    }

    public function categoriaFinanceira()
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_financeira_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'conta_pagar_tag')->withTimestamps();
    }

    public function formaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class);
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
        return $this->hasMany(BaixaTitulo::class, 'titulo_id')
            ->where('tipo_titulo', 'conta_pagar')
            ->where('estornado', false);
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoFinanceira::class);
    }

    public function anexos()
    {
        return $this->hasMany(ContaPagarAnexo::class);
    }

    public function getValorPendenteAttribute()
    {
        return $this->valor - $this->valor_pago;
    }

    public function getEstaVencidoAttribute()
    {
        return $this->status === 'aberto' && $this->data_vencimento < now()->toDateString();
    }

    protected static function booted(): void
    {
        static::created(function (self $model) {
            AuditFinanceiroMovimentacao::create([
                'entidade_tipo' => 'conta_pagar',
                'entidade_id' => $model->id,
                'acao' => 'created',
                'antes' => null,
                'depois' => $model->getAttributes(),
                'alteracoes' => null,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updating(function (self $model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }

            $original = $model->getOriginal();
            $alteracoes = [];
            foreach ($dirty as $campo => $novo) {
                $alteracoes[$campo] = [
                    'antes' => $original[$campo] ?? null,
                    'depois' => $novo,
                ];
            }

            AuditFinanceiroMovimentacao::create([
                'entidade_tipo' => 'conta_pagar',
                'entidade_id' => $model->id,
                'acao' => 'updated',
                'antes' => $original,
                'depois' => array_merge($original, $dirty),
                'alteracoes' => $alteracoes,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function (self $model) {
            AuditFinanceiroMovimentacao::create([
                'entidade_tipo' => 'conta_pagar',
                'entidade_id' => $model->id,
                'acao' => 'deleted',
                'antes' => $model->getOriginal(),
                'depois' => null,
                'alteracoes' => null,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::restored(function (self $model) {
            AuditFinanceiroMovimentacao::create([
                'entidade_tipo' => 'conta_pagar',
                'entidade_id' => $model->id,
                'acao' => 'restored',
                'antes' => null,
                'depois' => $model->getAttributes(),
                'alteracoes' => null,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
