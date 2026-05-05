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

namespace Plugins\ImportarSistemaExternoMos;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Plugins\ImportarSistemaExternoMos\Services\MosExportMetaReader;
use Plugins\ImportarSistemaExternoMos\Services\MosManifestFileImportService;
use Plugins\ImportarSistemaExternoMos\Services\MosMultipartSessionService;
use Plugins\ImportarSistemaExternoMos\Services\MosSqlImportService;
use Plugins\ImportarSistemaExternoMos\Services\MosZipSupport;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importação M-OS via Utilidades (filtros utilidades.import.*).
 *
 * ZIP simples: extrai o(s) .sql (um ou vários concatenados) e importa (MosSqlImportService).
 * ZIP completo: após o SQL, se existir manifest na pasta de ficheiros do export,
 * MosManifestFileImportService copia ficheiros
 * para storage público e atualiza caminhos nos registos já criados.
 */
class MosImportHandler
{
    /**
     * Importações M-OS podem demorar horas e exceder memória padrão (ZIP ~800MB+).
     * Ajuste opcional no .env: MOS_IMPORT_MEMORY_LIMIT=2048M ou -1
     */
    protected static function prepareLongRunningImport(): void
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $limit = env('MOS_IMPORT_MEMORY_LIMIT', '2048M');
        if (is_string($limit) && $limit !== '' && $limit !== 'default') {
            @ini_set('memory_limit', $limit);
        }
    }

    public static function handle($file, string $ext, Request $request): RedirectResponse|StreamedResponse
    {
        if (!in_array($ext, ['sql', 'zip'], true)) {
            return redirect()->back()->with('error', 'Para importar o export M-OS use um arquivo .sql (ou .zip contendo .sql).');
        }

        if ($ext === 'zip') {
            MosZipSupport::assertAvailable();
        }

        if ($request->boolean('stream')) {
            return self::stream($file, $ext);
        }

        return self::import($file, $ext);
    }

    /**
     * Vários ZIPs (exportação v2 em partes ~50MB).
     *
     * @param  array<int, UploadedFile>  $files
     */
    public static function handleMany(array $files, Request $request): RedirectResponse|StreamedResponse|null
    {
        $files = array_values(array_filter($files, static fn ($f) => $f instanceof UploadedFile && $f->isValid()));
        if ($files === []) {
            return null;
        }

        MosZipSupport::assertAvailable();

        if (count($files) === 1 && !self::zipHasExportMetaV2($files[0]->getRealPath())) {
            return null;
        }

        $sorted = self::sortUploadedZipPartsByMeta($files);

        if ($request->boolean('stream')) {
            return self::streamMany($sorted, $request);
        }

        return self::importManyRedirect($sorted);
    }

    /**
     * @param  array<int, UploadedFile>  $sortedFiles
     */
    protected static function importManyRedirect(array $sortedFiles): RedirectResponse
    {
        self::prepareLongRunningImport();
        $logFile = self::createMosImportLogFilePath();
        $logFn = self::makeFileOnlyLogFn($logFile);

        try {
            $sessionSvc = app(MosMultipartSessionService::class);
            $aggregate = self::runManyParts($sortedFiles, $logFn, $sessionSvc);

            $msg = self::buildAggregateMessage($aggregate);
            $msg .= ' Log: ' . $logFile;

            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('success', $msg)
                ->with('import_log_path', $logFile);
        } catch (\Throwable $e) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Erro na importação M-OS (multi-parte): ' . $e->getMessage());
        }
    }

    /**
     * @param  array<int, UploadedFile>  $sortedFiles
     */
    protected static function streamMany(array $sortedFiles, Request $request): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');

        return new StreamedResponse(function () use ($utilidadesUrl, $sortedFiles) {
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFile = MosImportHandler::createMosImportLogFilePath();
            MosImportHandler::appendMosLogLine($logFile, '=== Importação M-OS multi-parte (stream) ===');
            $echoLogFn = function (string $line): void {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $logFn = MosImportHandler::makeTeeStreamLogFn($echoLogFn, $logFile);
            $sendHtmlStart = function (): void {
                echo "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Importação M-OS (multi-parte)</title>";
                echo "<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>";
                echo "</head><body><pre id=\"log\">";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlEnd = function (bool $success, string $text, string $detail = '', string $logPath = '') use ($utilidadesUrl): void {
                echo "</pre><div class=\"msg " . ($success ? 'success' : 'error') . "\">" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</div>";
                if ($detail !== '') {
                    echo "<p style=\"margin-top:0.5rem;font-size:0.9em;\">" . nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')) . "</p>";
                }
                if ($logPath !== '') {
                    echo '<p style="margin-top:0.75rem;font-size:0.85em;opacity:0.9;">Log: <code style="word-break:break-all;">' . htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8') . '</code></p>';
                }
                echo "<p style=\"margin-top:1rem;\"><a href=\"" . htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_parent\">Voltar para Utilidades</a></p></body></html>";
            };

            $sendHtmlStart();
            self::prepareLongRunningImport();

            try {
                $sessionSvc = app(MosMultipartSessionService::class);
                $aggregate = self::runManyParts($sortedFiles, $logFn, $sessionSvc);
                $detail = self::buildAggregateMessage($aggregate);
                $sendHtmlEnd(true, 'Importação M-OS multi-parte concluída.', $detail, $logFile);
            } catch (\Throwable $e) {
                $logFn('ERRO: ' . $e->getMessage());
                $sendHtmlEnd(false, 'Erro na importação M-OS multi-parte', $e->getMessage(), $logFile);
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $sortedFiles
     * @return array{parts:int, empresa:int, clientes:int, manifest_copiados:int, manifest_falhas:int}
     */
    protected static function runManyParts(array $sortedFiles, callable $logFn, MosMultipartSessionService $sessionSvc): array
    {
        $aggregate = [
            'parts' => 0,
            'empresa' => 0,
            'clientes' => 0,
            'manifest_copiados' => 0,
            'manifest_falhas' => 0,
        ];

        $logFn('=== Importação M-OS multi-parte (livreos_format_version 2) ===');
        $logFn('[' . date('Y-m-d H:i:s') . '] ' . count($sortedFiles) . ' ficheiro(s) neste pedido.');

        foreach ($sortedFiles as $upload) {
            $zipPath = $upload->getRealPath();
            if (!$zipPath || !is_readable($zipPath)) {
                throw new \RuntimeException('ZIP ilegível: ' . $upload->getClientOriginalName());
            }

            $meta = MosExportMetaReader::readFromZip($zipPath);
            if ($meta === null || !MosExportMetaReader::isFormatV2($meta)) {
                throw new \RuntimeException(
                    'ZIP sem export_meta.json válido (livreos_format_version 2). ' .
                    'Com vários ficheiros, todas as partes devem ser da exportação em partes (formato v2).'
                );
            }

            $exportId = trim((string) ($meta['export_id'] ?? ''));
            $partIndex = (int) ($meta['part_index'] ?? 0);
            $partTotal = (int) ($meta['part_total'] ?? 0);
            if ($exportId === '' || $partIndex < 1 || $partTotal < 1) {
                throw new \RuntimeException('export_meta.json incompleto: export_id, part_index ou part_total em falta.');
            }

            if ($partIndex === 1) {
                $st = $sessionSvc->getState();
                if ($st !== null && $st['export_id'] !== $exportId) {
                    $sessionSvc->clear();
                    $logFn('  [sessão] Nova export_id na parte 1 — estado anterior limpo.');
                }
            }

            $sessionSvc->assertCanImportPart($exportId, $partIndex, $partTotal);

            $logFn('');
            $logFn('>>> Parte ' . $partIndex . '/' . $partTotal . ' (export_id: ' . $exportId . ') <<<');

            $extracted = self::extractSqlFromZipToTemp($zipPath);
            if ($extracted === null) {
                throw new \RuntimeException('Parte ' . $partIndex . ': não foi encontrado ficheiro .sql no ZIP.');
            }
            $tmpSql = $extracted['path'];
            if ($extracted['multiple_sql']) {
                $logFn('  [meta] Vários ficheiros .sql nesta parte foram concatenados.');
            }

            try {
                $checksum = isset($meta['checksum_sha256_sql']) ? (string) $meta['checksum_sha256_sql'] : null;
                if ($extracted['multiple_sql'] && $checksum !== null && trim($checksum) !== '') {
                    $logFn('  [meta] Checksum SHA-256 ignorado (conteúdo SQL resulta da junção de vários .sql no ZIP).');
                    $checksum = null;
                }
                MosExportMetaReader::assertChecksum($tmpSql, $checksum !== null && $checksum !== '' ? $checksum : null, $logFn);

                $result = app(MosSqlImportService::class)->importFromSqlPath($tmpSql, $logFn, true, $exportId);
                $manifestStats = app(MosManifestFileImportService::class)->processZip($zipPath, $logFn);

                $sessionSvc->markPartCompleted($exportId, $partIndex, $partTotal, $upload->getClientOriginalName());

                $aggregate['parts']++;
                $aggregate['empresa'] += (int) ($result['empresa'] ?? 0);
                $aggregate['clientes'] += (int) ($result['clientes'] ?? 0);
                if (!empty($manifestStats['tinha_manifest'])) {
                    $aggregate['manifest_copiados'] += (int) ($manifestStats['copiados'] ?? 0);
                    $aggregate['manifest_falhas'] += (int) ($manifestStats['falhas'] ?? 0);
                }

                $logFn('>>> Parte ' . $partIndex . '/' . $partTotal . ' concluída.');
            } finally {
                if (is_file($tmpSql)) {
                    @unlink($tmpSql);
                }
            }
        }

        return $aggregate;
    }

    protected static function buildAggregateMessage(array $aggregate): string
    {
        return sprintf(
            'Partes importadas neste pedido: %d. Acumulado (SQL): empresa +%d, clientes +%d. Manifest (ficheiros): +%d copiados, +%d falhas.',
            $aggregate['parts'],
            $aggregate['empresa'],
            $aggregate['clientes'],
            $aggregate['manifest_copiados'],
            $aggregate['manifest_falhas']
        );
    }

    protected static function zipHasExportMetaV2(?string $zipPath): bool
    {
        if (!$zipPath || !is_readable($zipPath)) {
            return false;
        }
        $meta = MosExportMetaReader::readFromZip($zipPath);

        return $meta !== null && MosExportMetaReader::isFormatV2($meta);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, UploadedFile>
     */
    protected static function sortUploadedZipPartsByMeta(array $files): array
    {
        $rows = [];
        foreach ($files as $f) {
            $path = $f->getRealPath();
            $meta = $path ? MosExportMetaReader::readFromZip($path) : null;
            if ($meta === null || !MosExportMetaReader::isFormatV2($meta)) {
                throw new \RuntimeException(
                    'Cada ZIP deve conter export_meta.json com livreos_format_version 2. Ficheiro: ' . $f->getClientOriginalName()
                );
            }
            $partIndex = (int) ($meta['part_index'] ?? 0);
            if ($partIndex < 1) {
                throw new \RuntimeException('part_index inválido em: ' . $f->getClientOriginalName());
            }
            $rows[] = ['part' => $partIndex, 'file' => $f, 'export' => trim((string) ($meta['export_id'] ?? ''))];
        }

        usort($rows, static fn ($a, $b) => $a['part'] <=> $b['part']);

        $seen = [];
        foreach ($rows as $r) {
            $k = $r['export'] . ':' . $r['part'];
            if (isset($seen[$k])) {
                throw new \RuntimeException('Parte duplicada no mesmo pedido: export_id ' . $r['export'] . ' part ' . $r['part']);
            }
            $seen[$k] = true;
        }

        $exportIds = array_unique(array_column($rows, 'export'));
        if (count($exportIds) > 1) {
            throw new \RuntimeException('Todos os ZIPs deste pedido devem ter o mesmo export_id. Encontrados: ' . implode(', ', $exportIds));
        }

        return array_map(static fn ($r) => $r['file'], $rows);
    }

    protected static function import($file, string $ext): RedirectResponse
    {
        self::prepareLongRunningImport();

        $tmpPath = null;
        $path = $file->getRealPath();
        $sqlMergedFromMultipleZipEntries = false;

        try {
            if ($ext === 'zip') {
                $extracted = self::extractSqlFromZipToTemp((string) $path);
                if ($extracted === null) {
                    return redirect()->back()->with('error', 'Nenhum arquivo .sql foi encontrado no ZIP (ou falha ao extrair por streaming).');
                }
                $tmpPath = $extracted['path'];
                $sqlMergedFromMultipleZipEntries = $extracted['multiple_sql'];
                $path = $tmpPath;
            }

            $logFile = self::createMosImportLogFilePath();
            $logFn = self::makeFileOnlyLogFn($logFile);

            $logFn('=== Importação M-OS (plugin) ===');
            $logFn('[' . date('Y-m-d H:i:s') . '] Iniciando...');

            $manifestStats = null;
            if ($ext === 'zip') {
                $zipRealPath = $file->getRealPath();
                [$result, $manifestStats] = self::runZipSqlAndManifest((string) $zipRealPath, $path, $logFn, $file->getClientOriginalName(), $sqlMergedFromMultipleZipEntries);
            } else {
                $result = app(MosSqlImportService::class)->importFromSqlPath($path, $logFn, false);
            }

            $logFn('[' . date('H:i:s') . '] Fim.');

            $msg = sprintf(
                'Importação M-OS concluída. Empresa: %d, Clientes: %d, Produtos: %d, Serviços: %d, OS: %d, Equipamentos: %d, Documentos: %d, Financeiro (CR/CP): %d/%d, Pedidos de venda (novos): %d, Vendas CR/Cobranças: %d/%d, Anexos OS: %d, Histórico OS (anotações/logs): %d/%d, Conciliações (CR/CP): %d/%d.',
                $result['empresa'] ?? 0,
                $result['clientes'] ?? 0,
                $result['produtos'] ?? 0,
                $result['servicos'] ?? 0,
                $result['ordens_servico'] ?? 0,
                $result['equipamentos'] ?? 0,
                $result['documentos'] ?? 0,
                $result['contas_receber'] ?? 0,
                $result['contas_pagar'] ?? 0,
                $result['pedidos_venda'] ?? 0,
                $result['vendas'] ?? 0,
                $result['cobrancas'] ?? 0,
                $result['ordem_servico_anexos'] ?? 0,
                $result['anotacoes_os_logs'] ?? 0,
                $result['logs_os'] ?? 0,
                $result['conciliacoes_receber'] ?? 0,
                $result['conciliacoes_pagar'] ?? 0
            );
            if ($manifestStats !== null && !empty($manifestStats['tinha_manifest'])) {
                $msg .= sprintf(
                    ' Ficheiros no ZIP (manifest → storage público): %d copiados/atualizados, %d falha(s), %d ignorado(s).',
                    $manifestStats['copiados'],
                    $manifestStats['falhas'],
                    $manifestStats['ignorados']
                );
            }

            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('success', $msg)
                ->with('import_log_path', $logFile);
        } catch (\Throwable $e) {
            return redirect()->route('configuracoes-sistema.utilidades.index')
                ->with('error', 'Erro ao importar SQL do export: ' . $e->getMessage());
        } finally {
            if ($tmpPath && File::exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    protected static function stream($file, string $ext): StreamedResponse
    {
        $utilidadesUrl = route('configuracoes-sistema.utilidades.index');
        $filePath = $file->getRealPath();
        $originalZipFilename = $file->getClientOriginalName();

        return new StreamedResponse(function () use ($utilidadesUrl, $filePath, $ext, $originalZipFilename) {
            while (ob_get_level()) {
                ob_end_flush();
            }
            $logFile = MosImportHandler::createMosImportLogFilePath();
            MosImportHandler::appendMosLogLine($logFile, '=== Importação M-OS (stream) ===');
            $echoLogFn = function (string $line): void {
                echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $logFn = MosImportHandler::makeTeeStreamLogFn($echoLogFn, $logFile);
            $sendHtmlStart = function (): void {
                echo "<!DOCTYPE html><html><head><meta charset=\"utf-8\"><title>Importação M-OS</title>";
                echo "<style>body{font-family:Consolas,monospace;background:#1a1a2e;color:#a0e0a0;padding:1rem;margin:0;} pre{white-space:pre-wrap;word-break:break-all;margin:0;} .msg{margin-top:1rem;padding:0.75rem;border-radius:6px;} .msg.success{background:#0d3d0d;color:#90ee90;} .msg.error{background:#3d0d0d;color:#ff9090;} a{color:#6eb6ff;}</style>";
                echo "</head><body><pre id=\"log\">";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $sendHtmlEnd = function (bool $success, string $text, string $detail = '', string $logPath = '') use ($utilidadesUrl): void {
                echo "</pre><div class=\"msg " . ($success ? 'success' : 'error') . "\">" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</div>";
                if ($detail !== '') {
                    echo "<p style=\"margin-top:0.5rem;font-size:0.9em;\">" . nl2br(htmlspecialchars($detail, ENT_QUOTES, 'UTF-8')) . "</p>";
                }
                if ($logPath !== '') {
                    echo '<p style="margin-top:0.75rem;font-size:0.85em;opacity:0.9;">Log: <code style="word-break:break-all;">' . htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8') . '</code></p>';
                }
                echo "<p style=\"margin-top:1rem;\"><a href=\"" . htmlspecialchars($utilidadesUrl, ENT_QUOTES, 'UTF-8') . "\" target=\"_parent\">Voltar para Utilidades</a></p></body></html>";
            };

            $sendHtmlStart();
            MosImportHandler::prepareLongRunningImport();
            $tmpPath = null;
            $sqlPath = $filePath;
            $sqlMergedFromMultipleZipEntries = false;

            try {
                if ($ext === 'zip') {
                    $logFn('[' . date('H:i:s') . '] Extraindo .sql do ZIP (streaming, adequado a arquivos grandes)...');
                    $extracted = MosImportHandler::extractSqlFromZipToTemp($filePath);
                    if ($extracted === null) {
                        throw new \RuntimeException('Nenhum arquivo .sql foi encontrado no ZIP (ou falha ao extrair por streaming).');
                    }
                    $tmpPath = $extracted['path'];
                    $sqlMergedFromMultipleZipEntries = $extracted['multiple_sql'];
                    if ($sqlMergedFromMultipleZipEntries) {
                        $logFn('[' . date('H:i:s') . '] Vários ficheiros .sql no ZIP foram concatenados num único ficheiro temporário.');
                    }
                    $sqlPath = $tmpPath;
                }

                $logFn('=== Importação M-OS (.sql) ===');
                $logFn('[' . date('Y-m-d H:i:s') . '] Iniciando...');

                if ($ext === 'zip' && $filePath && is_readable($filePath)) {
                    [$result,] = MosImportHandler::runZipSqlAndManifest($filePath, $sqlPath, $logFn, $originalZipFilename, $sqlMergedFromMultipleZipEntries);
                } else {
                    $result = app(MosSqlImportService::class)->importFromSqlPath($sqlPath, $logFn, false);
                }

                $summary = sprintf(
                    'Empresa: %d | Clientes: %d | Produtos: %d | Serviços: %d | OS: %d | Equipamentos: %d (%d vinc. OS) | Documentos: %d | Itens OS (prod/serv): %d/%d | Financeiro (CR/CP): %d/%d | Pedidos venda (novos): %d | Vendas CR/Cobranças: %d/%d | Anexos OS: %d | Histórico OS (anot/log): %d/%d | Conciliações (CR/CP): %d/%d',
                    $result['empresa'] ?? 0,
                    $result['clientes'] ?? 0,
                    $result['produtos'] ?? 0,
                    $result['servicos'] ?? 0,
                    $result['ordens_servico'] ?? 0,
                    $result['equipamentos'] ?? 0,
                    $result['equipamentos_os'] ?? 0,
                    $result['documentos'] ?? 0,
                    $result['os_produtos'] ?? 0,
                    $result['os_servicos'] ?? 0,
                    $result['contas_receber'] ?? 0,
                    $result['contas_pagar'] ?? 0,
                    $result['pedidos_venda'] ?? 0,
                    $result['vendas'] ?? 0,
                    $result['cobrancas'] ?? 0,
                    $result['ordem_servico_anexos'] ?? 0,
                    $result['anotacoes_os_logs'] ?? 0,
                    $result['logs_os'] ?? 0,
                    $result['conciliacoes_receber'] ?? 0,
                    $result['conciliacoes_pagar'] ?? 0
                );
                $logFn('[' . date('H:i:s') . '] Concluído.');
                $sendHtmlEnd(true, 'Importação M-OS concluída com sucesso.', $summary, $logFile);
            } catch (\Throwable $e) {
                $logFn('ERRO: ' . $e->getMessage());
                $sendHtmlEnd(false, 'Erro na importação M-OS', $e->getMessage(), $logFile);
            } finally {
                if ($tmpPath && File::exists($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * SQL + manifest de um ZIP (legacy v1 ou parte única; v2 com export_meta.json).
     *
     * @return array{0: array<string, int>, 1: array<string, mixed>|null}
     */
    protected static function runZipSqlAndManifest(string $zipRealPath, string $sqlTmpPath, callable $logFn, ?string $originalZipFilename = null, bool $sqlMergedFromMultipleZipEntries = false): array
    {
        if (!is_readable($zipRealPath)) {
            throw new \RuntimeException('ZIP ilegível.');
        }

        $meta = MosExportMetaReader::readFromZip($zipRealPath);
        $isV2 = $meta !== null && MosExportMetaReader::isFormatV2($meta);

        if ($isV2) {
            $exportId = trim((string) ($meta['export_id'] ?? ''));
            $partIndex = (int) ($meta['part_index'] ?? 0);
            $partTotal = (int) ($meta['part_total'] ?? 0);
            if ($exportId === '' || $partIndex < 1 || $partTotal < 1) {
                throw new \RuntimeException('export_meta.json incompleto (export_id, part_index, part_total).');
            }

            $sessionSvc = app(MosMultipartSessionService::class);
            if ($partIndex === 1) {
                $st = $sessionSvc->getState();
                if ($st !== null && $st['export_id'] !== $exportId) {
                    $sessionSvc->clear();
                    $logFn('  [sessão] Nova export_id na parte 1 — estado anterior limpo.');
                }
            }
            $sessionSvc->assertCanImportPart($exportId, $partIndex, $partTotal);

            $checksum = isset($meta['checksum_sha256_sql']) ? (string) $meta['checksum_sha256_sql'] : null;
            if ($sqlMergedFromMultipleZipEntries && $checksum !== null && trim($checksum) !== '') {
                $logFn('  [meta] Checksum SHA-256 ignorado (vários .sql do ZIP foram concatenados num único ficheiro).');
                $checksum = '';
            }
            MosExportMetaReader::assertChecksum($sqlTmpPath, $checksum !== '' ? $checksum : null, $logFn);

            $result = app(MosSqlImportService::class)->importFromSqlPath($sqlTmpPath, $logFn, true, $exportId);
            $manifestStats = app(MosManifestFileImportService::class)->processZip($zipRealPath, $logFn);
            $sessionSvc->markPartCompleted($exportId, $partIndex, $partTotal, $originalZipFilename);

            return [$result, $manifestStats];
        }

        $result = app(MosSqlImportService::class)->importFromSqlPath($sqlTmpPath, $logFn, false);
        $manifestStats = app(MosManifestFileImportService::class)->processZip($zipRealPath, $logFn);

        return [$result, $manifestStats];
    }

    protected static function createMosImportLogFilePath(): string
    {
        $dir = storage_path('app');
        File::ensureDirectoryExists($dir);

        return $dir . DIRECTORY_SEPARATOR . 'import-mos-' . date('Y-m-d_His') . '_' . bin2hex(random_bytes(3)) . '.log';
    }

    protected static function appendMosLogLine(string $logFile, string $line): void
    {
        $written = @file_put_contents($logFile, $line . "\n", FILE_APPEND | LOCK_EX);
        if ($written === false) {
            Log::warning('Import M-OS: não foi possível escrever no ficheiro de log.', ['path' => $logFile]);
        }
    }

    /**
     * @return callable(string): void
     */
    protected static function makeFileOnlyLogFn(string $logFile): callable
    {
        return function (string $line) use ($logFile): void {
            self::appendMosLogLine($logFile, $line);
        };
    }

    /**
     * Grava no disco (texto cru) e replica no browser (HTML escapado).
     *
     * @param  callable(string): void  $echoLogFn
     * @return callable(string): void
     */
    protected static function makeTeeStreamLogFn(callable $echoLogFn, string $logFile): callable
    {
        return function (string $line) use ($echoLogFn, $logFile): void {
            self::appendMosLogLine($logFile, $line);
            $echoLogFn($line);
        };
    }

    /**
     * Prioridade na ordenação de .sql dentro do ZIP (export atual: livreos_m_os_export.sql; legado: livreos_export.sql).
     */
    protected static function zipSqlBasenameSortPriority(string $basenameLower): int
    {
        if (str_contains($basenameLower, 'livreos_m_os_export.sql')) {
            return 2;
        }
        if (str_contains($basenameLower, 'livreos_export.sql')) {
            return 1;
        }

        return 0;
    }

    /**
     * Lista entradas .sql do ZIP (exclui __MACOSX). Ordem: livreos_m_os_export.sql, depois livreos_export.sql, depois por caminho.
     *
     * @return list<string>
     */
    protected static function listSqlEntriesFromZip(string $zipPath): array
    {
        MosZipSupport::assertAvailable();

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $norm = str_replace('\\', '/', (string) $name);
            if (str_contains($norm, '__MACOSX/')) {
                continue;
            }
            if (!preg_match('/\.sql$/i', $norm)) {
                continue;
            }
            $entries[] = (string) $name;
        }
        $zip->close();

        if ($entries === []) {
            return [];
        }

        usort($entries, static function (string $a, string $b): int {
            $na = strtolower(basename(str_replace('\\', '/', $a)));
            $nb = strtolower(basename(str_replace('\\', '/', $b)));
            $pa = self::zipSqlBasenameSortPriority($na);
            $pb = self::zipSqlBasenameSortPriority($nb);
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }

            return strcmp(str_replace('\\', '/', $a), str_replace('\\', '/', $b));
        });

        return array_values($entries);
    }

    /**
     * Extrai .sql do ZIP: um ficheiro em streaming ou vários concatenados (para dumps com tabelas em ficheiros separados).
     * Requer PHP 8+ (ZipArchive::getStream).
     *
     * @return array{path: string, multiple_sql: bool}|null
     */
    protected static function extractSqlFromZipToTemp(string $zipPath): ?array
    {
        $entries = self::listSqlEntriesFromZip($zipPath);
        if ($entries === []) {
            return null;
        }

        if (count($entries) === 1) {
            $path = self::streamSingleSqlEntryFromZipToTemp($zipPath, $entries[0]);

            return $path !== null ? ['path' => $path, 'multiple_sql' => false] : null;
        }

        $path = self::streamMergeSqlEntriesFromZipToTemp($zipPath, $entries);

        return $path !== null ? ['path' => $path, 'multiple_sql' => true] : null;
    }

    protected static function streamSingleSqlEntryFromZipToTemp(string $zipPath, string $sqlName): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $readStream = $zip->getStream($sqlName);
        if ($readStream === false) {
            $readStream = $zip->getStream(str_replace('/', '\\', $sqlName));
        }
        if ($readStream === false) {
            $zip->close();

            return null;
        }

        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mos_sql_' . bin2hex(random_bytes(12)) . '.sql';
        $out = fopen($tmpPath, 'wb');
        if ($out === false) {
            fclose($readStream);
            $zip->close();

            return null;
        }

        stream_copy_to_stream($readStream, $out);
        fclose($out);
        fclose($readStream);
        $zip->close();

        return is_file($tmpPath) && filesize($tmpPath) > 0 ? $tmpPath : null;
    }

    /**
     * @param  list<string>  $entries
     */
    protected static function streamMergeSqlEntriesFromZipToTemp(string $zipPath, array $entries): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mos_sql_' . bin2hex(random_bytes(12)) . '.sql';
        $out = fopen($tmpPath, 'wb');
        if ($out === false) {
            $zip->close();

            return null;
        }

        $copied = 0;
        $first = true;
        foreach ($entries as $sqlName) {
            $readStream = $zip->getStream($sqlName);
            if ($readStream === false) {
                $readStream = $zip->getStream(str_replace('/', '\\', $sqlName));
            }
            if ($readStream === false) {
                continue;
            }
            if (!$first) {
                fwrite($out, "\n\n");
            }
            $first = false;
            $safeComment = preg_replace('/[\r\n\x00]/', ' ', (string) $sqlName) ?? '';
            fwrite($out, '-- importar-sistema-externo-mos: merged ZIP entry: ' . $safeComment . "\n");
            stream_copy_to_stream($readStream, $out);
            fclose($readStream);
            $copied++;
        }
        fclose($out);
        $zip->close();

        if ($copied === 0 || !is_file($tmpPath) || filesize($tmpPath) === 0) {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }

            return null;
        }

        return $tmpPath;
    }
}
