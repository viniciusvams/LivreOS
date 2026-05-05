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
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            if (!Schema::hasColumn('ordem_servicos', 'equipamento_publico_token')) {
                $table->string('equipamento_publico_token', 32)->unique()->nullable()->after('equipamento_acessorios');
            }
        });

        $this->backfillTokens();
    }

    private function backfillTokens(): void
    {
        $ordens = \DB::table('ordem_servicos')->whereNull('equipamento_publico_token')->get();
        foreach ($ordens as $os) {
            $token = null;
            for ($i = 0; $i < 10; $i++) {
                $t = Str::lower(Str::random(12));
                if (!\DB::table('ordem_servicos')->where('equipamento_publico_token', $t)->exists()) {
                    $token = $t;
                    break;
                }
            }
            if ($token) {
                \DB::table('ordem_servicos')->where('id', $os->id)->update(['equipamento_publico_token' => $token]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ordem_servicos', function (Blueprint $table) {
            $table->dropColumn('equipamento_publico_token');
        });
    }
};
