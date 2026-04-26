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
            if (!Schema::hasColumn('pagamentos_recorrentes', 'controle_origem')) {
                $table->string('controle_origem', 60)->nullable()->after('observacoes')
                    ->comment('Origem da integração. Ex.: plugin:locacao');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'controle_entidade_tipo')) {
                $table->string('controle_entidade_tipo', 80)->nullable()->after('controle_origem')
                    ->comment('Tipo da entidade externa. Ex.: contrato_locacao');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'controle_entidade_id')) {
                $table->string('controle_entidade_id', 120)->nullable()->after('controle_entidade_tipo')
                    ->comment('ID externo da entidade vinculada');
            }
            if (!Schema::hasColumn('pagamentos_recorrentes', 'controle_referencia')) {
                $table->string('controle_referencia', 120)->nullable()->after('controle_entidade_id')
                    ->comment('Chave de referência adicional para conciliação');
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
                'controle_origem',
                'controle_entidade_tipo',
                'controle_entidade_id',
                'controle_referencia',
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

