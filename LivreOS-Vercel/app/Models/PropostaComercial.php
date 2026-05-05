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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropostaComercial extends Model
{
    protected $table = 'propostas_comerciais';

    public const STATUS_RASCUNHO = 'rascunho';

    public const STATUS_ENVIADA = 'enviada';

    public const STATUS_APROVADA = 'aprovada';

    public const STATUS_EXPIRADA = 'expirada';

    public const STATUS_CONVERTIDA = 'convertida';

    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'numero_sequencial',
        'grupo_uuid',
        'versao',
        'is_versao_atual',
        'cliente_id',
        'cliente_unidade_id',
        'contato_id',
        'endereco_id',
        'vendedor_id',
        'tabela_preco_id',
        'status',
        'titulo',
        'descricao',
        'observacoes',
        'observacoes_internas',
        'data_emissao',
        'validade_dias',
        'validade_em',
        'pedido_venda_id',
        'ordem_servico_id',
        'total_produtos',
        'total_servicos',
        'total_descontos',
        'total_geral',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'validade_em' => 'date',
        'is_versao_atual' => 'boolean',
        'versao' => 'integer',
        'validade_dias' => 'integer',
        'total_produtos' => 'decimal:2',
        'total_servicos' => 'decimal:2',
        'total_descontos' => 'decimal:2',
        'total_geral' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->numero_sequencial) && (int) $model->versao === 1) {
                $model->numero_sequencial = static::gerarNumeroSequencial();
            }
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_unidade_id');
    }

    public function contato(): BelongsTo
    {
        return $this->belongsTo(Contato::class, 'contato_id');
    }

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class, 'endereco_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function tabelaPreco(): BelongsTo
    {
        return $this->belongsTo(TabelaPreco::class, 'tabela_preco_id');
    }

    public function pedidoVenda(): BelongsTo
    {
        return $this->belongsTo(PedidoVenda::class, 'pedido_venda_id');
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PropostaComercialItem::class)->orderBy('ordem')->orderBy('id');
    }

    public function versoesDoGrupo(): HasMany
    {
        return $this->hasMany(self::class, 'grupo_uuid', 'grupo_uuid')->orderBy('versao');
    }

    public function recalcularTotais(): void
    {
        $totalProdutos = 0;
        $totalServicos = 0;
        $totalDescontos = 0;

        foreach ($this->itens as $item) {
            $q = (float) $item->quantidade;
            $sub = (float) $item->preco_unitario * $q;
            $desc = (float) $item->desconto;
            if ($item->tipo === 'servico') {
                $totalServicos += $sub;
            } else {
                $totalProdutos += $sub;
            }
            $totalDescontos += $desc;
        }

        $base = $totalProdutos + $totalServicos;
        $totalGeral = $base - $totalDescontos;

        $this->total_produtos = round($totalProdutos, 2);
        $this->total_servicos = round($totalServicos, 2);
        $this->total_descontos = round($totalDescontos, 2);
        $this->total_geral = round(max($totalGeral, 0), 2);
    }

    /**
     * Maior sufixo numérico no final de qualquer numero_sequencial (todas as linhas/versões).
     * Usado para sequência contínua ao mudar máscara.
     */
    public static function maiorIndiceNumericoNumeroSequencial(): int
    {
        $max = 0;
        foreach (static::query()->whereNotNull('numero_sequencial')->pluck('numero_sequencial') as $ns) {
            if (preg_match('/(\d+)$/', (string) $ns, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }

    public static function proximoNumeroNaturalPorPropostas(): int
    {
        return static::maiorIndiceNumericoNumeroSequencial() + 1;
    }

    /**
     * Gera código do grupo; todas as versões do mesmo grupo devem reutilizar o valor (replicate).
     *
     * @throws \RuntimeException
     */
    public static function gerarNumeroSequencial(): string
    {
        $mascara = (string) Configuracao::getValue('propostas_comerciais.mascara_numero', 'PC{numero}');
        if (! str_contains($mascara, '{numero}')) {
            $mascara = 'PC{numero}';
        }

        $pad = (int) Configuracao::getValue('propostas_comerciais.numero_pad', '6');
        $pad = max(1, min(12, $pad));

        $proximoNatural = static::proximoNumeroNaturalPorPropostas();
        $proximoConfigurado = (int) Configuracao::getValue('propostas_comerciais.proximo_numero', '0');
        if ($proximoConfigurado < 1) {
            $proximoConfigurado = 0;
        }
        $proximoNumero = max($proximoNatural, $proximoConfigurado ?: 1);

        for ($i = 0; $i < 500; $i++) {
            $numeroFmt = str_pad((string) $proximoNumero, $pad, '0', STR_PAD_LEFT);
            $codigo = str_replace('{numero}', $numeroFmt, $mascara);
            if (strlen($codigo) > 30) {
                throw new \RuntimeException(
                    'A máscara e o preenchimento geram número com mais de 30 caracteres. Ajuste a máscara ou o padding em Configurações → Propostas comerciais.'
                );
            }
            if (! static::query()->where('numero_sequencial', $codigo)->exists()) {
                Configuracao::setValue('propostas_comerciais.proximo_numero', (string) ($proximoNumero + 1));

                return $codigo;
            }
            $proximoNumero++;
        }

        throw new \RuntimeException('Não foi possível gerar um número único para a proposta comercial.');
    }

    public function referenciaGrupo(): string
    {
        if ($this->numero_sequencial) {
            return $this->numero_sequencial.' · v'.$this->versao;
        }

        $short = substr((string) $this->grupo_uuid, 0, 8);

        return strtoupper($short).' · v'.$this->versao;
    }

    public function podeEditar(): bool
    {
        return $this->status === self::STATUS_RASCUNHO && $this->is_versao_atual;
    }

    public function podeExcluir(): bool
    {
        return $this->status === self::STATUS_RASCUNHO && $this->is_versao_atual;
    }

    public function podeNovaVersao(): bool
    {
        return $this->is_versao_atual
            && ! in_array($this->status, [self::STATUS_CONVERTIDA, self::STATUS_CANCELADA], true);
    }

    public function podeConverterParaPedido(): bool
    {
        return $this->is_versao_atual
            && $this->pedido_venda_id === null
            && in_array($this->status, [
                self::STATUS_RASCUNHO,
                self::STATUS_ENVIADA,
                self::STATUS_APROVADA,
                self::STATUS_EXPIRADA,
            ], true)
            && $this->itens()->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RASCUNHO => 'Rascunho',
            self::STATUS_ENVIADA => 'Enviada',
            self::STATUS_APROVADA => 'Aprovada',
            self::STATUS_EXPIRADA => 'Expirada',
            self::STATUS_CONVERTIDA => 'Convertida',
            self::STATUS_CANCELADA => 'Cancelada',
            default => ucfirst($this->status),
        };
    }
}
