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
use Illuminate\Support\Str;

class OrdemServico extends Model
{
    use HasFactory;

    protected $table = 'ordem_servicos';

    protected $fillable = [
        'tenant_id',
        'numero_sequencial',
        'codigo_interno',
        'cliente_id',
        'cliente_unidade_id',
        'contato_id',
        'endereco_id',
        'tipo_os',
        'origem_os',
        'prioridade',
        'status',
        'data_abertura',
        'prevista_inicio',
        'prevista_conclusao',
        'real_inicio',
        'real_conclusao',
        'sla_valor',
        'sla_unidade',
        'sla_cumprido',
        'relato_cliente',
        'diagnostico_tecnico',
        'servicos_realizados',
        'observacoes_internas',
        'observacoes_cliente',
        'garantia_dias',
        'equipamento_id',
        'equipamento_nome',
        'equipamento_marca',
        'equipamento_modelo',
        'equipamento_numero_serie',
        'equipamento_numero_patrimonio',
        'equipamento_acessorios',
        'equipamento_publico_token',
        'equipamento_unlock_type',
        'equipamento_unlock_code',
        'quilometragem_entrada',
        'quilometragem_saida',
        'horimetro_entrada',
        'horimetro_saida',
        'garantia_tipo',
        'termo_garantia_id',
        'contrato_numero',
        'contrato_tipo',
        'recorrente',
        'recorrencia_periodicidade',
        'coberto_por_contrato',
        'aprovado_nome',
        'aprovado_em',
        'aprovado_canal',
        'aprovado_assinatura',
        'aprovado_cliente',
        'desconto_global',
        'acrescimos_global',
        'impostos_global',
        'total_produtos',
        'total_servicos',
        'total_descontos',
        'total_acrescimos',
        'total_impostos',
        'total_geral',
        'estoque_baixado',
        'financeiro_gerado',
        'created_by',
        'updated_by',
        'ip_criacao',
        'ip_atualizacao',
    ];

    protected $casts = [
        'data_abertura' => 'datetime',
        'prevista_inicio' => 'datetime',
        'prevista_conclusao' => 'datetime',
        'real_inicio' => 'datetime',
        'real_conclusao' => 'datetime',
        'aprovado_em' => 'datetime',
        'recorrente' => 'boolean',
        'aprovado_cliente' => 'boolean',
        'coberto_por_contrato' => 'boolean',
        'sla_cumprido' => 'boolean',
        'desconto_global' => 'decimal:2',
        'acrescimos_global' => 'decimal:2',
        'impostos_global' => 'decimal:2',
        'total_produtos' => 'decimal:2',
        'total_servicos' => 'decimal:2',
        'total_descontos' => 'decimal:2',
        'total_acrescimos' => 'decimal:2',
        'total_impostos' => 'decimal:2',
        'total_geral' => 'decimal:2',
        'estoque_baixado' => 'boolean',
        'financeiro_gerado' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (OrdemServico $os) {
            if (! $os->data_abertura) {
                $os->data_abertura = now();
            }

            if (empty($os->codigo_interno)) {
                $os->gerarCodigoInterno();
            }

            if (empty($os->equipamento_publico_token)) {
                $os->equipamento_publico_token = $os->gerarTokenPublico();
            }
        });
    }

    public function gerarTokenPublico(): string
    {
        do {
            $token = Str::lower(Str::random(12));
        } while (self::where('equipamento_publico_token', $token)->exists());

        return $token;
    }

    public function garantirTokenPublico(): void
    {
        if (empty($this->equipamento_publico_token)) {
            $this->equipamento_publico_token = $this->gerarTokenPublico();
            $this->saveQuietly();
        }
    }

    public function acessosPublico()
    {
        return $this->hasMany(EquipamentoPublicoAcesso::class, 'ordem_servico_id');
    }

    public function gerarCodigoInterno(): void
    {
        $tenantId = $this->tenant_id;
        $ultimoNumero = (int) self::where('tenant_id', $tenantId)->max('numero_sequencial');
        $proximoNatural = $ultimoNumero > 0 ? ($ultimoNumero + 1) : 1;

        // Permite configurar o próximo número de OS sem quebrar a sequência.
        // Se o valor configurado for menor que o natural, prevalece o natural.
        $proximoConfigurado = (int) Configuracao::getValue('ordem_servico_proximo_numero', '0');
        if ($proximoConfigurado < 1) {
            $proximoConfigurado = 0;
        }
        $proximoNumero = max($proximoNatural, $proximoConfigurado ?: 1);
        $this->numero_sequencial = $proximoNumero;

        // Mantém a configuração apontando para o próximo número disponível.
        Configuracao::setValue('ordem_servico_proximo_numero', (string) ($proximoNumero + 1));

        $mascara = null;
        if ($tenantId) {
            $mascara = Tenant::whereKey($tenantId)->value('mascara_os');
        }
        $mascara = $mascara ?: 'OS-{numero}';
        $this->codigo_interno = str_replace(
            '{numero}',
            str_pad((string) $proximoNumero, 6, '0', STR_PAD_LEFT),
            $mascara
        );
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function unidade()
    {
        return $this->belongsTo(Cliente::class, 'cliente_unidade_id');
    }

    public function contato()
    {
        return $this->belongsTo(Contato::class);
    }

    public function endereco()
    {
        return $this->belongsTo(Endereco::class);
    }

    public function equipamento()
    {
        return $this->belongsTo(Equipamento::class, 'equipamento_id');
    }

    public function produtos()
    {
        return $this->hasMany(OrdemServicoProduto::class);
    }

    public function servicos()
    {
        return $this->hasMany(OrdemServicoServico::class);
    }

    public function anexos()
    {
        return $this->hasMany(OrdemServicoAnexo::class);
    }

    public function tecnicos()
    {
        return $this->hasMany(OrdemServicoTecnico::class);
    }

    public function apontamentos()
    {
        return $this->hasMany(OrdemServicoApontamento::class);
    }

    public function logs()
    {
        return $this->hasMany(OrdemServicoLog::class);
    }

    public function assinaturas()
    {
        return $this->hasMany(OrdemServicoAssinatura::class);
    }

    public function assinaturaCliente()
    {
        return $this->hasOne(OrdemServicoAssinatura::class)->where('tipo', 'cliente');
    }

    public function assinaturaTecnico()
    {
        return $this->hasOne(OrdemServicoAssinatura::class)->where('tipo', 'tecnico');
    }

    public function documentos()
    {
        return $this->hasMany(OrdemServicoDocumento::class);
    }

    public function documentoAtivo()
    {
        return $this->hasOne(OrdemServicoDocumento::class)->where('ativo', true);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'ordem_servico_tag');
    }

    public function termoGarantia()
    {
        return $this->belongsTo(TermoGarantia::class);
    }

    public function checklists()
    {
        return $this->hasMany(ChecklistAnswer::class, 'ordem_servico_id');
    }

    public function contasReceber()
    {
        return $this->hasMany(ContaReceber::class);
    }

    /** Contas a receber que são adiantamentos (campo de controle origem_tipo) */
    public function adiantamentos()
    {
        return $this->hasMany(ContaReceber::class)->where('origem_tipo', 'adiantamento_os');
    }

    /** Adiantamentos ativos (não cancelados) para exibir na tela e validar limite */
    public function adiantamentosAtivos()
    {
        return $this->adiantamentos()->whereNotIn('status', ['cancelado']);
    }

    /** Adiantamentos já confirmados (status pago) — considerados como recebidos no encerramento da OS */
    public function adiantamentosPagos()
    {
        return $this->adiantamentos()->where('status', 'pago');
    }

    /** Soma dos valores já recebidos como adiantamento nesta OS (exclui cancelados/estornados) */
    public function getTotalAdiantadoAttribute(): float
    {
        return (float) $this->adiantamentosAtivos()->sum('valor');
    }

    /** Soma dos adiantamentos confirmados (pago) — usado no encerramento para calcular valor a receber */
    public function getTotalAdiantadoConfirmadoAttribute(): float
    {
        return (float) $this->adiantamentosPagos()->sum('valor');
    }

}
