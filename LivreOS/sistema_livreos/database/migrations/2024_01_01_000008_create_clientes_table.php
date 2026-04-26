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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            
            // Seção 1: Dados Básicos
            $table->enum('tipo_pessoa', ['F', 'J', 'E'])->comment('F=Física, J=Jurídica, E=Estrangeira');
            $table->string('pais_origem', 100)->default('Brasil');
            $table->string('nome', 100);
            $table->string('razao_social', 255)->nullable();
            $table->string('ramo_atividade', 255)->nullable();
            $table->tinyInteger('classificacao_rating')->default(0)->comment('0 a 3 estrelas');
            $table->boolean('produtor_rural')->default(false);
            
            // Documentação PF Brasil
            $table->string('rg', 20)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            
            // Documentação PJ Brasil
            $table->string('cnpj', 18)->nullable();
            $table->string('inscricao_estadual', 20)->nullable();
            $table->string('uf_ie', 2)->nullable();
            $table->string('inscricao_municipal', 20)->nullable();
            $table->boolean('optante_simples_nacional')->default(false);
            $table->boolean('ignorar_im_nfse')->default(false);
            
            // Documentação Estrangeira
            $table->string('documento_estrangeiro', 30)->nullable();
            
            // Seção 4: SLA (Service Desk)
            $table->json('sla_config')->nullable()->comment('Configurações de SLA por prioridade');
            
            // Seção 5: Faturamento
            $table->integer('dias_antecedencia_faturamento')->nullable();
            $table->integer('dia_faturamento')->nullable()->comment('1-31');
            $table->enum('emissao_nfse', ['config_empresa', 'faturamento', 'quitacao'])->default('config_empresa');
            
            // Seção 6: Observações
            $table->text('observacoes')->nullable();
            
            // Seção 7: Multi-empresa
            $table->json('empresas_vinculadas')->nullable();
            
            // Seção 8: Permissões Portal
            $table->boolean('portal_suporte')->default(false);
            $table->boolean('portal_financeiro')->default(false);
            
            // Controle
            $table->boolean('bloqueado_alteracao_tipo')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('tipo_pessoa');
            $table->index('cpf');
            $table->index('cnpj');
            $table->index('documento_estrangeiro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
