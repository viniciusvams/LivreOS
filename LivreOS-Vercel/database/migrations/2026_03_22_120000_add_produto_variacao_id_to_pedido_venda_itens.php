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
        Schema::table('pedido_venda_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('produto_variacao_id')->nullable()->after('produto_id');
            $table->foreign('produto_variacao_id')
                ->references('id')
                ->on('produto_variacoes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_venda_itens', function (Blueprint $table) {
            $table->dropForeign(['produto_variacao_id']);
            $table->dropColumn('produto_variacao_id');
        });
    }
};
