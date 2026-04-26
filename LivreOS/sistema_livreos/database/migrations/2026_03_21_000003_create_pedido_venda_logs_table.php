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
        Schema::create('pedido_venda_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_venda_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status_anterior', 50)->nullable();
            $table->string('status_novo', 50)->nullable();
            $table->string('acao', 80);
            $table->text('descricao')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pedido_venda_id')->references('id')->on('pedidos_venda')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('pedido_venda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_venda_logs');
    }
};
