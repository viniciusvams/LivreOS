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
        Schema::create('propostas_comerciais', function (Blueprint $table) {
            $table->id();
            $table->uuid('grupo_uuid');
            $table->unsignedSmallInteger('versao')->default(1);
            $table->boolean('is_versao_atual')->default(true);

            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tabela_preco_id')->nullable()->constrained('tabelas_precos')->nullOnDelete();

            $table->string('status', 32)->default('rascunho');
            $table->string('titulo', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->text('observacoes_internas')->nullable();

            $table->date('data_emissao');
            $table->unsignedSmallInteger('validade_dias')->nullable();
            $table->date('validade_em')->nullable();

            $table->foreignId('pedido_venda_id')->nullable()->constrained('pedidos_venda')->nullOnDelete();
            $table->foreignId('ordem_servico_id')->nullable()->constrained('ordem_servicos')->nullOnDelete();

            $table->decimal('total_produtos', 10, 2)->default(0);
            $table->decimal('total_servicos', 10, 2)->default(0);
            $table->decimal('total_descontos', 10, 2)->default(0);
            $table->decimal('total_geral', 10, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grupo_uuid', 'versao']);
            $table->index(['is_versao_atual', 'status']);
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas_comerciais');
    }
};
