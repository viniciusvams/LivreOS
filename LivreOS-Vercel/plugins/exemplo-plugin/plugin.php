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

/**
 * Plugin Name: Exemplo de Plugin
 * Description: Plugin de exemplo para demonstrar o sistema de plugins do ERP. Adiciona um item de menu.
 * Version: 1.0.0
 * Author: ERP Open Source
 * Requires PHP: 8.0
 * Requires Plugins: (opcional, ex: outro-plugin, woocommerce)
 *
 * APIs disponíveis: get_option, update_option, set_transient, add_options_page,
 * plugin_dir_path(__FILE__), add_admin_notice, add_action('plugins.schedule', ...)
 *
 * Estrutura inspirada no WordPress. Para criar seu plugin:
 * 1. Crie uma pasta em plugins/ com o slug do plugin
 * 2. Crie plugin.php com os metadados no cabeçalho
 * 3. Use add_action e add_filter para estender o sistema
 * 4. Ative o plugin em Configurações > Plugins
 *
 * Hooks de ciclo de vida:
 * - erp_register_activation_hook: ao ativar (criar tabelas, config inicial)
 * - erp_register_deactivation_hook: ao desativar (limpar cache, pausar tarefas)
 * - erp_register_uninstall_hook: ao excluir (remover tabelas, dados)
 */

// ERPLUGIN_LOADED é definido pelo PluginManager ao carregar

// Hook de ativação: executado quando o plugin é ativado
erp_register_activation_hook(__FILE__, function () {
    \Illuminate\Support\Facades\Log::info('[exemplo-plugin] Plugin ativado');
    // Ex.: criar tabelas, seed inicial, config em cache
});

// Hook de desativação: executado quando o plugin é desativado
erp_register_deactivation_hook(__FILE__, function () {
    \Illuminate\Support\Facades\Log::info('[exemplo-plugin] Plugin desativado');
    // Ex.: limpar cache, pausar jobs, revogar tokens
});

// Hook de exclusão: executado quando o plugin é excluído (antes de remover os arquivos)
erp_register_uninstall_hook(__FILE__, function () {
    \Illuminate\Support\Facades\Log::info('[exemplo-plugin] Plugin excluído');
    // Ex.: remover tabelas do banco, opções, arquivos em storage
});

// Exemplo: adicionar item ao menu lateral
add_filter('menu.items', function ($items) {
    if (!auth()->check() || !auth()->user()->canAccessOperational()) {
        return $items;
    }

    $items[] = [
        'name' => 'Meu Plugin',
        'icon' => 'task',
        'subItems' => [
            ['name' => 'Página do Plugin', 'path' => '/plugin/exemplo-plugin', 'pro' => false],
        ],
    ];

    return $items;
}, 20);
