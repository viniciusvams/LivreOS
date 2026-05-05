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

use App\Models\StatusOs;
use Illuminate\Database\Seeder;

class StatusOsSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['nome' => 'Aberta', 'ordem' => 10, 'cor' => '#3b82f6', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Em análise/orçamento', 'ordem' => 20, 'cor' => '#8b5cf6', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Aguardando aprovação', 'ordem' => 30, 'cor' => '#f59e0b', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Aprovado pelo cliente', 'ordem' => 40, 'cor' => '#10b981', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Aguardando peças', 'ordem' => 50, 'cor' => '#6366f1', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Em execução', 'ordem' => 60, 'cor' => '#06b6d4', 'marca_inicio' => true, 'marca_conclusao' => false],
            ['nome' => 'Em teste', 'ordem' => 70, 'cor' => '#ec4899', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Concluída', 'ordem' => 80, 'cor' => '#22c55e', 'marca_inicio' => false, 'marca_conclusao' => true],
            ['nome' => 'Cancelada', 'ordem' => 90, 'cor' => '#94a3b8', 'marca_inicio' => false, 'marca_conclusao' => false],
            ['nome' => 'Encerrada', 'ordem' => 100, 'cor' => '#64748b', 'marca_inicio' => false, 'marca_conclusao' => false, 'sistema' => true],
        ];

        foreach ($statuses as $s) {
            $sistema = $s['sistema'] ?? false;
            unset($s['sistema']);
            $existe = StatusOs::where('nome', $s['nome'])->first();
            if (!$existe) {
                StatusOs::create(array_merge($s, ['ativo' => true, 'sistema' => $sistema]));
            } else {
                $update = ['ordem' => $s['ordem'], 'cor' => $s['cor'], 'marca_inicio' => $s['marca_inicio'], 'marca_conclusao' => $s['marca_conclusao'], 'sistema' => $sistema];
                if (!($existe->sistema ?? false)) {
                    $update['nome'] = $s['nome'];
                } else {
                    unset($update['sistema']);
                }
                $existe->update($update);
            }
        }
    }
}
