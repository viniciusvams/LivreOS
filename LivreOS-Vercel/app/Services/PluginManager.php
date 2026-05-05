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

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Gerenciador de plugins - Estilo WordPress.
 * Permite que usuários desenvolvam e instalem plugins para estender o ERP.
 *
 * Hooks disponíveis:
 * - Actions: do_action() - executa callbacks em pontos do código
 * - Filters: apply_filters() - permite modificar dados antes de uso
 *
 * Ciclo de vida (cada plugin pode registrar):
 * - erp_register_activation_hook() - ao ativar
 * - erp_register_deactivation_hook() - ao desativar
 * - erp_register_uninstall_hook() - ao excluir
 *
 * Actions globais (qualquer código pode escutar):
 * - plugin.installed, plugin.activated, plugin.deactivated, plugin.uninstalled
 */
class PluginManager
{
    protected array $plugins = [];
    protected array $actions = [];
    protected array $filters = [];
    protected array $activationHooks = [];
    protected array $deactivationHooks = [];
    protected array $uninstallHooks = [];
    protected static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPluginsPath(): string
    {
        return base_path('plugins');
    }

    protected static bool $earlyBootstrapped = false;

    /**
     * Carrega apenas os arquivos dos plugins (para registrar filters). Não carrega rotas.
     * Deve ser chamado antes da resolução do HttpKernel (ex: bootstrap/app.php).
     */
    public function bootstrapEarly(): void
    {
        if (self::$earlyBootstrapped) {
            return;
        }
        self::$earlyBootstrapped = true;
        $this->loadPluginFilesOnly();
    }

    protected static bool $fullBootstrapped = false;

    /**
     * Descobre e carrega todos os plugins ativos (arquivos + rotas).
     * Idempotente: ignora chamadas subsequentes após carregamento completo.
     */
    public function bootstrap(): void
    {
        $this->bootstrapEarly();
        if (self::$fullBootstrapped) {
            return;
        }
        self::$fullBootstrapped = true;
        $this->loadPluginRoutes();
        $this->registerPluginViewNamespaces();
        $this->doAction('plugins.loaded');
    }

    /**
     * Carrega apenas os arquivos PHP dos plugins (para registrar hooks/filters).
     * Usa funções nativas PHP para funcionar antes do bootstrap (sem facades).
     */
    protected function loadPluginFilesOnly(): void
    {
        $pluginsPath = $this->getPluginsPath();
        if (!is_dir($pluginsPath)) {
            @mkdir($pluginsPath, 0755, true);
            return;
        }

        $activePlugins = $this->getActivePluginsNative();

        $dirs = glob($pluginsPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        foreach ($dirs as $pluginDir) {
            $slug = $this->getPluginSlugFromDir($pluginDir);
            if ($slug === null) {
                continue;
            }
            $pluginFile = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.php';

            if (!file_exists($pluginFile) || !in_array($slug, $activePlugins, true)) {
                continue;
            }

            $metadata = $this->readPluginMetadata($pluginFile);
            if (!$metadata) {
                continue;
            }

            $requirementsError = $this->checkPluginRequirements($metadata);
            if ($requirementsError !== null) {
                continue;
            }

            $this->plugins[$slug] = array_merge($metadata, [
                'path' => $pluginDir,
                'slug' => $slug,
            ]);

            try {
                $this->loadPlugin($pluginDir, $pluginFile, $slug, true);
                $this->doAction('plugin.loaded', $slug);
            } catch (\Throwable $e) {
                unset($this->plugins[$slug]);
                error_log("[PluginManager] Erro ao carregar plugin {$slug}: " . $e->getMessage());
                try {
                    if (function_exists('config') && config('plugins.debug', false)) {
                        continue;
                    }
                } catch (\Throwable) {
                    // config pode não estar disponível no early bootstrap
                }
                throw $e;
            }
        }
    }

    /**
     * Retorna plugins ativos usando funções nativas (sem File facade).
     */
    protected function getActivePluginsNative(): array
    {
        $configPath = null;
        if (function_exists('storage_path')) {
            $configPath = storage_path('app/plugins_active.json');
        } elseif (function_exists('base_path')) {
            $configPath = base_path('storage/app/plugins_active.json');
        } else {
            $configPath = realpath(__DIR__ . '/../../storage/app/plugins_active.json') ?: null;
        }
        if (!$configPath || !file_exists($configPath)) {
            return [];
        }
        $content = @file_get_contents($configPath);
        if ($content === false) {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data['plugins'] ?? null) ? $data['plugins'] : [];
    }

    /**
     * Retorna o slug do plugin a partir do diretório.
     * Pastas com sufixo .disabled são ignoradas (desativação de emergência).
     */
    protected function getPluginSlugFromDir(string $pluginDir): ?string
    {
        $base = basename($pluginDir);
        if (str_ends_with($base, '.disabled')) {
            return null;
        }
        return $base;
    }

    /**
     * Registra namespaces de views dos plugins (quando carregados em early bootstrap).
     */
    protected function registerPluginViewNamespaces(): void
    {
        foreach ($this->plugins as $slug => $plugin) {
            $viewsDir = ($plugin['path'] ?? '') . DIRECTORY_SEPARATOR . 'views';
            if (is_dir($viewsDir)) {
                View::addNamespace($slug, $viewsDir);
            }
        }
    }

    /**
     * Carrega rotas definidas em routes.php de cada plugin ativo.
     * Plugins podem criar plugins/{slug}/routes.php para registrar rotas.
     */
    protected function loadPluginRoutes(): void
    {
        foreach ($this->plugins as $slug => $plugin) {
            $routesFile = ($plugin['path'] ?? '') . DIRECTORY_SEPARATOR . 'routes.php';
            if (File::exists($routesFile)) {
                Route::middleware('web')
                    ->prefix('plugin/' . $slug)
                    ->name('plugin.' . $slug . '.')
                    ->group($routesFile);
            }
        }
    }

    public function getActivePlugins(): array
    {
        $configPath = storage_path('app/plugins_active.json');
        if (!File::exists($configPath)) {
            return [];
        }
        $content = File::get($configPath);
        $data = json_decode($content, true);
        return is_array($data['plugins'] ?? null) ? $data['plugins'] : [];
    }

    public function setActivePlugins(array $slugs): void
    {
        $configPath = storage_path('app/plugins_active.json');
        File::put($configPath, json_encode(['plugins' => $slugs], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function activate(string $slug): array
    {
        $pluginPath = $this->getPluginsPath() . DIRECTORY_SEPARATOR . $slug;
        $pluginFile = $pluginPath . DIRECTORY_SEPARATOR . 'plugin.php';
        if (!File::exists($pluginFile)) {
            return ['success' => false, 'message' => 'Plugin não encontrado.'];
        }

        $metadata = $this->readPluginMetadata($pluginFile);
        if (!$metadata) {
            return ['success' => false, 'message' => 'Cabeçalho do plugin inválido.'];
        }

        $requirementsError = $this->checkPluginRequirements($metadata);
        if ($requirementsError !== null) {
            return ['success' => false, 'message' => $requirementsError];
        }

        $depError = $this->checkPluginDependencies($slug, $metadata);
        if ($depError !== null) {
            return ['success' => false, 'message' => $depError];
        }

        $active = $this->getActivePlugins();
        if (!in_array($slug, $active, true)) {
            try {
                $this->runActivationHook($slug);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Erro ao ativar: ' . $e->getMessage()];
            }
            $active[] = $slug;
            $this->setActivePlugins($active);
        }
        $this->doAction('plugin.activated', $slug);
        return ['success' => true];
    }

    public function deactivate(string $slug): bool
    {
        $active = $this->getActivePlugins();
        if (in_array($slug, $active, true)) {
            $this->runDeactivationHook($slug);
            $active = array_values(array_filter($active, fn($s) => $s !== $slug));
            $this->setActivePlugins($active);
        }
        $this->doAction('plugin.deactivated', $slug);
        return true;
    }

    /**
     * Registra callback executado na ativação do plugin.
     * Uso no plugin.php: erp_register_activation_hook(__FILE__, fn() => ...);
     */
    public function registerActivationHook(string $pluginFile, callable $callback): void
    {
        $slug = $this->getSlugFromPluginFile($pluginFile);
        if ($slug !== '') {
            $this->activationHooks[$slug] = $callback;
        }
    }

    /**
     * Registra callback executado na desativação do plugin.
     */
    public function registerDeactivationHook(string $pluginFile, callable $callback): void
    {
        $slug = $this->getSlugFromPluginFile($pluginFile);
        if ($slug !== '') {
            $this->deactivationHooks[$slug] = $callback;
        }
    }

    /**
     * Registra callback executado na exclusão do plugin (antes dos arquivos serem removidos).
     */
    public function registerUninstallHook(string $pluginFile, callable $callback): void
    {
        $slug = $this->getSlugFromPluginFile($pluginFile);
        if ($slug !== '') {
            $this->uninstallHooks[$slug] = $callback;
        }
    }

    public function getSlugFromPluginFile(string $pluginFile): string
    {
        $pluginsPath = $this->getPluginsPath();
        $real = realpath($pluginFile);
        if (!$real || !str_starts_with($real, realpath($pluginsPath) . DIRECTORY_SEPARATOR)) {
            return '';
        }
        $relative = str_replace(realpath($pluginsPath) . DIRECTORY_SEPARATOR, '', dirname($real));
        return basename($relative);
    }

    protected function runActivationHook(string $slug): void
    {
        $pluginPath = $this->getPluginsPath() . DIRECTORY_SEPARATOR . $slug;
        $pluginFile = $pluginPath . DIRECTORY_SEPARATOR . 'plugin.php';
        if (!File::exists($pluginFile)) {
            return;
        }
        $this->loadPluginForHooks($pluginPath, $pluginFile, $slug);
        if (isset($this->activationHooks[$slug])) {
            try {
                ($this->activationHooks[$slug])();
            } finally {
                unset($this->activationHooks[$slug]);
            }
        }
        $this->doAction('activate_' . $slug . '/plugin.php', $slug);
    }

    protected function runDeactivationHook(string $slug): void
    {
        if (isset($this->deactivationHooks[$slug])) {
            try {
                ($this->deactivationHooks[$slug])();
            } finally {
                unset($this->deactivationHooks[$slug]);
            }
        }
    }

    protected function runUninstallHook(string $slug): void
    {
        $pluginPath = $this->getPluginsPath() . DIRECTORY_SEPARATOR . $slug;
        $pluginFile = $pluginPath . DIRECTORY_SEPARATOR . 'plugin.php';
        $uninstallFile = $pluginPath . DIRECTORY_SEPARATOR . 'uninstall.php';

        if (File::exists($uninstallFile)) {
            try {
                if (!defined('ERPLUGIN_UNINSTALL')) {
                    define('ERPLUGIN_UNINSTALL', true);
                }
                require_once $uninstallFile;
            } catch (\Throwable $e) {
            }
        }

        if (File::exists($pluginFile)) {
            $this->loadPluginForHooks($pluginPath, $pluginFile, $slug);
            if (isset($this->uninstallHooks[$slug])) {
                ($this->uninstallHooks[$slug])();
            }
        }
    }

    /**
     * Carrega plugin apenas para registrar/executar hooks de ciclo de vida.
     */
    protected function loadPluginForHooks(string $pluginDir, string $pluginFile, string $slug): void
    {
        $loader = $pluginDir . DIRECTORY_SEPARATOR . 'autoload.php';
        if (File::exists($loader)) {
            require_once $loader;
        }
        if (!defined('ERPLUGIN_LOADED')) {
            define('ERPLUGIN_LOADED', true);
        }
        $GLOBALS['erp_current_plugin_slug'] = $slug;
        require_once $pluginFile;
        $GLOBALS['erp_current_plugin_slug'] = null;
    }

    /**
     * Faz upload e instala um plugin a partir de arquivo .zip.
     * O zip deve conter uma pasta com plugin.php na raiz (estilo WordPress).
     *
     * @return array{success: bool, message: string, slug?: string}
     */
    public function uploadPlugin(\Illuminate\Http\UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return ['success' => false, 'message' => 'Apenas arquivos .zip são aceitos.'];
        }

        $pluginsPath = $this->getPluginsPath();
        if (!File::isDirectory($pluginsPath)) {
            File::makeDirectory($pluginsPath, 0755, true);
        }

        $tempDir = storage_path('app/temp/plugin_upload_' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'Não foi possível abrir o arquivo ZIP.'];
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $pluginDirName = null;
        $pluginFilePath = null;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getFilename() === 'plugin.php') {
                $pluginFilePath = $path->getPathname();
                $relative = str_replace($tempDir . DIRECTORY_SEPARATOR, '', $path->getPathname());
                $parts = explode(DIRECTORY_SEPARATOR, $relative);
                $pluginDirName = $parts[0];
                break;
            }
        }

        if (!$pluginDirName || !$pluginFilePath || !File::exists($pluginFilePath)) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'O ZIP deve conter uma pasta com plugin.php (ex: meu-plugin/plugin.php).'];
        }

        $sourceDir = dirname($pluginFilePath);
        $pluginDirName = basename($sourceDir);
        if ($pluginDirName === '' || preg_match('/[\.\/\\\\]/', $pluginDirName) || str_ends_with($pluginDirName, '.disabled')) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'Nome da pasta do plugin inválido.'];
        }
        $targetPath = $pluginsPath . DIRECTORY_SEPARATOR . $pluginDirName;

        $isUpdate = false;
        if (File::isDirectory($targetPath)) {
            $active = $this->getActivePlugins();
            if (in_array($pluginDirName, $active, true)) {
                File::deleteDirectory($tempDir);
                return ['success' => false, 'message' => "O plugin '{$pluginDirName}' está ativo. Desative-o antes de atualizar."];
            }
            $isUpdate = true;
            File::deleteDirectory($targetPath);
        }

        File::moveDirectory($sourceDir, $targetPath);
        File::deleteDirectory($tempDir);

        $metadata = $this->readPluginMetadata($targetPath . DIRECTORY_SEPARATOR . 'plugin.php');
        if (!$metadata) {
            File::deleteDirectory($targetPath);
            return ['success' => false, 'message' => 'Cabeçalho do plugin inválido. É necessário "Plugin Name:" no plugin.php.'];
        }

        $requirementsError = $this->checkPluginRequirements($metadata);
        if ($requirementsError !== null) {
            File::deleteDirectory($targetPath);
            return ['success' => false, 'message' => $requirementsError];
        }

        $this->doAction('plugin.installed', $pluginDirName);

        $message = $isUpdate
            ? "Plugin '{$pluginDirName}' atualizado com sucesso! Ative-o para usar a nova versão."
            : 'Plugin instalado com sucesso! Ative-o abaixo para começar a usar.';

        return ['success' => true, 'message' => $message, 'slug' => $pluginDirName];
    }

    /**
     * Remove um plugin do disco (deve estar desativado).
     */
    public function deletePlugin(string $slug): array
    {
        $active = $this->getActivePlugins();
        if (in_array($slug, $active, true)) {
            return ['success' => false, 'message' => 'Desative o plugin antes de excluí-lo.'];
        }

        $pluginPath = $this->getPluginsPath() . DIRECTORY_SEPARATOR . $slug;
        if (!File::isDirectory($pluginPath)) {
            return ['success' => false, 'message' => 'Plugin não encontrado.'];
        }

        $this->runUninstallHook($slug);
        $this->doAction('plugin.uninstalled', $slug);
        app(PluginOptionsService::class)->deleteAllOptions($slug);

        if (File::deleteDirectory($pluginPath)) {
            return ['success' => true, 'message' => 'Plugin excluído com sucesso.'];
        }

        return ['success' => false, 'message' => 'Não foi possível excluir o plugin. Verifique permissões.'];
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getAvailablePlugins(): array
    {
        $pluginsPath = $this->getPluginsPath();
        if (!File::isDirectory($pluginsPath)) {
            return [];
        }

        $available = [];
        foreach (File::directories($pluginsPath) as $pluginDir) {
            $slug = $this->getPluginSlugFromDir($pluginDir);
            if ($slug === null) {
                continue;
            }
            $pluginFile = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.php';
            if (!File::exists($pluginFile)) {
                continue;
            }
            $metadata = $this->readPluginMetadata($pluginFile);
            if ($metadata) {
                $requirementsError = $this->checkPluginRequirements($metadata);
                $depError = $requirementsError === null ? $this->checkPluginDependencies($slug, $metadata) : null;
                $available[$slug] = array_merge($metadata, [
                    'path' => $pluginDir,
                    'slug' => $slug,
                    'active' => isset($this->plugins[$slug]),
                    'requirements_error' => $requirementsError,
                    'dependency_error' => $depError,
                ]);
            }
        }
        return $available;
    }

    public function getPlugin(string $slug): ?array
    {
        $plugins = $this->getAvailablePlugins();
        return $plugins[$slug] ?? null;
    }

    protected function readPluginMetadata(string $pluginFile): ?array
    {
        $content = @file_get_contents($pluginFile);
        if ($content === false) {
            return null;
        }
        if (!preg_match('/\*\s*Plugin Name:\s*(.+)/', $content, $name)) {
            return null;
        }
        $metadata = [
            'name' => trim($name[1]),
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'requires_php' => null,
            'requires_erp' => null,
        ];
        if (preg_match('/\*\s*Version:\s*(.+)/', $content, $v)) {
            $metadata['version'] = trim($v[1]);
        }
        if (preg_match('/\*\s*Description:\s*(.+)/', $content, $d)) {
            $metadata['description'] = trim($d[1]);
        }
        if (preg_match('/\*\s*Author:\s*(.+)/', $content, $a)) {
            $metadata['author'] = trim($a[1]);
        }
        if (preg_match('/\*\s*Requires PHP:\s*(.+)/', $content, $r)) {
            $metadata['requires_php'] = trim($r[1]);
        }
        if (preg_match('/\*\s*Requires ERP:\s*(.+)/', $content, $r)) {
            $metadata['requires_erp'] = trim($r[1]);
        }
        if (preg_match('/\*\s*Requires Plugins:\s*(.+)/', $content, $r)) {
            $metadata['requires_plugins'] = array_map('trim', explode(',', trim($r[1])));
        } else {
            $metadata['requires_plugins'] = [];
        }
        return $metadata;
    }

    protected function checkPluginDependencies(string $slug, array $metadata): ?string
    {
        $required = $metadata['requires_plugins'] ?? [];
        if (empty($required)) {
            return null;
        }
        $active = $this->getActivePlugins();
        foreach ($required as $dep) {
            $dep = trim($dep);
            if ($dep === '' || $dep === $slug) {
                continue;
            }
            if (!in_array($dep, $active, true)) {
                return "Este plugin requer que '{$dep}' esteja ativo. Ative-o primeiro.";
            }
        }
        return null;
    }

    protected function checkPluginRequirements(array $metadata): ?string
    {
        if (!empty($metadata['requires_php'])) {
            $required = $metadata['requires_php'];
            if (version_compare(PHP_VERSION, $required, '<')) {
                return "Requer PHP {$required} ou superior. Versão atual: " . PHP_VERSION;
            }
        }
        if (!empty($metadata['requires_erp'])) {
            $required = $metadata['requires_erp'];
            $current = function_exists('app') ? app()->version() : '0';
            if (version_compare((string) $current, $required, '<')) {
                return "Requer ERP/Laravel {$required} ou superior. Versão atual: {$current}";
            }
        }
        return null;
    }

    protected function loadPlugin(string $pluginDir, string $pluginFile, string $slug, bool $earlyBootstrap = false): void
    {
        $loader = $pluginDir . DIRECTORY_SEPARATOR . 'autoload.php';
        if (($earlyBootstrap ? file_exists($loader) : File::exists($loader))) {
            require_once $loader;
        }
        if (!defined('ERPLUGIN_LOADED')) {
            define('ERPLUGIN_LOADED', true);
        }

        if (!$earlyBootstrap) {
            $viewsDir = $pluginDir . DIRECTORY_SEPARATOR . 'views';
            if (File::isDirectory($viewsDir)) {
                View::addNamespace($slug, $viewsDir);
            }
        }

        $GLOBALS['erp_current_plugin_slug'] = $slug;
        require_once $pluginFile;
        $GLOBALS['erp_current_plugin_slug'] = null;
    }

    // ==================== ACTIONS (como WordPress do_action) ====================

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset($this->actions[$hook])) {
            $this->actions[$hook] = [];
        }
        $this->actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
        usort($this->actions[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        if (!isset($this->actions[$hook])) {
            return false;
        }
        $before = count($this->actions[$hook]);
        $this->actions[$hook] = array_values(array_filter($this->actions[$hook], function ($item) use ($callback, $priority) {
            return !($item['priority'] === $priority && $item['callback'] === $callback);
        }));
        return count($this->actions[$hook]) < $before;
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): bool
    {
        if (!isset($this->filters[$hook])) {
            return false;
        }
        $before = count($this->filters[$hook]);
        $this->filters[$hook] = array_values(array_filter($this->filters[$hook], function ($item) use ($callback, $priority) {
            return !($item['priority'] === $priority && $item['callback'] === $callback);
        }));
        return count($this->filters[$hook]) < $before;
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        if (!isset($this->actions[$hook])) {
            return;
        }
        foreach ($this->actions[$hook] as $item) {
            ($item['callback'])(...$args);
        }
    }

    // ==================== FILTERS (como WordPress apply_filters) ====================

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        if (!isset($this->filters[$hook])) {
            $this->filters[$hook] = [];
        }
        $this->filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
        usort($this->filters[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$hook])) {
            return $value;
        }
        foreach ($this->filters[$hook] as $item) {
            $value = ($item['callback'])($value, ...$args);
        }
        return $value;
    }
}
