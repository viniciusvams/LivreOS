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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Servico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'servicos';

    protected $fillable = [
        'codigo_interno',
        'nome',
        'preco',
        'unidade',
        'codigo_sku',
        'descricao',
        'categoria_id',
        'informacoes_fiscais',
        'campos_customizados',
        'valor_custo',
        'valor_comissao',
        'tipo_comissao',
        'prazos',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'valor_custo' => 'decimal:2',
        'valor_comissao' => 'decimal:2',
        'informacoes_fiscais' => 'array',
        'campos_customizados' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Servico $servico) {
            if (!$servico->codigo_interno) {
                $servico->codigo_interno = self::gerarCodigoInterno();
            }
        });
        static::retrieved(function (self $model): void {
            foreach (['preco', 'valor_custo', 'valor_comissao'] as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
        });
    }

    public static function gerarCodigoInterno(): string
    {
        do {
            $codigo = 'SRV-' . strtoupper(Str::random(6));
        } while (self::where('codigo_interno', $codigo)->exists());

        return $codigo;
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }

    public function imagens()
    {
        return $this->hasMany(ServicoImagem::class);
    }
}
