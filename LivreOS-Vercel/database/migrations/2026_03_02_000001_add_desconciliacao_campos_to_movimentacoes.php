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
        Schema::table('movimentacoes_financeiras', function (Blueprint $table) {
            $table->text('motivo_desconciliacao')->nullable()->after('conciliado_por');
            $table->foreignId('desconciliado_por')->nullable()->after('motivo_desconciliacao')->constrained('users')->nullOnDelete();
            $table->timestamp('data_desconciliacao')->nullable()->after('desconciliado_por');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_financeiras', function (Blueprint $table) {
            $table->dropForeign(['desconciliado_por']);
            $table->dropColumn(['motivo_desconciliacao', 'desconciliado_por', 'data_desconciliacao']);
        });
    }
};
