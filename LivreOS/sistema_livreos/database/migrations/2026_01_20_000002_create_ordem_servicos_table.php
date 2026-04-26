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
        Schema::create('ordem_servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->integer('numero_sequencial')->nullable();
            $table->string('codigo_interno', 50)->nullable();

            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('cliente_unidade_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('contato_id')->nullable()->constrained('contatos')->nullOnDelete();
            $table->foreignId('endereco_id')->nullable()->constrained('enderecos')->nullOnDelete();

            $table->string('tipo_os', 50)->nullable();
            $table->string('origem_os', 50)->nullable();
            $table->string('prioridade', 20)->default('normal');
            $table->string('status', 50)->default('Aberta');

            $table->dateTime('data_abertura')->nullable();
            $table->dateTime('prevista_inicio')->nullable();
            $table->dateTime('prevista_conclusao')->nullable();
            $table->dateTime('real_inicio')->nullable();
            $table->dateTime('real_conclusao')->nullable();

            $table->integer('sla_valor')->nullable();
            $table->string('sla_unidade', 10)->nullable();
            $table->boolean('sla_cumprido')->nullable();

            $table->text('relato_cliente')->nullable();
            $table->text('diagnostico_tecnico')->nullable();
            $table->text('servicos_realizados')->nullable();
            $table->text('observacoes_internas')->nullable();
            $table->text('observacoes_cliente')->nullable();

            $table->unsignedBigInteger('equipamento_id')->nullable();
            $table->string('equipamento_nome', 150)->nullable();
            $table->string('equipamento_marca', 100)->nullable();
            $table->string('equipamento_modelo', 100)->nullable();
            $table->string('equipamento_numero_serie', 100)->nullable();
            $table->string('equipamento_numero_patrimonio', 100)->nullable();
            $table->text('equipamento_acessorios')->nullable();
            $table->decimal('quilometragem_entrada', 10, 2)->nullable();
            $table->decimal('quilometragem_saida', 10, 2)->nullable();
            $table->decimal('horimetro_entrada', 10, 2)->nullable();
            $table->decimal('horimetro_saida', 10, 2)->nullable();

            $table->string('garantia_tipo', 20)->nullable();
            $table->string('contrato_numero', 100)->nullable();
            $table->string('contrato_tipo', 50)->nullable();
            $table->boolean('recorrente')->default(false);
            $table->string('recorrencia_periodicidade', 50)->nullable();

            $table->string('aprovado_nome', 150)->nullable();
            $table->dateTime('aprovado_em')->nullable();
            $table->string('aprovado_canal', 50)->nullable();
            $table->string('aprovado_assinatura', 255)->nullable();
            $table->boolean('aprovado_cliente')->default(false);

            $table->decimal('total_produtos', 12, 2)->default(0);
            $table->decimal('total_servicos', 12, 2)->default(0);
            $table->decimal('desconto_global', 12, 2)->default(0);
            $table->decimal('acrescimos_global', 12, 2)->default(0);
            $table->decimal('impostos_global', 12, 2)->default(0);
            $table->decimal('total_descontos', 12, 2)->default(0);
            $table->decimal('total_acrescimos', 12, 2)->default(0);
            $table->decimal('total_impostos', 12, 2)->default(0);
            $table->decimal('total_geral', 12, 2)->default(0);
            $table->boolean('estoque_baixado')->default(false);
            $table->boolean('financeiro_gerado')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_criacao', 45)->nullable();
            $table->string('ip_atualizacao', 45)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'codigo_interno']);
            $table->index(['cliente_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servicos');
    }
};
