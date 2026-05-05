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
        Schema::create('conta_pagar_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_pagar_id')->constrained('contas_pagar')->cascadeOnDelete();
            $table->string('nome_arquivo', 255);
            $table->string('caminho_arquivo', 500);
            $table->string('tipo_mime', 100)->nullable();
            $table->unsignedInteger('tamanho')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conta_pagar_anexos');
    }
};
