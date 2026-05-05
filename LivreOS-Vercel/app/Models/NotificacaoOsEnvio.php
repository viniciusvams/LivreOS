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

class NotificacaoOsEnvio extends Model
{
    protected $table = 'notificacoes_os_envios';

    protected $fillable = [
        'tipo',
        'ordem_servico_id',
        'destinatario',
        'assunto',
        'conteudo',
        'status',
        'erro',
        'enviado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ENVIADO = 'enviado';
    public const STATUS_FALHA = 'falha';

    public const TIPO_EMAIL = 'email';
    public const TIPO_WHATSAPP = 'whatsapp';

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function isPendente(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isEnviado(): bool
    {
        return $this->status === self::STATUS_ENVIADO;
    }
}
