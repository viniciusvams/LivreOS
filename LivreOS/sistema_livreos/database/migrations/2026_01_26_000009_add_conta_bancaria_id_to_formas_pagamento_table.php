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
        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->foreignId('conta_bancaria_id')
                ->nullable()
                ->after('max_parcelas')
                ->constrained('contas_bancarias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('formas_pagamento', function (Blueprint $table) {
            $table->dropForeign(['conta_bancaria_id']);
            $table->dropColumn('conta_bancaria_id');
        });
    }
};
