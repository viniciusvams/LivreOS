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

namespace Pdv\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sessão de caixa do PDV (abertura até fechamento).
 */
class Caixa extends Model
{
    protected $table = 'plugin_pdv_caixas';

    protected $fillable = [
        'numero_caixa',
        'user_id',
        'valor_abertura',
        'valor_fechamento_informado',
        'valor_fechamento_sistema',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'valor_abertura' => 'decimal:2',
        'valor_fechamento_informado' => 'decimal:2',
        'valor_fechamento_sistema' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_ABERTO = 'aberto';
    public const STATUS_FECHADO = 'fechado';

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(CaixaMovimentacao::class, 'caixa_id')->orderBy('created_at');
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(Venda::class, 'caixa_id');
    }

    public function fechamentoQuebraSobra(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CaixaFechamentoQuebraSobra::class, 'caixa_id');
    }

    /**
     * Totais calculados por forma de pagamento para o fechamento.
     * Dinheiro = abertura + reforços - sangrias + vendas em dinheiro.
     * Outras formas = soma dos pagamentos daquela forma nas vendas finalizadas.
     *
     * @return array<int, float> forma_pagamento_id => total_calculado
     */
    public function getTotaisPorFormaPagamento(): array
    {
        $d = $this->getDetalheSaldo();
        $valorDinheiroCaixa = $d['valor_abertura'] + $d['total_reforcos'] - $d['total_sangrias'] + $d['dinheiro_vendas'];

        $formasUsadas = VendaPagamento::query()
            ->whereHas('venda', fn ($q) => $q->where('caixa_id', $this->id)->where('status', Venda::STATUS_FINALIZADA))
            ->selectRaw('forma_pagamento_id, SUM(valor) as total')
            ->groupBy('forma_pagamento_id')
            ->get();

        $formas = \App\Models\FormaPagamento::whereIn('id', $formasUsadas->pluck('forma_pagamento_id'))->get()->keyBy('id');
        $totais = [];
        foreach ($formasUsadas as $row) {
            $formaId = (int) $row->forma_pagamento_id;
            $totalVendas = (float) $row->total;
            $forma = $formas->get($formaId);
            if ($forma && ($forma->tipo ?? '') === 'dinheiro') {
                $totais[$formaId] = $valorDinheiroCaixa;
            } else {
                $totais[$formaId] = $totalVendas;
            }
        }

        if ($formasUsadas->isEmpty() || !$formas->contains(fn ($f) => ($f->tipo ?? '') === 'dinheiro')) {
            $formaDinheiro = \App\Models\FormaPagamento::where('tipo', 'dinheiro')->where('ativo', true)->first();
            if ($formaDinheiro) {
                $totais[$formaDinheiro->id] = $valorDinheiroCaixa;
            }
        }

        return $totais;
    }

    /**
     * Saldo em caixa: abertura + reforços - sangrias + dinheiro recebido nas vendas (formas de pagamento tipo "dinheiro").
     */
    public function getSaldoAtualAttribute(): float
    {
        $d = $this->getDetalheSaldo();
        return $d['entradas'] - $d['saidas'] + $d['dinheiro_vendas'];
    }

    /**
     * Retorna o detalhamento do saldo para exibição no fechamento e na tela do caixa.
     *
     * @return array{entradas: float, saidas: float, dinheiro_vendas: float, valor_abertura: float, total_reforcos: float, total_sangrias: float}
     */
    public function getDetalheSaldo(): array
    {
        $valorAbertura = (float) $this->movimentacoes()->where('tipo', CaixaMovimentacao::TIPO_ABERTURA)->sum('valor');
        $totalReforcos = (float) $this->movimentacoes()->where('tipo', CaixaMovimentacao::TIPO_REFORCO)->sum('valor');
        $totalSangrias = (float) $this->movimentacoes()->where('tipo', CaixaMovimentacao::TIPO_SANGRIA)->sum('valor');
        $entradas = $valorAbertura + $totalReforcos;
        $saidas = $totalSangrias;
        $dinheiroVendas = (float) VendaPagamento::query()
            ->whereHas('venda', fn ($q) => $q->where('caixa_id', $this->id)->where('status', Venda::STATUS_FINALIZADA))
            ->whereHas('formaPagamento', fn ($q) => $q->where('tipo', 'dinheiro'))
            ->sum('valor');
        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'dinheiro_vendas' => $dinheiroVendas,
            'valor_abertura' => $valorAbertura,
            'total_reforcos' => $totalReforcos,
            'total_sangrias' => $totalSangrias,
        ];
    }
}
