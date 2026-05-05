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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('nome', 200);
            $table->string('razao_social', 200)->nullable();
            $table->string('cnpj', 20)->nullable();
            $table->string('inscricao_estadual', 50)->nullable();
            $table->string('inscricao_municipal', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('site', 150)->nullable();
            $table->string('cep', 15)->nullable();
            $table->string('logradouro', 200)->nullable();
            $table->string('numero', 30)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 120)->nullable();
            $table->string('estado', 60)->nullable();
            $table->string('pais', 60)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
