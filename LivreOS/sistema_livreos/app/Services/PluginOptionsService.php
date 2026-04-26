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

/**
 * Options API para plugins (estilo WordPress).
 * Armazena opções por plugin em JSON em storage/app/plugin_options/{slug}.json
 */
class PluginOptionsService
{
    protected function getOptionsPath(string $slug): string
    {
        return storage_path('app/plugin_options/' . $slug . '.json');
    }

    protected function loadOptions(string $slug): array
    {
        $path = $this->getOptionsPath($slug);
        if (!File::exists($path)) {
            return [];
        }
        $content = File::get($path);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    protected function saveOptions(string $slug, array $options): void
    {
        $dir = dirname($this->getOptionsPath($slug));
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($dir . DIRECTORY_SEPARATOR . $slug . '.json', json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
        $path = $this->getOptionsPath($slug);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
