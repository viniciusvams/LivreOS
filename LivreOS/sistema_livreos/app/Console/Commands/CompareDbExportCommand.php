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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompareDbExportCommand extends Command
{
    protected $signature = 'erp:compare-db-export
                            {sql_file : Caminho do arquivo .sql exportado (Utilidades > Exportar somente banco)}';

    protected $description = 'Compara o banco atual (database.sqlite) com o arquivo SQL exportado e verifica se contém todos os dados.';

    public function handle(): int
    {
        $sqlPath = $this->argument('sql_file');

        if (! is_file($sqlPath)) {
            $this->error("Arquivo não encontrado: {$sqlPath}");

            return 1;
        }

        $driver = config('database.default');
        if ($driver !== 'sqlite') {
            $this->warn("Este comando foi testado com SQLite. Driver atual: {$driver}");
        }

        // 1) Tabelas e contagem no banco atual
        $tablesInDb = $this->getTablesFromDb();
        $countsDb = [];
        foreach ($tablesInDb as $t) {
            $countsDb[$t] = (int) DB::table($t)->count();
        }

        // 2) Tabelas e contagem no SQL exportado
        $sql = file_get_contents($sqlPath);
        $tablesInSql = $this->extractTableNamesFromSql($sql);
        $countsSql = [];
        foreach ($tablesInSql as $t) {
            $countsSql[$t] = $this->countInsertsInSql($sql, $t);
        }

        // 3) Comparação
        $allTables = array_unique(array_merge($tablesInDb, $tablesInSql));
        sort($allTables);

        $this->line('');
        $this->line('=== COMPARAÇÃO: banco atual vs SQL exportado ===');
        $this->line('');
        $this->line('Tabelas no banco: '.count($tablesInDb));
        $this->line('Tabelas no SQL:  '.count($tablesInSql));
        $this->line('');

        $missingInSql = array_diff($tablesInDb, $tablesInSql);
        $missingInDb = array_diff($tablesInSql, $tablesInDb);
        $ok = true;

        if (! empty($missingInSql)) {
            $this->error('Tabelas que existem no BANCO mas NÃO no SQL exportado:');
            foreach ($missingInSql as $t) {
                $this->line("  - {$t} (no banco: {$countsDb[$t]} linhas)");
                $ok = false;
            }
            $this->line('');
        }

        if (! empty($missingInDb)) {
            $this->warn('Tabelas que existem no SQL mas NÃO no banco (pode ser normal se o banco for mais novo):');
            foreach ($missingInDb as $t) {
                $this->line("  - {$t}");
            }
            $this->line('');
        }

        $this->line('Contagem de linhas por tabela (banco vs SQL):');
        $this->line(str_repeat('-', 65));
        $this->line(sprintf('%-42s %8s %8s %s', 'Tabela', 'Banco', 'SQL', 'Status'));
        $this->line(str_repeat('-', 65));

        foreach ($allTables as $t) {
            $cDb = $countsDb[$t] ?? 0;
            $cSql = $countsSql[$t] ?? 0;
            $status = ($cDb === $cSql) ? 'OK' : 'DIFERENCA';
            if ($cDb !== $cSql) {
                $ok = false;
            }
            $this->line(sprintf('%-42s %8d %8d %s', $t, $cDb, $cSql, $status));
        }

        $this->line(str_repeat('-', 65));
        $this->line('');

        if ($ok && empty($missingInSql)) {
            $this->info('Resultado: O arquivo exportado CONTÉM todos os dados do banco (mesmas tabelas e mesmas contagens).');
        } else {
            $this->warn('Resultado: Há diferenças. Verifique tabelas faltando no SQL ou contagens diferentes.');
        }

        return $ok ? 0 : 1;
    }

    private function getTablesFromDb(): array
    {
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            return array_column($rows, 'name');
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $db = config('database.connections.'.$driver.'.database');
            $rows = DB::select('SELECT TABLE_NAME as name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', [$db]);

            return array_column($rows, 'name');
        }
        $this->error("Driver não suportado: {$driver}");

        return [];
    }

    private function extractTableNamesFromSql(string $sql): array
    {
        if (! preg_match_all('/^-- Table:\s*(\w+)\s*$/m', $sql, $m)) {
            return [];
        }

        return array_values(array_unique($m[1]));
    }

    /** Conta ocorrências de INSERT INTO table_name (uma por linha no dump típico = 1 row). */
    private function countInsertsInSql(string $sql, string $tableName): int
    {
        $escaped = preg_quote($tableName, '/');

        return (int) preg_match_all('/^INSERT\s+INTO\s+'.$escaped.'\s+/mi', $sql);
    }
}
