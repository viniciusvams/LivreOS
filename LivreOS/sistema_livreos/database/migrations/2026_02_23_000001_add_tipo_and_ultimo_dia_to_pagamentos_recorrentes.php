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
        if (!Schema::hasTable('pagamentos_recorrentes')) {
            return;
        }

        Schema::table('pagamentos_recorrentes', function (Blueprint $table) {
            if (!Schema::hasColumn('pagamentos_recorrentes', 'tipo')) {
                $table->string('tipo', 30)->nullable()->after('descricao'); // aluguel, contrato, assinatura, manutencao, outro
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'gerar_ultimo_dia_mes')) {
                $table->boolean('gerar_ultimo_dia_mes')->default(false)->after('frequencia'); // para mensal: vencimento no último dia do mês
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pagamentos_recorrentes')) {
            return;
        }

        Schema::table('pagamentos_recorrentes', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('pagamentos_recorrentes', 'tipo')) {
                $cols[] = 'tipo';
            }
            if (Schema::hasColumn('pagamentos_recorrentes', 'gerar_ultimo_dia_mes')) {
                $cols[] = 'gerar_ultimo_dia_mes';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
