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

/**
 * Lê export_meta.json na raiz do ZIP (formato LivreOS v2 / exportação multi-parte M-OS).
 */
class MosExportMetaReader
{
    /**
     * @return array<string, mixed>|null
     */
    public static function readFromZip(string $zipPath): ?array
    {
        MosZipSupport::assertAvailable();

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $raw = self::zipGetSmallFile($zip, 'export_meta.json');
        $zip->close();

        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function zipGetSmallFile(\ZipArchive $zip, string $name): ?string
    {
        $name = str_replace('\\', '/', $name);
        $data = $zip->getFromName($name);
        if ($data !== false) {
            return $data;
        }

        return ($data = $zip->getFromName(str_replace('/', '\\', $name))) !== false ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function isFormatV2(array $meta): bool
    {
        $v = isset($meta['livreos_format_version']) ? trim((string) $meta['livreos_format_version']) : '';

        return $v === '2';
    }

    public static function assertChecksum(string $sqlPath, ?string $expectedSha256, ?callable $log = null): void
    {
        // O export normaliza o hex a minúsculas; aceitamos qualquer casing na meta.
        $expected = $expectedSha256 !== null ? strtolower(preg_replace('/\s+/', '', trim((string) $expectedSha256))) : '';
        if ($expected === '') {
            return;
        }

        if (!is_readable($sqlPath)) {
            throw new \RuntimeException('Não foi possível validar checksum: ficheiro SQL ilegível.');
        }

        $hash = hash_file('sha256', $sqlPath);
        $hash = is_string($hash) ? strtolower($hash) : '';
        if ($hash === '' || $hash !== $expected) {
            throw new \RuntimeException(
                'Checksum SHA-256 do SQL não confere com export_meta.json. O ficheiro pode estar corrompido ou incompleto.'
            );
        }

        if ($log) {
            $log('  [meta] Checksum SHA-256 do SQL validado com sucesso.');
        }
    }
}
