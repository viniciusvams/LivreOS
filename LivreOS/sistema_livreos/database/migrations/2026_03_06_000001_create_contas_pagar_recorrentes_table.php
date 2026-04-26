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
     * Pagamentos fixos recorrentes (contas a pagar): água, luz, aluguel, etc.
     * Gera contas a pagar automaticamente conforme a frequência.
     */
    public function up(): void
    {
        Schema::create('contas_pagar_recorrentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('clientes')->nullOnDelete()->comment('Fornecedor/cliente quando aplicável');
            $table->string('descricao', 255);
            $table->string('tipo', 30)->nullable()->comment('agua, luz, gas, aluguel, condominio, telefone, internet, outro');
            $table->decimal('valor', 12, 2);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('frequencia', 20);
            $table->boolean('gerar_ultimo_dia_mes')->default(false);
            $table->date('proxima_geracao_em');
            $table->foreignId('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->nullOnDelete();
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->foreignId('plano_conta_id')->nullable()->constrained('plano_contas')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar_recorrentes');
    }
};
