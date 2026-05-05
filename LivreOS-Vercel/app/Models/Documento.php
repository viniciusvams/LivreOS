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

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'nome_arquivo',
        'caminho_arquivo',
        'tipo_mime',
        'tamanho',
        'categoria',
        'tags',
        'descricao',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function categoriaRelacao()
    {
        return $this->belongsTo(CategoriaDocumento::class, 'categoria', 'slug');
    }

    public function getTagsArrayAttribute()
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

    public function getTamanhoFormatadoAttribute()
    {
        $bytes = $this->tamanho;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
