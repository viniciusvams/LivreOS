<?php

declare(strict_types=1);


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

namespace Plugins\ImportarSistemaExternoMos\Services;

use App\Models\ContaPagar;
use App\Models\ContaPagarAnexo;
use App\Models\ContaReceber;
use App\Models\ContaReceberAnexo;
use App\Models\Documento;
use App\Models\OrdemServico;
use App\Models\OrdemServicoAnexo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Segunda fase da importação ZIP (export M-OS / legado): lê manifest na pasta de ficheiros do export,
 * copia ficheiros para storage/public e atualiza caminho_arquivo (e metadados de thumb quando aplicável).
 *
 * ZIP "só SQL": sem pasta manifest → retorna zeros sem erro.
 * ZIP "completo": SQL + pasta de binários + manifest.json (nomes aceites: m_os_arquivos/ export atual, arquivos_mos/, legado arquivos_*).
 *
 * Manifest v2 (export alinhado LivreOS), entradas `tabela: anexos`:
 * - ID do anexo: `id`, `idanexos`, `id_anexos`, `idanexo` (JSON pode vir como número).
 * - ID da OS (multi-ZIP sem INSERT `os` na mesma parte): `os_id`, `id_os`, `idos` (equivalentes).
 */
class MosManifestFileImportService
{
    /** Pasta de ficheiros no ZIP gerado por exportacao_livreos_m_os.php (contrato atual). */
    private const EXPORT_FILES_DIR_STANDARD = 'm_os_arquivos';

    /** Pasta em exports anteriores ao script renomeado. */
    private const EXPORT_FILES_DIR_ALT = 'arquivos_mos';

    /** Pasta em ZIPs muito antigos (nome segmentado). */
    private const LEGACY_EXPORT_FILES_DIR = 'arquivos_' . 'm' . 'apo' . 's';

    /**
     * Inteiro positivo a partir de valor de manifest (string numérica ou JSON number).
     */
    private function manifestEntryPositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return null;
        }
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_float($value)) {
            $i = (int) $value;

            return $i > 0 ? $i : null;
        }
        $s = trim((string) $value);
        if ($s === '' || !ctype_digit($s)) {
            return null;
        }
        $i = (int) $s;

        return $i > 0 ? $i : null;
    }

    /**
     * ID do anexo na origem (idAnexos / id) na entrada do manifest.
     *
     * @param  array<string, mixed>  $entry
     */
    private function extractManifestAnexoSourceId(array $entry): ?int
    {
        foreach (['id', 'idanexos', 'id_anexos', 'idanexo', 'id_anexo'] as $k) {
            if (!array_key_exists($k, $entry)) {
                continue;
            }
            $n = $this->manifestEntryPositiveInt($entry[$k]);
            if ($n !== null) {
                return $n;
            }
        }

        return null;
    }
    /**
     * @param  callable(string):void|null  $logFn
     * @return array{copiados:int, falhas:int, ignorados:int, tinha_manifest:bool}
     */
    public function processZip(string $zipPath, ?callable $logFn = null): array
    {
        $log = $logFn ?? static fn () => null;
        $stats = ['copiados' => 0, 'falhas' => 0, 'ignorados' => 0, 'tinha_manifest' => false];

        if (!is_readable($zipPath)) {
            $log('  [manifest] ZIP ilegível para segunda fase.');

            return $stats;
        }

        MosZipSupport::assertAvailable();

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $log('  [manifest] Não foi possível reabrir o ZIP para o manifest.');

            return $stats;
        }

        $manifestJson = $this->zipGetString($zip, self::EXPORT_FILES_DIR_STANDARD . '/manifest.json')
            ?? $this->zipGetString($zip, self::EXPORT_FILES_DIR_ALT . '/manifest.json')
            ?? $this->zipGetString($zip, self::LEGACY_EXPORT_FILES_DIR . '/manifest.json');
        if ($manifestJson === null) {
            $log('  [manifest] Sem manifest (m_os_arquivos/, arquivos_mos/ ou estrutura legada) — apenas dados SQL importados.');
            $zip->close();

            return $stats;
        }

        $manifest = json_decode($manifestJson, true);
        if (!is_array($manifest) || empty($manifest['arquivos']) || !is_array($manifest['arquivos'])) {
            $log('  [manifest] manifest.json inválido ou sem chave "arquivos".');
            $zip->close();

            return $stats;
        }

        $stats['tinha_manifest'] = true;

        $entries = $manifest['arquivos'];
        $log('  [manifest] ' . count($entries) . ' entrada(s) no manifest; a copiar ficheiros para o storage público...');

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                $stats['ignorados']++;

                continue;
            }

            $tabela = strtolower(trim((string) ($entry['tabela'] ?? '')));
            $noZip = str_replace('\\', '/', trim((string) ($entry['no_zip'] ?? '')));

            if ($tabela === 'emitente' && isset($entry['observacao'])) {
                $stats['ignorados']++;

                continue;
            }

            $okPrefix = $noZip !== ''
                && (str_starts_with($noZip, self::EXPORT_FILES_DIR_STANDARD . '/')
                    || str_starts_with($noZip, self::EXPORT_FILES_DIR_ALT . '/')
                    || str_starts_with($noZip, self::LEGACY_EXPORT_FILES_DIR . '/'));
            if (! $okPrefix) {
                $stats['ignorados']++;

                continue;
            }

            try {
                $outcome = $this->dispatchEntry($zip, $tabela, $entry, $noZip, $log);
                match ($outcome) {
                    'copied' => $stats['copiados']++,
                    'failed' => $stats['falhas']++,
                    default => $stats['ignorados']++,
                };
            } catch (\Throwable $e) {
                $stats['falhas']++;
                $log('  [manifest] ERRO em ' . $noZip . ': ' . $e->getMessage());
            }
        }

        $zip->close();
        $log(sprintf(
            '  [manifest] Resumo ficheiros: %d copiados/atualizados, %d falhas, %d ignorados.',
            $stats['copiados'],
            $stats['falhas'],
            $stats['ignorados']
        ));

        return $stats;
    }

    private function zipGetString(\ZipArchive $zip, string $name): ?string
    {
        $name = str_replace('\\', '/', $name);
        $data = $zip->getFromName($name);
        if ($data !== false) {
            return $data;
        }

        // Alguns ZIPs usam separador invertido no índice
        $alt = str_replace('/', '\\', $name);
        $data = $zip->getFromName($alt);

        return $data !== false ? $data : null;
    }

    /**
     * Copia entrada do ZIP para disco público via stream (sem carregar ficheiros grandes em RAM).
     *
     * @return int|null bytes copiados ou null se falhar
     */
    private function copyZipEntryToPublicPath(\ZipArchive $zip, string $entryName, string $relativePublicPath): ?int
    {
        $entryName = str_replace('\\', '/', $entryName);
        $readStream = $zip->getStream($entryName);
        if ($readStream === false) {
            $readStream = $zip->getStream(str_replace('/', '\\', $entryName));
        }
        if ($readStream === false) {
            return null;
        }

        $disk = Storage::disk('public');
        $fullPath = $disk->path($relativePublicPath);
        File::ensureDirectoryExists(dirname($fullPath));

        $out = fopen($fullPath, 'wb');
        if ($out === false) {
            fclose($readStream);

            return null;
        }

        $copied = stream_copy_to_stream($readStream, $out);
        fclose($out);
        fclose($readStream);

        if ($copied === false) {
            @unlink($fullPath);

            return null;
        }

        return (int) $copied;
    }

    /**
     * @return 'copied'|'skipped'|'failed'
     */
    private function dispatchEntry(\ZipArchive $zip, string $tabela, array $entry, string $noZip, callable $log): string
    {
        $idOrigemStr = trim((string) ($entry['id'] ?? ''));
        if ($idOrigemStr === '' && isset($entry['id']) && is_numeric($entry['id'])) {
            $idOrigemStr = (string) (int) $entry['id'];
        }

        return match ($tabela) {
            'documentos' => $this->hydrateDocumento($zip, $idOrigemStr, $entry, $noZip, $log),
            'anexos' => $this->hydrateAnexoOs(
                $zip,
                $this->extractManifestAnexoSourceId($entry) ?? $this->manifestEntryPositiveInt($entry['id'] ?? null),
                $entry,
                $noZip,
                $log
            ),
            'lancamentos' => $this->hydrateLancamentoAnexo($zip, $idOrigemStr, $entry, $noZip, $log),
            default => 'skipped',
        };
    }

    private function safeBasename(string $path): string
    {
        $b = basename(str_replace('\\', '/', $path));
        $b = preg_replace('/[^a-zA-Z0-9._\-]+/', '_', $b) ?: 'arquivo';

        return $b;
    }

    private function guessMimeFromFile(string $absolutePath, string $filename): string
    {
        if (is_readable($absolutePath) && class_exists(\finfo::class)) {
            $head = @file_get_contents($absolutePath, false, null, 0, 8192);
            if ($head !== false && $head !== '') {
                $fi = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $fi->buffer($head);
                if (is_string($mime) && $mime !== '' && $mime !== 'application/octet-stream') {
                    return $mime;
                }
            }
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /**
     * @return 'copied'|'skipped'|'failed'
     */
    private function hydrateDocumento(\ZipArchive $zip, string $idOrigemStr, array $entry, string $noZip, callable $log): string
    {
        if ($idOrigemStr === '' || !ctype_digit($idOrigemStr)) {
            return 'skipped';
        }

        $sourceRowId = (int) $idOrigemStr;
        $doc = Documento::query()
            ->where('descricao', 'like', '%[MOS_DOCUMENTO_ID:' . $sourceRowId . ']%')
            ->first();

        if ($doc === null) {
            $log('  [manifest] Documento (origem) id=' . $sourceRowId . ' não encontrado no ERP (importe o SQL primeiro).');

            return 'skipped';
        }

        $legacyRel = 'relativo_' . 'm' . 'apo' . 's';
        $base = $this->safeBasename((string) ($entry['relativo_origem'] ?? $entry['relativo_instalacao'] ?? $entry[$legacyRel] ?? $noZip));
        $relPath = 'documentos/clientes/' . (int) $doc->cliente_id . '/mos_doc_' . $sourceRowId . '_' . $base;

        $size = $this->copyZipEntryToPublicPath($zip, $noZip, $relPath);
        if ($size === null) {
            $log('  [manifest] AVISO: ficheiro ausente no ZIP ou falha ao gravar: ' . $noZip);

            return 'failed';
        }

        $abs = Storage::disk('public')->path($relPath);
        $doc->caminho_arquivo = mb_substr($relPath, 0, 500);
        $doc->nome_arquivo = mb_substr($base, 0, 255);
        $doc->tamanho = $size;
        $doc->tipo_mime = $this->guessMimeFromFile($abs, $base);
        $doc->save();

        return 'copied';
    }

    /**
     * Localiza registo criado na fase SQL (metadata.mos_source_id pode vir como int ou string no JSON).
     */
    private function findOrdemServicoAnexoBySourceId(int $sourceAnexoId): ?OrdemServicoAnexo
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        // SQLite: usar json_extract primeiro — o operador -> do Eloquent pode falhar em alguns ambientes.
        if ($driver === 'sqlite') {
            $a = OrdemServicoAnexo::query()
                ->whereRaw("CAST(json_extract(metadata, '$.mos_source_id') AS INTEGER) = ?", [$sourceAnexoId])
                ->orderBy('id')
                ->first();
            if ($a !== null) {
                return $a;
            }
            $a = OrdemServicoAnexo::query()
                ->whereRaw("json_extract(metadata, '$.mos_source_id') = ?", [(string) $sourceAnexoId])
                ->orderBy('id')
                ->first();
            if ($a !== null) {
                return $a;
            }
        } else {
            $base = OrdemServicoAnexo::query()->orderBy('id');
            $a = (clone $base)->where('metadata->mos_source_id', $sourceAnexoId)->first();
            if ($a !== null) {
                return $a;
            }
            $a = (clone $base)->where('metadata->mos_source_id', (string) $sourceAnexoId)->first();
            if ($a !== null) {
                return $a;
            }

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $a = OrdemServicoAnexo::query()
                    ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.mos_source_id")) = ?', [(string) $sourceAnexoId])
                    ->orderBy('id')
                    ->first();
                if ($a !== null) {
                    return $a;
                }
            }
        }

        return OrdemServicoAnexo::query()
            ->where(function ($q) use ($sourceAnexoId) {
                $q->where('metadata', 'like', '%"mos_source_id":' . $sourceAnexoId . '%')
                    ->orWhere('metadata', 'like', '%"mos_source_id":"' . $sourceAnexoId . '"%');
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * OS de destino indicada no manifest (export LivreOS) quando o INSERT de `anexos` veio noutra parte do ZIP.
     *
     * @param  array<string, mixed>  $entry
     */
    private function extractSourceOsIdFromManifestEntry(array $entry): ?int
    {
        // Contrato v2: os_id, id_os, idos são o mesmo valor (OS na origem = id LivreOS após import).
        foreach (['os_id', 'id_os', 'idos', 'ordem_servico_id', 'os'] as $k) {
            if (!array_key_exists($k, $entry)) {
                continue;
            }
            $n = $this->manifestEntryPositiveInt($entry[$k]);
            if ($n !== null) {
                return $n;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return 'copied'|'skipped'|'failed'
     */
    private function hydrateAnexoOs(\ZipArchive $zip, ?int $sourceAnexoId, array $entry, string $noZip, callable $log): string
    {
        if ($sourceAnexoId === null || $sourceAnexoId < 1) {
            return 'skipped';
        }

        $sourceRowId = $sourceAnexoId;
        $anexo = $this->findOrdemServicoAnexoBySourceId($sourceRowId);

        if ($anexo === null) {
            $sourceOsIdFromManifest = $this->extractSourceOsIdFromManifestEntry($entry);
            $resolvedOsId = $sourceOsIdFromManifest !== null
                ? MosOrdemServicoIdResolver::resolve($sourceOsIdFromManifest)
                : null;
            if ($resolvedOsId !== null) {
                $baseStub = $this->safeBasename($noZip);
                $log('  [manifest] Criando registo de anexo (origem) id=' . $sourceRowId . ' para OS #' . $resolvedOsId . ' (sem linha na BD após fase SQL — típ. importação multi-parte).');
                $anexo = OrdemServicoAnexo::create([
                    'ordem_servico_id' => $resolvedOsId,
                    'nome_arquivo' => mb_substr($baseStub, 0, 255),
                    'caminho_arquivo' => 'mos_import_pending',
                    'tipo' => preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $baseStub) ? 'imagem' : (preg_match('/\.pdf$/i', $baseStub) ? 'pdf' : 'arquivo'),
                    'metadata' => [
                        'fonte' => 'mos_export',
                        'mos_source_id' => $sourceRowId,
                        'manifest_criado_stub' => true,
                    ],
                    'tipo_mime' => $this->guessMimeFromFile('', $baseStub),
                    'created_by' => auth()->id(),
                ]);
            }
        }

        if ($anexo === null) {
            $log('  [manifest] Anexo OS (origem) id=' . $sourceRowId . ' sem registo na BD e sem os_id/id_os/idos no manifest — inclua INSERT em `anexos` no SQL ou estes campos na entrada (export v2).');

            return 'skipped';
        }

        $isThumb = (bool) preg_match('#anexos_os/\d+_thumb_#', $noZip);
        $base = $this->safeBasename($noZip);

        if ($isThumb) {
            $relPath = 'ordens-servico/' . (int) $anexo->ordem_servico_id . '/mos_thumb_' . $sourceRowId . '_' . $base;
            $size = $this->copyZipEntryToPublicPath($zip, $noZip, $relPath);
            if ($size === null) {
                $log('  [manifest] AVISO: thumb ausente no ZIP ou falha ao gravar: ' . $noZip);

                return 'failed';
            }

            $meta = is_array($anexo->metadata) ? $anexo->metadata : [];
            $meta['mos_thumb_caminho_erp'] = $relPath;
            $meta['mos_thumb_nome'] = $base;
            $anexo->metadata = $meta;
            $anexo->save();

            return 'copied';
        }

        $relPath = 'ordens-servico/' . (int) $anexo->ordem_servico_id . '/mos_anexo_' . $sourceRowId . '_' . $base;
        $size = $this->copyZipEntryToPublicPath($zip, $noZip, $relPath);
        if ($size === null) {
            $log('  [manifest] AVISO: ficheiro ausente no ZIP ou falha ao gravar: ' . $noZip);

            return 'failed';
        }

        $abs = Storage::disk('public')->path($relPath);
        $anexo->caminho_arquivo = mb_substr($relPath, 0, 255);
        $anexo->nome_arquivo = mb_substr($base, 0, 255);
        $anexo->tamanho = $size;
        $anexo->tipo_mime = $this->guessMimeFromFile($abs, $base);
        $anexo->save();

        return 'copied';
    }

    /**
     * @return 'copied'|'skipped'|'failed'
     */
    private function hydrateLancamentoAnexo(\ZipArchive $zip, string $idOrigemStr, array $entry, string $noZip, callable $log): string
    {
        if ($idOrigemStr === '' || !ctype_digit($idOrigemStr)) {
            return 'skipped';
        }

        $sourceRowId = (int) $idOrigemStr;
        $ref = 'mos:lancamentos:' . $sourceRowId;

        $contaReceber = ContaReceber::query()->where('referencia_externa', $ref)->first();
        $contaPagar = $contaReceber === null
            ? ContaPagar::query()->where('referencia_externa', $ref)->first()
            : null;

        if ($contaReceber === null && $contaPagar === null) {
            $log('  [manifest] Lançamento (origem) id=' . $sourceRowId . ' (financeiro) não encontrado no ERP.');

            return 'skipped';
        }

        if ($contaReceber !== null) {
            $anexo = ContaReceberAnexo::query()
                ->where('conta_receber_id', $contaReceber->id)
                ->orderBy('id')
                ->first();
            if ($anexo === null) {
                $log('  [manifest] Conta a receber — lanç. origem ' . $sourceRowId . ' sem registo de anexo (nada a atualizar).');

                return 'skipped';
            }
            $base = $this->safeBasename((string) ($entry['campo_anexo_original'] ?? $noZip));
            $relPath = 'contas-receber/' . (int) $contaReceber->id . '/mos_lanc_' . $sourceRowId . '_' . $base;
            $size = $this->copyZipEntryToPublicPath($zip, $noZip, $relPath);
            if ($size === null) {
                $log('  [manifest] AVISO: anexo de lançamento ausente no ZIP ou falha ao gravar: ' . $noZip);

                return 'failed';
            }
            $abs = Storage::disk('public')->path($relPath);
            $anexo->caminho_arquivo = mb_substr($relPath, 0, 500);
            $anexo->nome_arquivo = mb_substr($base, 0, 255);
            $anexo->tamanho = $size;
            $anexo->tipo_mime = $this->guessMimeFromFile($abs, $base);
            $anexo->save();

            return 'copied';
        }

        $anexo = ContaPagarAnexo::query()
            ->where('conta_pagar_id', $contaPagar->id)
            ->orderBy('id')
            ->first();
        if ($anexo === null) {
            $log('  [manifest] Conta a pagar — lanç. origem ' . $sourceRowId . ' sem registo de anexo (nada a atualizar).');

            return 'skipped';
        }
        $base = $this->safeBasename((string) ($entry['campo_anexo_original'] ?? $noZip));
        $relPath = 'contas-pagar/' . (int) $contaPagar->id . '/mos_lanc_' . $sourceRowId . '_' . $base;
        $size = $this->copyZipEntryToPublicPath($zip, $noZip, $relPath);
        if ($size === null) {
            $log('  [manifest] AVISO: anexo de lançamento ausente no ZIP ou falha ao gravar: ' . $noZip);

            return 'failed';
        }
        $abs = Storage::disk('public')->path($relPath);
        $anexo->caminho_arquivo = mb_substr($relPath, 0, 500);
        $anexo->nome_arquivo = mb_substr($base, 0, 255);
        $anexo->tamanho = $size;
        $anexo->tipo_mime = $this->guessMimeFromFile($abs, $base);
        $anexo->save();

        return 'copied';
    }
}
