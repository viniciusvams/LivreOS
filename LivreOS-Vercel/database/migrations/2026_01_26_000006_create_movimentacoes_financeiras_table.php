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
        Schema::create('movimentacoes_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('conta_bancaria_id')->constrained('contas_bancarias')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'saida']);
            $table->enum('origem', ['conta_receber', 'conta_pagar', 'transferencia', 'ajuste', 'outro'])->default('outro');
            $table->foreignId('conta_receber_id')->nullable()->constrained('contas_receber')->nullOnDelete();
            $table->foreignId('conta_pagar_id')->nullable()->constrained('contas_pagar')->nullOnDelete();
            $table->foreignId('transferencia_id')->nullable()->constrained('movimentacoes_financeiras')->nullOnDelete()->comment('ID da movimentação de origem/destino');
            $table->decimal('valor', 15, 2);
            $table->date('data_movimentacao');
            $table->string('descricao', 255);
            $table->text('observacoes')->nullable();
            $table->boolean('conciliado')->default(false);
            $table->date('data_conciliacao')->nullable();
            $table->foreignId('conciliado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // MySQL limita identificadores a 64 caracteres.
            $table->index(['conta_bancaria_id', 'data_movimentacao', 'tipo'], 'mov_fin_idx_conta_data_tipo');
            $table->index(['conta_receber_id'], 'mov_fin_idx_conta_receber');
            $table->index(['conta_pagar_id'], 'mov_fin_idx_conta_pagar');
            $table->index(['conciliado', 'data_conciliacao'], 'mov_fin_idx_concil_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_financeiras');
    }
};
