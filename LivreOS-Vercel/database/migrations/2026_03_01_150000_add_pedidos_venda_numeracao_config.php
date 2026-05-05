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

use App\Models\Configuracao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.mascara_numero'],
            ['valor' => 'PV{numero}']
        );

        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.numero_pad'],
            ['valor' => '6']
        );

        $max = 0;
        if (DB::getSchemaBuilder()->hasTable('pedidos_venda')) {
            foreach (DB::table('pedidos_venda')->whereNotNull('numero_sequencial')->pluck('numero_sequencial') as $ns) {
                if (preg_match('/(\d+)$/', (string) $ns, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        Configuracao::firstOrCreate(
            ['chave' => 'pedidos_venda.proximo_numero'],
            ['valor' => (string) ($max + 1)]
        );
    }

    public function down(): void
    {
        Configuracao::whereIn('chave', [
            'pedidos_venda.mascara_numero',
            'pedidos_venda.numero_pad',
            'pedidos_venda.proximo_numero',
        ])->delete();
    }
};
