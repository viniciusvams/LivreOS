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

class AuditFinanceiroMovimentacao extends Model
{
    use HasFactory;

    protected $table = 'audit_financeiro_movimentacoes';

    protected $fillable = [
        'entidade_tipo',
        'entidade_id',
        'acao',
        'alteracoes',
        'antes',
        'depois',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'alteracoes' => 'array',
        'antes' => 'array',
        'depois' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function entidadeTipoLabel(?string $tipo): string
    {
        return match ($tipo) {
            'conta_receber' => 'Conta a receber',
            'conta_pagar' => 'Conta a pagar',
            default => (string) ($tipo ?: '—'),
        };
    }

    public static function acaoLabel(?string $acao): string
    {
        return match ($acao) {
            'created' => 'Criação',
            'updated' => 'Edição',
            'deleted' => 'Exclusão',
            'restored' => 'Restauração',
            default => (string) ($acao ?: '—'),
        };
    }
}

