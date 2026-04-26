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
        Schema::create('produto_variacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->string('referencia_sku', 100);
            $table->json('opcoes_valores')->nullable();
            $table->string('ean_variacao', 80)->nullable();
            $table->decimal('quantidade', 12, 6)->nullable();
            $table->boolean('herdar_do_pai')->default(true);
            $table->timestamps();

            $table->unique('referencia_sku');
            $table->index(['produto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_variacoes');
    }
};
