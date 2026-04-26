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

namespace Atendimento\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Atendimento extends Model
{
    protected $table = 'plugin_atendimento_atendimentos';

    protected $fillable = [
        'cliente_id',
        'data_hora_atendimento',
        'tempo_estimado_minutos',
        'endereco_tipo',
        'endereco_id',
        'endereco_completo',
        'endereco_cep',
        'endereco_logradouro',
        'endereco_numero',
        'endereco_complemento',
        'endereco_bairro',
        'endereco_cidade',
        'endereco_uf',
        'problema_reportado',
        'diagnostico_tecnico',
        'observacoes_tecnicas',
        'fase',
        'checklist_model_id',
        'checklist_respostas',
        'checklist_campos_adhoc',
        'assinatura_cliente_path',
        'assinatura_data',
        'assinatura_local',
        'status_id',
        'prioridade_id',
        'ordem_servico_id',
        'tecnico_id',
        'created_by',
    ];

    protected $casts = [
        'data_hora_atendimento' => 'datetime',
        'assinatura_data' => 'datetime',
        'tempo_estimado_minutos' => 'integer',
        'checklist_respostas' => 'array',
        'checklist_campos_adhoc' => 'array',
    ];

    public const FASE_RASCUNHO = 'rascunho';
    public const FASE_CONCLUIDO = 'concluido';
    public const FASE_CONVERTIDO_OS = 'convertido_os';

    public const ENDERECO_USAR_CADASTRADO = 'usar_cadastrado';
    public const ENDERECO_CLIENTE = 'endereco_cliente';
    public const ENDERECO_NOVO = 'novo';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Endereco::class, 'endereco_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusAtendimento::class, 'status_id');
    }

    public function prioridade(): BelongsTo
    {
        return $this->belongsTo(PrioridadeAtendimento::class, 'prioridade_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'tecnico_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function fotos()
    {
        return $this->hasMany(AtendimentoFoto::class)->orderBy('ordem')->orderBy('id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\OrdemServico::class, 'ordem_servico_id');
    }

    /** Checklist do sistema principal (quando vinculado) */
    public function checklistModel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChecklistModel::class, 'checklist_model_id');
    }

    /**
     * Endereço para exibição e para Google Maps (texto pesquisável).
     */
    public function getEnderecoParaMapaAttribute(): string
    {
        if ($this->endereco_tipo === self::ENDERECO_CLIENTE && $this->endereco_id && $this->relationLoaded('endereco') && $this->endereco) {
            return $this->endereco->getEnderecoCompletoAttribute();
        }
        if ($this->endereco_tipo === self::ENDERECO_USAR_CADASTRADO && $this->cliente_id) {
            if ($this->relationLoaded('cliente') && $this->cliente && $this->cliente->relationLoaded('enderecos')) {
                $principal = $this->cliente->enderecos->sortByDesc('principal')->first();
                if ($principal) {
                    return $principal->getEnderecoCompletoAttribute();
                }
            }
            $endereco = \App\Models\Endereco::where('cliente_id', $this->cliente_id)->orderByDesc('principal')->first();
            if ($endereco) {
                return $endereco->getEnderecoCompletoAttribute();
            }
        }
        if ($this->endereco_completo) {
            return $this->endereco_completo;
        }
        $parts = array_filter([
            $this->endereco_logradouro,
            $this->endereco_numero,
            $this->endereco_complemento,
            $this->endereco_bairro,
            $this->endereco_cidade,
            $this->endereco_uf,
            $this->endereco_cep,
        ]);
        return implode(', ', $parts) ?: '';
    }

    /**
     * URL do Google Maps para o endereço.
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        $endereco = $this->endereco_para_mapa;
        if ($endereco === '') {
            return null;
        }
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($endereco);
    }
}
