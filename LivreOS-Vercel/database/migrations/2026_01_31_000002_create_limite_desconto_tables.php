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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limite_desconto', function (Blueprint $table) {
            $table->id();
            $table->decimal('limite_valor', 12, 2)->nullable()->comment('Limite máximo de desconto em R$ (por item ou global)');
            $table->decimal('limite_percentual', 5, 2)->nullable()->comment('Limite máximo de desconto em % (por item ou global)');
            $table->timestamps();
        });

        Schema::create('limite_desconto_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('limite_desconto_id')->constrained('limite_desconto')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['limite_desconto_id', 'role_id']);
        });

        // Inserir registro padrão (singleton)
        DB::table('limite_desconto')->insert([
            'limite_valor' => null,
            'limite_percentual' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('limite_desconto_role');
        Schema::dropIfExists('limite_desconto');
    }
};
