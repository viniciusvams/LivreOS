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

use App\Models\Adquirente;
use Illuminate\Database\Seeder;

/**
 * Só associa SumUp às formas Cartão de Crédito e Débito (idempotente).
 * Use quando a SumUp já existir e você não quiser rodar o AdquirenteSumUpSeeder completo.
 */
class AdquirenteSumUpFormasPagamentoSeeder extends Seeder
{
    public function run(): void
    {
        $a = Adquirente::where('codigo', 'SUMUP')->first();
        if (! $a) {
            if ($this->command) {
                $this->command->warn('Adquirente SumUp não encontrada. Execute: php artisan db:seed --class=AdquirenteSumUpSeeder');
            }

            return;
        }

        (new AdquirenteSumUpSeeder)->vincularFormasPagamentoCartao($a);
    }
}
