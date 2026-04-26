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
        Schema::create('pedidos_venda', function (Blueprint $table) {
            $table->id();
            $table->string('numero_sequencial', 30)->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('vendedor_id')->nullable();
            $table->enum('status', ['rascunho', 'confirmado', 'faturado', 'entregue', 'cancelado'])->default('rascunho');
            $table->enum('canal_venda', ['presencial', 'whatsapp', 'telefone', 'email', 'site', 'outro'])->nullable();
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->date('data_pedido');
            $table->date('data_prevista_entrega')->nullable();
            $table->timestamp('data_confirmacao')->nullable();
            $table->timestamp('data_faturamento')->nullable();
            $table->timestamp('data_entrega')->nullable();
            $table->timestamp('data_cancelamento')->nullable();
            $table->unsignedBigInteger('endereco_entrega_id')->nullable();
            $table->decimal('desconto_global', 10, 2)->default(0);
            $table->decimal('acrescimos_global', 10, 2)->default(0);
            $table->decimal('total_produtos', 10, 2)->default(0);
            $table->decimal('total_servicos', 10, 2)->default(0);
            $table->decimal('total_descontos', 10, 2)->default(0);
            $table->decimal('total_geral', 10, 2)->default(0);
            $table->boolean('estoque_reservado')->default(false);
            $table->boolean('estoque_baixado')->default(false);
            $table->boolean('financeiro_gerado')->default(false);
            $table->unsignedBigInteger('origem_pedido_id')->nullable();
            $table->string('origem_tipo', 60)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('vendedor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('endereco_entrega_id')->references('id')->on('enderecos')->nullOnDelete();
            $table->foreign('origem_pedido_id')->references('id')->on('pedidos_venda')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('cliente_id');
            $table->index('vendedor_id');
            $table->index('status');
            $table->index('data_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_venda');
    }
};
