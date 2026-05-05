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
        Schema::table('pedidos_venda', function (Blueprint $table) {
            $table->unsignedSmallInteger('validade_orcamento_dias')->nullable()->after('data_prevista_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_venda', function (Blueprint $table) {
            $table->dropColumn('validade_orcamento_dias');
        });
    }
};
