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

class PropostaComercialItem extends Model
{
    protected $table = 'proposta_comercial_itens';

    protected $fillable = [
        'proposta_comercial_id',
        'tipo',
        'produto_id',
        'produto_variacao_id',
        'servico_id',
        'descricao',
        'quantidade',
        'preco_unitario',
        'desconto',
        'total',
        'ordem',
    ];

    protected $casts = [
        'quantidade'     => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'desconto'       => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item) {
            $q = (float) $item->quantidade;
            $sub = (float) $item->preco_unitario * $q;
            $item->total = max(round($sub - (float) $item->desconto, 2), 0);
        });
    }

    public function propostaComercial(): BelongsTo
    {
        return $this->belongsTo(PropostaComercial::class, 'proposta_comercial_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function variacao(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariacao::class, 'produto_variacao_id');
    }
}
