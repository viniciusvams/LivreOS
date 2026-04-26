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
        Schema::create('produto_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->foreignId('fornecedor_id')->constrained('contatos')->onDelete('restrict');
            $table->string('descricao', 255)->nullable();
            $table->string('codigo_fornecedor', 100)->nullable();
            $table->decimal('preco_compra', 10, 2)->nullable();
            $table->decimal('preco_custo', 10, 2)->nullable();
            $table->integer('garantia_meses')->nullable();
            $table->timestamps();

            $table->index(['produto_id']);
            $table->index(['fornecedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_fornecedores');
    }
};
