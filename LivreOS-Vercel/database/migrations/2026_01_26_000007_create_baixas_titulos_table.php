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
        Schema::create('baixas_titulos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_titulo', ['conta_receber', 'conta_pagar']);
            $table->foreignId('titulo_id')->comment('ID da conta_receber ou conta_pagar');
            $table->foreignId('movimentacao_id')->constrained('movimentacoes_financeiras')->cascadeOnDelete();
            $table->decimal('valor_baixa', 12, 2);
            $table->date('data_baixa');
            $table->decimal('juros', 10, 2)->default(0);
            $table->decimal('multa', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->boolean('estornado')->default(false);
            $table->date('data_estorno')->nullable();
            $table->foreignId('estornado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_estorno')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_titulo', 'titulo_id']);
            $table->index(['estornado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baixas_titulos');
    }
};
