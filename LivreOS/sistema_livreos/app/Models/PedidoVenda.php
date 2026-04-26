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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PedidoVenda extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pedidos_venda';

    protected $fillable = [
        'numero_sequencial',
        'cliente_id',
        'cliente_unidade_id',
        'contato_id',
        'endereco_id',
        'vendedor_id',
        'tabela_preco_id',
        'status',
        'canal_venda',
        'observacoes',
        'observacoes_internas',
        'motivo_cancelamento',
        'data_pedido',
        'data_prevista_entrega',
        'validade_orcamento_dias',
        'data_confirmacao',
        'data_faturamento',
        'data_entrega',
        'data_cancelamento',
        'endereco_entrega_id',
        'desconto_global',
        'acrescimos_global',
        'total_produtos',
        'total_servicos',
        'total_descontos',
        'total_geral',
        'estoque_reservado',
        'estoque_baixado',
        'financeiro_gerado',
        'origem_pedido_id',
        'origem_tipo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_pedido'           => 'date',
        'data_prevista_entrega' => 'date',
        'data_confirmacao'      => 'datetime',
        'data_faturamento'      => 'datetime',
        'data_entrega'          => 'datetime',
        'data_cancelamento'     => 'datetime',
        'validade_orcamento_dias' => 'integer',
        'total_produtos'        => 'decimal:2',
        'total_servicos'        => 'decimal:2',
        'total_descontos'       => 'decimal:2',
        'total_geral'           => 'decimal:2',
        'desconto_global'       => 'decimal:2',
        'acrescimos_global'     => 'decimal:2',
        'estoque_reservado'     => 'boolean',
        'estoque_baixado'       => 'boolean',
        'financeiro_gerado'     => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->numero_sequencial)) {
                $model->numero_sequencial = static::gerarNumeroSequencial();
            }
            if (empty($model->data_pedido)) {
                $model->data_pedido = now()->toDateString();
            }
            if (empty($model->status)) {
                $model->status = 'rascunho';
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

    /**
     * Maior sufixo numérico encontrado no final de qualquer numero_sequencial (inclui excluídos).
     * Usado para sequência contínua ao mudar máscara ou após importação.
     */
    public static function maiorIndiceNumericoNumeroSequencial(): int
    {
        $max = 0;
        foreach (static::withTrashed()->pluck('numero_sequencial') as $ns) {
            if (preg_match('/(\d+)$/', (string) $ns, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }

    public static function proximoNumeroNaturalPorPedidos(): int
    {
        return static::maiorIndiceNumericoNumeroSequencial() + 1;
    }

    /**
     * Gera código único conforme máscara e configuração (similar à numeração de OS).
     *
     * @throws \RuntimeException
     */
    public static function gerarNumeroSequencial(): string
    {
        $mascara = (string) Configuracao::getValue('pedidos_venda.mascara_numero', 'PV{numero}');
        if (! str_contains($mascara, '{numero}')) {
            $mascara = 'PV{numero}';
        }

        $pad = (int) Configuracao::getValue('pedidos_venda.numero_pad', '6');
        $pad = max(1, min(12, $pad));

        $proximoNatural = static::proximoNumeroNaturalPorPedidos();
        $proximoConfigurado = (int) Configuracao::getValue('pedidos_venda.proximo_numero', '0');
        if ($proximoConfigurado < 1) {
            $proximoConfigurado = 0;
        }
        $proximoNumero = max($proximoNatural, $proximoConfigurado ?: 1);

        for ($i = 0; $i < 500; $i++) {
            // str_pad só preenche se o valor tiver menos dígitos que $pad; acima disso mantém o número integral.
            $numeroFmt = str_pad((string) $proximoNumero, $pad, '0', STR_PAD_LEFT);
            $codigo = str_replace('{numero}', $numeroFmt, $mascara);
            if (strlen($codigo) > 30) {
                throw new \RuntimeException(
                    'A máscara e o preenchimento geram número com mais de 30 caracteres. Ajuste a máscara ou o padding em Configurações → Pedidos de venda.'
                );
            }
            if (! static::withTrashed()->where('numero_sequencial', $codigo)->exists()) {
                Configuracao::setValue('pedidos_venda.proximo_numero', (string) ($proximoNumero + 1));

                return $codigo;
            }
            $proximoNumero++;
        }

        throw new \RuntimeException('Não foi possível gerar um número único para o pedido de venda.');
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
        return $this->belongsTo(\App\Models\TabelaPreco::class, 'tabela_preco_id');
    }

    public function enderecoEntrega(): BelongsTo
    {
        return $this->belongsTo(Endereco::class, 'endereco_entrega_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PedidoVendaItem::class)->orderBy('ordem')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PedidoVendaLog::class)->orderByDesc('created_at');
    }

    public function contasReceber(): HasMany
    {
        return $this->hasMany(ContaReceber::class, 'entidade_id')
            ->where('entidade_tipo', 'pedido_venda');
    }

    public function origemPedido(): BelongsTo
    {
        return $this->belongsTo(static::class, 'origem_pedido_id');
    }

    public function recalcularTotais(): void
    {
        $totalProdutos = 0;
        $totalServicos = 0;
        $totalDescontos = 0;

        foreach ($this->itens as $item) {
            $q = (float) $item->quantidade;
            $qc = (float) ($item->quantidade_cancelada ?? 0);
            $qEff = max(0, $q - $qc);
            $sub = (float) $item->preco_unitario * $qEff;
            $desc = (float) $item->desconto;
            if ($item->tipo === 'servico') {
                $totalServicos += $sub;
            } else {
                $totalProdutos += $sub;
            }
            $totalDescontos += $desc;
        }

        $totalDescontos += (float) $this->desconto_global;
        $base = $totalProdutos + $totalServicos;
        $totalGeral = $base + (float) $this->acrescimos_global - $totalDescontos;

        $this->total_produtos  = round($totalProdutos, 2);
        $this->total_servicos  = round($totalServicos, 2);
        $this->total_descontos = round($totalDescontos, 2);
        $this->total_geral     = round(max($totalGeral, 0), 2);
    }

    // ---------- Helpers de status ----------

    public function isRascunho(): bool  { return $this->status === 'rascunho'; }
    public function isConfirmado(): bool { return $this->status === 'confirmado'; }
    public function isFaturado(): bool   { return $this->status === 'faturado'; }
    public function isEntregue(): bool   { return $this->status === 'entregue'; }
    public function isCancelado(): bool  { return $this->status === 'cancelado'; }

    public function podeEditar(): bool
    {
        return in_array($this->status, ['rascunho', 'confirmado'], true);
    }

    public function podeConfirmar(): bool  { return $this->status === 'rascunho'; }
    public function podeFaturar(): bool    { return in_array($this->status, ['rascunho', 'confirmado'], true); }
    public function podeEntregar(): bool   { return $this->status === 'faturado'; }
    public function isPosFaturamento(): bool
    {
        return in_array($this->status, ['faturado', 'entregue'], true);
    }

    public function podeCancelar(): bool
    {
        if ($this->status === 'cancelado') {
            return false;
        }

        return in_array($this->status, ['rascunho', 'confirmado', 'faturado', 'entregue'], true);
    }

    /**
     * Dias de validade da proposta: valor do pedido, se informado; senão o padrão em configuracoes.
     */
    public function validadeOrcamentoDiasEfetiva(): ?int
    {
        $local = $this->validade_orcamento_dias;
        if ($local !== null && (int) $local > 0) {
            return (int) $local;
        }
        $cfg = (int) Configuracao::getValue('pedidos_venda.validade_orcamento_dias', '');

        return $cfg > 0 ? $cfg : null;
    }

    /**
     * Data limite de referência (data do pedido + dias efetivos), ou null.
     */
    public function dataValidadeOrcamentoReferencia(): ?Carbon
    {
        $dias = $this->validadeOrcamentoDiasEfetiva();
        if ($dias === null || ! $this->data_pedido) {
            return null;
        }

        return $this->data_pedido->copy()->addDays($dias);
    }

    /** Cancelamento com estorno (pedido já faturado ou entregue). */
    public function podeCancelarPosFaturamento(): bool
    {
        return $this->podeCancelar() && $this->isPosFaturamento();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'rascunho'   => 'gray',
            'confirmado' => 'sky',
            'faturado'   => 'amber',
            'entregue'   => 'green',
            'cancelado'  => 'red',
            default      => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'rascunho'   => 'Rascunho',
            'confirmado' => 'Confirmado',
            'faturado'   => 'Faturado',
            'entregue'   => 'Entregue',
            'cancelado'  => 'Cancelado',
            default      => ucfirst($this->status),
        };
    }
}
