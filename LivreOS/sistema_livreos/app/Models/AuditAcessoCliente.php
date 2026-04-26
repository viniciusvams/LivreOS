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

class AuditAcessoCliente extends Model
{
    protected $table = 'audit_acesso_clientes';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'evento',
        'mensagem',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public static function eventoLabel(string $evento): string
    {
        return match ($evento) {
            'acesso_index' => 'Acesso à listagem de clientes',
            'acesso_show' => 'Visualização de cliente',
            'acesso_edit' => 'Acesso à edição de cliente',
            default => $evento,
        };
    }
}
