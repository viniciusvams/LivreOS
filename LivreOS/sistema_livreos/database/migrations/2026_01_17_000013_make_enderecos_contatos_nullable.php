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
        Schema::table('enderecos', function (Blueprint $table) {
            $table->string('cep', 10)->nullable()->change();
            $table->string('logradouro', 255)->nullable()->change();
            $table->string('numero', 20)->nullable()->change();
            $table->string('bairro', 100)->nullable()->change();
            $table->string('cidade', 100)->nullable()->change();
            $table->string('estado', 2)->nullable()->change();
        });

        Schema::table('contatos', function (Blueprint $table) {
            $table->string('nome', 100)->nullable()->change();
            $table->string('telefone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enderecos', function (Blueprint $table) {
            $table->string('cep', 10)->nullable(false)->change();
            $table->string('logradouro', 255)->nullable(false)->change();
            $table->string('numero', 20)->nullable(false)->change();
            $table->string('bairro', 100)->nullable(false)->change();
            $table->string('cidade', 100)->nullable(false)->change();
            $table->string('estado', 2)->nullable(false)->change();
        });

        Schema::table('contatos', function (Blueprint $table) {
            $table->string('nome', 100)->nullable(false)->change();
            $table->string('telefone', 20)->nullable(false)->change();
        });
    }
};
