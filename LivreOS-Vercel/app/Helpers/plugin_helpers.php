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

use App\Services\PluginManager;
use App\Services\PluginOptionsService;
use App\Services\PluginSettingsRegistry;
use Illuminate\Support\Facades\Cache;

// ==================== Helpers de path (item 7) ====================
if (!function_exists('plugin_dir_path')) {
    /**
     * Retorna o caminho do diretório do plugin.
     * Uso: plugin_dir_path(__FILE__) -> /path/to/plugins/meu-plugin/
     */
    function plugin_dir_path(string $pluginFile): string
    {
        $dir = dirname(realpath($pluginFile));
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('plugin_dir_url')) {
    /**
     * Retorna a URL base do plugin para assets.
     * Os plugins podem registrar rota para servir arquivos em /plugin/{slug}/assets/
     */
    function plugin_dir_url(string $pluginFile): string
    {
        $slug = PluginManager::instance()->getSlugFromPluginFile($pluginFile);
        if ($slug === '') {
            return '/';
        }
        return url('/plugin/' . $slug . '/');
    }
}

if (!function_exists('plugin_basename')) {
    /**
     * Retorna o basename do plugin (ex: meu-plugin/plugin.php).
     */
    function plugin_basename(string $pluginFile): string
    {
        $pluginsPath = realpath(base_path('plugins'));
        $real = realpath($pluginFile);
        if (!$pluginsPath || !$real || !str_starts_with($real, $pluginsPath . DIRECTORY_SEPARATOR)) {
            return basename($pluginFile);
        }
        return ltrim(str_replace($pluginsPath . DIRECTORY_SEPARATOR, '', dirname($real) . DIRECTORY_SEPARATOR . basename($pluginFile)), DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('erp_current_plugin_slug')) {
    /**
     * Retorna o slug do plugin em execução (quando chamado de dentro de um plugin).
     */
    function erp_current_plugin_slug(): ?string
    {
        return $GLOBALS['erp_current_plugin_slug'] ?? null;
    }
}

// ==================== Options API (item 1) ====================
if (!function_exists('get_option')) {
    /**
     * Obtém opção do plugin atual. Em contexto sem slug (ex: cron), use get_plugin_option($slug, $key, $default).
     */
    function get_option(string $key, mixed $default = null, ?string $slug = null): mixed
    {
        $slug = $slug ?? erp_current_plugin_slug();
        if (!$slug) {
            return $default;
        }
        return app(PluginOptionsService::class)->getOption($slug, $key, $default);
    }
}

if (!function_exists('get_plugin_option')) {
    /** Obtém opção de um plugin pelo slug. Use em cron, jobs, etc. */
    function get_plugin_option(string $slug, string $key, mixed $default = null): mixed
    {
        return app(PluginOptionsService::class)->getOption($slug, $key, $default);
    }
}

if (!function_exists('add_option')) {
    function add_option(string $key, mixed $value, ?string $slug = null): bool
    {
        $slug = $slug ?? erp_current_plugin_slug();
        return $slug ? app(PluginOptionsService::class)->addOption($slug, $key, $value) : false;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $key, mixed $value, ?string $slug = null): bool
    {
        $slug = $slug ?? erp_current_plugin_slug();
        return $slug ? app(PluginOptionsService::class)->updateOption($slug, $key, $value) : false;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $key, ?string $slug = null): bool
    {
        $slug = $slug ?? erp_current_plugin_slug();
        return $slug ? app(PluginOptionsService::class)->deleteOption($slug, $key) : false;
    }
}

// ==================== Transients API (item 2) ====================
if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool
    {
        $slug = erp_current_plugin_slug();
        $cacheKey = $slug ? "plugin_transient_{$slug}_{$key}" : "plugin_transient_{$key}";
        $ttl = $expiration > 0 ? $expiration : null;
        return Cache::put($cacheKey, $value, $ttl);
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        $slug = erp_current_plugin_slug();
        $cacheKey = $slug ? "plugin_transient_{$slug}_{$key}" : "plugin_transient_{$key}";
        return Cache::get($cacheKey);
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        $slug = erp_current_plugin_slug();
        $cacheKey = $slug ? "plugin_transient_{$slug}_{$key}" : "plugin_transient_{$key}";
        return Cache::forget($cacheKey);
    }
}

// ==================== remove_action / remove_filter (item 3) ====================
if (!function_exists('remove_action')) {
    function remove_action(string $hook, callable $callback, int $priority = 10): bool
    {
        return PluginManager::instance()->removeAction($hook, $callback, $priority);
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        return PluginManager::instance()->removeFilter($hook, $callback, $priority);
    }
}

// ==================== Settings (item 9) ====================
if (!function_exists('add_options_page')) {
    /**
     * Registra uma página de opções no menu de configurações.
     * Uso: add_options_page('API Config', 'Meu Plugin', 'plugin.meu-plugin.settings', 'meu-plugin');
     */
    function add_options_page(string $pageTitle, string $menuTitle, string $routeName, ?string $pluginSlug = null): void
    {
        PluginSettingsRegistry::instance()->addOptionsPage($pageTitle, $menuTitle, $routeName, $pluginSlug);
    }
}

if (!function_exists('register_setting')) {
    /**
     * Registra um campo de configuração.
     * Uso: register_setting('meu-plugin', 'api_key', ['type' => 'string', 'default' => '']);
     */
    function register_setting(string $pluginSlug, string $optionKey, array $args = []): void
    {
        PluginSettingsRegistry::instance()->registerSetting($pluginSlug, $optionKey, $args);
    }
}

if (!function_exists('add_admin_notice')) {
    /**
     * Adiciona um aviso na área admin (exibe em admin.notices).
     * Uso: add_admin_notice('mensagem', 'success'); // success|error|warning|info
     */
    function add_admin_notice(string $message, string $type = 'info'): void
    {
        $notices = $GLOBALS['erp_admin_notices'] ?? [];
        $notices[] = ['message' => $message, 'type' => $type];
        $GLOBALS['erp_admin_notices'] = $notices;
    }
}

if (!function_exists('get_admin_notices')) {
    function get_admin_notices(): array
    {
        return $GLOBALS['erp_admin_notices'] ?? [];
    }
}

// ==================== Hooks padrão ====================
if (!function_exists('do_action')) {
    /**
     * Executa callbacks registrados em um hook de ação (estilo WordPress).
     * Uso: do_action('menu.items.before', $items);
     */
    function do_action(string $hook, mixed ...$args): void
    {
        PluginManager::instance()->doAction($hook, ...$args);
    }
}

if (!function_exists('add_action')) {
    /**
     * Registra um callback para um hook de ação.
     * Uso: add_action('menu.items.before', fn($items) => ...);
     */
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        PluginManager::instance()->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Aplica filtros a um valor (estilo WordPress).
     * Uso: $items = apply_filters('menu.items', $items);
     */
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return PluginManager::instance()->applyFilters($hook, $value, ...$args);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Registra um callback para um hook de filtro.
     * Uso: add_filter('menu.items', fn($items) => array_merge($items, [...]));
     */
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        PluginManager::instance()->addFilter($hook, $callback, $priority);
    }
}

if (!function_exists('erp_register_activation_hook')) {
    /**
     * Registra callback executado quando o plugin é ativado.
     * Use __FILE__ como primeiro parâmetro (referência ao plugin.php).
     * Ex: erp_register_activation_hook(__FILE__, fn() => DB::statement('CREATE TABLE ...'));
     */
    function erp_register_activation_hook(string $pluginFile, callable $callback): void
    {
        PluginManager::instance()->registerActivationHook($pluginFile, $callback);
    }
}

if (!function_exists('erp_register_deactivation_hook')) {
    /**
     * Registra callback executado quando o plugin é desativado.
     */
    function erp_register_deactivation_hook(string $pluginFile, callable $callback): void
    {
        PluginManager::instance()->registerDeactivationHook($pluginFile, $callback);
    }
}

if (!function_exists('erp_register_uninstall_hook')) {
    /**
     * Registra callback executado quando o plugin é excluído (antes dos arquivos serem removidos).
     * Use para limpar tabelas, opções, cache etc.
     */
    function erp_register_uninstall_hook(string $pluginFile, callable $callback): void
    {
        PluginManager::instance()->registerUninstallHook($pluginFile, $callback);
    }
}

if (!function_exists('erp_view')) {
    /**
     * Retorna uma view aplicando hooks de plugins (view.render.before, view.data).
     * Use no lugar de view() para que plugins possam modificar dados.
     */
    function erp_view(string $view, array $data = [])
    {
        if (function_exists('do_action')) {
            do_action('view.render.before', $view, $data);
        }
        if (function_exists('apply_filters')) {
            $data = apply_filters('view.data', $data, $view);
        }
        return view($view, $data);
    }
}

if (!function_exists('erp_json')) {
    /**
     * Retorna response JSON aplicando hooks de plugins (response.json).
     * Use no lugar de response()->json() para que plugins possam modificar a resposta.
     *
     * @param mixed $data Dados a retornar (array ou objeto)
     * @param int $status Status HTTP
     * @param array $headers Headers opcionais
     * @param string|null $context Contexto para o hook (ex: 'simulador.simular')
     */
    function erp_json(mixed $data, int $status = 200, array $headers = [], ?string $context = null)
    {
        if (function_exists('apply_filters')) {
            $data = apply_filters('response.json', $data, $context);
        }
        return response()->json($data, $status, $headers);
    }
}
