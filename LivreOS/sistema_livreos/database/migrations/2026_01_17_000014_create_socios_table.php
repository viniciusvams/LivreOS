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
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');

            // Qualificação
            $table->string('nome_completo', 255)->nullable();
            $table->string('cpf', 20)->nullable();
            $table->string('rg_orgao_emissor', 100)->nullable();
            $table->string('nacionalidade', 100)->nullable();
            $table->string('estado_civil', 50)->nullable();
            $table->string('profissao', 100)->nullable();

            // Endereço residencial
            $table->string('cep', 10)->nullable();
            $table->string('logradouro', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();

            // Autoridade
            $table->boolean('socio_administrador')->nullable();
            $table->enum('tipo_assinatura', ['isoladamente', 'conjunto'])->nullable();
            $table->decimal('percentual_participacao', 5, 2)->nullable();

            // Risco
            $table->integer('score_credito')->nullable();
            $table->boolean('possui_restricoes')->nullable();
            $table->string('patrimonio_estimado', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
