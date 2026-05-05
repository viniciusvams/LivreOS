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
use App\Services\PluginManager;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index(PluginManager $pluginManager)
    {
        $plugins = $pluginManager->getAvailablePlugins();

        return erp_view('configuracoes_sistema.plugins.index', [
            'title' => 'Plugins',
            'plugins' => $plugins,
        ]);
    }

    public function upload(Request $request, PluginManager $pluginManager)
    {
        $request->validate([
            'plugin_zip' => 'required|file|mimes:zip|max:10240',
        ], [
            'plugin_zip.required' => 'Selecione um arquivo .zip',
            'plugin_zip.mimes' => 'Apenas arquivos .zip são aceitos',
            'plugin_zip.max' => 'O arquivo deve ter no máximo 10 MB',
        ]);

        $result = $pluginManager->uploadPlugin($request->file('plugin_zip'));

        if ($result['success']) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('configuracoes-sistema.plugins.index')
            ->with('error', $result['message']);
    }

    public function activate(string $slug, PluginManager $pluginManager)
    {
        $result = $pluginManager->activate($slug);

        if ($result['success']) {
            $plugin = $pluginManager->getPlugin($slug);
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('success', "Plugin \"" . ($plugin['name'] ?? $slug) . "\" ativado com sucesso!");
        }

        return redirect()->route('configuracoes-sistema.plugins.index')
            ->with('error', $result['message'] ?? 'Erro ao ativar o plugin.');
    }

    public function show(string $slug, PluginManager $pluginManager)
    {
        $plugin = $pluginManager->getPlugin($slug);
        if (!$plugin) {
            abort(404, 'Plugin não encontrado.');
        }

        $settingsView = function_exists('apply_filters')
            ? apply_filters('configuracoes.plugins.settings_view', null, $plugin)
            : null;

        return erp_view('configuracoes_sistema.plugins.show', [
            'title' => ($plugin['name'] ?? $slug) . ' — Plugin',
            'plugin' => $plugin,
            'settingsView' => $settingsView,
        ]);
    }

    public function bulkAction(Request $request, PluginManager $pluginManager)
    {
        $action = $request->input('action');
        $slugs = $request->input('plugins', []);

        if (!in_array($action, ['activate', 'deactivate'], true)) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('error', 'Ação inválida.');
        }

        if (!is_array($slugs) || empty($slugs)) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('error', 'Nenhum plugin selecionado.');
        }

        $success = [];
        $errors = [];

        foreach ($slugs as $slug) {
            if ($action === 'activate') {
                $result = $pluginManager->activate($slug);
                if ($result['success']) {
                    $success[] = $slug;
                } else {
                    $errors[$slug] = $result['message'] ?? 'Erro';
                }
            } elseif ($action === 'deactivate') {
                $pluginManager->deactivate($slug);
                $success[] = $slug;
            }
        }

        $messages = [];
        if (!empty($success)) {
            $count = count($success);
            $messages[] = $action === 'activate'
                ? "{$count} plugin(s) ativado(s) com sucesso."
                : "{$count} plugin(s) desativado(s) com sucesso.";
        }
        if (!empty($errors)) {
            $messages[] = 'Erros: ' . implode('; ', array_map(fn ($s, $m) => "{$s}: {$m}", array_keys($errors), array_values($errors)));
        }

        return redirect()->route('configuracoes-sistema.plugins.index')
            ->with(count($errors) > 0 ? 'warning' : 'success', implode(' ', $messages));
    }

    public function deactivate(string $slug, PluginManager $pluginManager)
    {
        $plugins = $pluginManager->getAvailablePlugins();
        if (!isset($plugins[$slug])) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('error', 'Plugin não encontrado.');
        }

        $pluginManager->deactivate($slug);

        return redirect()->route('configuracoes-sistema.plugins.index')
            ->with('success', "Plugin \"{$plugins[$slug]['name']}\" desativado com sucesso!");
    }

    public function destroy(string $slug, PluginManager $pluginManager)
    {
        $result = $pluginManager->deletePlugin($slug);

        if ($result['success']) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('configuracoes-sistema.plugins.index')
            ->with('error', $result['message']);
    }
}
