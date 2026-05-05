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
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->string('origem_tipo', 80)->nullable()->after('pagamento_recorrente_id');
            $table->string('entidade_tipo', 80)->nullable()->after('origem_tipo');
            $table->unsignedBigInteger('entidade_id')->nullable()->after('entidade_tipo');
            $table->json('metadata')->nullable()->after('observacoes');
            $table->string('referencia_externa', 255)->nullable()->after('metadata');

            $table->index('origem_tipo');
            $table->index('entidade_tipo');
            $table->index('entidade_id');
            $table->index('referencia_externa');
        });

        // Backfill: contas que hoje são identificadas por descrição como adiantamento
        DB::table('contas_receber')
            ->whereNotNull('ordem_servico_id')
            ->where('descricao', 'like', 'Adiantamento %')
            ->update([
                'origem_tipo' => 'adiantamento_os',
                'entidade_tipo' => 'ordem_servico',
                'entidade_id' => DB::raw('ordem_servico_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('contas_receber', function (Blueprint $table) {
            $table->dropIndex(['origem_tipo']);
            $table->dropIndex(['entidade_tipo']);
            $table->dropIndex(['entidade_id']);
            $table->dropIndex(['referencia_externa']);
            $table->dropColumn([
                'origem_tipo',
                'entidade_tipo',
                'entidade_id',
                'metadata',
                'referencia_externa',
            ]);
        });
    }
};
