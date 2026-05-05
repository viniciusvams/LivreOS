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
            if (!Schema::hasColumn('contas_receber', 'adquirente_id')) {
                $table->foreignId('adquirente_id')->nullable()->after('forma_pagamento_id')->constrained('adquirentes')->nullOnDelete();
            }
            if (!Schema::hasColumn('contas_receber', 'bandeira')) {
                $table->string('bandeira', 20)->nullable()->after('adquirente_id')->comment('master, visa, elo, amex, hipercard, outros');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->dropForeign(['adquirente_id']);
            $table->dropColumn(['adquirente_id', 'bandeira']);
        });
    }
};
