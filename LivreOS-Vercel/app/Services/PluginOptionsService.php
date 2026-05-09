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

use App\Models\Configuracao;

/**
 * Options API para plugins (estilo WordPress).
 * Armazena opções por plugin no banco de dados (tabela configuracoes).
 */
class PluginOptionsService
{
    protected function getOptionKey(string $slug): string
    {
        return 'plugin_options_' . $slug;
    }

    protected function loadOptions(string $slug): array
    {
        $val = Configuracao::getValue($this->getOptionKey($slug), null);
        if ($val === null) {
            return [];
        }
        $data = json_decode($val, true);
        return is_array($data) ? $data : [];
    }

    protected function saveOptions(string $slug, array $options): void
    {
        Configuracao::setValue($this->getOptionKey($slug), json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getOption(string $slug, string $key, mixed $default = null): mixed
    {
        $options = $this->loadOptions($slug);
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    public function addOption(string $slug, string $key, mixed $value): bool
    {
        $options = $this->loadOptions($slug);
        if (array_key_exists($key, $options)) {
            return false;
        }
        $options[$key] = $value;
        $this->saveOptions($slug, $options);
        return true;
    }

    public function updateOption(string $slug, string $key, mixed $value): bool
    {
        $options = $this->loadOptions($slug);
        $options[$key] = $value;
        $this->saveOptions($slug, $options);
        return true;
    }

    public function deleteOption(string $slug, string $key): bool
    {
        $options = $this->loadOptions($slug);
        if (!array_key_exists($key, $options)) {
            return false;
        }
        unset($options[$key]);
        $this->saveOptions($slug, $options);
        return true;
    }

    /**
     * Remove todas as opções de um plugin (ao desinstalar).
     */
    public function deleteAllOptions(string $slug): void
    {
        Configuracao::where('chave', $this->getOptionKey($slug))->delete();
    }
}
