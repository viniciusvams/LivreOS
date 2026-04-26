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
        Schema::create('adquirente_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adquirente_id')->constrained('adquirentes')->onDelete('cascade');
            $table->foreignId('conta_bancaria_id')->constrained('contas_bancarias')->onDelete('cascade');
            $table->boolean('padrao')->default(false)->comment('Conta padrão para recebimentos desta adquirente');
            $table->integer('ordem')->default(0)->comment('Ordem de prioridade');
            $table->timestamps();

            $table->unique(['adquirente_id', 'conta_bancaria_id']);
            $table->index(['adquirente_id', 'padrao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adquirente_contas');
    }
};
