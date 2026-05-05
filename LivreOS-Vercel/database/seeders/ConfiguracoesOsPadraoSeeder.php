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
use App\Models\PlanoConta;
use App\Models\CentroCusto;
use Illuminate\Database\Seeder;

class ConfiguracoesOsPadraoSeeder extends Seeder
{
    /**
     * Define os padrões para lançamentos do encerramento de OS:
     * - Plano de Contas: Receita de Serviços (1.1)
     * - Centro de Custo: OPE - Operacional
     */
    public function run(): void
    {
        $planoConta = PlanoConta::where('codigo', '1.1')
            ->orWhere('nome', 'Receita de Serviços')
            ->where('ativo', true)
            ->first();

        $centroCusto = CentroCusto::where('codigo', 'OPE')
            ->where('ativo', true)
            ->first();

        if ($planoConta) {
            Configuracao::setValue('plano_conta_os_encerramento', (string) $planoConta->id);
        }

        if ($centroCusto) {
            Configuracao::setValue('centro_custo_os_encerramento', (string) $centroCusto->id);
        }

        // WhatsApp de OS: copiar/colar em vez de API, até o utilizador configurar integração.
        Configuracao::setValue('whatsapp_modo', 'manual');
    }
}
