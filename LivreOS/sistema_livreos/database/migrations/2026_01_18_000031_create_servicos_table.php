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
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo_interno', 30)->unique();
            $table->string('nome', 255);
            $table->decimal('preco', 10, 2)->nullable();
            $table->string('unidade', 50);

            $table->string('codigo_sku', 100)->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_servicos')->nullOnDelete();

            $table->json('informacoes_fiscais')->nullable();
            $table->json('campos_customizados')->nullable();
            $table->decimal('valor_custo', 10, 2)->nullable();
            $table->decimal('valor_comissao', 10, 2)->nullable();
            $table->text('prazos')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['nome']);
            $table->index(['codigo_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
