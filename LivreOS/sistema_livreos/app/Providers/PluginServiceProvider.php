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

namespace App\Providers;

use App\Services\PluginManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, fn () => PluginManager::instance());
        require_once app_path('Helpers/plugin_helpers.php');
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            PluginManager::instance()->bootstrap();
            $this->registerModelHooks();
        });
    }

    /**
     * Registra hooks globais no ciclo de vida dos Models Eloquent.
     * Plugins podem usar add_action('model.creating', fn($model) => ...) etc.
     */
    protected function registerModelHooks(): void
    {
        if (!function_exists('do_action')) {
            return;
        }

        $events = [
            'creating' => 'model.creating',
            'created' => 'model.created',
            'updating' => 'model.updating',
            'updated' => 'model.updated',
            'saving' => 'model.saving',
            'saved' => 'model.saved',
            'deleting' => 'model.deleting',
            'deleted' => 'model.deleted',
        ];

        foreach ($events as $event => $hook) {
            Model::$event(function (Model $model) use ($hook) {
                do_action($hook, $model);
            });
        }
    }
}
