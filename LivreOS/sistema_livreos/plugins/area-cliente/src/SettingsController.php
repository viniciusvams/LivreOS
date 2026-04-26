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

namespace AreaCliente;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

/**
 * Configurações do plugin na tela Configurações do Sistema > Plugins > Área do Cliente.
 * Exportar/Importar dados e exibir área de ajuda.
 */
class SettingsController
{
    public function export()
    {
        if (!Schema::hasTable('plugin_area_cliente_chamados')) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'area-cliente')
                ->with('error', 'Tabelas do plugin não encontradas.');
        }

        $chamados = DB::table('plugin_area_cliente_chamados')->orderBy('id')->get()->map(fn ($r) => (array) $r);
        $respostas = DB::table('plugin_area_cliente_chamado_respostas')->orderBy('id')->get()->map(fn ($r) => (array) $r);
        $anexos = DB::table('plugin_area_cliente_chamado_anexos')->orderBy('id')->get()->map(fn ($r) => (array) $r);

        $manifest = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'plugin' => 'area-cliente',
            'tables' => [
                'chamados' => $chamados->toArray(),
                'respostas' => $respostas->toArray(),
                'anexos' => $anexos->toArray(),
            ],
        ];

        $disk = Storage::disk('local');
        $zip = new ZipArchive();
        $zipPath = storage_path('app/area_cliente_export_' . date('Y-m-d_His') . '.zip');
        $zipDir = dirname($zipPath);
        if (!is_dir($zipDir)) {
            @mkdir($zipDir, 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'area-cliente')
                ->with('error', 'Não foi possível criar o arquivo de exportação.');
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        foreach ($anexos as $a) {
            $path = $a['path'] ?? '';
            if ($path !== '' && $disk->exists($path)) {
                $fullPath = $disk->path($path);
                if (is_file($fullPath)) {
                    $zip->addFile($fullPath, 'files/' . $path);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, 'area-cliente-export-' . date('Y-m-d') . '.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function importForm()
    {
        return view('area-cliente::settings-import', [
            'title' => 'Importar dados — Área do Cliente',
        ]);
    }

    public function importProcess(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:zip,json|max:512000',
        ], [
            'arquivo.required' => 'Selecione um arquivo .zip ou .json da exportação.',
            'arquivo.mimes' => 'Use um arquivo .zip ou .json exportado pelo plugin.',
            'arquivo.max' => 'O arquivo deve ter no máximo 500 MB.',
        ]);

        if (!Schema::hasTable('plugin_area_cliente_chamados')) {
            return redirect()->route('configuracoes-sistema.plugins.show', 'area-cliente')
                ->with('error', 'Tabelas do plugin não encontradas. Ative o plugin primeiro.');
        }

        $file = $request->file('arquivo');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            return $this->importFromZip($file);
        }

        if ($extension === 'json') {
            return $this->importFromJson($file);
        }

        return redirect()->route('plugin.area-cliente.configuracoes.import')
            ->with('error', 'Formato não suportado. Use .zip ou .json.');
    }

    protected function importFromZip($file): \Illuminate\Http\RedirectResponse
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
            return redirect()->route('plugin.area-cliente.configuracoes.import')
                ->with('error', 'Arquivo ZIP inválido ou corrompido.');
        }

        $manifestContent = $zip->getFromName('manifest.json');
        $zip->close();

        if ($manifestContent === false || $manifestContent === '') {
            return redirect()->route('plugin.area-cliente.configuracoes.import')
                ->with('error', 'O ZIP não contém manifest.json. Use um arquivo exportado por este plugin.');
        }

        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest) || empty($manifest['tables'])) {
            return redirect()->route('plugin.area-cliente.configuracoes.import')
                ->with('error', 'manifest.json inválido.');
        }

        $extractPath = storage_path('app/area_cliente_import_' . uniqid());
        $zip2 = new ZipArchive();
        $zip2->open($file->getRealPath(), ZipArchive::RDONLY);
        $zip2->extractTo($extractPath);
        $zip2->close();

        try {
            $result = $this->importManifest($manifest, $extractPath . '/files');
        } finally {
            if (is_dir($extractPath)) {
                array_map('unlink', glob($extractPath . '/files/*') ?: []);
                @rmdir($extractPath . '/files');
                @rmdir($extractPath);
            }
        }

        return $result;
    }

    protected function importFromJson($file): \Illuminate\Http\RedirectResponse
    {
        $content = file_get_contents($file->getRealPath());
        $manifest = json_decode($content, true);
        if (!is_array($manifest) || empty($manifest['tables'])) {
            return redirect()->route('plugin.area-cliente.configuracoes.import')
                ->with('error', 'Arquivo JSON inválido. Use um export do plugin.');
        }
        return $this->importManifest($manifest, null);
    }

    protected function importManifest(array $manifest, ?string $filesPath): \Illuminate\Http\RedirectResponse
    {
        $tables = $manifest['tables'] ?? [];
        $chamados = $tables['chamados'] ?? [];
        $respostas = $tables['respostas'] ?? [];
        $anexos = $tables['anexos'] ?? [];

        $chamadoIdMap = [];
        $respostaIdMap = [];
        $disk = Storage::disk('local');

        try {
            DB::beginTransaction();

            foreach ($chamados as $row) {
                $oldId = $row['id'] ?? null;
                $insert = [
                    'cliente_id' => $row['cliente_id'] ?? 0,
                    'user_id' => $row['user_id'] ?? null,
                    'ordem_servico_id' => $row['ordem_servico_id'] ?? null,
                    'titulo' => $row['titulo'] ?? '',
                    'descricao' => $row['descricao'] ?? null,
                    'prioridade' => $row['prioridade'] ?? 'media',
                    'status' => $row['status'] ?? 'aberto',
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
                $newId = DB::table('plugin_area_cliente_chamados')->insertGetId($insert);
                if ($oldId !== null) {
                    $chamadoIdMap[$oldId] = $newId;
                }
            }

            foreach ($respostas as $row) {
                $oldId = $row['id'] ?? null;
                $oldChamadoId = $row['chamado_id'] ?? 0;
                $newChamadoId = $chamadoIdMap[$oldChamadoId] ?? $oldChamadoId;
                $insert = [
                    'chamado_id' => $newChamadoId,
                    'user_id' => $row['user_id'] ?? null,
                    'mensagem' => $row['mensagem'] ?? '',
                    'interno' => (bool) ($row['interno'] ?? false),
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ];
                $newId = DB::table('plugin_area_cliente_chamado_respostas')->insertGetId($insert);
                if ($oldId !== null) {
                    $respostaIdMap[$oldId] = $newId;
                }
            }

            foreach ($anexos as $row) {
                $oldChamadoId = $row['chamado_id'] ?? 0;
                $oldRespostaId = $row['chamado_resposta_id'] ?? null;
                $newChamadoId = $chamadoIdMap[$oldChamadoId] ?? $oldChamadoId;
                $newRespostaId = $oldRespostaId !== null ? ($respostaIdMap[$oldRespostaId] ?? null) : null;
                $path = $row['path'] ?? '';
                $nomeOriginal = $row['nome_original'] ?? null;

                $newPath = $path;
                $sourceFile = $filesPath !== null && $path !== '' ? $filesPath . '/' . $path : null;
                if ($sourceFile && file_exists($sourceFile)) {
                    $dir = 'plugin_area_cliente/chamados/' . $newChamadoId;
                    $newPath = $dir . '/' . uniqid() . '_' . ($nomeOriginal ?: basename($path));
                    $disk->put($newPath, file_get_contents($sourceFile));
                }

                DB::table('plugin_area_cliente_chamado_anexos')->insert([
                    'chamado_id' => $newChamadoId,
                    'chamado_resposta_id' => $newRespostaId,
                    'path' => $newPath,
                    'nome_original' => $nomeOriginal,
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('plugin.area-cliente.configuracoes.import')
                ->with('error', 'Erro ao importar: ' . $e->getMessage());
        }

        $total = count($chamados) + count($respostas) + count($anexos);
        return redirect()->route('configuracoes-sistema.plugins.show', 'area-cliente')
            ->with('success', "Importação concluída. {$total} registro(s) processado(s).");
    }
}
