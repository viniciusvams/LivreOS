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

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class PdvSettingsController
{
    /**
     * Salva a lista de usuários que podem abrir caixa PDV e as permissões por usuário.
     */
    public function storeHabilitarCaixaUsuarios(Request $request)
    {
        $request->validate([
            'usuarios' => 'nullable|array',
            'usuarios.*' => 'integer|exists:users,id',
            'permissoes' => 'nullable|array',
            'permissoes.*' => 'nullable|array',
            'permissoes.*.pode_cancelar_vendas' => 'nullable|boolean',
            'permissoes.*.pode_cancelar_venda_total' => 'nullable|boolean',
            'permissoes.*.pode_cancelar_venda_parcial' => 'nullable|boolean',
            'permissoes.*.pode_sangria' => 'nullable|boolean',
            'permissoes.*.pode_vender_estoque_zerado' => 'nullable|boolean',
            'autorizador_user_id' => 'nullable|integer|exists:users,id',
            'autorizadores_user_ids' => 'nullable|array',
            'autorizadores_user_ids.*' => 'integer|exists:users,id',
            'desconto_maximo_percentual' => 'nullable|numeric|min:0|max:100',
            'quebra_sobra_limite_sem_aprovacao' => 'nullable|numeric|min:0',
        ]);
        $ids = array_values(array_map('intval', (array) $request->input('usuarios', [])));
        update_option('pdv_usuarios_podem_abrir_caixa', $ids, 'pdv');

        $permissoes = [];
        foreach ((array) $request->input('permissoes', []) as $uid => $p) {
            if (!is_array($p)) {
                continue;
            }
            $total = !empty($p['pode_cancelar_venda_total']);
            $parcial = !empty($p['pode_cancelar_venda_parcial']);
            if (!array_key_exists('pode_cancelar_venda_total', $p) && !array_key_exists('pode_cancelar_venda_parcial', $p)) {
                $legado = !empty($p['pode_cancelar_vendas']);
                $total = $legado;
                $parcial = $legado;
            }
            $permissoes[(string) $uid] = [
                'pode_cancelar_venda_total' => $total,
                'pode_cancelar_venda_parcial' => $parcial,
                'pode_cancelar_vendas' => $total || $parcial,
                'pode_sangria' => !empty($p['pode_sangria']),
                'pode_vender_estoque_zerado' => !empty($p['pode_vender_estoque_zerado']),
            ];
        }
        update_option('pdv_permissoes_usuarios', $permissoes, 'pdv');

        $autorizadorId = $request->input('autorizador_user_id');
        update_option('pdv_autorizador_user_id', $autorizadorId !== null && $autorizadorId !== '' ? (int) $autorizadorId : null, 'pdv');

        $autorizadoresIds = array_values(array_map('intval', array_filter((array) $request->input('autorizadores_user_ids', []))));
        update_option('pdv_autorizadores_user_ids', $autorizadoresIds, 'pdv');
        update_option('pdv_autorizador_user_id', $autorizadoresIds[0] ?? null, 'pdv');

        $descontoMax = $request->input('desconto_maximo_percentual');
        update_option('pdv_desconto_maximo_percentual', $descontoMax !== null && $descontoMax !== '' ? (float) $descontoMax : null, 'pdv');

        $limiteQuebra = $request->input('quebra_sobra_limite_sem_aprovacao');
        update_option('pdv_quebra_sobra_limite_sem_aprovacao', $limiteQuebra !== null && $limiteQuebra !== '' ? (float) $limiteQuebra : 5.0, 'pdv');

        return redirect()->route('configuracoes-sistema.plugins.show', 'pdv')
            ->with('success_usuarios', 'Configurações salvas. Usuários habilitados, permissões, autorizadores e desconto máximo atualizados.');
    }

    /**
     * Salva as opções do plugin PDV (cliente balcão, numeração, categorias e produtos que exigem serial).
     */
    public function store(Request $request)
    {
        $request->validate([
            'pdv_cliente_balcao_modo' => 'nullable|in:auto,selecionar',
            'pdv_cliente_balcao_id' => 'nullable|integer|exists:clientes,id|required_if:pdv_cliente_balcao_modo,selecionar',
            'pdv_numero_venda_inicial' => 'nullable|integer|min:1',
            'pdv_controle_estoque' => 'nullable',
            'categorias_com_serial' => 'nullable|array',
            'categorias_com_serial.*' => 'integer|exists:categorias_produtos,id',
            'produtos_com_serial' => 'nullable|array',
            'produtos_com_serial.*' => 'integer|exists:produtos,id',
        ]);

        $modo = $request->input('pdv_cliente_balcao_modo', 'auto');
        update_option('pdv_cliente_balcao_modo', $modo, 'pdv');

        if ($modo === 'auto') {
            $clienteBalcao = Cliente::where('nome', 'Cliente Balcão')->first();
            if (!$clienteBalcao) {
                $clienteBalcao = Cliente::create([
                    'nome' => 'Cliente Balcão',
                    'tipo_pessoa' => 'F',
                ]);
            }
            update_option('pdv_cliente_balcao_id', $clienteBalcao->id, 'pdv');
        } else {
            $id = $request->input('pdv_cliente_balcao_id');
            update_option('pdv_cliente_balcao_id', $id ? (int) $id : null, 'pdv');
        }

        $numeroInicial = $request->input('pdv_numero_venda_inicial');
        update_option('pdv_numero_venda_inicial', $numeroInicial !== null && $numeroInicial !== '' ? max(1, (int) $numeroInicial) : 1, 'pdv');

        update_option('pdv_controle_estoque', $request->filled('pdv_controle_estoque'), 'pdv');

        $categorias = $request->input('categorias_com_serial', []);
        $produtos = $request->input('produtos_com_serial', []);

        update_option('categorias_com_serial', $categorias, 'pdv');
        update_option('produtos_com_serial', $produtos, 'pdv');

        return redirect()->route('configuracoes-sistema.plugins.show', 'pdv')
            ->with('success', 'Configurações do PDV salvas.');
    }

    /**
     * Exportar dados do plugin PDV (opções + tabelas) em JSON/ZIP.
     */
    public function export()
    {
        $optionsPath = storage_path('app/plugin_options/pdv.json');
        $options = [];
        if (File::exists($optionsPath)) {
            $content = File::get($optionsPath);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $options = $decoded;
            }
        }

        $tables = [];
        $tableList = [
            'plugin_pdv_caixas',
            'plugin_pdv_caixa_movimentacoes',
            'plugin_pdv_vendas',
            'plugin_pdv_venda_itens',
            'plugin_pdv_venda_pagamentos',
            'plugin_pdv_orcamentos',
            'plugin_pdv_orcamento_itens',
            'plugin_pdv_caixa_fechamento_quebra_sobra',
            'plugin_pdv_aviso_quebra_sobra_oculto',
        ];
        foreach ($tableList as $table) {
            if (Schema::hasTable($table)) {
                $tables[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->toArray();
            }
        }

        $manifest = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'plugin' => 'pdv',
            'options' => $options,
            'tables' => $tables,
        ];

        $filename = 'pdv-export-' . date('Y-m-d_His') . '.json';
        return response()->streamDownload(
            function () use ($manifest) {
                echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Exibe o formulário de importação.
     */
    public function importForm()
    {
        return view('pdv::settings-import', [
            'title' => 'Importar dados — PDV',
        ]);
    }

    /**
     * Processa o arquivo de importação (JSON).
     */
    public function importProcess(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:json|max:512000',
        ], [
            'arquivo.required' => 'Selecione um arquivo .json da exportação.',
            'arquivo.mimes' => 'Use um arquivo .json exportado pelo plugin PDV.',
            'arquivo.max' => 'O arquivo deve ter no máximo 500 MB.',
        ]);

        $content = file_get_contents($request->file('arquivo')->getRealPath());
        $manifest = json_decode($content, true);
        if (!is_array($manifest) || empty($manifest['plugin']) || $manifest['plugin'] !== 'pdv') {
            return redirect()->route('plugin.pdv.configuracoes.import')
                ->with('error', 'Arquivo JSON inválido. Use um export do plugin PDV.');
        }

        $options = $manifest['options'] ?? [];
        $tables = $manifest['tables'] ?? [];

        try {
            DB::beginTransaction();
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            if (!empty($options)) {
                $optionsPath = storage_path('app/plugin_options/pdv.json');
                $dir = dirname($optionsPath);
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                File::put($optionsPath, json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $tableOrder = [
                'plugin_pdv_caixas',
                'plugin_pdv_caixa_movimentacoes',
                'plugin_pdv_vendas',
                'plugin_pdv_venda_itens',
                'plugin_pdv_venda_pagamentos',
                'plugin_pdv_orcamentos',
                'plugin_pdv_orcamento_itens',
                'plugin_pdv_caixa_fechamento_quebra_sobra',
                'plugin_pdv_aviso_quebra_sobra_oculto',
            ];
            $truncateOrder = array_reverse($tableOrder);
            foreach ($truncateOrder as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
            foreach ($tableOrder as $table) {
                $rows = $tables[$table] ?? [];
                if (empty($rows) || !Schema::hasTable($table)) {
                    continue;
                }
                foreach ($rows as $row) {
                    DB::table($table)->insert($row);
                }
            }

            DB::commit();
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        } catch (\Throwable $e) {
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
            DB::rollBack();
            return redirect()->route('plugin.pdv.configuracoes.import')
                ->with('error', 'Erro ao importar: ' . $e->getMessage());
        }

        return redirect()->route('configuracoes-sistema.plugins.show', 'pdv')
            ->with('success', 'Dados do PDV importados com sucesso.');
    }
}
