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
            $table->text('motivo_cancelamento_transferencia')->nullable()->after('data_desconciliacao');
            $table->foreignId('cancelado_por')->nullable()->after('motivo_cancelamento_transferencia')->constrained('users')->nullOnDelete();
            $table->timestamp('data_cancelamento')->nullable()->after('cancelado_por');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_financeiras', function (Blueprint $table) {
            $table->dropForeign(['cancelado_por']);
            $table->dropColumn(['motivo_cancelamento_transferencia', 'cancelado_por', 'data_cancelamento']);
        });
    }
};
