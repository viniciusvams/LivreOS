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

class OrdemServicoProduto extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_produtos';

    protected $fillable = [
        'ordem_servico_id',
        'produto_id',
        'produto_variacao_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'desconto_valor',
        'desconto_percentual',
        'tributos',
        'impostos_total',
        'total',
    ];

    protected $casts = [
        'tributos' => 'array',
        'quantidade' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'desconto_valor' => 'decimal:2',
        'desconto_percentual' => 'decimal:2',
        'impostos_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'produto_variacao_id');
    }
}
