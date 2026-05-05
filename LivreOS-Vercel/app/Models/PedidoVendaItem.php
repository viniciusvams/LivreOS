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

class PedidoVendaItem extends Model
{
    protected $table = 'pedido_venda_itens';

    protected $fillable = [
        'pedido_venda_id',
        'tipo',
        'produto_id',
        'produto_variacao_id',
        'servico_id',
        'descricao',
        'quantidade',
        'quantidade_cancelada',
        'preco_unitario',
        'desconto',
        'desconto_inicial',
        'total',
        'serial',
        'gerar_os',
        'os_gerada_id',
        'observacao',
        'ordem',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'quantidade_cancelada' => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'desconto' => 'decimal:2',
        'desconto_inicial' => 'decimal:2',
        'total' => 'decimal:2',
        'gerar_os' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item) {
            $q = (float) $item->quantidade;
            $qc = (float) ($item->quantidade_cancelada ?? 0);
            $qEff = max(0, $q - $qc);
            $sub = (float) $item->preco_unitario * $qEff;
            $di = $item->desconto_inicial !== null && $item->desconto_inicial !== ''
                ? (float) $item->desconto_inicial
                : (float) $item->desconto;
            if ($q > 0) {
                $item->desconto = round($di * ($qEff / $q), 2);
            } else {
                $item->desconto = 0;
            }
            if ($item->desconto_inicial === null || $item->desconto_inicial === '') {
                $item->desconto_inicial = $di;
            }
            $item->total = max(round($sub - (float) $item->desconto, 2), 0);
        });
    }

    public function pedidoVenda(): BelongsTo
    {
        return $this->belongsTo(PedidoVenda::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function variacao(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariacao::class, 'produto_variacao_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function osGerada(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'os_gerada_id');
    }
}
