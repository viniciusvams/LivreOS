<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plugin_compras_ordens')) {
            Schema::create('plugin_compras_ordens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fornecedor_id')->nullable();
                $table->string('numero_ordem', 60)->nullable()->unique();
                $table->string('status', 40)->default('rascunho');
                $table->date('data_emissao')->nullable();
                $table->date('data_previsao')->nullable();
                $table->timestamp('data_aprovacao')->nullable();
                $table->text('observacoes')->nullable();
                $table->decimal('valor_total', 18, 2)->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('motivo_rejeicao')->nullable();
                $table->timestamps();

                $table->foreign('fornecedor_id')->references('id')->on('contatos')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('plugin_compras_ordens_itens')) {
            Schema::create('plugin_compras_ordens_itens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ordem_compra_id');
                $table->unsignedBigInteger('produto_id')->nullable();
                $table->string('fornecedor_codigo', 120)->nullable();
                $table->string('descricao', 255)->nullable();
                $table->decimal('quantidade', 12, 4)->default(0);
                $table->string('unidade', 40)->nullable();
                $table->decimal('preco_unitario', 18, 2)->default(0);
                $table->decimal('valor_total', 18, 2)->default(0);
                $table->decimal('quantidade_recebida', 12, 4)->default(0);
                $table->string('status_item', 40)->default('pendente');
                $table->text('observacoes')->nullable();
                $table->timestamps();

                $table->foreign('ordem_compra_id')->references('id')->on('plugin_compras_ordens')->onDelete('cascade');
                $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('plugin_compras_recebimentos')) {
            Schema::create('plugin_compras_recebimentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ordem_compra_id');
                $table->date('data_recebimento')->nullable();
                $table->string('numero_documento', 80)->nullable();
                $table->text('observacoes')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->string('status', 40)->default('recebido');
                $table->decimal('valor_total', 18, 2)->default(0);
                $table->timestamps();

                $table->foreign('ordem_compra_id')->references('id')->on('plugin_compras_ordens')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('plugin_compras_recebimento_itens')) {
            Schema::create('plugin_compras_recebimento_itens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('recebimento_id');
                $table->unsignedBigInteger('ordem_item_id')->nullable();
                $table->unsignedBigInteger('produto_id')->nullable();
                $table->decimal('quantidade_recebida', 12, 4)->default(0);
                $table->string('unidade', 40)->nullable();
                $table->decimal('preco_unitario', 18, 2)->default(0);
                $table->decimal('valor_total', 18, 2)->default(0);
                $table->string('lote', 120)->nullable();
                $table->date('validade')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();

                $table->foreign('recebimento_id')->references('id')->on('plugin_compras_recebimentos')->onDelete('cascade');
                $table->foreign('ordem_item_id')->references('id')->on('plugin_compras_ordens_itens')->onDelete('set null');
                $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_compras_recebimento_itens');
        Schema::dropIfExists('plugin_compras_recebimentos');
        Schema::dropIfExists('plugin_compras_ordens_itens');
        Schema::dropIfExists('plugin_compras_ordens');
    }
};
