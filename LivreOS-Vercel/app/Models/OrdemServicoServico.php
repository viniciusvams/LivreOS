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
use App\Models\User;

class OrdemServicoServico extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_servicos';

    protected $fillable = [
        'ordem_servico_id',
        'servico_id',
        'descricao',
        'cobranca_tipo',
        'quantidade',
        'quantidade_horas',
        'tecnico_id',
        'valor_unitario',
        'desconto_valor',
        'desconto_percentual',
        'total',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'desconto_valor' => 'decimal:2',
        'desconto_percentual' => 'decimal:2',
        'total' => 'decimal:2',
        'quantidade' => 'decimal:3',
        'quantidade_horas' => 'decimal:3',
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }
}
