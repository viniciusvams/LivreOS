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

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategoriaDocumentoSeeder::class,
            TabelaPrecoSeeder::class,
            ProdutoSeeder::class,
            TagSeeder::class,
            TermoGarantiaSeeder::class,
            StatusOsSeeder::class,
            ContaBancariaSeeder::class,
            FormaPagamentoSeeder::class,
            PlanoContaSeeder::class,
            ConfiguracoesFinanceiroSeeder::class,
            FinanceiroCategoriasTagsPadraoSeeder::class,
            CentrosCustoSecer::class,
            ConfiguracoesOsPadraoSeeder::class,
            AdquirenteSeeder::class,
            AdquirenteSumUpSeeder::class,
            AdquirenteSumUpFormasPagamentoSeeder::class,
            ConfiguracoesVendasEPropostasPadraoSeeder::class,
            OrdemServicoSeeder::class,
            PropostaDocumentoModeloPadraoSeeder::class,
        ]);
    }
}
