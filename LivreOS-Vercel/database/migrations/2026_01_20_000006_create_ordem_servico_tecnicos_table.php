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
        Schema::create('ordem_servico_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordem_servicos')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->nullable();
            $table->dateTime('inicio')->nullable();
            $table->dateTime('fim')->nullable();
            $table->integer('pausas_minutos')->nullable();
            $table->decimal('horas_trabalhadas', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['ordem_servico_id', 'tecnico_id']);
        });

        Schema::create('ordem_servico_apontamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordem_servicos')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fim')->nullable();
            $table->integer('pausas_minutos')->nullable();
            $table->string('status', 30)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('ordem_servico_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordem_servicos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 50);
            $table->string('descricao', 255)->nullable();
            $table->json('dados')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_logs');
        Schema::dropIfExists('ordem_servico_apontamentos');
        Schema::dropIfExists('ordem_servico_tecnicos');
    }
};
