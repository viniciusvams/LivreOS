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

namespace App\Console\Commands;

use App\Services\PluginManager;
use Illuminate\Console\Command;

/**
 * Após migrate/seed (ex.: instalador web): desativa todos os plugins que estavam ativos
 * e reativa em rodadas respeitando Requires Plugins, para executar novamente os hooks de ativação.
 */
class PluginsResyncAfterInstallCommand extends Command
{
    protected $signature = 'plugins:resync-after-install';

    protected $description = 'Desativa e reativa plugins ativos (hooks de ativação), útil após instalação/atualização';

    public function handle(PluginManager $pluginManager): int
    {
        $lines = [];
        $wasActive = $pluginManager->getActivePlugins();
        $lines[] = 'Plugins ativos antes: '.(empty($wasActive) ? '(nenhum)' : implode(', ', $wasActive));

        foreach (array_reverse($wasActive) as $slug) {
            $pluginManager->deactivate($slug);
            $lines[] = 'Desativado: '.$slug;
        }

        if ($wasActive === []) {
            $lines[] = 'Nenhum plugin para reativar.';
            $this->output->write(implode("\n", $lines));

            return self::SUCCESS;
        }

        $pending = array_values($wasActive);
        $round = 0;
        while ($pending !== [] && $round < 40) {
            $round++;
            $beforeCount = count($pending);
            foreach ($pending as $k => $slug) {
                $result = $pluginManager->activate($slug);
                if ($result['success']) {
                    $lines[] = 'Reativado: '.$slug;
                    unset($pending[$k]);
                } else {
                    $lines[] = 'Rodada '.$round.' — '.$slug.': '.($result['message'] ?? 'falha');
                }
            }
            $pending = array_values($pending);
            if (count($pending) === $beforeCount) {
                foreach ($pending as $slug) {
                    $lines[] = 'AVISO: não reativado após dependências: '.$slug;
                }
                break;
            }
        }

        $this->output->write(implode("\n", $lines));

        return self::SUCCESS;
    }
}
