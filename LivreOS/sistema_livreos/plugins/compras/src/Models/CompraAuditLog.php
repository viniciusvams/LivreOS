<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace Compras\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraAuditLog extends Model
{
    protected $table = 'plugin_compras_audit_logs';

    public $timestamps = true;

    protected $fillable = [
        'compra_importacao_id',
        'user_id',
        'acao',
        'dados',
    ];

    protected $casts = [
        'dados' => 'array',
    ];

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(CompraImportacao::class, 'compra_importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
