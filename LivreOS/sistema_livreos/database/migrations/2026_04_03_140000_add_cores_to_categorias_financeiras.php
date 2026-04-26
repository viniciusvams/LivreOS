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
        Schema::table('categorias_financeiras', function (Blueprint $table) {
            $table->string('cor_fundo', 7)->default('#64748b')->after('nome');
            $table->string('cor_fonte', 7)->default('#ffffff')->after('cor_fundo');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_financeiras', function (Blueprint $table) {
            $table->dropColumn(['cor_fundo', 'cor_fonte']);
        });
    }
};
