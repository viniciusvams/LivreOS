<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace Compras\Models;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraProdutoFornecedorMap extends Model
{
    protected $table = 'plugin_compras_produto_fornecedor_maps';

    protected $fillable = [
        'fornecedor_cnpj',
        'codigo_fornecedor',
        'produto_id',
        'created_by',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
