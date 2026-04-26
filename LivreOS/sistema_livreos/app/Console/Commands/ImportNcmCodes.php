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

use App\Models\NcmCode;
use Illuminate\Console\Command;

class ImportNcmCodes extends Command
{
    protected $signature = 'ncm:import {file : Caminho do CSV (codigo;descricao)}';
    protected $description = 'Importa a base oficial de NCM a partir de um CSV';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!is_file($file)) {
            $this->error('Arquivo não encontrado: ' . $file);
            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error('Não foi possível abrir o arquivo.');
            return self::FAILURE;
        }

        $count = 0;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = str_getcsv($line, ';');
            $codigo = preg_replace('/\D/', '', $parts[0] ?? '');
            $descricao = $parts[1] ?? null;

            if (strlen($codigo) !== 8) {
                continue;
            }

            NcmCode::updateOrCreate(
                ['codigo' => $codigo],
                ['descricao' => $descricao]
            );
            $count++;
        }

        fclose($handle);
        $this->info("NCM importados/atualizados: {$count}");
        return self::SUCCESS;
    }
}
