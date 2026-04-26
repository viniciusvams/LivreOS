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
     * Campos para integração futura com módulo de contrato: reajuste do valor
     * após X períodos (ex.: anualmente), usando índice (IGPM, IPCA, etc.) ou
     * percentual manual, com opção de desconto sobre o reajuste.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pagamentos_recorrentes')) {
            return;
        }

        Schema::table('pagamentos_recorrentes', function (Blueprint $table) {
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_habilitado')) {
                $table->boolean('reajuste_habilitado')->default(false)->after('observacoes');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_periodo_meses')) {
                $table->unsignedSmallInteger('reajuste_periodo_meses')->nullable()->after('reajuste_habilitado')->comment('Reajustar a cada X meses (ex: 12 = anual). Null = não reajustar por período');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_tipo')) {
                $table->string('reajuste_tipo', 20)->nullable()->after('reajuste_periodo_meses')->comment('nenhum, indice, manual');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_indice')) {
                $table->string('reajuste_indice', 30)->nullable()->after('reajuste_tipo')->comment('Código do índice: IGPM, IPCA, INPC, etc.');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_desconto_pct')) {
                $table->decimal('reajuste_desconto_pct', 5, 2)->nullable()->after('reajuste_indice')->comment('Desconto em % sobre o reajuste (ex: 10 = aplicar 90% do índice)');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_manual_pct')) {
                $table->decimal('reajuste_manual_pct', 5, 2)->nullable()->after('reajuste_desconto_pct')->comment('Quando tipo=manual: percentual fixo de reajuste');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'reajuste_ultima_data')) {
                $table->date('reajuste_ultima_data')->nullable()->after('reajuste_manual_pct')->comment('Última data em que o reajuste foi aplicado (para o módulo de contrato)');
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
            foreach ([
                'reajuste_habilitado',
                'reajuste_periodo_meses',
                'reajuste_tipo',
                'reajuste_indice',
                'reajuste_desconto_pct',
                'reajuste_manual_pct',
                'reajuste_ultima_data',
            ] as $col) {
                if (Schema::hasColumn('pagamentos_recorrentes', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
