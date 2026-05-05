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

use Illuminate\Database\Seeder;
use App\Models\TabelaPreco;

class TabelaPrecoSeeder extends Seeder
{
    public function run(): void
    {
        $tabelas = [
            ['nome' => 'Varejo', 'percentual_ajuste' => 0],
            ['nome' => 'Empresa', 'percentual_ajuste' => -15],
            ['nome' => 'Urgência', 'percentual_ajuste' => 20],
        ];

        foreach ($tabelas as $tabela) {
            TabelaPreco::updateOrCreate(
                ['nome' => $tabela['nome']],
                $tabela
            );
        }
    }
}
