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

class Endereco extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'pais',
        'inscricao_produtor_rural',
        'principal',
        'cobranca',
        'entrega',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'cobranca' => 'boolean',
        'entrega' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getEnderecoCompletoAttribute()
    {
        $endereco = "{$this->logradouro}, {$this->numero}";
        if ($this->complemento) {
            $endereco .= " - {$this->complemento}";
        }
        $endereco .= " - {$this->bairro} - {$this->cidade}/{$this->estado} - CEP: {$this->cep}";
        return $endereco;
    }
}
