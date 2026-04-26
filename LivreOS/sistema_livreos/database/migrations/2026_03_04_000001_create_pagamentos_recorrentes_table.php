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
        Schema::create('pagamentos_recorrentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('descricao', 255);
            $table->string('tipo', 30)->nullable(); // aluguel, contrato, assinatura, manutencao, outro
            $table->decimal('valor', 12, 2);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('frequencia', 20); // semanal, mensal, anual
            $table->boolean('gerar_ultimo_dia_mes')->default(false); // para mensal: vencimento no último dia do mês
            $table->date('proxima_geracao_em'); // próxima data para gerar conta a receber
            $table->foreignId('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->nullOnDelete();
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->foreignId('plano_conta_id')->nullable()->constrained('plano_contas')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();

            // Reajuste (integração futura com módulo de contrato / reajuste por índice)
            $table->boolean('reajuste_habilitado')->default(false);
            $table->unsignedSmallInteger('reajuste_periodo_meses')->nullable();
            $table->string('reajuste_tipo', 20)->nullable(); // nenhum, indice, manual
            $table->string('reajuste_indice', 30)->nullable(); // IGPM, IPCA, INPC, IPC_FIPE
            $table->decimal('reajuste_desconto_pct', 5, 2)->nullable();
            $table->decimal('reajuste_manual_pct', 5, 2)->nullable();
            $table->date('reajuste_ultima_data')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_recorrentes');
    }
};
