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

/**
 * Registro de páginas e campos de configuração de plugins (estilo WordPress).
 * add_options_page registra um submenu; register_setting registra campos.
 */
class PluginSettingsRegistry
{
    protected array $optionsPages = [];
    protected array $settings = [];
    protected static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra uma página de opções no menu.
     * Uso: add_options_page('Título', 'Menu', 'plugin.meu-plugin.settings', 'meu-plugin');
     */
    public function addOptionsPage(string $pageTitle, string $menuTitle, string $routeName, ?string $pluginSlug = null): void
    {
        $this->optionsPages[] = [
            'page_title' => $pageTitle,
            'menu_title' => $menuTitle,
            'route' => $routeName,
            'slug' => $pluginSlug,
        ];
    }

    /**
     * Registra um campo de configuração.
     * Uso: register_setting('meu-plugin', 'api_key', ['type' => 'string', 'default' => '']);
     */
    public function registerSetting(string $pluginSlug, string $optionKey, array $args = []): void
    {
        $group = $pluginSlug . '_settings';
        if (!isset($this->settings[$group])) {
            $this->settings[$group] = [];
        }
        $this->settings[$group][$optionKey] = array_merge([
            'type' => 'string',
            'default' => null,
            'sanitize' => null,
        ], $args);
    }

    public function getOptionsPages(): array
    {
        return $this->optionsPages;
    }

    public function getRegisteredSettings(string $pluginSlug): array
    {
        $group = $pluginSlug . '_settings';
        return $this->settings[$group] ?? [];
    }

    public function getSettingDefault(string $pluginSlug, string $optionKey): mixed
    {
        $settings = $this->getRegisteredSettings($pluginSlug);
        return $settings[$optionKey]['default'] ?? null;
    }
}
