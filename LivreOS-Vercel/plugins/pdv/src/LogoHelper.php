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

namespace Pdv;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Mantém uma cópia do logo da empresa em pasta pública (public/logo/)
 * para exibição nas impressões do PDV, evitando erros de path ou permissão.
 * Na ativação do plugin copia o logo; ao imprimir, se o tamanho do arquivo
 * no sistema for diferente da cópia pública, atualiza a cópia.
 */
class LogoHelper
{
    public const PUBLIC_SUBDIR = 'logo';
    public const PUBLIC_FILENAME = 'empresa-logo';

    /**
     * Retorna o caminho absoluto do arquivo de origem do logo (disco public).
     */
    private static function getSourcePath(string $logoPathRel): ?string
    {
        $logoPathRel = ltrim(str_replace('\\', '/', $logoPathRel), '/');
        if ($logoPathRel === '') {
            return null;
        }
        if (Storage::disk('public')->exists($logoPathRel)) {
            return Storage::disk('public')->path($logoPathRel);
        }
        $storagePath = storage_path('app/public/' . $logoPathRel);
        if (is_file($storagePath) && is_readable($storagePath)) {
            return $storagePath;
        }
        return null;
    }

    /**
     * Garante que existe uma cópia do logo em public/logo/ e retorna a URL absoluta.
     * A URL absoluta é necessária para a janela de impressão (document.write) carregar a imagem.
     * Se o logo cadastrado no sistema tiver tamanho diferente do arquivo na pasta pública,
     * copia novamente. Retorna null se não houver logo ou em caso de erro.
     */
    public static function ensureAndGetUrl(): ?string
    {
        if (!class_exists(\App\Models\Empresa::class)) {
            return null;
        }
        try {
            $empresa = \App\Models\Empresa::first();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$empresa?->logo_path) {
            return null;
        }
        $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
        $sourcePath = self::getSourcePath($logoPathRel);
        if ($sourcePath === null || !is_file($sourcePath) || !is_readable($sourcePath)) {
            return null;
        }
        $sourceSize = @filesize($sourcePath);
        if ($sourceSize === false) {
            return null;
        }
        $ext = pathinfo($logoPathRel, PATHINFO_EXTENSION);
        if ($ext === '' || preg_match('/^[a-zA-Z0-9]+$/', $ext) !== 1) {
            $ext = 'png';
        }
        $ext = strtolower($ext);
        $publicDir = public_path(self::PUBLIC_SUBDIR);
        if (!is_dir($publicDir)) {
            if (!@mkdir($publicDir, 0755, true)) {
                return null;
            }
        }
        $destPath = $publicDir . '/' . self::PUBLIC_FILENAME . '.' . $ext;
        $destExists = is_file($destPath);
        $destSize = $destExists ? @filesize($destPath) : 0;
        if (!$destExists || $destSize !== $sourceSize) {
            if (@copy($sourcePath, $destPath) !== true) {
                return null;
            }
        }
        $relativePath = self::PUBLIC_SUBDIR . '/' . self::PUBLIC_FILENAME . '.' . $ext;
        return URL::to($relativePath);
    }

    /**
     * Chamado na ativação do plugin: cria a pasta public/logo e copia o logo uma vez.
     */
    public static function copyOnActivation(): void
    {
        if (!class_exists(\App\Models\Empresa::class)) {
            return;
        }
        try {
            $empresa = \App\Models\Empresa::first();
        } catch (\Throwable $e) {
            return;
        }
        if (!$empresa?->logo_path) {
            return;
        }
        $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
        $sourcePath = self::getSourcePath($logoPathRel);
        if ($sourcePath === null || !is_file($sourcePath) || !is_readable($sourcePath)) {
            return;
        }
        $publicDir = public_path(self::PUBLIC_SUBDIR);
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        if (!is_dir($publicDir)) {
            return;
        }
        $ext = pathinfo($logoPathRel, PATHINFO_EXTENSION);
        if ($ext === '' || preg_match('/^[a-zA-Z0-9]+$/', $ext) !== 1) {
            $ext = 'png';
        }
        $ext = strtolower($ext);
        $destPath = $publicDir . '/' . self::PUBLIC_FILENAME . '.' . $ext;
        @copy($sourcePath, $destPath);
    }
}
