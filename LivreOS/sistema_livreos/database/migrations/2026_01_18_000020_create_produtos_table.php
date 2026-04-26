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
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();

            // Dados básicos
            $table->string('nome', 255);
            $table->string('codigo_sku', 100)->nullable()->unique();
            $table->decimal('preco_venda', 10, 2)->nullable();
            $table->string('unidade', 30);
            $table->enum('formato', ['simples', 'variacao', 'composicao'])->default('simples');
            $table->enum('tipo', ['produto', 'servico'])->default('produto');
            $table->enum('condicao', ['novo', 'usado', 'recondicionado'])->nullable();
            $table->string('linha_produto', 100)->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_produtos')->nullOnDelete();

            // Características
            $table->string('marca', 100)->nullable();
            $table->enum('producao', ['propria', 'terceiros'])->nullable();
            $table->date('data_validade')->nullable();
            $table->boolean('frete_gratis')->default(false);
            $table->decimal('peso_liquido', 10, 4)->nullable();
            $table->decimal('peso_bruto', 10, 4)->nullable();
            $table->decimal('largura', 10, 4)->nullable();
            $table->decimal('altura', 10, 4)->nullable();
            $table->decimal('profundidade', 10, 4)->nullable();
            $table->integer('volumes')->nullable();
            $table->integer('itens_por_caixa')->nullable();
            $table->string('ean', 80)->nullable();
            $table->string('ean_tributario', 80)->nullable();
            $table->string('descricao_curta', 255)->nullable();
            $table->text('descricao_complementar')->nullable();
            $table->string('link_externo', 255)->nullable();
            $table->string('video_url', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->json('tags')->nullable();

            // Estoque
            $table->integer('estoque_minimo')->nullable();
            $table->integer('estoque_maximo')->nullable();
            $table->integer('estoque_crossdocking')->nullable();
            $table->string('estoque_localizacao', 100)->nullable();
            $table->foreignId('estoque_deposito_id')->nullable()->constrained('depositos')->nullOnDelete();
            $table->decimal('estoque_quantidade', 12, 6)->nullable();
            $table->decimal('estoque_preco_compra_unitario', 10, 2)->nullable();
            $table->decimal('estoque_preco_custo_unitario', 10, 2)->nullable();
            $table->text('estoque_observacao')->nullable();

            // Tributação
            $table->enum('tributacao_origem', ['0','1','2','3','4','5','6','7','8'])->nullable();
            $table->string('tributacao_ncm', 8)->nullable();
            $table->string('tributacao_cest', 7)->nullable();
            $table->string('tributacao_tipo_item', 2)->nullable();
            $table->decimal('tributacao_percentual_tributos', 5, 2)->nullable();
            $table->string('tributacao_grupo_produtos', 100)->nullable();
            $table->text('tributacao_info_adicionais')->nullable();
            $table->json('tributacao_icms')->nullable();
            $table->json('tributacao_pis_cofins')->nullable();

            // Auditoria
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['nome']);
            $table->index(['codigo_sku']);
            $table->index(['tributacao_ncm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
