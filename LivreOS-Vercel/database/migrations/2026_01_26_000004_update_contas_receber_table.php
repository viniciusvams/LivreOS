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
        Schema::table('contas_receber', function (Blueprint $table) {
            // Adicionar campos que faltam
            if (!Schema::hasColumn('contas_receber', 'numero_parcela')) {
                $table->integer('numero_parcela')->default(1)->after('ordem_servico_id');
                $table->integer('total_parcelas')->default(1)->after('numero_parcela');
            }
            if (!Schema::hasColumn('contas_receber', 'valor_original')) {
                $table->decimal('valor_original', 12, 2)->after('valor');
            }
            if (!Schema::hasColumn('contas_receber', 'valor_recebido')) {
                $table->decimal('valor_recebido', 12, 2)->default(0)->after('valor_original');
            }
            if (!Schema::hasColumn('contas_receber', 'data_recebimento')) {
                $table->date('data_recebimento')->nullable()->after('data_vencimento');
            }
            if (!Schema::hasColumn('contas_receber', 'forma_pagamento_id')) {
                $table->foreignId('forma_pagamento_id')->nullable()->constrained('formas_pagamento')->nullOnDelete()->after('data_recebimento');
            }
            if (!Schema::hasColumn('contas_receber', 'conta_bancaria_id')) {
                $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete()->after('forma_pagamento_id');
            }
            if (!Schema::hasColumn('contas_receber', 'plano_conta_id')) {
                $table->foreignId('plano_conta_id')->nullable()->constrained('plano_contas')->nullOnDelete()->after('conta_bancaria_id');
            }
            if (!Schema::hasColumn('contas_receber', 'juros')) {
                $table->decimal('juros', 10, 2)->default(0)->after('valor_recebido');
            }
            if (!Schema::hasColumn('contas_receber', 'multa')) {
                $table->decimal('multa', 10, 2)->default(0)->after('juros');
            }
            if (!Schema::hasColumn('contas_receber', 'desconto')) {
                $table->decimal('desconto', 10, 2)->default(0)->after('multa');
            }
            if (!Schema::hasColumn('contas_receber', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('contas_receber', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('contas_receber', 'deleted_at')) {
                $table->softDeletes();
            }

            // Atualizar enum de status
            $table->string('status', 20)->default('aberto')->change(); // aberto, pago, parcial, cancelado, vencido
        });
    }

    public function down(): void
    {
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->dropForeign(['forma_pagamento_id']);
            $table->dropForeign(['conta_bancaria_id']);
            $table->dropForeign(['plano_conta_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'numero_parcela', 'total_parcelas', 'valor_original', 'valor_recebido',
                'data_recebimento', 'forma_pagamento_id', 'conta_bancaria_id', 'plano_conta_id',
                'juros', 'multa', 'desconto', 'observacoes', 'updated_by', 'deleted_at'
            ]);
        });
    }
};
