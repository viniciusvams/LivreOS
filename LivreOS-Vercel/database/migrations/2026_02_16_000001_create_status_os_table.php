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
        Schema::create('status_os', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->string('cor', 20)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->boolean('sistema')->default(false)->comment('Status do sistema (ex: Encerrada) - não pode ser excluído');
            $table->boolean('marca_inicio')->default(false)->comment('Ao selecionar, marca real_inicio');
            $table->boolean('marca_conclusao')->default(false)->comment('Ao selecionar, marca real_conclusao e aciona baixa estoque/financeiro');
            $table->timestamps();

            $table->index(['ativo', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_os');
    }
};
