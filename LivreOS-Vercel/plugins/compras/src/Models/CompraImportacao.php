<?php

/**
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

namespace Compras\Models;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraImportacao extends Model
{
    protected $table = 'plugin_compras_importacoes';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'status',
        'tipo_fonte',
        'arquivo_original_path',
        'chave_acesso',
        'numero',
        'serie',
        'data_emissao',
        'fornecedor_cnpj',
        'fornecedor_nome',
        'fornecedor_id',
        'totais',
        'emitente',
        'destinatario',
        'cobranca',
        'sefaz_resultado',
        'parse_warnings',
        'erro_mensagem',
        'confirmed_at',
        'undo_snapshot',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'totais' => 'array',
        'emitente' => 'array',
        'destinatario' => 'array',
        'cobranca' => 'array',
        'sefaz_resultado' => 'array',
        'parse_warnings' => 'array',
        'confirmed_at' => 'datetime',
        'undo_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'fornecedor_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(CompraImportacaoItem::class, 'compra_importacao_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(CompraAuditLog::class, 'compra_importacao_id');
    }
}
