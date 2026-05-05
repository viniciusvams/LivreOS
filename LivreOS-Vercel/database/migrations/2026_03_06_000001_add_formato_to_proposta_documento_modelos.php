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
        if (! Schema::hasTable('proposta_documento_modelos')) {
            return;
        }
        if (Schema::hasColumn('proposta_documento_modelos', 'formato')) {
            return;
        }
        Schema::table('proposta_documento_modelos', function (Blueprint $table) {
            $table->string('formato', 16)->default('docx')->after('arquivo_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('proposta_documento_modelos') || ! Schema::hasColumn('proposta_documento_modelos', 'formato')) {
            return;
        }
        Schema::table('proposta_documento_modelos', function (Blueprint $table) {
            $table->dropColumn('formato');
        });
    }
};
