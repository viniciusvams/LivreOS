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

class FormaPagamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'formas_pagamento';

    protected $fillable = [
        'nome',
        'tipo',
        'taxa_percentual',
        'taxa_fixa',
        'dias_recebimento',
        'ativo',
        'permite_parcela',
        'max_parcelas',
        'conta_bancaria_id',
        'pix_chaves',
        'observacoes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'taxa_percentual' => 'decimal:2',
        'taxa_fixa' => 'decimal:2',
        'dias_recebimento' => 'integer',
        'ativo' => 'boolean',
        'permite_parcela' => 'boolean',
        'max_parcelas' => 'integer',
        'pix_chaves' => 'array',
    ];

    public function getPixChavesAtivasAttribute(): array
    {
        $chaves = is_array($this->pix_chaves) ? $this->pix_chaves : [];
        return array_values(array_filter($chaves, static function ($item) {
            if (!is_array($item)) {
                return false;
            }
            if (array_key_exists('ativo', $item) && !$item['ativo']) {
                return false;
            }
            return !empty(trim((string) ($item['chave'] ?? '')));
        }));
    }

    public function encontrarPixChavePorId($pixChaveId): ?array
    {
        $id = (string) $pixChaveId;
        if ($id === '') {
            return null;
        }
        foreach ($this->pix_chaves_ativas as $item) {
            if ((string) ($item['id'] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contasReceber()
    {
        return $this->hasMany(ContaReceber::class);
    }

    public function contasPagar()
    {
        return $this->hasMany(ContaPagar::class);
    }

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class);
    }

    /**
     * PIX, dinheiro e cartões são tratados como recebimento imediato (não consomem "limite de crédito" a prazo).
     * Boleto, transferência (TED), cheque e demais tipos exigem controle de limite cadastrado no cliente.
     */
    public static function tipoEhRecebimentoImediato(?string $tipo): bool
    {
        if ($tipo === null || $tipo === '') {
            return false;
        }

        return in_array(strtolower(trim($tipo)), ['pix', 'dinheiro', 'cartao_credito', 'cartao_debito'], true);
    }

    public function adquirentes()
    {
        return $this->belongsToMany(Adquirente::class, 'forma_pagamento_adquirente')
            ->withPivot('padrao', 'ordem')
            ->withTimestamps()
            ->orderByPivot('ordem');
    }

    public function adquirentePadrao()
    {
        return $this->adquirentes()->wherePivot('padrao', true)->first();
    }

    /**
     * Adquirente informado na linha de pagamento ou o marcado como padrão na associação forma ↔ adquirente.
     */
    public function resolverAdquirenteId(?int $fromPagamento): int
    {
        $fromPagamento = (int) ($fromPagamento ?? 0);
        if ($fromPagamento > 0) {
            return $fromPagamento;
        }

        return (int) ($this->adquirentePadrao()?->id ?? 0);
    }

    /**
     * Conta onde o recebimento deve cair.
     *
     * Com adquirente efetivo (cartão, etc.): **conta padrão do adquirente** tem prioridade sobre a conta
     * genérica da forma — o modal/PDV costuma repetir `forma.conta_bancaria_id` em toda linha e isso não deve
     * substituir a conta cadastrada no adquirente. Sem conta no adquirente: usa explícita, depois da forma.
     *
     * @param  int|null  $contaExplicita  conta_bancaria_id escolhida no pagamento (PIX, dinheiro, override)
     */
    public function resolverContaBancariaIdParaRecebimento(?int $contaExplicita, int $adquirenteIdEfetivo): ?int
    {
        $contaAdquirente = null;
        if ($adquirenteIdEfetivo > 0) {
            $contaAdquirente = Adquirente::find($adquirenteIdEfetivo)?->contaPadrao();
        }
        if ($contaAdquirente) {
            return $contaAdquirente->id;
        }
        if ($contaExplicita) {
            return $contaExplicita;
        }

        return $this->conta_bancaria_id;
    }

    public function calcularTaxa($valor)
    {
        $taxa = ($valor * $this->taxa_percentual / 100) + $this->taxa_fixa;
        return round($taxa, 2);
    }
}
