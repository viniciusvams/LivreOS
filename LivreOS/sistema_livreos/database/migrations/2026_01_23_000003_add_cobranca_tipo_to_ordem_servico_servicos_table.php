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
        Schema::table('ordem_servico_servicos', function (Blueprint $table) {
            $table->string('cobranca_tipo', 20)->default('unidade')->after('descricao');
            $table->decimal('quantidade', 10, 3)->nullable()->after('tempo_real_min');
            $table->decimal('quantidade_horas', 10, 3)->nullable()->after('quantidade');
        });
    }

    public function down(): void
    {
        Schema::table('ordem_servico_servicos', function (Blueprint $table) {
            $table->dropColumn(['cobranca_tipo', 'quantidade', 'quantidade_horas']);
        });
    }
};
