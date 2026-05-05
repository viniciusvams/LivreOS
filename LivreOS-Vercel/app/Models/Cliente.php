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
use App\Models\User;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_pessoa',
        'pais_origem',
        'nome',
        'razao_social',
        'natureza_juridica',
        'porte_empresa',
        'capital_social',
        'data_inicio_atividade',
        'ramo_atividade',
        'classificacao_rating',
        'produtor_rural',
        'condominio',
        'vendedor_id',
        'limite_credito',
        'bloqueado_vendas',
        'rg',
        'cpf',
        'data_nascimento',
        'sexo',
        'cnpj',
        'inscricao_estadual',
        'uf_ie',
        'inscricao_municipal',
        'optante_simples_nacional',
        'ignorar_im_nfse',
        'documento_estrangeiro',
        'sla_config',
        'dias_antecedencia_faturamento',
        'dia_faturamento',
        'emissao_nfse',
        'observacoes',
        'empresas_vinculadas',
        'grupo_economico_id',
        'tipo_unidade',
        'matriz_id',
        'bloqueado_alteracao_tipo',
    ];

    public function getFillable(): array
    {
        return function_exists('apply_filters') ? apply_filters('cliente.fillable', $this->fillable) : $this->fillable;
    }

    protected $casts = [
        'produtor_rural' => 'boolean',
        'condominio' => 'boolean',
        'optante_simples_nacional' => 'boolean',
        'ignorar_im_nfse' => 'boolean',
        'bloqueado_alteracao_tipo' => 'boolean',
        'data_nascimento' => 'date',
        'data_inicio_atividade' => 'date',
        'sla_config' => 'array',
        'empresas_vinculadas' => 'array',
        'limite_credito' => 'decimal:2',
        'bloqueado_vendas' => 'boolean',
        'grupo_economico_id' => 'integer',
        'matriz_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::retrieved(function (self $model): void {
            $keys = [
                'limite_credito',
                'data_nascimento', 'data_inicio_atividade',
                'grupo_economico_id', 'matriz_id',
                'produtor_rural', 'condominio', 'optante_simples_nacional',
                'ignorar_im_nfse', 'bloqueado_alteracao_tipo', 'bloqueado_vendas',
            ];
            foreach ($keys as $key) {
                if (isset($model->attributes[$key]) && $model->attributes[$key] === '') {
                    $model->attributes[$key] = null;
                }
            }
        });
    }

    public function getCasts(): array
    {
        $default = parent::getCasts();
        return function_exists('apply_filters') ? apply_filters('cliente.casts', $default) : $default;
    }

    // Relacionamentos
    public function enderecos()
    {
        return $this->hasMany(Endereco::class);
    }

    public function contatos()
    {
        return $this->hasMany(Contato::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function socios()
    {
        return $this->hasMany(Socio::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function grupoEconomico()
    {
        return $this->belongsTo(GrupoEconomico::class, 'grupo_economico_id');
    }

    public function matriz()
    {
        return $this->belongsTo(self::class, 'matriz_id');
    }

    public function filiais()
    {
        return $this->hasMany(self::class, 'matriz_id');
    }

    public function equipamentos()
    {
        return $this->hasMany(Equipamento::class);
    }

    public function ordensServico()
    {
        return $this->hasMany(OrdemServico::class)->orderByDesc('created_at');
    }

    // Acessors
    public function getTipoPessoaTextoAttribute()
    {
        return match($this->tipo_pessoa) {
            'F' => 'Pessoa Física',
            'J' => 'Pessoa Jurídica',
            'E' => 'Estrangeira',
            default => 'Não informado',
        };
    }

    public function getDocumentoPrincipalAttribute()
    {
        return match($this->tipo_pessoa) {
            'F' => $this->cpf,
            'J' => $this->cnpj,
            'E' => $this->documento_estrangeiro,
            default => null,
        };
    }

    // Scopes
    public function scopePessoaFisica($query)
    {
        return $query->where('tipo_pessoa', 'F');
    }

    public function scopePessoaJuridica($query)
    {
        return $query->where('tipo_pessoa', 'J');
    }

    public function scopeEstrangeira($query)
    {
        return $query->where('tipo_pessoa', 'E');
    }
}
