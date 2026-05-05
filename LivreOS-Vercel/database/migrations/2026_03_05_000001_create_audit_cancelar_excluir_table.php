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
    /**
     * Registro de cancelamentos e exclusões no sistema: motivo, usuário, entidade.
     * Apenas administradores podem ver o histórico.
     */
    public function up(): void
    {
        Schema::create('audit_cancelar_excluir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20); // cancelar | excluir
            $table->string('entity_type', 80); // ex: conta_receber, ordem_servico, cliente
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_descricao', 500)->nullable();
            $table->text('motivo');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('audit_cancelar_excluir', function (Blueprint $table) {
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_cancelar_excluir');
    }
};
