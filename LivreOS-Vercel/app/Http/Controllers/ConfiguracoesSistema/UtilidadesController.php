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

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Role;
use App\Models\User;
use App\Services\ZerarFabricaService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use ZipArchive;

class UtilidadesController extends Controller
{
    /**
     * Soma aproximada do tamanho dos ficheiros por ZIP de anexos no backup automático.
     * Vários ficheiros menores reduzem pico de memória e tempo contínuo em hospedagem partilhada.
     */
    private const BACKUP_ANEXOS_PART_MAX_BYTES = 28 * 1024 * 1024;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $backups = $this->listarBackupsDisponiveis();
        // Preferir log vindo da sessão (flash após restauração) para garantir exibição na tela
        $restoreLog = session('restore_log');
        if ($restoreLog === null || $restoreLog === '') {
            $restoreLogPath = session('restore_log_path');
            if ($restoreLogPath && File::exists($restoreLogPath)) {
                $restoreLog = File::get($restoreLogPath);
            }
        }

        $importLog = null;
        $importLogPath = session('import_log_path');
        if ($importLogPath && File::exists($importLogPath)) {
            $importLog = File::get($importLogPath);
        }

        $exportParts = null;
        $exportPartsSet = (string) session('export_parts_set', '');
        if ($exportPartsSet !== '') {
            $partsBaseDir = storage_path('app/temp/export_parts/'.$exportPartsSet);
            $manifestPath = $partsBaseDir.DIRECTORY_SEPARATOR.'manifest.json';
            if (File::isDirectory($partsBaseDir) && File::exists($manifestPath)) {
                $manifest = json_decode((string) File::get($manifestPath), true);
                if (is_array($manifest)) {
                    $files = [];
                    foreach ((array) ($manifest['parts'] ?? []) as $part) {
                        $partName = basename((string) $part);
                        $partPath = $partsBaseDir.DIRECTORY_SEPARATOR.$partName;
                        if (! File::exists($partPath)) {
                            continue;
                        }
                        $isFirstPart = count($files) === 0;
                        $files[] = [
                            'name' => $partName,
                            'size' => (int) File::size($partPath),
                            'is_first' => $isFirstPart,
                            'url' => route('configuracoes-sistema.utilidades.export-part.download', [
                                'set' => $exportPartsSet,
                                'arquivo' => $partName,
                            ]),
                            'url_with_manifest' => $isFirstPart ? route('configuracoes-sistema.utilidades.export-part.download', [
                                'set' => $exportPartsSet,
                                'arquivo' => $partName,
                                'with_manifest' => 1,
                            ]) : null,
                        ];
                    }
                    $manifestUrl = route('configuracoes-sistema.utilidades.export-part.download', [
                        'set' => $exportPartsSet,
                        'arquivo' => 'manifest.json',
                    ]);
                    $exportParts = [
                        'set' => $exportPartsSet,
                        'manifest' => $manifest,
                        'manifest_url' => $manifestUrl,
                        'files' => $files,
                    ];
                }
            }
        }

        return erp_view('configuracoes_sistema.utilidades.index', [
            'title' => 'Utilidades — Exportar e Importar',
            'backups' => $backups,
            'restore_log' => $restoreLog,
            'import_log' => $importLog,
            'export_parts' => $exportParts,
        ]);
    }

    /**
     * Lista pastas em storage/app/backups que contêm dump.sql.
     * Retorna nome, path, data em formato legível e rótulo (cópia antes de zerar vs antes de restaurar).
     *
     * @return array<int, array{nome: string, path: string, data_formatada: string, tipo_label: string}>
     */
    protected function listarBackupsDisponiveis(): array
    {
        $backupsBase = storage_path('app/backups');
        if (! File::isDirectory($backupsBase)) {
            return [];
        }
        $backups = [];
        foreach (File::directories($backupsBase) as $dir) {
            $nome = basename($dir);
            if (! File::exists($dir.DIRECTORY_SEPARATOR.'dump.sql')) {
                continue;
            }
            $parsed = $this->parseBackupFolderName($nome);
            $backups[] = [
                'nome' => $nome,
                'path' => $dir,
                'data_formatada' => $parsed['data_formatada'],
                'tipo_label' => $parsed['tipo_label'],
            ];
        }
        usort($backups, fn ($a, $b) => strcmp($b['nome'], $a['nome']));

        return array_values($backups);
    }

    /**
     * Extrai data/hora e tipo do nome da pasta de backup (ex.: pre-reset-2026-03-15_212029).
     *
     * @return array{data_formatada: string, tipo_label: string}
     */
    protected function parseBackupFolderName(string $nome): array
    {
        $dataFormatada = $nome;
        $tipoLabel = 'Backup';
        if (preg_match('/^pre-(reset|restore)-(\d{4})-(\d{2})-(\d{2})_(\d{2})(\d{2})(\d{2})$/', $nome, $m)) {
            $ano = $m[2];
            $mes = (int) $m[3];
            $dia = $m[4];
            $h = $m[5];
            $min = $m[6];
            $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
            $mesNome = $meses[$mes] ?? $m[3];
            $dataFormatada = (int) $dia.' de '.$mesNome.' de '.$ano.', '.$h.':'.$min;
            $tipoLabel = $m[1] === 'reset'
                ? 'Cópia de segurança (antes de zerar o sistema)'
                : 'Cópia do estado atual (antes de uma restauração)';
        }

        return ['data_formatada' => $dataFormatada, 'tipo_label' => $tipoLabel];
    }

    /**
     * Exportar: completo | banco | anexos
     */
    public function export(Request $request, string $tipo)
    {
        if (! in_array($tipo, ['completo', 'banco', 'anexos'], true)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Tipo de exportação inválido.');
        }

        $particionado = $request->boolean('particionado');
        $chunkMb = (int) $request->input('chunk_mb', 50);
        if ($chunkMb < 1) {
            $chunkMb = 50;
        }
        if ($chunkMb > 1024) {
            $chunkMb = 1024;
        }
        $chunkBytes = $chunkMb * 1024 * 1024;

        if ($tipo === 'banco') {
            return $this->downloadDumpBanco();
        }
        if ($tipo === 'anexos') {
            return $this->downloadZipAnexos($particionado, $chunkBytes);
        }

        return $this->downloadExportCompleto($particionado, $chunkBytes);
    }

    protected function downloadDumpBanco()
    {
        $this->limparExportsTempAntigos();

        $driver = config('database.default');
        $filename = 'erp-backup-banco-'.date('Y-m-d_His').'.sql';
        $dumpPath = storage_path('app/temp/'.$filename);
        if (! File::isDirectory(dirname($dumpPath))) {
            File::makeDirectory(dirname($dumpPath), 0755, true);
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            if ($this->dumpMysqlToFile($dumpPath, 300)) {
                // Sem deleteFileAfterSend: em alguns hosts (LiteSpeed/cPanel) o Symfony tentava unlink duas vezes e gerava ErrorException.
                return response()->download($dumpPath, $filename);
            }
        }

        if ($driver === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if (! file_exists($database)) {
                return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Arquivo do banco SQLite não encontrado.');
            }
            if (! $this->gerarDumpSqliteParaArquivo($database, $dumpPath)) {
                return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Erro ao gerar dump do banco SQLite.');
            }

            return response()->download($dumpPath, $filename);
        }

        return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Exportação de banco não suportada para o driver atual.');
    }

    /**
     * Cria backup completo (dump.sql + anexos) na pasta informada.
     * Usado pelo "Zerar sistema" e pela "Restauração" (cópia do estado atual).
     * Anexos: por defeito em vários ficheiros {@see anexos-part-001.zip}… para aliviar memória/tempo em hospedagem partilhada;
     * restauração aceita ainda {@see anexos.zip} legado.
     *
     * @param  array{anexos?: 'partes'|'unico'|'omitir'}  $opcoes  anexos: partes (padrão), unico (um ZIP), omitir (só dump.sql)
     *
     * @throws \RuntimeException em caso de falha
     */
    public function criarBackupParaPasta(string $dir, array $opcoes = []): void
    {
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $modoAnexos = $opcoes['anexos'] ?? 'partes';
        if (! in_array($modoAnexos, ['partes', 'unico', 'omitir'], true)) {
            $modoAnexos = 'partes';
        }

        $driver = config('database.default');
        $dumpPath = $dir.DIRECTORY_SEPARATOR.'dump.sql';

        if ($driver === 'mysql' || $driver === 'mariadb') {
            if (! $this->dumpMysqlToFile($dumpPath, 300)) {
                throw new \RuntimeException('Falha ao gerar dump do banco MySQL/MariaDB.');
            }
        } elseif ($driver === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if (! file_exists($database)) {
                throw new \RuntimeException('Arquivo do banco SQLite não encontrado.');
            }
            if (! $this->gerarDumpSqliteParaArquivo($database, $dumpPath)) {
                throw new \RuntimeException('Erro ao gerar dump do banco SQLite.');
            }
        } else {
            throw new \RuntimeException('Backup de banco não suportado para o driver: '.$driver);
        }

        if ($modoAnexos === 'omitir') {
            return;
        }

        $storagePath = storage_path('app/public');
        if (! File::isDirectory($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        if ($modoAnexos === 'unico') {
            $this->criarAnexosZipUnicoNoBackupDir($dir, $storagePath);

            return;
        }

        $this->criarAnexosZipEmPartesNoBackupDir($dir, $storagePath, self::BACKUP_ANEXOS_PART_MAX_BYTES);
    }

    /**
     * Um único anexos.zip (comportamento antigo).
     */
    protected function criarAnexosZipUnicoNoBackupDir(string $dir, string $storagePath): void
    {
        $zipPath = $dir.DIRECTORY_SEPARATOR.'anexos.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo anexos.zip no backup.');
        }
        $this->addDirToZip($zip, $storagePath, 'anexos');
        $zip->close();
    }

    /**
     * Vários anexos-part-NNN.zip, rolando o ZIP quando a soma dos tamanhos dos ficheiros excede o limite.
     *
     * @param  int  $maxBytesSomaFicheiros  referência à soma dos tamanhos em disco (não ao tamanho do ZIP)
     */
    protected function criarAnexosZipEmPartesNoBackupDir(string $dir, string $storagePath, int $maxBytesSomaFicheiros): void
    {
        $items = File::allFiles($storagePath);
        if ($items === []) {
            return;
        }

        usort($items, fn ($a, $b) => strcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname()));

        $maxBytes = max(5 * 1024 * 1024, $maxBytesSomaFicheiros);
        $partIndex = 0;
        $zip = null;
        $accum = 0;

        $fecharZip = function () use (&$zip): void {
            if ($zip instanceof ZipArchive) {
                $zip->close();
                $zip = null;
            }
        };

        foreach ($items as $file) {
            $path = $file->getPathname();
            if (! File::isFile($path)) {
                continue;
            }
            $size = (int) File::size($path);
            $precisaRodar = $zip instanceof ZipArchive && $accum > 0 && ($accum + $size > $maxBytes);
            if ($precisaRodar) {
                $fecharZip();
                $accum = 0;
            }
            if (! $zip instanceof ZipArchive) {
                $partIndex++;
                $zipPath = $dir.DIRECTORY_SEPARATOR.'anexos-part-'.str_pad((string) $partIndex, 3, '0', STR_PAD_LEFT).'.zip';
                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    $fecharZip();
                    throw new \RuntimeException('Não foi possível criar o arquivo ZIP de anexos: '.basename($zipPath));
                }
                $accum = 0;
            }
            $relative = 'anexos/'.$file->getRelativePathname();
            $zip->addFile($path, str_replace('\\', '/', $relative));
            $accum += $size;
        }

        $fecharZip();
    }

    /**
     * Lista ficheiros ZIP de anexos numa pasta de backup: anexos.zip legado ou anexos-part-*.zip por ordem.
     *
     * @return list<string> caminhos absolutos
     */
    protected function listarArquivosZipAnexosDoBackup(string $backupDir): array
    {
        $legacy = $backupDir.DIRECTORY_SEPARATOR.'anexos.zip';
        if (File::exists($legacy)) {
            return [$legacy];
        }

        $glob = glob($backupDir.DIRECTORY_SEPARATOR.'anexos-part-*.zip', GLOB_NOSORT) ?: [];
        usort($glob, 'strnatcasecmp');

        return array_values(array_filter($glob, fn (string $p) => File::isFile($p)));
    }

    protected function relaxarLimitesProcessoLongo(): void
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
    }

    /**
     * Gera dump SQL do banco SQLite (retorna string SQL ou null em erro).
     */
    protected function gerarDumpSqlite(string $databasePath): ?string
    {
        // Compatibilidade legado: manter retorno em string somente onde ainda for necessário.
        $tmp = tempnam(sys_get_temp_dir(), 'erp_sqlite_dump_');
        if ($tmp === false) {
            return null;
        }
        if (! $this->gerarDumpSqliteParaArquivo($databasePath, $tmp)) {
            @unlink($tmp);
            return null;
        }
        $out = File::get($tmp);
        @unlink($tmp);

        return $out;
    }

    protected function gerarDumpSqliteParaArquivo(string $databasePath, string $outputPath): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$databasePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $out = fopen($outputPath, 'wb');
            if ($out === false) {
                return false;
            }
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $create = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=".$pdo->quote($table))->fetchColumn();
                if ($create) {
                    fwrite($out, "-- Table: {$table}\n");
                    fwrite($out, 'DROP TABLE IF EXISTS '.$table.";\n");
                    fwrite($out, $create.";\n\n");
                }
                $stmt = $pdo->query('SELECT * FROM '.$table);
                $cols = [];
                $hasDeletedAt = false;
                $hasRows = false;
                while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                    if (! $hasRows) {
                        $cols = array_keys($row);
                        $hasDeletedAt = in_array('deleted_at', $cols, true);
                        $hasRows = true;
                    }

                    if ($table === 'socios' && array_key_exists('tipo_assinatura', $row) && $row['tipo_assinatura'] === '') {
                        $row['tipo_assinatura'] = null;
                    }
                    if ($table === 'produtos') {
                        foreach (['formato', 'tipo', 'condicao', 'producao', 'tributacao_origem'] as $col) {
                            if (array_key_exists($col, $row) && $row[$col] === '') {
                                $row[$col] = in_array($col, ['formato', 'tipo'], true) ? ($col === 'formato' ? 'simples' : 'produto') : null;
                            }
                        }
                    }
                    // Na exportação: deleted_at vazio ou null vira NULL no dump (evita '' que esconde registros na importação)
                    if ($hasDeletedAt && (array_key_exists('deleted_at', $row) && ($row['deleted_at'] === '' || $row['deleted_at'] === null))) {
                        $row['deleted_at'] = null;
                    }
                    $vals = [];
                    foreach ($cols as $col) {
                        $v = $row[$col] ?? null;
                        if ($v === null) {
                            $vals[] = 'NULL';
                        } elseif ($col === 'deleted_at' && $v === '') {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote((string) $v);
                        }
                    }
                    fwrite($out, 'INSERT INTO '.$table.' ('.implode(',', $cols).') VALUES ('.implode(',', $vals).");\n");
                }
                if ($hasRows) {
                    fwrite($out, "\n");
                }
            }
            fclose($out);
            return true;
        } catch (\Throwable $e) {
            if (isset($out) && is_resource($out)) {
                fclose($out);
            }
            return false;
        }
    }

    protected function dumpMysqlToFile(string $dumpPath, int $timeoutSeconds = 300): bool
    {
        $driver = config('database.default');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $db = config('database.connections.'.$driver);
        $cmd = [
            'mysqldump',
            '--host='.($db['host'] ?? '127.0.0.1'),
            '--port='.($db['port'] ?? '3306'),
            '--user='.($db['username'] ?? 'root'),
            '--password='.($db['password'] ?? ''),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--result-file='.$dumpPath,
            ($db['database'] ?? 'laravel'),
        ];

        $process = new Process($cmd);
        $process->setTimeout($timeoutSeconds);
        $process->run();

        return $process->isSuccessful() && File::exists($dumpPath) && File::size($dumpPath) > 0;
    }

    protected function downloadZipAnexos(bool $particionado = false, int $chunkBytes = 52428800)
    {
        $this->limparExportsTempAntigos();

        $storagePath = storage_path('app/public');
        if (! File::isDirectory($storagePath)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Pasta de anexos não encontrada.');
        }
        $zipPath = storage_path('app/temp/erp-anexos-'.date('Y-m-d_His').'.zip');
        if (! File::isDirectory(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Não foi possível criar o arquivo ZIP.');
        }
        $this->addDirToZip($zip, $storagePath, 'anexos');
        $zip->close();

        if ($particionado) {
            return $this->downloadZipParticionado($zipPath, 'erp-anexos-'.date('Y-m-d_His').'.zip', $chunkBytes);
        }

        return response()->download($zipPath, 'erp-anexos-'.date('Y-m-d_His').'.zip');
    }

    protected function addDirToZip(ZipArchive $zip, string $dir, string $localPrefix): void
    {
        $items = File::allFiles($dir);
        foreach ($items as $file) {
            $relative = $localPrefix.'/'.$file->getRelativePathname();
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
    }

    protected function downloadExportCompleto(bool $particionado = false, int $chunkBytes = 52428800)
    {
        $this->limparExportsTempAntigos();

        $zipPath = storage_path('app/temp/erp-export-completo-'.date('Y-m-d_His').'.zip');
        if (! File::isDirectory(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Não foi possível criar o arquivo ZIP.');
        }

        $driver = config('database.default');
        $sqlFile = storage_path('app/temp/dump-completo-'.date('Y-m-d_His').'.sql');
        $dumpOk = false;
        if ($driver === 'mysql' || $driver === 'mariadb') {
            if ($this->dumpMysqlToFile($sqlFile, 300)) {
                $zip->addFile($sqlFile, 'dump.sql');
                $dumpOk = true;
            }
        } elseif ($driver === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if (file_exists($database)) {
                if ($this->gerarDumpSqliteParaArquivo($database, $sqlFile)) {
                    $zip->addFile($sqlFile, 'dump.sql');
                    $dumpOk = true;
                }
            }
        }

        if (! $dumpOk) {
            $zip->close();
            if (File::exists($sqlFile)) {
                File::delete($sqlFile);
            }
            File::delete($zipPath);
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Falha ao gerar dump do banco para o export completo. Tente novamente ou reduza o volume de dados.');
        }

        $storagePath = storage_path('app/public');
        if (File::isDirectory($storagePath)) {
            $this->addDirToZip($zip, $storagePath, 'anexos');
        }

        $zip->close();
        if (File::exists($sqlFile)) {
            File::delete($sqlFile);
        }

        if ($particionado) {
            return $this->downloadZipParticionado($zipPath, 'erp-export-completo-'.date('Y-m-d_His').'.zip', $chunkBytes);
        }

        return response()->download($zipPath, 'erp-export-completo-'.date('Y-m-d_His').'.zip');
    }

    protected function downloadZipParticionado(string $sourceZipPath, string $nomeOriginal, int $chunkBytes)
    {
        if (! File::exists($sourceZipPath)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Arquivo de exportação não encontrado para particionar.');
        }
        $setId = date('YmdHis').'_'.substr((string) uniqid('', true), -8);
        $partsDir = storage_path('app/temp/export_parts/'.$setId);
        File::makeDirectory($partsDir, 0755, true);

        $handle = fopen($sourceZipPath, 'rb');
        if ($handle === false) {
            File::delete($sourceZipPath);
            File::deleteDirectory($partsDir);
            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Falha ao abrir exportação para particionamento.');
        }

        $sourceSize = (int) File::size($sourceZipPath);
        $parts = [];
        $idx = 1;
        $bufferBytes = 4 * 1024 * 1024; // 4MB por leitura para evitar pico de memória
        $hashOriginal = hash_init('sha256');
        $partsHash = [];
        $writtenTotal = 0;
        $baseName = preg_replace('/\.zip$/i', '', $nomeOriginal) ?: $nomeOriginal;
        while (! feof($handle)) {
            $partName = $baseName.'.part'.str_pad((string) $idx, 3, '0', STR_PAD_LEFT).'.zip';
            $partPath = $partsDir.DIRECTORY_SEPARATOR.$partName;
            $partHandle = fopen($partPath, 'wb');
            if ($partHandle === false) {
                fclose($handle);
                File::delete($sourceZipPath);
                File::deleteDirectory($partsDir);
                return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Falha ao criar arquivo de parte.');
            }

            $writtenInPart = 0;
            $hashPart = hash_init('sha256');
            while ($writtenInPart < $chunkBytes && ! feof($handle)) {
                $toRead = min($bufferBytes, $chunkBytes - $writtenInPart);
                $buf = fread($handle, $toRead);
                if ($buf === false) {
                    fclose($partHandle);
                    fclose($handle);
                    File::delete($sourceZipPath);
                    File::deleteDirectory($partsDir);
                    return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Falha de leitura durante particionamento.');
                }
                if ($buf === '') {
                    break;
                }
                hash_update($hashOriginal, $buf);
                hash_update($hashPart, $buf);
                $len = strlen($buf);
                $offset = 0;
                while ($offset < $len) {
                    $bytes = fwrite($partHandle, substr($buf, $offset));
                    if ($bytes === false || $bytes === 0) {
                        fclose($partHandle);
                        fclose($handle);
                        File::delete($sourceZipPath);
                        File::deleteDirectory($partsDir);
                        return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', 'Falha de escrita durante particionamento.');
                    }
                    $offset += (int) $bytes;
                    $writtenInPart += (int) $bytes;
                }
            }
            fclose($partHandle);

            if ($writtenInPart <= 0) {
                if (File::exists($partPath)) {
                    File::delete($partPath);
                }
                break;
            }

            $parts[] = $partName;
            $partsHash[$partName] = hash_final($hashPart);
            $writtenTotal += $writtenInPart;
            $idx++;
        }
        fclose($handle);

        if ($sourceSize > 0 && $writtenTotal !== $sourceSize) {
            File::deleteDirectory($partsDir);
            File::delete($sourceZipPath);
            return redirect()->route('configuracoes-sistema.utilidades.index')->with(
                'error',
                'Falha ao particionar: partes não cobrem todo o ZIP. Reduza "Tamanho da parte (MB)" e tente novamente.'
            );
        }

        $manifest = [
            'version' => 1,
            'original_name' => $nomeOriginal,
            'original_size' => $sourceSize,
            'original_sha256' => hash_final($hashOriginal),
            'part_size_bytes' => $chunkBytes,
            'total_parts' => count($parts),
            'parts' => $parts,
            'parts_sha256' => $partsHash,
        ];
        File::put($partsDir.DIRECTORY_SEPARATOR.'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        File::delete($sourceZipPath);

        return redirect()
            ->route('configuracoes-sistema.utilidades.index')
            ->with('success', 'Exportação em partes concluída. Baixe os arquivos gerados abaixo.')
            ->with('export_parts_set', $setId);
    }

    public function downloadExportPart(Request $request, string $set, string $arquivo)
    {
        $this->limparExportsTempAntigos();

        $set = preg_replace('/[^a-zA-Z0-9_\-]/', '', $set) ?? '';
        $arquivo = basename($arquivo);
        if ($set === '' || $arquivo === '') {
            abort(404);
        }

        $partsDir = storage_path('app/temp/export_parts/'.$set);
        $filePath = $partsDir.DIRECTORY_SEPARATOR.$arquivo;
        if (! File::isDirectory($partsDir) || ! File::exists($filePath)) {
            abort(404);
        }

        if ($request->boolean('with_manifest')) {
            $manifestPath = $partsDir.DIRECTORY_SEPARATOR.'manifest.json';
            if (! File::exists($manifestPath) || strcasecmp(pathinfo($arquivo, PATHINFO_EXTENSION), 'json') === 0) {
                return response()->download($filePath, $arquivo);
            }

            $bundlePath = storage_path('app/temp/export_parts/'.$set.'/'.basename($arquivo).'.with-manifest.zip');
            $zip = new ZipArchive;
            if ($zip->open($bundlePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($filePath, basename($arquivo));
                $zip->addFile($manifestPath, 'manifest.json');
                $zip->close();

                return response()->download($bundlePath, basename($arquivo).'.with-manifest.zip');
            }
        }

        return response()->download($filePath, $arquivo);
    }

    public function importProcess(Request $request)
    {
        // Slug legado de importação completa (compat. formulários em cache).
        if ($request->input('tipo') === base64_decode('bWFwb3NfZnVsbA==')) {
            $request->merge(['tipo' => 'mos_export_full']);
        }

        $allowedTypes = $this->getAllowedImportTypes();

        $request->validate([
            'tipo' => ['required', Rule::in($allowedTypes)],
            'arquivo' => 'nullable|file|max:512000',
            'arquivos' => 'nullable|array',
            'arquivos.*' => 'file|max:512000',
            'arquivos_partes' => 'nullable|array',
            'arquivos_partes.*' => 'file|max:512000',
            'seq_token' => 'nullable|string|max:128',
            'part_index' => 'nullable|integer|min:1',
            'part_total' => 'nullable|integer|min:1',
            'arquivo_part' => 'nullable|file|max:512000',
        ], [
            'tipo.required' => 'Selecione o tipo de importação.',
            'tipo.in' => 'Tipo inválido.',
        ]);

        $tipo = (string) $request->input('tipo');

        // Importação sequencial das partes (evita falhas imediatas e permite logs por etapa).
        $seqToken = (string) $request->input('seq_token', '');
        $partIndex = (int) $request->input('part_index', 0);
        $partTotal = (int) $request->input('part_total', 0);
        if (
            $tipo === 'completo'
            && $seqToken !== ''
            && $partIndex >= 1
            && $partTotal >= 1
            && $request->hasFile('arquivo_part')
        ) {
            /** @var UploadedFile $arquivoPart */
            $arquivoPart = $request->file('arquivo_part');
            if (! $arquivoPart instanceof UploadedFile || ! $arquivoPart->isValid()) {
                return redirect()->back()->with('error', 'Parte inválida.');
            }

            return $this->importCompletoPartesSequencialHtml($seqToken, $partIndex, $partTotal, $arquivoPart);
        }

        if ($tipo === 'mos_export_full') {
            $filesList = [];
            foreach ((array) $request->file('arquivos', []) as $f) {
                if ($f instanceof UploadedFile && $f->isValid()) {
                    $filesList[] = $f;
                }
            }
            if ($filesList === [] && $request->hasFile('arquivo')) {
                $f = $request->file('arquivo');
                if ($f instanceof UploadedFile && $f->isValid()) {
                    $filesList = [$f];
                }
            }
            if ($filesList === []) {
                return redirect()->back()->withErrors(['arquivo' => 'Selecione um ou mais arquivos (.zip ou .sql).'])->withInput();
            }
            foreach ($filesList as $f) {
                $e = strtolower($f->getClientOriginalExtension());
                if (! in_array($e, ['zip', 'sql'], true)) {
                    return redirect()->back()->withErrors(['arquivos' => 'Use apenas arquivos .zip ou .sql.'])->withInput();
                }
            }

            if (function_exists('apply_filters')) {
                $handledMany = apply_filters('utilidades.import.handle_many', null, $tipo, $filesList, $request);
                if ($handledMany instanceof Response) {
                    return $handledMany;
                }
            }

            $file = $filesList[0];
            $ext = strtolower($file->getClientOriginalExtension());

            if (function_exists('apply_filters')) {
                $handled = apply_filters('utilidades.import.handle', null, $tipo, $file, $ext, $request);
                if ($handled instanceof Response) {
                    return $handled;
                }
            }

            return redirect()->back()->with('error', 'Tipo de importação não tratado.');
        }

        if (! $request->hasFile('arquivo')) {
            return redirect()->back()->withErrors(['arquivo' => 'Selecione o arquivo.'])->withInput();
        }

        $file = $request->file('arquivo');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['zip', 'sql'], true)) {
            return redirect()->back()->withErrors(['arquivo' => 'Use um arquivo .zip ou .sql.'])->withInput();
        }

        if ($tipo === 'banco' && $request->boolean('stream')) {
            if ($ext === 'sql') {
                return $this->importProcessStreamBanco($file->getRealPath(), basename($file->getClientOriginalName()));
            }
            if ($ext === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($file->getRealPath()) !== true) {
                    return redirect()->back()->with('error', 'Arquivo ZIP inválido.');
                }
                $sqlContent = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (preg_match('/\.sql$/i', $name) || $name === 'dump.sql') {
                        $sqlContent = $zip->getFromIndex($i);
                        break;
                    }
                }
                $zip->close();
                if ($sqlContent === null) {
                    return redirect()->back()->with('error', 'Nenhum arquivo .sql encontrado no ZIP.');
                }
                $tmp = tempnam(sys_get_temp_dir(), 'erp_import');
                File::put($tmp, $sqlContent);

                return $this->importProcessStreamBanco($tmp, 'dump.sql', true);
            }
        }

        if ($tipo === 'banco') {
            if ($ext === 'sql') {
                return $this->importarSql($file);
            }
            if ($ext === 'zip') {
                return $this->importarBancoDeZip($file);
            }

            return redirect()->back()->with('error', 'Para importar só o banco, use um arquivo .sql ou .zip contendo dump.sql.');
        }

        if ($tipo === 'anexos') {
            $partes = (array) $request->file('arquivos_partes', []);
            $partes = array_values(array_filter($partes, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
            if ($partes !== []) {
                $uploaded = $this->recomporUploadParticionado($partes);
                if ($uploaded === null) {
                    return redirect()->back()->with('error', 'Não foi possível recompor as partes do arquivo de anexos.');
                }
                if ($request->boolean('stream')) {
                    return $this->importProcessStreamAnexos($uploaded);
                }

                return $this->importarAnexosZip($uploaded);
            }

            if ($ext !== 'zip') {
                return redirect()->back()->with('error', 'Para importar anexos use um arquivo .zip ou múltiplas partes.');
            }
            if ($request->boolean('stream')) {
                return $this->importProcessStreamAnexos($file);
            }

            return $this->importarAnexosZip($file);
        }

        if ($tipo === 'completo') {
            $partes = (array) $request->file('arquivos_partes', []);
            $partes = array_values(array_filter($partes, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
            if ($partes !== []) {
                $uploaded = $this->recomporUploadParticionado($partes);
                if ($uploaded === null) {
                    return redirect()->back()->with('error', 'Não foi possível recompor as partes do backup completo.');
                }
                if ($request->boolean('stream')) {
                    return $this->importProcessStreamCompleto($uploaded);
                }

                return $this->importarCompletoZip($uploaded);
            }

            if ($ext !== 'zip') {
                return redirect()->back()->with('error', 'Para importação completa use um arquivo .zip exportado pelo sistema (ou múltiplas partes).');
            }
            if ($request->boolean('stream')) {
                return $this->importProcessStreamCompleto($file);
            }

            return $this->importarCompletoZip($file);
        }

        if (function_exists('apply_filters')) {
            $handled = apply_filters('utilidades.import.handle', null, $tipo, $file, $ext, $request);
            if ($handled instanceof Response) {
                return $handled;
            }
        }

        return redirect()->back()->with('error', 'Tipo de importação não tratado.');
    }

    protected function recomporUploadParticionado(array $files): ?UploadedFile
    {
        if ($files === []) {
            return null;
        }

        $tmpDir = storage_path('app/temp/import_parts_'.uniqid());
        File::makeDirectory($tmpDir, 0755, true);

        // Normaliza entrada: aceita partes diretas, ZIP único com manifest+parts e modo misto (parte1+manifest + demais partes).
        foreach ($files as $f) {
            if (! ($f instanceof UploadedFile) || ! $f->isValid()) {
                continue;
            }
            $originalName = basename((string) $f->getClientOriginalName());
            $ext = strtolower((string) $f->getClientOriginalExtension());

            if ($ext === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($f->getRealPath()) === true) {
                    $hasManifestInside = $zip->locateName('manifest.json', ZipArchive::FL_NODIR) !== false;
                    if ($hasManifestInside) {
                        if (! $zip->extractTo($tmpDir)) {
                            $zip->close();
                            File::deleteDirectory($tmpDir);
                            return null;
                        }
                        $zip->close();
                        continue;
                    }
                    $zip->close();
                }
            }

            $dest = $tmpDir.DIRECTORY_SEPARATOR.$originalName;
            if (! @copy($f->getRealPath(), $dest)) {
                File::deleteDirectory($tmpDir);
                return null;
            }
        }

        $manifestPath = $tmpDir.DIRECTORY_SEPARATOR.'manifest.json';
        if (File::exists($manifestPath)) {
            $manifest = json_decode((string) File::get($manifestPath), true);
            if (! is_array($manifest) || empty($manifest['parts']) || ! is_array($manifest['parts'])) {
                File::deleteDirectory($tmpDir);
                return null;
            }
            $outputPath = $tmpDir.DIRECTORY_SEPARATOR.basename((string) ($manifest['original_name'] ?? 'recomposto.zip'));
            if (! str_ends_with(strtolower($outputPath), '.zip')) {
                $outputPath .= '.zip';
            }
        } else {
            $partFiles = [];
            foreach ((array) File::files($tmpDir) as $file) {
                $name = $file->getFilename();
                if (preg_match('/part(\d+)/i', $name)) {
                    $partFiles[] = $name;
                }
            }
            if ($partFiles === []) {
                File::deleteDirectory($tmpDir);
                return null;
            }
            usort($partFiles, function ($a, $b) {
                preg_match('/part(\d+)/i', (string) $a, $ma);
                preg_match('/part(\d+)/i', (string) $b, $mb);
                $ia = isset($ma[1]) ? (int) $ma[1] : PHP_INT_MAX;
                $ib = isset($mb[1]) ? (int) $mb[1] : PHP_INT_MAX;
                return $ia <=> $ib;
            });
            $manifest = ['parts' => $partFiles];
            $base = preg_replace('/\.part\d+(?:\.zip)?$/i', '', (string) $partFiles[0]) ?: 'recomposto';
            $outputPath = $tmpDir.DIRECTORY_SEPARATOR.$base.'.zip';
        }

        $out = fopen($outputPath, 'wb');
        if ($out === false) {
            File::deleteDirectory($tmpDir);
            return null;
        }
        foreach ((array) ($manifest['parts'] ?? []) as $partName) {
            $partPath = $tmpDir.DIRECTORY_SEPARATOR.basename((string) $partName);
            if (! File::exists($partPath)) {
                fclose($out);
                File::deleteDirectory($tmpDir);
                return null;
            }
            $expectedPartHash = (string) (($manifest['parts_sha256'][basename((string) $partName)] ?? ''));
            if ($expectedPartHash !== '') {
                $currentPartHash = hash_file('sha256', $partPath);
                if (! is_string($currentPartHash) || ! hash_equals($expectedPartHash, $currentPartHash)) {
                    fclose($out);
                    File::deleteDirectory($tmpDir);
                    return null;
                }
            }
            $in = fopen($partPath, 'rb');
            if ($in === false) {
                fclose($out);
                File::deleteDirectory($tmpDir);
                return null;
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $expectedOriginalHash = (string) ($manifest['original_sha256'] ?? '');
        if ($expectedOriginalHash !== '') {
            $currentOriginalHash = hash_file('sha256', $outputPath);
            if (! is_string($currentOriginalHash) || ! hash_equals($expectedOriginalHash, $currentOriginalHash)) {
                File::deleteDirectory($tmpDir);
                return null;
            }
        }

        return new UploadedFile($outputPath, basename($outputPath), 'application/zip', 0, true);
    }

    /**
     * @return array<int, string>
     */
    protected function getAllowedImportTypes(): array
    {
        $base = ['completo', 'banco', 'anexos'];
        if (! function_exists('apply_filters')) {
            return $base;
        }
        $options = apply_filters('utilidades.import.options', []);
        if (! is_array($options)) {
            return $base;
        }
        $custom = [];
        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $value = isset($opt['value']) ? trim((string) $opt['value']) : '';
            if ($value === '') {
                continue;
            }
            $custom[] = $value;
        }

        return array_values(array_unique(array_merge($base, $custom)));
    }

    /** Tabelas preservadas na restauração/importação completa (acesso/permissões) para não quebrar o login. Não incluir migrations: ao pular CREATE TABLE migrations mas executar INSERT, a tabela não existiria. */
    protected const TABELAS_PRESERVAR_RESTAURAR = [
        'users',
        'roles',
        'permissions',
        'permission_role',
        'role_user',
    ];

    /**
     * MySQL/MariaDB: desliga verificação de FK durante o restore (dumps costumam fazer DROP por ordem alfabética;
     * sem isso, DROP de tabela referenciada falha com erro 1451).
     */
    protected function executarImportMysqlSemVerificacaoFk(callable $callback): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            $callback();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Aplica dump SQL no banco (caminho do arquivo). Usado na importação "somente banco".
     * Na restauração usa aplicarDumpSqlPreservandoAuth para não sobrescrever usuários/permissões.
     * Para SQLite: PDO::exec() executa só a primeira instrução; por isso aplicamos instrução a instrução.
     *
     * @param  \Closure(string): void|null  $logFn  Opcional: chamado com cada linha de log (para debug).
     */
    protected function aplicarDumpSql(string $path, ?\Closure $logFn = null): void
    {
        $driver = config('database.default');
        $sql = File::get($path);
        $logFn = $logFn ?? fn () => null;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->executarImportMysqlSemVerificacaoFk(function () use ($sql, $logFn): void {
                if ($this->sqlDumpEhSqlite($sql)) {
                    $statements = $this->splitSqlStatements($sql);
                    $logFn('['.date('H:i:s').'] Dump no formato SQLite detectado: importando em MySQL/MariaDB ('.count($statements).' instrução(ões)).');
                    $executadas = 0;
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if ($statement === '') {
                            continue;
                        }
                        $statement = $this->normalizarStatementDumpSqliteParaMysql($statement);
                        $statement = $this->normalizarInsertMysqlBacktickIdentificadores($statement);
                        $statement = $this->normalizarInsertEnumVazios($statement);
                        $statement = $this->normalizarInsertDeletedAt($statement);
                        $statement = $this->normalizarInsertTenantIdVazio($statement);
                        $preview = strlen($statement) > 80 ? substr($statement, 0, 77).'...' : $statement;
                        $preview = str_replace(["\r", "\n"], ' ', $preview);
                        try {
                            DB::unprepared($statement);
                            $executadas++;
                            $logFn('  [OK] '.$preview);
                        } catch (\Throwable $e) {
                            $logFn('  [ERRO] '.$preview);
                            $logFn('         '.$e->getMessage());
                            throw $e;
                        }
                    }
                    $logFn('['.date('H:i:s').'] Dump aplicado. Executadas: '.$executadas.'.');

                    return;
                }
                DB::unprepared($sql);
                $logFn('['.date('H:i:s').'] Dump aplicado (MySQL/MariaDB) em uma única execução.');
            });

            return;
        }

        if ($driver === 'sqlite') {
            $statements = $this->splitSqlStatements($sql);
            $total = count($statements);
            $logFn('['.date('H:i:s').'] Importação SQLite: '.$total.' instrução(ões).');
            $database = config('database.connections.sqlite.database');
            $pdo = new \PDO('sqlite:'.$database);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $executadas = 0;
            $erros = 0;
            foreach ($statements as $i => $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                $statement = $this->normalizarInsertEnumVazios($statement);
                $statement = $this->normalizarInsertDeletedAt($statement);
                $statement = $this->normalizarInsertTenantIdVazio($statement);
                $preview = strlen($statement) > 80 ? substr($statement, 0, 77).'...' : $statement;
                $preview = str_replace(["\r", "\n"], ' ', $preview);
                try {
                    $pdo->exec($statement);
                    $executadas++;
                    $logFn('  [OK] '.$preview);
                } catch (\Throwable $e) {
                    $erros++;
                    $logFn('  [ERRO] '.$preview);
                    $logFn('         '.$e->getMessage());
                    throw $e;
                }
            }
            $logFn('['.date('H:i:s').'] Dump aplicado. Executadas: '.$executadas.', erros: '.$erros);

            return;
        }

        throw new \RuntimeException('Importação de banco não suportada para o driver: '.$driver);
    }

    /**
     * Aplica o dump SQL preservando as tabelas de acesso (users, roles, permissions, etc.)
     * para que o login continue funcionando após a restauração.
     * Se $log for passado, chama $log(string $linha) para cada evento (terminal de log).
     */
    protected function aplicarDumpSqlPreservandoAuth(string $path, ?\Closure $log = null): void
    {
        $driver = config('database.default');
        $sql = File::get($path);
        $logFn = $log ?? fn () => null;

        $statements = $this->splitSqlStatements($sql);
        $total = count($statements);
        $logFn('['.date('H:i:s').'] Iniciando aplicação do dump: '.$total.' instrução(ões) encontrada(s).');

        $executadas = 0;
        $ignoradas = 0;
        $erros = 0;
        $dumpSqliteParaMysql = ($driver === 'mysql' || $driver === 'mariadb') && $this->sqlDumpEhSqlite($sql);
        if ($dumpSqliteParaMysql) {
            $logFn('['.date('H:i:s').'] Dump no formato SQLite: DDL será convertido para MySQL/MariaDB.');
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->executarImportMysqlSemVerificacaoFk(function () use ($statements, $dumpSqliteParaMysql, &$executadas, &$ignoradas, &$erros, $logFn): void {
                foreach ($statements as $i => $statement) {
                    $statement = trim($statement);
                    if ($statement === '') {
                        continue;
                    }
                    if ($dumpSqliteParaMysql) {
                        $statement = $this->normalizarStatementDumpSqliteParaMysql($statement);
                        $statement = $this->normalizarInsertMysqlBacktickIdentificadores($statement);
                        $statement = $this->normalizarInsertEnumVazios($statement);
                        $statement = $this->normalizarInsertDeletedAt($statement);
                        $statement = $this->normalizarInsertTenantIdVazio($statement);
                    }
                    $preview = strlen($statement) > 80 ? substr($statement, 0, 77).'...' : $statement;
                    $preview = str_replace(["\r", "\n"], ' ', $preview);
                    $tocaPreservada = $this->statementTocaTabelaPreservada($statement);
                    if ($tocaPreservada) {
                        $ignoradas++;
                        $logFn('  [SKIP] '.$preview);

                        continue;
                    }
                    try {
                        DB::unprepared($statement);
                        $executadas++;
                        $logFn('  [OK] '.$preview);
                    } catch (\Throwable $e) {
                        $erros++;
                        $logFn('  [ERRO] '.$preview);
                        $logFn('         '.$e->getMessage());
                        throw $e;
                    }
                }
                $logFn('['.date('H:i:s').'] Dump aplicado. Executadas: '.$executadas.', ignoradas (preservar auth): '.$ignoradas.', erros: '.$erros);
            });

            return;
        }

        if ($driver === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            $pdo = new \PDO('sqlite:'.$database);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            foreach ($statements as $i => $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                $preview = strlen($statement) > 80 ? substr($statement, 0, 77).'...' : $statement;
                $preview = str_replace(["\r", "\n"], ' ', $preview);
                $tocaPreservada = $this->statementTocaTabelaPreservada($statement);
                if ($tocaPreservada) {
                    $ignoradas++;
                    $logFn('  [SKIP] '.$preview);

                    continue;
                }
                $statement = $this->normalizarInsertEnumVazios($statement);
                $statement = $this->normalizarInsertDeletedAt($statement);
                $statement = $this->normalizarInsertTenantIdVazio($statement);
                $preview = strlen($statement) > 80 ? substr($statement, 0, 77).'...' : $statement;
                $preview = str_replace(["\r", "\n"], ' ', $preview);
                try {
                    $pdo->exec($statement);
                    $executadas++;
                    $logFn('  [OK] '.$preview);
                } catch (\Throwable $e) {
                    $erros++;
                    $logFn('  [ERRO] '.$preview);
                    $logFn('         '.$e->getMessage());
                    throw $e;
                }
            }
            $logFn('['.date('H:i:s').'] Dump aplicado. Executadas: '.$executadas.', ignoradas (preservar auth): '.$ignoradas.', erros: '.$erros);

            return;
        }

        throw new \RuntimeException('Restauração preservando auth não suportada para o driver: '.$driver);
    }

    /**
     * Colunas enum que não aceitam '' no SQLite.
     * Durante a importação, '' é substituído por NULL ou pelo valor padrão indicado.
     * Chave = tabela, valor = [ coluna => NULL ou 'valor_padrao' ].
     *
     * @var array<string, array<string, string|null>>
     */
    protected const IMPORT_NORMALIZE_ENUM_EMPTY = [
        'socios' => ['tipo_assinatura' => null],
        'produtos' => [
            'formato' => 'simples',
            'tipo' => 'produto',
            'condicao' => null,
            'producao' => null,
            'tributacao_origem' => null,
        ],
        'ordem_servicos' => ['equipamento_unlock_type' => null],
    ];

    /**
     * Na importação: substitui deleted_at = '' por NULL nos INSERTs, para que registros
     * "não excluídos" (exportados como string vazia) apareçam nas listagens (SoftDeletes).
     * Só altera quando o valor é vazio; se for data (registro realmente excluído), mantém.
     */
    protected function normalizarInsertDeletedAt(string $statement): string
    {
        if (! preg_match('/^\s*INSERT\s+INTO\s+\w+\s*\(\s*([^)]+)\s*\)\s*VALUES\s*\(/i', $statement, $m)) {
            return $statement;
        }
        $colList = array_map('trim', explode(',', $m[1]));
        $deletedAtIdx = array_search('deleted_at', $colList, true);
        if ($deletedAtIdx === false) {
            return $statement;
        }
        $pos = stripos($statement, 'VALUES');
        if ($pos === false) {
            return $statement;
        }
        $afterValues = substr($statement, $pos + 5);
        $open = strpos($afterValues, '(');
        if ($open === false) {
            return $statement;
        }
        $valsStart = $pos + 5 + $open + 1;
        $vals = $this->parseValuesList(substr($statement, $valsStart));
        if ($vals === null || count($vals) <= $deletedAtIdx) {
            return $statement;
        }
        $val = trim($vals[$deletedAtIdx]);
        // Só substituir quando for '' ou "''" (não excluído). Data de exclusão permanece.
        if ($val !== "''" && $val !== '') {
            return $statement;
        }
        $vals[$deletedAtIdx] = 'NULL';
        $before = substr($statement, 0, $valsStart);
        $listLen = $this->valuesListLength(substr($statement, $valsStart));
        $after = substr($statement, $valsStart + $listLen);

        return $before.implode(',', $vals).')'.$after;
    }

    /**
     * Corrige INSERT onde colunas enum vieram como '' no dump (CHECK constraint no SQLite).
     */
    protected function normalizarInsertEnumVazios(string $statement): string
    {
        if (! preg_match('/^\s*INSERT\s+INTO\s+(\w+)\s*\(\s*([^)]+)\s*\)\s*VALUES\s*\(/i', $statement, $m)) {
            return $statement;
        }
        $table = strtolower($m[1]);
        $columnRules = self::IMPORT_NORMALIZE_ENUM_EMPTY[$table] ?? null;
        if ($columnRules === null) {
            return $statement;
        }
        $colListRaw = array_map('trim', explode(',', $m[2]));
        $colList = array_map(function (string $col): string {
            return trim($col, " \t\n\r\0\x0B`\"[]");
        }, $colListRaw);
        $pos = stripos($statement, 'VALUES');
        if ($pos === false) {
            return $statement;
        }
        $afterValues = substr($statement, $pos + 5);
        $open = strpos($afterValues, '(');
        if ($open === false) {
            return $statement;
        }
        $valsStart = $pos + 5 + $open + 1;
        $vals = $this->parseValuesList(substr($statement, $valsStart));
        if ($vals === null) {
            return $statement;
        }
        $changed = false;
        foreach ($columnRules as $columnName => $replacement) {
            $idx = array_search($columnName, $colList, true);
            if ($idx === false) {
                continue;
            }
            if (count($vals) <= $idx) {
                continue;
            }
            $val = trim($vals[$idx]);
            if ($val === "''" || $val === '') {
                $vals[$idx] = $replacement === null ? 'NULL' : "'".str_replace("'", "''", $replacement)."'";
                $changed = true;
            }
        }
        if (! $changed) {
            return $statement;
        }
        $before = substr($statement, 0, $valsStart);
        $listLen = $this->valuesListLength(substr($statement, $valsStart));
        $after = substr($statement, $valsStart + $listLen);

        return $before.implode(',', $vals).')'.$after;
    }

    /**
     * Compatibilidade com dumps antigos: tenant_id exportado como '' deve voltar como NULL.
     * Isso evita filtros de tenant falhando e "sumiço" de dados no financeiro.
     */
    protected function normalizarInsertTenantIdVazio(string $statement): string
    {
        if (! preg_match('/^\s*INSERT\s+INTO\s+\w+\s*\(\s*([^)]+)\s*\)\s*VALUES\s*\(/i', $statement, $m)) {
            return $statement;
        }
        $colList = array_map('trim', explode(',', $m[1]));
        $tenantIdx = array_search('tenant_id', $colList, true);
        if ($tenantIdx === false) {
            return $statement;
        }
        $pos = stripos($statement, 'VALUES');
        if ($pos === false) {
            return $statement;
        }
        $afterValues = substr($statement, $pos + 5);
        $open = strpos($afterValues, '(');
        if ($open === false) {
            return $statement;
        }
        $valsStart = $pos + 5 + $open + 1;
        $vals = $this->parseValuesList(substr($statement, $valsStart));
        if ($vals === null || count($vals) <= $tenantIdx) {
            return $statement;
        }
        $val = trim($vals[$tenantIdx]);
        if ($val !== "''" && $val !== '') {
            return $statement;
        }
        $vals[$tenantIdx] = 'NULL';
        $before = substr($statement, 0, $valsStart);
        $listLen = $this->valuesListLength(substr($statement, $valsStart));
        $after = substr($statement, $valsStart + $listLen);

        return $before.implode(',', $vals).')'.$after;
    }

    /**
     * Extrai valores de uma lista VALUES ( val1, val2, ... ) respeitando strings com ''.
     *
     * @return array<int, string>|null
     */
    protected function parseValuesList(string $s): ?array
    {
        $vals = [];
        $len = strlen($s);
        $i = 0;
        $cur = '';
        $depth = 0;
        while ($i < $len) {
            $c = $s[$i];
            if ($c === '(') {
                $depth++;
                $cur .= $c;
                $i++;

                continue;
            }
            if ($c === ')') {
                $depth--;
                if ($depth < 0) {
                    $vals[] = trim($cur);

                    return $vals;
                }
                $cur .= $c;
                $i++;

                continue;
            }
            if ($c === "'") {
                $cur .= $c;
                $i++;
                while ($i < $len) {
                    $c = $s[$i];
                    $cur .= $c;
                    if ($c === "'" && $i + 1 < $len && $s[$i + 1] === "'") {
                        $cur .= "'";
                        $i += 2;

                        continue;
                    }
                    if ($c === "'") {
                        $i++;
                        break;
                    }
                    $i++;
                }

                continue;
            }
            if (($c === ',' || $c === ')') && $depth === 0) {
                $vals[] = trim($cur);
                $cur = '';
                if ($c === ')') {
                    return $vals;
                }
                $i++;

                continue;
            }
            $cur .= $c;
            $i++;
        }

        return $vals;
    }

    /** Comprimento da lista VALUES a partir de $s (até o ) que fecha o primeiro nível). */
    protected function valuesListLength(string $s): int
    {
        $len = strlen($s);
        $depth = 0;
        $inString = false;
        $i = 0;
        while ($i < $len) {
            $c = $s[$i];
            if (! $inString) {
                if ($c === '(') {
                    $depth++;
                } elseif ($c === ')') {
                    $depth--;
                    if ($depth < 0) {
                        return $i + 1;
                    }
                } elseif ($c === "'") {
                    $inString = true;
                }
                $i++;

                continue;
            }
            if ($c === "'" && $i + 1 < $len && $s[$i + 1] === "'") {
                $i += 2;
            } elseif ($c === "'") {
                $inString = false;
                $i++;
            } else {
                $i++;
            }
        }

        return $len;
    }

    /**
     * Divide SQL em instruções respeitando aspas simples (e '' dentro de strings) e comentários --.
     *
     * @return array<int, string>
     */
    protected function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $len = strlen($sql);
        $start = 0;
        $inString = false;
        $inLineComment = false;

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($inLineComment) {
                if ($c === "\n" || $c === "\r") {
                    $inLineComment = false;
                }

                continue;
            }
            if ($inString) {
                if ($c === "'" && $i + 1 < $len && $sql[$i + 1] === "'") {
                    $i++;

                    continue;
                }
                if ($c === "'") {
                    $inString = false;
                }

                continue;
            }
            if ($c === "'") {
                $inString = true;

                continue;
            }
            if ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $inLineComment = true;
                $i++;

                continue;
            }
            if ($c === ';') {
                $statements[] = substr($sql, $start, $i - $start);
                $start = $i + 1;
            }
        }
        $rest = trim(substr($sql, $start));
        if ($rest !== '') {
            $statements[] = $rest;
        }

        return $statements;
    }

    /**
     * Dump gerado por export SQLite (gerarDumpSqliteParaArquivo): DDL com aspas duplas e autoincrement.
     * Importar esse arquivo em MySQL/MariaDB exige conversão; caso contrário ocorre erro 1064.
     */
    protected function sqlDumpEhSqlite(string $sql): bool
    {
        $head = substr($sql, 0, 16384);

        return (bool) preg_match('/CREATE\s+TABLE\s+"/i', $head)
            || ((bool) preg_match('/\bautoincrement\b/i', $head) && (bool) preg_match('/CREATE\s+TABLE/i', $head));
    }

    /**
     * Converte uma instrução de dump SQLite para MySQL/MariaDB quando aplicável (principalmente CREATE TABLE).
     */
    protected function normalizarStatementDumpSqliteParaMysql(string $statement): string
    {
        $s = trim($statement);
        if ($s === '') {
            return $statement;
        }
        $semComentarioLinha = preg_replace('/^\s*--[^\n]*\n/m', '', $s);
        $semComentarioLinha = trim((string) $semComentarioLinha);
        if ($semComentarioLinha === '') {
            return $statement;
        }
        $upper = strtoupper($semComentarioLinha);
        if (! str_starts_with($upper, 'CREATE TABLE')) {
            return $statement;
        }

        return $this->converterDdlCreateTableSqliteParaMysql($semComentarioLinha);
    }

    /**
     * SQLite (Laravel enum) usa CHECK inline entre tipo e NOT NULL; MySQL/MariaDB não aceita essa ordem (erro 1064).
     * Remove CHECK(...) por coluna; validação continua na aplicação.
     */
    protected function removerCheckConstraintsInlineDaDdlCreateMysql(string $ddl): string
    {
        while (preg_match('/\s+check\s*\(/i', $ddl, $m, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $m[0][0];
            $matchStart = $m[0][1];
            $openPos = $matchStart + strlen($fullMatch) - 1;
            $len = strlen($ddl);
            if ($openPos < 0 || $openPos >= $len || $ddl[$openPos] !== '(') {
                break;
            }
            $depth = 0;
            $end = null;
            for ($j = $openPos; $j < $len; $j++) {
                $c = $ddl[$j];
                if ($c === '(') {
                    $depth++;
                } elseif ($c === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $end = $j;
                        break;
                    }
                }
            }
            if ($end === null) {
                break;
            }
            $ddl = substr($ddl, 0, $matchStart).substr($ddl, $end + 1);
        }

        return preg_replace('/\s+/', ' ', trim($ddl));
    }

    /**
     * Converte DDL CREATE TABLE do SQLite (sqlite_master) para sintaxe MySQL/MariaDB.
     */
    protected function converterDdlCreateTableSqliteParaMysql(string $ddl): string
    {
        $ddl = preg_replace('/\s+/', ' ', trim($ddl));
        $ddl = preg_replace('/"([^"]+)"/', '`$1`', $ddl);
        $ddl = preg_replace('/\s+/', ' ', $ddl);

        $ddl = preg_replace(
            '/(`[^`]+`)\s+integer\s+primary\s+key\s+autoincrement(?:\s+not\s+null)?/i',
            '$1 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            $ddl
        );
        $ddl = preg_replace(
            '/(`[^`]+`)\s+integer\s+primary\s+key(?:\s+not\s+null)?/i',
            '$1 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            $ddl
        );

        $ddl = preg_replace('/\bwithout\s+rowid\b/i', '', $ddl);
        $ddl = preg_replace('/\bstrict\b/i', '', $ddl);
        $ddl = preg_replace('/\bautoincrement\b/i', '', $ddl);
        $ddl = preg_replace('/\s+/', ' ', trim($ddl));

        $placeholders = [];
        $ddl = preg_replace_callback('/`[^`]+`/', function (array $m) use (&$placeholders): string {
            $key = '___COL'.count($placeholders).'___';
            $placeholders[$key] = $m[0];

            return $key;
        }, $ddl);

        $ddl = preg_replace('/\bvarchar(?!\s*\()/i', 'VARCHAR(255)', $ddl);
        // BIGINT UNSIGNED alinha com bigIncrements / unsignedBigInteger do Laravel; INT assinado quebra FK para `id` BIGINT UNSIGNED (errno 150).
        $ddl = preg_replace('/\binteger\b/i', 'BIGINT UNSIGNED', $ddl);
        $ddl = preg_replace('/\breal\b/i', 'DOUBLE', $ddl);
        $ddl = preg_replace('/\bfloat\b/i', 'DOUBLE', $ddl);
        $ddl = preg_replace('/\bdouble\b/i', 'DOUBLE', $ddl);
        $ddl = preg_replace('/\bblob\b/i', 'LONGBLOB', $ddl);
        $ddl = preg_replace('/\btext\b/i', 'LONGTEXT', $ddl);
        $ddl = preg_replace('/\bnumeric\b/i', 'DECIMAL(18,4)', $ddl);
        $ddl = preg_replace('/\bboolean\b/i', 'TINYINT(1)', $ddl);
        $ddl = preg_replace('/\bdatetime\b/i', 'DATETIME', $ddl);
        $ddl = preg_replace('/\s+/', ' ', trim($ddl));

        if ($placeholders !== []) {
            $ddl = str_replace(array_keys($placeholders), array_values($placeholders), $ddl);
        }

        $ddl = $this->removerCheckConstraintsInlineDaDdlCreateMysql($ddl);

        $ddl = rtrim($ddl, "; \t\n\r");
        if ($ddl !== '' && ! preg_match('/\bDEFAULT\s+CHARSET\b/i', $ddl) && ! preg_match('/\)\s*ENGINE\s*=/i', $ddl)) {
            if (preg_match('/\)\s*$/', $ddl)) {
                $ddl .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
            }
        }

        return $ddl.';';
    }

    /**
     * MySQL/MariaDB: envolve tabela e colunas do INSERT com crases quando vêm sem aspas (dump SQLite).
     * Sem isso, nomes como "key", "order" viram palavras reservadas e o parser quebra (erro 1064).
     */
    protected function normalizarInsertMysqlBacktickIdentificadores(string $statement): string
    {
        $s = trim($statement);
        if ($s === '' || ! preg_match('/^\s*INSERT\s+INTO\s+/i', $s)) {
            return $statement;
        }

        $result = preg_replace_callback(
            '/^\s*(INSERT\s+INTO\s+)((?:`[^`]+`)|(?:[a-zA-Z_][a-zA-Z0-9_]*))(\s*\(\s*)([^)]+)(\s*\)\s*VALUES\s)/is',
            function (array $m): string {
                $table = $m[2];
                if (! str_starts_with($table, '`')) {
                    $table = '`'.str_replace('`', '``', $table).'`';
                }
                $cols = array_map('trim', explode(',', $m[4]));
                $quoted = [];
                foreach ($cols as $c) {
                    if ($c === '') {
                        continue;
                    }
                    if (str_starts_with($c, '`')) {
                        $quoted[] = $c;

                        continue;
                    }
                    if (str_starts_with($c, '"') && str_ends_with($c, '"') && strlen($c) >= 2) {
                        $ident = str_replace('""', '"', substr($c, 1, -1));
                        $quoted[] = '`'.str_replace('`', '``', $ident).'`';

                        continue;
                    }
                    $quoted[] = '`'.str_replace('`', '``', $c).'`';
                }

                return $m[1].$table.$m[3].implode(',', $quoted).$m[5];
            },
            $s,
            1
        );

        return is_string($result) ? $result : $statement;
    }

    /**
     * Verifica se a instrução tem como alvo direto uma das tabelas preservadas (não por referência).
     * Ex.: INSERT INTO roles -> sim; CREATE TABLE produtos (... REFERENCES roles(id)) -> não (alvo é produtos).
     */
    protected function statementTocaTabelaPreservada(string $statement): bool
    {
        $stmt = str_replace("\r\n", "\n", $statement);
        $stmt = str_replace("\r", "\n", $stmt);
        // Remover apenas linhas que são 100% comentário (não contêm comando SQL)
        $stmt = preg_replace('/^\s*--(?![^\n]*(?:DROP|CREATE|INSERT|UPDATE|DELETE|ALTER))[^\n]*$/m', '', $stmt);
        // Na mesma linha: comentário seguido de SQL (com ou sem espaço) — manter só o trecho SQL
        $stmt = preg_replace('/^\s*--[^\n]*?(\s*(?:DROP|CREATE|INSERT|UPDATE|DELETE|ALTER)\s[^\n]*)/im', '$1', $stmt);
        $stmt = preg_replace('/\s+/', ' ', $stmt);
        $stmt = trim($stmt);
        // Se ainda começa com -- mas contém comando SQL, extrair a partir do primeiro comando
        if ($stmt !== '' && preg_match('/^[\s\-]*--/i', $stmt) && preg_match('/((?:DROP|CREATE|INSERT|UPDATE|DELETE|ALTER)\s.+)/is', $stmt, $m)) {
            $stmt = trim($m[1]);
        }
        if ($stmt === '') {
            return false;
        }
        $upper = strtoupper($stmt);
        $preservar = array_fill_keys(array_map('strtolower', self::TABELAS_PRESERVAR_RESTAURAR), true);

        $tableName = null;
        $branch = 'none';
        if (str_starts_with($upper, 'DROP TABLE')) {
            $branch = 'DROP';
            if (preg_match('/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        } elseif (str_starts_with($upper, 'CREATE TABLE')) {
            $branch = 'CREATE';
            if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        } elseif (str_starts_with($upper, 'INSERT INTO')) {
            $branch = 'INSERT';
            if (preg_match('/INSERT\s+INTO\s+(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        } elseif (str_starts_with($upper, 'UPDATE')) {
            $branch = 'UPDATE';
            if (preg_match('/UPDATE\s+(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        } elseif (str_starts_with($upper, 'DELETE FROM')) {
            $branch = 'DELETE';
            if (preg_match('/DELETE\s+FROM\s+(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        } elseif (str_starts_with($upper, 'ALTER TABLE')) {
            $branch = 'ALTER';
            if (preg_match('/ALTER\s+TABLE\s+(?:["`]([^"`]+)["`]|(\w+))/i', $stmt, $m)) {
                $tableName = $m[1] ?? $m[2] ?? null;
            }
        }

        $result = false;
        if ($tableName !== null) {
            $tableName = strtolower(trim($tableName));
            $result = isset($preservar[$tableName]);
        }

        return $result;
    }

    protected function importarSql($file)
    {
        $path = $file->getRealPath();
        $logFile = storage_path('app/import-banco-'.date('Y-m-d_His').'.log');
        $logLines = [];
        $logFn = function (string $line) use (&$logLines, $logFile) {
            $logLines[] = $line;
            @file_put_contents($logFile, $line."\n", FILE_APPEND);
        };
        $logFn('=== Importação somente banco: '.basename($path).' ===');
        $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

        try {
            $this->aplicarDumpSql($path, $logFn);
        } catch (\Throwable $e) {
            $logFn('ERRO: '.$e->getMessage());

            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Erro ao importar SQL: '.$e->getMessage())
                ->with('import_log_path', $logFile);
        }
        $logFn('['.date('H:i:s').'] Garantindo usuário admin (admin@admin.com / senha: password)...');
        $this->garantirAdminAposRestore();
        $logFn('['.date('H:i:s').'] Importação concluída com sucesso.');

        return redirect()->route('configuracoes-sistema.utilidades.index')
            ->with('success', 'Banco de dados importado com sucesso. Use admin@admin.com e senha "password" para entrar.')
            ->with('import_log_path', $logFile);
    }

    protected function importarBancoDeZip($file)
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            return redirect()->back()->with('error', 'Arquivo ZIP inválido.');
        }
        $sqlContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/\.sql$/i', $name) || $name === 'dump.sql') {
                $sqlContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();
        if ($sqlContent === null) {
            return redirect()->back()->with('error', 'Nenhum arquivo .sql encontrado no ZIP.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'erp_import');
        File::put($tmp, $sqlContent);
        $uploaded = new UploadedFile($tmp, 'dump.sql', 'text/plain', 0, true);
        $result = $this->importarSql($uploaded);
        @unlink($tmp);

        return $result;
    }

    /**
     * Importação somente banco em streaming para exibição no terminal (iframe).
     *
     * @param  string  $path  Caminho do arquivo .sql
     * @param  string  $label  Nome do arquivo para o log
     * @param  bool  $deleteTemp  Se true, remove $path ao final (arquivo temporário)
     */
    protected function importProcessStreamBanco(string $path, string $label, bool $deleteTemp = false): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        return new StreamedResponse(function () use ($path, $label, $deleteTemp, $utilidadesUrl) {
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFn = function (string $line) {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlStart = function () {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Importação em andamento</title>';
                echo '<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>';
                echo '</head><body><pre id="log">';
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlEnd = function (bool $success, string $text, string $detail = '') use ($utilidadesUrl) {
                echo '</pre><div class="msg '.($success ? 'success' : 'error').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
                if ($detail !== '') {
                    echo '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
                }
                echo '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p></body></html>';
            };

            $sendHtmlStart();
            $logFn('=== Importação somente banco: '.$label.' ===');
            $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

            try {
                $this->aplicarDumpSql($path, $logFn);
                $logFn('['.date('H:i:s').'] Garantindo usuário admin (admin@admin.com / senha: password)...');
                $this->garantirAdminAposRestore();
                $logFn('['.date('H:i:s').'] Importação concluída com sucesso.');
                if ($deleteTemp && File::exists($path)) {
                    @unlink($path);
                }
                $sendHtmlEnd(true, 'Importação concluída com sucesso. Use admin@admin.com e senha "password" para entrar.', 'Você pode fechar o terminal ou voltar para Utilidades.');
            } catch (\Throwable $e) {
                $logFn('ERRO: '.$e->getMessage());
                if ($deleteTemp && File::exists($path)) {
                    @unlink($path);
                }
                $sendHtmlEnd(false, 'Erro na importação', $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Importação somente anexos em streaming para o terminal (iframe).
     */
    protected function importProcessStreamAnexos($file): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');
        $filePath = $file->getRealPath();

        return new StreamedResponse(function () use ($filePath, $utilidadesUrl) {
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFn = function (string $line) {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlStart = function () {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Importação de anexos</title>';
                echo '<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>';
                echo '</head><body><pre id="log">';
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlEnd = function (bool $success, string $text, string $detail = '') use ($utilidadesUrl) {
                echo '</pre><div class="msg '.($success ? 'success' : 'error').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
                if ($detail !== '') {
                    echo '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
                }
                echo '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p></body></html>';
            };

            $sendHtmlStart();
            $logFn('=== Importação somente anexos ===');
            $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

            try {
                $zip = new ZipArchive;
                if ($zip->open($filePath) !== true) {
                    throw new \RuntimeException('Arquivo ZIP inválido.');
                }
                $dest = storage_path('app/public');
                if (! File::isDirectory($dest)) {
                    File::makeDirectory($dest, 0775, true);
                }
                $count = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (str_ends_with($name, '/')) {
                        continue;
                    }
                    $path = str_replace('\\', '/', $name);
                    if (str_starts_with($path, 'anexos/')) {
                        $path = substr($path, 7);
                    }
                    $path = trim($path, '/');
                    if ($path === '') {
                        continue;
                    }
                    $fullPath = $dest.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
                    $dir = dirname($fullPath);
                    if (! File::isDirectory($dir)) {
                        File::makeDirectory($dir, 0775, true);
                    }
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        File::put($fullPath, $content);
                        $count++;
                    }
                }
                $zip->close();
                $logFn('['.date('H:i:s').'] '.$count.' arquivo(s) extraído(s).');
                $logFn('['.date('H:i:s').'] Concluído.');
                $sendHtmlEnd(true, 'Anexos importados com sucesso.', 'Total: '.$count.' arquivo(s).');
            } catch (\Throwable $e) {
                $logFn('ERRO: '.$e->getMessage());
                $sendHtmlEnd(false, 'Erro na importação de anexos', $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Importação completa em streaming para o terminal (iframe).
     */
    protected function importProcessStreamCompleto($file): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        return new StreamedResponse(function () use ($file, $utilidadesUrl) {
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFn = function (string $line) {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlStart = function () {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Importação completa</title>';
                echo '<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>';
                echo '</head><body><pre id="log">';
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlEnd = function (bool $success, string $text, string $detail = '') use ($utilidadesUrl) {
                echo '</pre><div class="msg '.($success ? 'success' : 'error').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
                if ($detail !== '') {
                    echo '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
                }
                echo '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p></body></html>';
            };

            $sendHtmlStart();
            $logFn('=== Importação completa (banco + anexos) ===');
            $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

            try {
                $this->runImportCompletoZipWithLog($file, $logFn);
                $logFn('['.date('H:i:s').'] Concluído.');
                $sendHtmlEnd(true, 'Importação completa concluída. Use admin@admin.com / password para entrar.', '');
            } catch (\Throwable $e) {
                $logFn('ERRO: '.$e->getMessage());
                $sendHtmlEnd(false, 'Erro na importação completa', $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Executa a importação completa (zip com dump + anexos) com callback de log. Lança em caso de erro.
     */
    protected function runImportCompletoZipWithLog($file, callable $logFn): void
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new \RuntimeException('Arquivo ZIP inválido.');
        }
        $tempDir = storage_path('app/temp/import_'.uniqid());
        if (! File::isDirectory(dirname($tempDir))) {
            File::makeDirectory(dirname($tempDir), 0775, true);
        }
        File::makeDirectory($tempDir, 0775, true);

        $hasDumpSql = false;
        $hasAnexos = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if ($name === 'dump.sql' || preg_match('#^[^/]*\.sql$#i', $name)) {
                $hasDumpSql = true;
            }
            if (str_starts_with($name, 'anexos/') && ! str_ends_with($name, '/')) {
                $hasAnexos = true;
            }
        }
        if (! $hasDumpSql) {
            $zip->close();
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('O ZIP não contém dump.sql na raiz. Use um arquivo exportado por "Exportar por completo".');
        }
        if (! $zip->extractTo($tempDir)) {
            $zip->close();
            File::deleteDirectory($tempDir);
            throw new \RuntimeException('Erro ao extrair o ZIP.');
        }
        $zip->close();

        $ds = DIRECTORY_SEPARATOR;
        $tmpSql = $tempDir.$ds.'dump.sql';
        if (! File::exists($tmpSql)) {
            $rootSqlFiles = glob($tempDir.$ds.'*.sql') ?: [];
            if ($rootSqlFiles === []) {
                File::deleteDirectory($tempDir);
                throw new \RuntimeException('dump.sql não encontrado após extrair o ZIP.');
            }
            $tmpSql = $rootSqlFiles[0];
        }

        $logFn('['.date('H:i:s').'] Aplicando dump do banco...');
        $this->aplicarDumpSql($tmpSql, $logFn);
        $logFn('['.date('H:i:s').'] Garantindo usuário admin...');
        $this->garantirAdminAposRestore();

        $anexosDir = $tempDir.$ds.'anexos';
        if ($hasAnexos && ! File::isDirectory($anexosDir)) {
            $anexosDir = $this->findAnexosDirInExtracted($tempDir);
        }
        if ($hasAnexos && $anexosDir !== null && File::isDirectory($anexosDir)) {
            $logFn('['.date('H:i:s').'] Copiando anexos...');
            $dest = storage_path('app/public');
            if (! File::isDirectory($dest)) {
                File::makeDirectory($dest, 0775, true);
            }
            File::copyDirectory($anexosDir, $dest);
        }

        File::deleteDirectory($tempDir);
    }

    protected function importCompletoPartesSequencialHtml(string $seqToken, int $partIndex, int $partTotal, UploadedFile $arquivoPart): \Illuminate\Http\Response
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        try {
            $partsDir = storage_path('app/temp/import_parts_seq/'.$seqToken);
            if (! File::isDirectory($partsDir)) {
                File::makeDirectory($partsDir, 0755, true);
            }

            // Nome fixo pela ordem (não dependemos do nome do arquivo no cliente).
            $partPath = $partsDir.DIRECTORY_SEPARATOR.'part'.str_pad((string) $partIndex, 3, '0', STR_PAD_LEFT).'.zip';
            $sourcePath = $arquivoPart->getRealPath();
            $originalClientName = strtolower((string) $arquivoPart->getClientOriginalName());

            // Suporta caso o usuário envie o arquivo "Parte 1 + manifest" (um ZIP auxiliar):
            // extraímos e salvamos o slice binário correto como partNNN.zip.
            $unzipDir = null;
            if (str_ends_with($originalClientName, '.with-manifest.zip')) {
                $zip = new ZipArchive;
                if ($zip->open($sourcePath) === true) {
                    $manifestPos = $zip->locateName('manifest.json', ZipArchive::FL_NODIR);
                    if ($manifestPos !== false) {
                        $unzipDir = $partsDir.DIRECTORY_SEPARATOR.'unz_'.$partIndex;
                        if (! File::isDirectory($unzipDir)) {
                            File::makeDirectory($unzipDir, 0755, true);
                        }
                        if ($zip->extractTo($unzipDir) === true) {
                            $zip->close();
                            $innerFiles = File::allFiles($unzipDir);
                            $candidate = null;
                            foreach ($innerFiles as $f) {
                                $bn = $f->getFilename();
                                if (strcasecmp($bn, 'manifest.json') === 0) {
                                    continue;
                                }
                                $candidate = $f->getPathname();
                                break;
                            }
                            if (is_string($candidate) && File::exists($candidate)) {
                                $sourcePath = $candidate;
                            }
                        } else {
                            $zip->close();
                        }
                    } else {
                        $zip->close();
                    }
                }
            }

            $in = fopen($sourcePath, 'rb');
            $out = fopen($partPath, 'wb');
            if ($in === false || $out === false) {
                if (is_resource($in)) fclose($in);
                if (is_resource($out)) fclose($out);
                throw new \RuntimeException('Falha ao salvar arquivo recebido no servidor.');
            }
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);

            if ($unzipDir !== null && File::isDirectory($unzipDir)) {
                File::deleteDirectory($unzipDir);
            }

            $receivedMsg = 'Recebida parte '.$partIndex.' de '.$partTotal.'.';

            // Não é a última parte: devolve HTML pequeno para o JS mostrar no iframe.
            if ($partIndex < $partTotal) {
                $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
                $html .= '<pre id="log">'.htmlspecialchars($receivedMsg, ENT_QUOTES, 'UTF-8').'</pre>';
                $html .= '<div class="msg success">'.htmlspecialchars('Aguardando outras partes…', ENT_QUOTES, 'UTF-8').'</div>';
                $html .= '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p>';
                $html .= '</body></html>';

                return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
            }

            // Última parte: valida se todas existem.
            for ($i = 1; $i <= $partTotal; $i++) {
                $p = $partsDir.DIRECTORY_SEPARATOR.'part'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'.zip';
                if (! File::exists($p)) {
                    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
                    $html .= '<pre id="log">'.htmlspecialchars('Faltando parte '.$i.' de '.$partTotal.'.', ENT_QUOTES, 'UTF-8').'</pre>';
                    $html .= '<div class="msg error">'.htmlspecialchars('Envie todas as partes novamente.', ENT_QUOTES, 'UTF-8').'</div>';
                    $html .= '</body></html>';
                    File::deleteDirectory($partsDir);
                    return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
                }
            }

            // Recompõe o ZIP concatenando os slices na ordem.
            $recompostoPath = $partsDir.DIRECTORY_SEPARATOR.'recomposto.zip';
            $out = fopen($recompostoPath, 'wb');
            if ($out === false) {
                throw new \RuntimeException('Falha ao criar arquivo recomposto.');
            }

            $bufferBytes = 4 * 1024 * 1024;
            for ($i = 1; $i <= $partTotal; $i++) {
                $p = $partsDir.DIRECTORY_SEPARATOR.'part'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'.zip';
                $in = fopen($p, 'rb');
                if ($in === false) {
                    fclose($out);
                    File::deleteDirectory($partsDir);
                    throw new \RuntimeException('Falha ao ler parte '.$i.'.');
                }

                while (! feof($in)) {
                    $chunk = fread($in, $bufferBytes);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    fwrite($out, $chunk);
                }
                fclose($in);
            }

            fclose($out);

            $uploaded = new UploadedFile($recompostoPath, 'recomposto.zip', 'application/zip', 0, true);

            // Monta HTML completo de log (sem StreamedResponse) para funcionar bem via XHR.
            $logBuf = '';
            $logLinesRaw = [];
            $logFn = function (string $line) use (&$logBuf, &$logLinesRaw) {
                $logLinesRaw[] = $line;
                $logBuf .= htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
            };

            $ok = false;
            $msg = '';
            $detail = '';
            $logFileRelative = '';
            try {
                $logFn('=== Importação completa (partes) ===');
                $this->runImportCompletoZipWithLog($uploaded, $logFn);
                $ok = true;
                $msg = 'Importação completa concluída.';
            } catch (\Throwable $e) {
                $ok = false;
                $msg = 'Erro na importação completa';
                $detail = $this->formatImportThrowableChain($e);
                $logFileWritten = $this->writeImportCompletoErrorLog($e, $logLinesRaw);
                $logFileRelative = $logFileWritten !== '' ? str_replace(base_path().DIRECTORY_SEPARATOR, '', $logFileWritten) : '';
                // Garante que o iframe mostre o erro (às vezes o JS só lê o <pre>).
                $logBuf .= "\n".htmlspecialchars('=== DETALHE DO ERRO ===', ENT_QUOTES, 'UTF-8')."\n";
                $logBuf .= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')."\n";
                if ($logFileRelative !== '') {
                    $logBuf .= htmlspecialchars('Log salvo em: '.$logFileRelative.' (abra pelo gerenciador de arquivos ou FTP).', ENT_QUOTES, 'UTF-8')."\n";
                }
            }

            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
            $html .= '<pre id="log">'.$logBuf.'</pre>';
            $html .= '<div class="msg '.($ok ? 'success' : 'error').'">'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</div>';
            if ($detail !== '') {
                $html .= '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
            }
            if ($logFileRelative !== '') {
                $html .= '<p style="margin-top:0.5rem;font-size:0.85em;color:#555;">Arquivo de diagnóstico: <code>'.htmlspecialchars($logFileRelative, ENT_QUOTES, 'UTF-8').'</code></p>';
            }
            $html .= '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p>';
            $html .= '</body></html>';

            File::deleteDirectory($partsDir);
            return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Throwable $e) {
            $logFileWritten = $this->writeImportCompletoErrorLog($e, []);
            $logRel = $logFileWritten !== '' ? str_replace(base_path().DIRECTORY_SEPARATOR, '', $logFileWritten) : '';
            $detailOuter = $this->formatImportThrowableChain($e);
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
            $html .= '<pre id="log">'.htmlspecialchars('Erro na importação em partes.'."\n\n".$detailOuter.($logRel !== '' ? "\n\nLog: ".$logRel : ''), ENT_QUOTES, 'UTF-8').'</pre>';
            $html .= '<div class="msg error">'.htmlspecialchars($e->getMessage() !== '' ? $e->getMessage() : get_class($e), ENT_QUOTES, 'UTF-8').'</div>';
            $html .= '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p>';
            $html .= '</body></html>';
            return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

    protected function importarAnexosZip($file)
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            return redirect()->back()->with('error', 'Arquivo ZIP inválido.');
        }
        $dest = storage_path('app/public');
        if (! File::isDirectory($dest)) {
            File::makeDirectory($dest, 0755, true);
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '/')) {
                continue;
            }
            $path = $name;
            if (str_starts_with($path, 'anexos/')) {
                $path = substr($path, 7);
            }
            $fullPath = $dest.'/'.$path;
            $dir = dirname($fullPath);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                File::put($fullPath, $content);
            }
        }
        $zip->close();

        return redirect()->route('configuracoes-sistema.utilidades.index')->with('success', 'Anexos importados com sucesso.');
    }

    protected function importarCompletoZip($file)
    {
        try {
            $this->runImportCompletoZipWithLog($file, fn () => null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('configuracoes-sistema.utilidades.index')
            ->with('success', 'Importação completa concluída. Use admin@admin.com / password para entrar.');
    }

    /**
     * Procura o diretório "anexos" dentro da pasta extraída (suporta ZIP com pasta raiz).
     */
    protected function findAnexosDirInExtracted(string $tempDir): ?string
    {
        $ds = DIRECTORY_SEPARATOR;
        if (File::isDirectory($tempDir.$ds.'anexos')) {
            return $tempDir.$ds.'anexos';
        }
        $dirs = File::directories($tempDir);
        foreach ($dirs as $sub) {
            if (File::isDirectory($sub.$ds.'anexos')) {
                return $sub.$ds.'anexos';
            }
        }

        return null;
    }

    /**
     * Zerar sistema (padrão de fábrica): por defeito cria backup na pasta do sistema; opcionalmente sem pré-backup (anexos apagados antes).
     */
    public function zerarFabrica(Request $request)
    {
        // Após zerar, queremos deixar a opção habilitada no padrão (mesmo que estivesse desativada antes).
        $runTarefasAoAcessarAnterior = true;

        $request->validate([
            'confirmar_zerar' => 'required|accepted',
        ], [
            'confirmar_zerar.required' => 'Marque a confirmação para zerar o sistema.',
            'confirmar_zerar.accepted' => 'É necessário marcar que leu e confirma a operação.',
        ]);

        $pularPreBackup = $request->boolean('pular_pre_backup');
        if ($pularPreBackup) {
            $request->validate([
                'confirmar_risco_sem_pre_backup' => 'required|accepted',
            ], [
                'confirmar_risco_sem_pre_backup.required' => 'Marque a confirmação de risco para prosseguir sem cópia de segurança.',
                'confirmar_risco_sem_pre_backup.accepted' => 'É necessário aceitar o risco de não ter cópia do estado atual.',
            ]);
        }

        if ($request->boolean('stream')) {
            return $this->zerarFabricaStream($runTarefasAoAcessarAnterior, $pularPreBackup);
        }

        $backupDir = storage_path('app/backups/pre-reset-'.date('Y-m-d_His'));
        $backupInfo = '';

        if (! $pularPreBackup) {
            try {
                $this->criarBackupParaPasta($backupDir);
                $backupInfo = $backupDir;
            } catch (\Throwable $e) {
                if (File::isDirectory($backupDir)) {
                    File::deleteDirectory($backupDir);
                }

                return redirect()->route('configuracoes-sistema.utilidades.index')
                    ->with('error', 'Backup obrigatório falhou. Nenhuma alteração foi feita. Erro: '.$e->getMessage());
            }
        } else {
            try {
                $this->limparAnexos();
            } catch (\Throwable $e) {
                return redirect()->route('configuracoes-sistema.utilidades.index')
                    ->with('error', 'Falha ao limpar anexos antes de zerar: '.$e->getMessage());
            }
        }

        try {
            app(ZerarFabricaService::class)->executar();
        } catch (\Throwable $e) {
            $msg = $pularPreBackup
                ? 'Ocorreu erro ao zerar: '.$e->getMessage()
                : 'Backup foi salvo em: '.$backupDir.' — Mas ocorreu erro ao zerar: '.$e->getMessage();

            return redirect()->route('configuracoes-sistema.utilidades.index')->with('error', $msg);
        }

        // Reaplica a opção preservada após o reset.
        Configuracao::setValue('run_tarefas_ao_acessar', '1');

        $successMsg = $pularPreBackup
            ? 'Sistema zerado com sucesso. Não foi criada cópia de segurança (opção escolhida); os anexos em storage/app/public foram limpos antes do processo.'
            : 'Sistema zerado com sucesso. Backup completo salvo em: '.$backupInfo;

        return redirect()->route('configuracoes-sistema.utilidades.index')->with('success', $successMsg);
    }

    /**
     * Zerar fábrica em streaming: exibe log em tempo real no iframe.
     */
    protected function zerarFabricaStream(bool $runTarefasAoAcessarAnterior, bool $pularPreBackup = false): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        return new StreamedResponse(function () use ($utilidadesUrl, $pularPreBackup) {
            $this->relaxarLimitesProcessoLongo();
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFn = function (string $line) {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };

            $sendHtmlStart = function () {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Zerar sistema em andamento</title>';
                echo '<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>';
                echo '</head><body><pre id="log">';
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };

            $sendHtmlEnd = function (bool $success, string $text, string $detail = '') use ($utilidadesUrl) {
                echo '</pre><div class="msg '.($success ? 'success' : 'error').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
                if ($detail !== '') {
                    echo '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
                }
                echo '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p></body></html>';
            };

            $sendHtmlStart();

            $backupDir = storage_path('app/backups/pre-reset-'.date('Y-m-d_His'));
            $logFn('=== Zerar sistema (padrão de fábrica) ===');
            $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

            if ($pularPreBackup) {
                $logFn('['.date('H:i:s').'] Opção ativa: sem cópia de segurança. A limpar anexos em storage/app/public...');
                try {
                    $this->limparAnexos();
                    $logFn('['.date('H:i:s').'] Anexos limpos.');
                } catch (\Throwable $e) {
                    $logFn('['.date('H:i:s').'] ERRO ao limpar anexos: '.$e->getMessage());
                    $sendHtmlEnd(false, 'Falha ao limpar anexos. Operação cancelada.', $e->getMessage());

                    return;
                }
            } else {
                try {
                    $logFn('['.date('H:i:s').'] Criando backup em: '.$backupDir.' (dump + anexos em partes quando necessário)...');
                    $this->criarBackupParaPasta($backupDir);
                    $logFn('['.date('H:i:s').'] Backup criado com sucesso.');
                } catch (\Throwable $e) {
                    $logFn('['.date('H:i:s').'] ERRO no backup: '.$e->getMessage());
                    if (File::isDirectory($backupDir)) {
                        File::deleteDirectory($backupDir);
                    }
                    $sendHtmlEnd(false, 'Backup obrigatório falhou. Nenhuma alteração foi feita.', $e->getMessage());

                    return;
                }
            }

            try {
                $logFn('');
                $logFn('['.date('H:i:s').'] Zerando sistema (truncate + seeders)...');
                $logFn('Seeders que serão executados:');
                foreach (ZerarFabricaService::classesSeedersAposZerar() as $seederClass) {
                    $logFn('  • '.class_basename($seederClass));
                }
                $logFn('  • (em seguida) Empresa padrão + produtos iniciais de informática');
                app(ZerarFabricaService::class)->executar();
                $logFn('['.date('H:i:s').'] Sistema zerado com sucesso.');

                // Deixar habilitado após zerar.
                Configuracao::setValue('run_tarefas_ao_acessar', '1');

                $detalhe = $pularPreBackup
                    ? 'Não foi criada cópia de segurança (opção escolhida).'
                    : 'Backup completo salvo em: '.$backupDir;
                $sendHtmlEnd(true, 'Sistema zerado com sucesso.', $detalhe);
            } catch (\Throwable $e) {
                $logFn('['.date('H:i:s').'] ERRO ao zerar: '.$e->getMessage());
                $detalheErro = $pularPreBackup
                    ? $e->getMessage()
                    : 'Backup foi salvo em: '.$backupDir.' — Erro: '.$e->getMessage();
                $sendHtmlEnd(false, 'Erro ao zerar o sistema.', $detalheErro);
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Exclui uma pasta de backup específica (ex.: pre-reset-2026-03-15_212029).
     */
    public function excluirBackup(Request $request)
    {
        $request->validate([
            'pasta' => 'required|string|max:100',
        ], [
            'pasta.required' => 'Informe a pasta do backup.',
        ]);

        $pasta = basename($request->input('pasta'));
        if (preg_match('/[^a-zA-Z0-9_\-]/', $pasta)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Nome do backup inválido.');
        }

        $backupDir = storage_path('app/backups/'.$pasta);
        if (! File::isDirectory($backupDir)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Backup não encontrado.');
        }

        try {
            File::deleteDirectory($backupDir);

            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('success', 'Backup "'.$pasta.'" excluído com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Erro ao excluir backup: '.$e->getMessage());
        }
    }

    /**
     * Restaura sistema a partir de um backup listado (cria cópia do estado atual antes).
     * Com stream=1 abre em nova aba e exibe o log em tempo real até concluir ou erro.
     */
    public function restaurarBackup(Request $request)
    {
        $request->validate([
            'pasta' => 'required|string|max:100',
        ], [
            'pasta.required' => 'Selecione o backup para restaurar.',
        ]);

        $pasta = basename($request->input('pasta'));
        if (preg_match('/[^a-zA-Z0-9_\-]/', $pasta)) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Nome do backup inválido.');
        }

        $backupDir = storage_path('app/backups/'.$pasta);
        if (! File::isDirectory($backupDir) || ! File::exists($backupDir.DIRECTORY_SEPARATOR.'dump.sql')) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Backup não encontrado ou inválido.');
        }

        $pularPreBackup = $request->boolean('pular_pre_backup');
        if ($pularPreBackup) {
            $request->validate([
                'confirmar_risco_sem_pre_backup' => 'required|accepted',
            ], [
                'confirmar_risco_sem_pre_backup.required' => 'Marque a confirmação de risco para restaurar sem cópia do estado atual.',
                'confirmar_risco_sem_pre_backup.accepted' => 'É necessário aceitar o risco de não ter cópia do estado atual.',
            ]);
        }

        if ($request->boolean('stream')) {
            return $this->restaurarBackupStream($pasta, $backupDir, $pularPreBackup);
        }

        $logPath = storage_path('app/restore-'.date('Y-m-d_His').'.log');
        $logFn = function (string $line) use ($logPath) {
            File::append($logPath, $line."\n");
        };

        try {
            $backupAtualDir = $this->runRestoreSteps($pasta, $backupDir, $logPath, $logFn, $pularPreBackup);
        } catch (\Throwable $e) {
            $restoreLogContent = File::exists($logPath) ? File::get($logPath) : '';

            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', $e->getMessage())
                ->with('restore_log_path', $logPath)
                ->with('restore_log', $restoreLogContent);
        }

        $restoreLogContent = File::exists($logPath) ? File::get($logPath) : '';

        $msgSucesso = $pularPreBackup
            ? 'Restauração concluída. Não foi criada cópia do estado atual (opção escolhida). Anexos anteriores foram removidos antes de importar. Em caso de dúvida no login, use admin@admin.com com senha "password".'
            : 'Restauração concluída com sucesso. Acesse o sistema com os dados restaurados. A cópia do estado anterior foi salva em: '.$backupAtualDir.' Em caso de dúvida no login, use admin@admin.com com senha "password".';

        return redirect()->route('configuracoes-sistema.utilidades.index')
            ->with('success', $msgSucesso)
            ->with('restore_log_path', $logPath)
            ->with('restore_log', $restoreLogContent);
    }

    /**
     * Executa os passos da restauração; escreve no $logFn e lança em caso de erro.
     *
     * @return string Caminho da pasta de backup do estado atual, ou texto descritivo se {@see $pularPreBackup}
     */
    protected function runRestoreSteps(string $pasta, string $backupDir, string $logPath, callable $logFn, bool $pularPreBackup = false): string
    {
        $logFn('=== Restauração de backup: '.$pasta.' ===');
        $logFn('['.date('Y-m-d H:i:s').'] Iniciando...');

        $backupAtualDir = '';
        $refCopiaParaErro = '';

        if ($pularPreBackup) {
            $backupAtualDir = '(cópia do estado atual não criada — opção sem pré-backup)';
            $refCopiaParaErro = 'Não há cópia do estado atual (opção sem pré-backup).';
            $logFn('['.date('H:i:s').'] Opção ativa: sem cópia de segurança. A remover anexos atuais em storage/app/public...');
            try {
                $this->limparAnexos();
                $logFn('['.date('H:i:s').'] Anexos atuais removidos.');
            } catch (\Throwable $e) {
                $logFn('['.date('H:i:s').'] ERRO ao limpar anexos: '.$e->getMessage());
                throw new \RuntimeException('Falha ao limpar anexos antes de restaurar. Nenhuma alteração no banco foi feita. Erro: '.$e->getMessage(), 0, $e);
            }
        } else {
            $backupAtualDir = storage_path('app/backups/pre-restore-'.date('Y-m-d_His'));
            $refCopiaParaErro = 'Cópia do estado atual salva em: '.$backupAtualDir;
            try {
                $logFn('['.date('H:i:s').'] Criando cópia do estado atual em: '.$backupAtualDir.' (dump + anexos em partes quando necessário)...');
                $this->criarBackupParaPasta($backupAtualDir);
                $logFn('['.date('H:i:s').'] Cópia criada com sucesso.');
            } catch (\Throwable $e) {
                $logFn('['.date('H:i:s').'] ERRO: '.$e->getMessage());
                throw new \RuntimeException('Falha ao criar cópia de segurança do estado atual. Nenhuma alteração foi feita. Erro: '.$e->getMessage(), 0, $e);
            }
        }

        try {
            $dumpPath = $backupDir.DIRECTORY_SEPARATOR.'dump.sql';
            $logFn('');
            $this->aplicarDumpSql($dumpPath, $logFn);
        } catch (\Throwable $e) {
            $logFn('['.date('H:i:s').'] ERRO ao aplicar dump: '.$e->getMessage());
            throw new \RuntimeException($refCopiaParaErro.' — Erro ao restaurar banco: '.$e->getMessage(), 0, $e);
        }

        $zipsAnexos = $this->listarArquivosZipAnexosDoBackup($backupDir);
        if ($zipsAnexos !== []) {
            try {
                $logFn('');
                $logFn('['.date('H:i:s').'] Restaurando anexos ('.count($zipsAnexos).' ficheiro(s) ZIP)...');
                $this->limparAnexos();
                $publicPath = storage_path('app/public');
                if (! File::isDirectory($publicPath)) {
                    File::makeDirectory($publicPath, 0755, true);
                }
                foreach ($zipsAnexos as $zipPath) {
                    $logFn('['.date('H:i:s').'] A extrair '.basename($zipPath).'...');
                    $this->extrairAnexosZipParaStorage($zipPath);
                }
                $logFn('['.date('H:i:s').'] Anexos restaurados.');
            } catch (\Throwable $e) {
                $logFn('['.date('H:i:s').'] ERRO anexos: '.$e->getMessage());
                throw new \RuntimeException('Banco restaurado. '.$refCopiaParaErro.' — Erro ao restaurar anexos: '.$e->getMessage(), 0, $e);
            }
        } else {
            $logFn('['.date('H:i:s').'] Nenhum anexos.zip nem anexos-part-*.zip no backup.');
        }

        $logFn('');
        $logFn('['.date('H:i:s').'] Garantindo usuário admin...');
        $this->garantirAdminAposRestore();
        $logFn('['.date('H:i:s').'] Concluído.');
        $logFn('=== Fim da restauração ===');

        return $backupAtualDir;
    }

    /**
     * Resposta em streaming: log em tempo real na aba até concluir ou erro.
     */
    protected function restaurarBackupStream(string $pasta, string $backupDir, bool $pularPreBackup = false): StreamedResponse
    {
        $logPath = storage_path('app/restore-'.date('Y-m-d_His').'.log');
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        return new StreamedResponse(function () use ($pasta, $backupDir, $logPath, $utilidadesUrl, $pularPreBackup) {
            $this->relaxarLimitesProcessoLongo();
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFn = function (string $line) use ($logPath) {
                File::append($logPath, $line."\n");
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };

            $sendHtmlStart = function () {
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Restauração em andamento</title>';
                echo '<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>';
                echo '</head><body><pre id="log">';
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };

            $sendHtmlEnd = function (bool $success, string $text, string $detail = '') use ($utilidadesUrl) {
                echo '</pre><div class="msg '.($success ? 'success' : 'error').'">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
                if ($detail !== '') {
                    echo '<p style="margin-top:0.5rem;font-size:0.9em;">'.nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')).'</p>';
                }
                echo '<p style="margin-top:1rem;"><a href="'.htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8').'" target="_parent">Voltar para Utilidades</a></p></body></html>';
            };

            $sendHtmlStart();

            try {
                $backupAtualDir = $this->runRestoreSteps($pasta, $backupDir, $logPath, $logFn, $pularPreBackup);
                $detalheOk = $pularPreBackup
                    ? "Sem cópia do estado anterior (opção escolhida).\nEm caso de dúvida no login: admin@admin.com / password"
                    : 'Cópia do estado anterior: '.$backupAtualDir."\nEm caso de dúvida no login: admin@admin.com / password";
                $sendHtmlEnd(true, 'Restauração concluída com sucesso. Acesse o sistema com os dados restaurados.', $detalheOk);
            } catch (\Throwable $e) {
                $logFn('['.date('H:i:s').'] ERRO: '.$e->getMessage());
                $sendHtmlEnd(false, 'Erro na restauração', $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Garante que exista pelo menos um usuário admin após restauração, para não ficar sem acesso.
     */
    protected function garantirAdminAposRestore(): void
    {
        $temAdmin = User::where('is_admin', true)->exists();
        if ($temAdmin) {
            return;
        }

        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'ativo' => true,
            ]
        );
        $admin->password = Hash::make('password');
        $admin->is_admin = true;
        $admin->ativo = true;
        $admin->save();

        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }
    }

    protected function limparAnexos(): void
    {
        $storagePath = storage_path('app/public');
        if (! File::isDirectory($storagePath)) {
            return;
        }
        $items = File::allFiles($storagePath);
        foreach ($items as $file) {
            try {
                File::delete($file->getPathname());
            } catch (\Throwable $e) {
                // Continua; alguns arquivos podem estar em uso no Windows
            }
        }
        $dirs = File::directories($storagePath);
        foreach ($dirs as $dir) {
            try {
                File::deleteDirectory($dir);
            } catch (\Throwable $e) {
                // Continua; algumas pastas podem falhar (ex.: permissão no Windows)
            }
        }
        // Garante que o diretório base existe e está gravável (evita Permission denied ao recriar)
        if (! File::isDirectory($storagePath)) {
            File::makeDirectory($storagePath, 0775, true);
        }
    }

    /**
     * Extrai o conteúdo de anexos.zip (com prefixo anexos/ ou anexos\) para storage/app/public.
     * No Windows usa permissões mais permissivas (0775/0777) para evitar "Permission denied".
     */
    protected function extrairAnexosZipParaStorage(string $zipPath): void
    {
        $resolvedPath = realpath($zipPath);
        if ($resolvedPath === false || ! File::exists($resolvedPath)) {
            throw new \RuntimeException('Arquivo ZIP de anexos não encontrado: '.$zipPath);
        }
        $zip = new ZipArchive;
        if ($zip->open($resolvedPath) !== true) {
            throw new \RuntimeException('Arquivo ZIP de anexos inválido ou inacessível: '.basename($zipPath));
        }

        $dest = rtrim(storage_path('app/public'), DIRECTORY_SEPARATOR.'/');
        $dirMode = 0775;
        $this->ensureDirectoryExists($dest, $dirMode);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $name = str_replace('\\', '/', $name);
            if (str_ends_with($name, '/')) {
                continue;
            }
            if (str_starts_with($name, 'anexos/')) {
                $path = substr($name, 7);
            } else {
                $path = $name;
            }
            $path = trim($path, '/');
            if ($path === '') {
                continue;
            }
            // Evita path traversal e nomes inválidos no Windows
            if (preg_match('#\.\.|[\x00-\x1f*?:"<>|]#', $path)) {
                continue;
            }
            $fullPath = $dest.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
            $dir = dirname($fullPath);
            $this->ensureDirectoryExists($dir, $dirMode);
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                File::put($fullPath, $content);
            }
        }
        $zip->close();
    }

    /**
     * Texto completo da exceção (e anteriores) para tela e arquivo de log.
     */
    protected function formatImportThrowableChain(\Throwable $e): string
    {
        $parts = [];
        $cur = $e;
        $depth = 0;
        while ($cur !== null && $depth < 8) {
            $parts[] = sprintf(
                "[%s] %s\n%s:%d",
                $cur::class,
                $cur->getMessage() !== '' ? $cur->getMessage() : '(sem mensagem)',
                $cur->getFile(),
                $cur->getLine()
            );
            $cur = $cur->getPrevious();
            $depth++;
        }

        return implode("\n\n--- Causa anterior ---\n\n", $parts);
    }

    /**
     * Grava log de falha na importação completa (multipart ou ZIP) para diagnóstico na hospedagem.
     *
     * @param  array<int, string>  $logLinesRaw  Linhas já emitidas pelo callback de log antes do erro
     * @return string Caminho absoluto do arquivo criado, ou string vazia se não foi possível gravar
     */
    protected function writeImportCompletoErrorLog(\Throwable $e, array $logLinesRaw): string
    {
        $logsDir = storage_path('logs');
        if (! File::isDirectory($logsDir)) {
            try {
                File::makeDirectory($logsDir, 0775, true);
            } catch (\Throwable) {
                return '';
            }
        }

        $name = 'import-completo-error-'.date('Y-m-d_His').'.log';
        $path = $logsDir.DIRECTORY_SEPARATOR.$name;
        $body = '['.date('c')."] Importação completa falhou\n\n";
        $body .= $this->formatImportThrowableChain($e)."\n\n";
        $body .= "--- Stack trace ---\n".$e->getTraceAsString()."\n";
        if ($logLinesRaw !== []) {
            $body .= "\n--- Log parcial da importação ---\n".implode("\n", $logLinesRaw)."\n";
        }

        try {
            File::put($path, $body);
            @File::put($logsDir.DIRECTORY_SEPARATOR.'import-completo-LAST-error.log', $body);

            return $path;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Remove exportações temporárias antigas em storage/app/temp.
     * Usado no lugar de deleteFileAfterSend(true), que em alguns servidores (ex.: cPanel/LiteSpeed)
     * provoca unlink duplicado e ErrorException "No such file or directory".
     */
    protected function limparExportsTempAntigos(int $idadeMinimaSegundos = 7200): void
    {
        $dir = storage_path('app/temp');
        if (! File::isDirectory($dir)) {
            return;
        }

        $patterns = [
            'erp-export-completo-*.zip',
            'erp-anexos-*.zip',
            'erp-backup-banco-*.sql',
            'dump-completo-*.sql',
        ];

        $now = time();
        foreach ($patterns as $pattern) {
            foreach (File::glob($dir.DIRECTORY_SEPARATOR.$pattern) ?: [] as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $mtime = @filemtime($path);
                if ($mtime !== false && ($now - $mtime) >= $idadeMinimaSegundos) {
                    @unlink($path);
                }
            }
        }

        foreach (File::glob($dir.DIRECTORY_SEPARATOR.'export_parts'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.with-manifest.zip') ?: [] as $path) {
            if (! is_file($path)) {
                continue;
            }
            $mtime = @filemtime($path);
            if ($mtime !== false && ($now - $mtime) >= $idadeMinimaSegundos) {
                @unlink($path);
            }
        }
    }

    /**
     * Cria diretório recursivamente; no Windows tenta 0777 se 0775 falhar (evita Permission denied).
     */
    protected function ensureDirectoryExists(string $dir, int $mode = 0775): void
    {
        if (File::isDirectory($dir)) {
            return;
        }
        try {
            File::makeDirectory($dir, $mode, true);
        } catch (\Throwable $e) {
            if ($mode !== 0777 && str_contains($e->getMessage(), 'Permission denied')) {
                try {
                    File::makeDirectory($dir, 0777, true);
                } catch (\Throwable $e2) {
                    throw new \RuntimeException('Não foi possível criar o diretório: '.$dir.'. Verifique permissões em storage/app/public (no Windows: executar como administrador ou liberar permissão de gravação na pasta). Erro: '.$e2->getMessage(), 0, $e2);
                }
            } else {
                throw new \RuntimeException('Não foi possível criar o diretório: '.$dir.'. Erro: '.$e->getMessage(), 0, $e);
            }
        }
    }
}
