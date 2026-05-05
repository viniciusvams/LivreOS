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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->enum('tipo', ['padrao', 'personalizado'])->default('personalizado');
            $table->text('descricao')->nullable();
            $table->string('cor_fundo', 7)->default('#ffffff')->comment('Cor em hexadecimal');
            $table->string('cor_fonte', 7)->default('#000000')->comment('Cor em hexadecimal');
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nome']);
            $table->index(['tipo']);
            $table->index(['ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
