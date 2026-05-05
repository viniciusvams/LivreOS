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
        if (Schema::hasTable('contas_receber')) {
            Schema::table('contas_receber', function (Blueprint $table) {
                if (! Schema::hasColumn('contas_receber', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->after('id')->constrained('contas_receber')->nullOnDelete();
                }
                if (! Schema::hasColumn('contas_receber', 'estrutura_tipo')) {
                    $table->string('estrutura_tipo', 30)->default('normal')->after('parent_id');
                }
                if (! Schema::hasColumn('contas_receber', 'status_estrutura')) {
                    $table->string('status_estrutura', 20)->default('ativo')->after('estrutura_tipo');
                }
                if (! Schema::hasColumn('contas_receber', 'lote_uuid')) {
                    $table->string('lote_uuid', 64)->nullable()->after('status_estrutura');
                }
                if (! Schema::hasColumn('contas_receber', 'ordem_no_lote')) {
                    $table->unsignedSmallInteger('ordem_no_lote')->nullable()->after('lote_uuid');
                }
            });
        }

        if (Schema::hasTable('contas_pagar')) {
            Schema::table('contas_pagar', function (Blueprint $table) {
                if (! Schema::hasColumn('contas_pagar', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->after('id')->constrained('contas_pagar')->nullOnDelete();
                }
                if (! Schema::hasColumn('contas_pagar', 'estrutura_tipo')) {
                    $table->string('estrutura_tipo', 30)->default('normal')->after('parent_id');
                }
                if (! Schema::hasColumn('contas_pagar', 'status_estrutura')) {
                    $table->string('status_estrutura', 20)->default('ativo')->after('estrutura_tipo');
                }
                if (! Schema::hasColumn('contas_pagar', 'lote_uuid')) {
                    $table->string('lote_uuid', 64)->nullable()->after('status_estrutura');
                }
                if (! Schema::hasColumn('contas_pagar', 'ordem_no_lote')) {
                    $table->unsignedSmallInteger('ordem_no_lote')->nullable()->after('lote_uuid');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contas_receber')) {
            Schema::table('contas_receber', function (Blueprint $table) {
                $dropCols = [];
                foreach (['ordem_no_lote', 'lote_uuid', 'status_estrutura', 'estrutura_tipo'] as $col) {
                    if (Schema::hasColumn('contas_receber', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if (! empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
                if (Schema::hasColumn('contas_receber', 'parent_id')) {
                    $table->dropConstrainedForeignId('parent_id');
                }
            });
        }

        if (Schema::hasTable('contas_pagar')) {
            Schema::table('contas_pagar', function (Blueprint $table) {
                $dropCols = [];
                foreach (['ordem_no_lote', 'lote_uuid', 'status_estrutura', 'estrutura_tipo'] as $col) {
                    if (Schema::hasColumn('contas_pagar', $col)) {
                        $dropCols[] = $col;
                    }
                }
                if (! empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
                if (Schema::hasColumn('contas_pagar', 'parent_id')) {
                    $table->dropConstrainedForeignId('parent_id');
                }
            });
        }
    }
};
