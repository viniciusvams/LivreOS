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
        Schema::table('clientes', function (Blueprint $table) {
            $table->date('data_inicio_atividade')->nullable()->after('capital_social');
            $table->foreignId('vendedor_id')->nullable()->after('bloqueado_alteracao_tipo')->constrained('users')->nullOnDelete();
            $table->decimal('limite_credito', 12, 2)->nullable()->after('vendedor_id');
            $table->boolean('bloqueado_vendas')->default(false)->after('limite_credito');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            $table->dropColumn(['data_inicio_atividade', 'vendedor_id', 'limite_credito', 'bloqueado_vendas']);
        });
    }
};
