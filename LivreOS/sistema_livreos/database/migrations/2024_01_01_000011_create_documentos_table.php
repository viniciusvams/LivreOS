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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            
            $table->string('nome_arquivo', 255);
            $table->string('caminho_arquivo', 500);
            $table->string('tipo_mime', 100);
            $table->integer('tamanho')->comment('Tamanho em bytes');
            $table->string('categoria', 100)->nullable();
            $table->string('tags', 255)->nullable()->comment('Tags separadas por vírgula');
            $table->text('descricao')->nullable();
            
            $table->timestamps();
            
            $table->index('cliente_id');
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
