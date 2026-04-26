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
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            
            $table->string('cep', 10);
            $table->string('logradouro', 255);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->string('estado', 2);
            $table->string('pais', 100)->default('Brasil');
            $table->string('inscricao_produtor_rural', 20)->nullable();
            
            // Tipo de endereço (Principal, Cobrança, Entrega)
            $table->boolean('principal')->default(false);
            $table->boolean('cobranca')->default(false);
            $table->boolean('entrega')->default(false);
            
            $table->timestamps();
            
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
