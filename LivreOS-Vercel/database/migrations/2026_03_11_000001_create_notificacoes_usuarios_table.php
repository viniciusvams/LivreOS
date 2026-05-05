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
        Schema::create('notificacoes_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 50)->default('sistema'); // financeiro, sistema, etc.
            $table->string('titulo');
            $table->text('mensagem')->nullable();
            $table->string('url')->nullable();
            $table->string('icone', 50)->nullable(); // alerta, info, sucesso, erro
            $table->boolean('lida')->default(false);
            $table->string('referencia_tipo', 50)->nullable(); // para evitar duplicatas (ex: vence_hoje_cr)
            $table->string('referencia_data', 10)->nullable();  // data de referência (YYYY-MM-DD)
            $table->softDeletes(); // excluída pelo usuário
            $table->timestamps();

            // MySQL limita identificadores a 64 caracteres.
            $table->index(['user_id', 'lida', 'deleted_at'], 'notif_usr_idx_user_lida_del');
            $table->index(['user_id', 'referencia_tipo', 'referencia_data'], 'notif_usr_idx_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes_usuarios');
    }
};
