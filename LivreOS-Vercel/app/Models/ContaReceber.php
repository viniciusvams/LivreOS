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

use App\Services\OrdemServicoPagamentoTagSyncService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContaReceber extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contas_receber';

    protected $fillable = [
        'parent_id',
        'estrutura_tipo',
        'status_estrutura',
        'lote_uuid',
        'ordem_no_lote',
        'ordem_servico_id',
        'pagamento_recorrente_id',
        'origem_tipo',
        'entidade_tipo',
        'entidade_id',
        'cliente_id',
        'descricao',
        'valor',
        'valor_original',
        'valor_recebido',
        'data_vencimento',
        'data_recebimento',
        'numero_parcela',
        'total_parcelas',
        'forma_pagamento_id',
        'conta_bancaria_id',
        'adquirente_id',
        'bandeira',
        'plano_conta_id',
        'centro_custo_id',
        'categoria_financeira_id',
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
        'valor_recebido' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_recebimento' => 'date',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'desconto' => 'decimal:2',
        'numero_parcela' => 'integer',
        'total_parcelas' => 'integer',
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

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function pagamentoRecorrente()
    {
        return $this->belongsTo(PagamentoRecorrente::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function formaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class);
    }

    public function adquirente()
    {
        return $this->belongsTo(Adquirente::class);
    }

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class);
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
        return $this->belongsToMany(Tag::class, 'conta_receber_tag')->withTimestamps();
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
            ->where('tipo_titulo', 'conta_receber')
            ->where('estornado', false);
    }

    public function baixasTodas()
    {
        return $this->hasMany(BaixaTitulo::class, 'titulo_id')
            ->where('tipo_titulo', 'conta_receber');
    }

    public function movimentacoes()
    {
        return $this->hasMany(MovimentacaoFinanceira::class);
    }

    public function anexos()
    {
        return $this->hasMany(ContaReceberAnexo::class);
    }

    public function getValorPendenteAttribute()
    {
        // Valor total a receber = valor original + juros + multa - desconto
        $valorTotal = $this->valor + ($this->juros ?? 0) + ($this->multa ?? 0) - ($this->desconto ?? 0);
        // Valor pendente = valor total - valor já recebido
        $pendente = $valorTotal - $this->valor_recebido;
        // Nunca retornar negativo
        return max(0, round($pendente, 2));
    }

    public function getEstaVencidoAttribute()
    {
        return $this->status === 'aberto' && $this->data_vencimento < now()->toDateString();
    }

    /** Scope: contas que são adiantamento de OS (usa campo de controle, não descrição) */
    public function scopeAdiantamentoOs($query)
    {
        return $query->where('origem_tipo', 'adiantamento_os');
    }

    /** Verifica se esta conta é adiantamento de OS */
    public function getEhAdiantamentoOsAttribute(): bool
    {
        return $this->origem_tipo === 'adiantamento_os';
    }

    protected static function booted(): void
    {
        static::created(function (self $model) {
            AuditFinanceiroMovimentacao::create([
                'entidade_tipo' => 'conta_receber',
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
                'entidade_tipo' => 'conta_receber',
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
                'entidade_tipo' => 'conta_receber',
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
                'entidade_tipo' => 'conta_receber',
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

        static::saved(function (self $model) {
            if (! $model->ordem_servico_id) {
                return;
            }
            $relevant = $model->wasRecentlyCreated
                || $model->wasChanged('status')
                || $model->wasChanged('valor_recebido');
            if (! $relevant) {
                return;
            }
            $osId = (int) $model->ordem_servico_id;
            DB::afterCommit(function () use ($osId) {
                OrdemServicoPagamentoTagSyncService::syncSePendenteSemTituloAberto($osId);
            });
        });

        static::deleted(function (self $model) {
            if (! $model->ordem_servico_id) {
                return;
            }
            $osId = (int) $model->ordem_servico_id;
            DB::afterCommit(function () use ($osId) {
                OrdemServicoPagamentoTagSyncService::syncSePendenteSemTituloAberto($osId);
            });
        });
    }
}
