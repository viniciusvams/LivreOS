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

class Contato extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'nome',
        'telefone',
        'telefone_whatsapp',
        'telefone2',
        'email',
        'email2',
        'cargo',
        'principal',
        'cobranca',
        'is_fornecedor',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'cobranca' => 'boolean',
        'is_fornecedor' => 'boolean',
        'telefone_whatsapp' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function produtoFornecedores()
    {
        return $this->hasMany(ProdutoFornecedor::class, 'fornecedor_id');
    }
}
