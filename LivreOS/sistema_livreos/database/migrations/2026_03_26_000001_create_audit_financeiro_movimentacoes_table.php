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
        Schema::create('audit_financeiro_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->string('entidade_tipo', 30); // conta_receber | conta_pagar
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->string('acao', 20); // created | updated | deleted | restored
            $table->json('alteracoes')->nullable(); // campo => ['antes' => x, 'depois' => y]
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entidade_tipo', 'entidade_id']);
            $table->index(['acao', 'created_at']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_financeiro_movimentacoes');
    }
};

