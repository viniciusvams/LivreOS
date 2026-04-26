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
            $table->foreignId('grupo_economico_id')->nullable()->constrained('grupos_economicos')->nullOnDelete();
            $table->string('tipo_unidade', 20)->nullable()->comment('matriz|filial');
            $table->foreignId('matriz_id')->nullable()->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grupo_economico_id');
            $table->dropColumn('tipo_unidade');
            $table->dropConstrainedForeignId('matriz_id');
        });
    }
};
