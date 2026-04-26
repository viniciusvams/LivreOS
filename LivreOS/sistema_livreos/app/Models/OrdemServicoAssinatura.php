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

class OrdemServicoAssinatura extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_assinaturas';

    protected $fillable = [
        'ordem_servico_id',
        'tipo',
        'user_id',
        'signature_path',
        'signature_points',
        'metadata',
        'checkbox_text',
        'checkbox_checked',
        'checkbox_checked_at',
        'checkbox_checked_ip',
        'session_uuid',
        'signed_ip',
        'signed_user_agent',
        'signed_at',
        'geo',
        'terms_version',
        'privacy_version',
        'legal_basis',
    ];

    protected $casts = [
        'signature_points' => 'array',
        'metadata' => 'array',
        'geo' => 'array',
        'checkbox_checked' => 'boolean',
        'checkbox_checked_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
