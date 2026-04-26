<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace Compras\Models;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraImportacaoItem extends Model
{
    protected $table = 'plugin_compras_importacao_itens';

    protected $fillable = [
        'compra_importacao_id',
        'indice',
        'c_prod',
        'x_prod',
        'ncm',
        'cfop',
        'cst_icms',
        'ean',
        'q_com',
        'v_un_com',
        'v_prod',
        'impostos',
        'dados_nf',
        'produto_id',
        'atualizar_estoque',
    ];

    protected $casts = [
        'q_com' => 'decimal:6',
        'v_un_com' => 'decimal:10',
        'v_prod' => 'decimal:2',
        'impostos' => 'array',
        'dados_nf' => 'array',
        'atualizar_estoque' => 'boolean',
    ];

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(CompraImportacao::class, 'compra_importacao_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
