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

class Produto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'codigo_sku',
        'preco_venda',
        'unidade',
        'formato',
        'condicao',
        'linha_produto',
        'categoria_id',
        'marca',
        'producao',
        'data_validade',
        'frete_gratis',
        'peso_liquido',
        'peso_bruto',
        'largura',
        'altura',
        'profundidade',
        'volumes',
        'itens_por_caixa',
        'ean',
        'ean_tributario',
        'descricao_curta',
        'descricao_complementar',
        'link_externo',
        'video_url',
        'observacoes',
        'tags',
        'estoque_minimo',
        'estoque_maximo',
        'estoque_crossdocking',
        'estoque_localizacao',
        'estoque_deposito_id',
        'estoque_quantidade',
        'estoque_preco_compra_unitario',
        'estoque_preco_custo_unitario',
        'estoque_observacao',
        'tributacao_origem',
        'tributacao_ncm',
        'tributacao_cest',
        'tributacao_tipo_item',
        'tributacao_percentual_tributos',
        'tributacao_grupo_produtos',
        'tributacao_info_adicionais',
        'tributacao_icms',
        'tributacao_pis_cofins',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'frete_gratis' => 'boolean',
        'data_validade' => 'date',
        'peso_liquido' => 'decimal:4',
        'peso_bruto' => 'decimal:4',
        'largura' => 'decimal:4',
        'altura' => 'decimal:4',
        'profundidade' => 'decimal:4',
        'estoque_quantidade' => 'decimal:6',
        'estoque_preco_compra_unitario' => 'decimal:2',
        'estoque_preco_custo_unitario' => 'decimal:2',
        'tributacao_percentual_tributos' => 'decimal:2',
        'tags' => 'array',
        'tributacao_icms' => 'array',
        'tributacao_pis_cofins' => 'array',
    ];

    /**
     * Normaliza atributos que podem vir como string vazia do banco (ex.: após importação)
     * para evitar MathException ao fazer cast para decimal/boolean/date.
     */
    protected static function booted(): void
    {
        static::retrieved(function (self $model): void {
            $decimalKeys = [
                'peso_liquido', 'peso_bruto', 'largura', 'altura', 'profundidade',
                'estoque_quantidade', 'estoque_preco_compra_unitario', 'estoque_preco_custo_unitario',
                'tributacao_percentual_tributos',
            ];
            $boolKeys = ['frete_gratis'];
            $dateKeys = ['data_validade'];
            foreach (array_merge($decimalKeys, $dateKeys) as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
            foreach ($boolKeys as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
        });
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProduto::class, 'categoria_id');
    }

    public function deposito()
    {
        return $this->belongsTo(Deposito::class, 'estoque_deposito_id');
    }

    public function imagens()
    {
        return $this->hasMany(ProdutoImagem::class);
    }

    public function fornecedores()
    {
        return $this->hasMany(ProdutoFornecedor::class);
    }

    public function variacaoAtributos()
    {
        return $this->hasMany(ProdutoVariacaoAtributo::class);
    }

    public function variacoes()
    {
        return $this->hasMany(ProdutoVariacao::class);
    }

    public function composicoes()
    {
        return $this->hasMany(ProdutoComposicao::class);
    }

    public function tabelasPrecos()
    {
        return $this->belongsToMany(TabelaPreco::class, 'produto_tabela_preco')
            ->withPivot(['preco'])
            ->withTimestamps();
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atualizadoPor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
