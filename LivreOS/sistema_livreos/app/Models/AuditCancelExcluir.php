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

/**
 * Registro de auditoria de cancelamentos e exclusões.
 * Usado para exigir motivo, verificar permissão e permitir que o admin veja o histórico.
 */
class AuditCancelExcluir extends Model
{
    protected $table = 'audit_cancelar_excluir';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_descricao',
        'motivo',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rótulo da ação para exibição */
    public function getActionLabelAttribute(): string
    {
        return $this->action === 'cancelar' ? 'Cancelamento' : 'Exclusão';
    }

    /** Rótulo do tipo de entidade para exibição */
    public static function entityTypeLabel(string $type): string
    {
        return match ($type) {
            'conta_receber' => 'Conta a receber',
            'conta_pagar' => 'Conta a pagar',
            'ordem_servico' => 'Ordem de serviço',
            'cliente' => 'Cliente',
            'fornecedor' => 'Fornecedor',
            'pagamento_recorrente' => 'Pagamento recorrente',
            'conta_pagar_recorrente' => 'Pagamento fixo (conta a pagar)',
            'produto' => 'Produto',
            'servico' => 'Serviço',
            'categoria_produto' => 'Categoria de produto',
            'categoria_servico' => 'Categoria de serviço',
            'usuario' => 'Usuário',
            'role' => 'Role',
            'permission' => 'Permissão',
            'outro' => 'Outro',
            default => $type,
        };
    }
}
