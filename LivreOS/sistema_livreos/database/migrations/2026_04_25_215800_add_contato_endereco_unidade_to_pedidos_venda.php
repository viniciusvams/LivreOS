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
            $table->foreignId('contato_id')->nullable()->after('cliente_id')->constrained('contatos')->nullOnDelete();
            $table->foreignId('endereco_id')->nullable()->after('contato_id')->constrained('enderecos')->nullOnDelete();
            $table->foreignId('cliente_unidade_id')->nullable()->after('endereco_id')->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_venda', function (Blueprint $table) {
            $table->dropForeign(['contato_id']);
            $table->dropForeign(['endereco_id']);
            $table->dropForeign(['cliente_unidade_id']);
            $table->dropColumn(['contato_id', 'endereco_id', 'cliente_unidade_id']);
        });
    }
};

