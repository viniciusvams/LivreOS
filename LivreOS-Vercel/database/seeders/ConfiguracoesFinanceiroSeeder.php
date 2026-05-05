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

class ConfiguracoesFinanceiroSeeder extends Seeder
{
    /**
     * Valores padrão: Multa 10%, Juros 2% ao mês, Dias de carência 0.
     */
    public function run(): void
    {
        Configuracao::setValue('financeiro.multa_percentual', '10');
        Configuracao::setValue('financeiro.juros_mensal_percentual', '2');
        Configuracao::setValue('financeiro.dias_carencia_multa', '0');

        $this->command->info('Configurações Financeiro seedadas: Multa 10%, Juros 2% a.m., Carência 0 dia(s).');
    }
}
