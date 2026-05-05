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

use Plugins\ImportarSistemaExternoMos\MosImportHandler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin Name: Importar de sistema externo (M-OS)
 * Description: Importação de backup completo do sistema externo M-OS (.zip / .sql) na tela de Utilidades.
 * Version: 1.0.0
 * Author: LivreOS
 * Requires PHP: 8.1
 * Requires ext-zip: importação M-OS usa ZipArchive para ler .zip (ative extension=zip no php.ini).
 */

require_once __DIR__.'/src/MosZipSupport.php';
require_once __DIR__.'/src/MosOrdemServicoIdResolver.php';
require_once __DIR__.'/src/MosSqlImportService.php';
require_once __DIR__.'/src/MosManifestFileImportService.php';
require_once __DIR__.'/src/MosExportMetaReader.php';
require_once __DIR__.'/src/MosSqliteBusyRetry.php';
require_once __DIR__.'/src/MosMultipartSessionService.php';
require_once __DIR__.'/src/MosImportMultipartAccumulator.php';
require_once __DIR__.'/src/MosImportHandler.php';

add_filter('utilidades.import.options', function ($options) {
    if (! is_array($options)) {
        $options = [];
    }

    $have = [];
    foreach ($options as $opt) {
        if (is_array($opt) && isset($opt['value'])) {
            $have[(string) $opt['value']] = true;
        }
    }

    if (empty($have['mos_export_full'])) {
        $options[] = [
            'value' => 'mos_export_full',
            'label' => 'Importar de sistema externo (M-OS) — backup .zip / .sql',
            'hint' => 'ZIP ou SQL gerado pelo export para LivreOS (SQL + pasta de ficheiros + manifest). Um ou vários .zip/.sql; multi-parte v2: envio sequencial com barra de progresso; ordem da seleção alinhada a part_index 1→N (mesmo export_id).',
            'from_plugin' => true,
        ];
    }

    return $options;
}, 10);

add_filter('utilidades.import.handle_many', function ($response, $tipo, $files, $request) {
    if ($tipo !== 'mos_export_full') {
        return $response;
    }

    try {
        $out = MosImportHandler::handleMany(is_array($files) ? $files : [], $request);

        return $out instanceof Response ? $out : $response;
    } catch (\Throwable $e) {
        return redirect()->route('configuracoes-sistema.utilidades.index')
            ->with('error', 'Importação M-OS: '.$e->getMessage());
    }
}, 10);

add_filter('utilidades.import.handle', function ($response, $tipo, $file, $ext, $request) {
    if ($tipo !== 'mos_export_full') {
        return $response;
    }

    try {
        return MosImportHandler::handle($file, (string) $ext, $request);
    } catch (\Throwable $e) {
        return redirect()->route('configuracoes-sistema.utilidades.index')
            ->with('error', 'Importação M-OS: '.$e->getMessage());
    }
}, 10);
