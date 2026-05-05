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
        Schema::create('proposta_comercial_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_comercial_id')->constrained('propostas_comerciais')->cascadeOnDelete();
            $table->enum('tipo', ['produto', 'servico'])->default('produto');
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->foreignId('produto_variacao_id')->nullable()->constrained('produto_variacoes')->nullOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('descricao', 255);
            $table->decimal('quantidade', 10, 4)->default(1);
            $table->decimal('preco_unitario', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->index('proposta_comercial_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_comercial_itens');
    }
};
