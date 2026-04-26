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
        Schema::create('ordem_servico_servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordem_servicos')->cascadeOnDelete();
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('descricao', 255)->nullable();
            $table->integer('tempo_estimado_min')->nullable();
            $table->integer('tempo_real_min')->nullable();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->decimal('desconto_valor', 12, 2)->nullable();
            $table->decimal('desconto_percentual', 5, 2)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_servicos');
    }
};
