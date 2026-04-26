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

namespace Database\Seeders;

use App\Models\Configuracao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Padrão de fábrica: numeração de pedidos de venda e propostas comerciais (chaves em configuracoes).
 * Idempotente (firstOrCreate / setValue).
 */
class ConfiguracoesVendasEPropostasPadraoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPedidosVendaNumeracao();
        $this->seedPropostasComerciaisNumeracao();
    }

    private function seedPedidosVendaNumeracao(): void
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
        if (Schema::hasTable('pedidos_venda')) {
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

    private function seedPropostasComerciaisNumeracao(): void
    {
        Configuracao::firstOrCreate(
            ['chave' => 'propostas_comerciais.mascara_numero'],
            ['valor' => 'PC{numero}']
        );

        Configuracao::firstOrCreate(
            ['chave' => 'propostas_comerciais.numero_pad'],
            ['valor' => '6']
        );

        $max = 0;
        if (Schema::hasTable('propostas_comerciais')) {
            foreach (DB::table('propostas_comerciais')->whereNotNull('numero_sequencial')->pluck('numero_sequencial') as $ns) {
                if (preg_match('/(\d+)$/', (string) $ns, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        Configuracao::firstOrCreate(
            ['chave' => 'propostas_comerciais.proximo_numero'],
            ['valor' => (string) ($max + 1)]
        );
    }
}
