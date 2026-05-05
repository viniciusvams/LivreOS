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
        Schema::create('pedido_venda_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_venda_id');
            $table->enum('tipo', ['produto', 'servico'])->default('produto');
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->unsignedBigInteger('servico_id')->nullable();
            $table->string('descricao', 255);
            $table->decimal('quantidade', 10, 4)->default(1);
            $table->decimal('preco_unitario', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('serial', 100)->nullable();
            $table->boolean('gerar_os')->default(false);
            $table->unsignedBigInteger('os_gerada_id')->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->foreign('pedido_venda_id')->references('id')->on('pedidos_venda')->cascadeOnDelete();
            $table->foreign('produto_id')->references('id')->on('produtos')->nullOnDelete();
            $table->foreign('servico_id')->references('id')->on('servicos')->nullOnDelete();
            $table->foreign('os_gerada_id')->references('id')->on('ordem_servicos')->nullOnDelete();

            $table->index('pedido_venda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_venda_itens');
    }
};
