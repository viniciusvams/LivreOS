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

namespace App\Http\Controllers\Concerns;

/**
 * Trait que permite plugins estender queries de listagem e dados de views.
 * Use em controllers que possuem index() e retornam views com dados.
 */
trait HasEntityHooks
{
    /**
     * Aplica filtros de plugins na query de listagem.
     * Uso: $query = $this->applyEntityQuery($query, 'cliente');
     */
    protected function applyEntityQuery($query, string $entity): mixed
    {
        if (function_exists('apply_filters')) {
            return apply_filters('entity.index.query', $query, $entity);
        }

        return $query;
    }

    /**
     * Dispara ação e aplica filtros nos dados antes de retornar a view.
     * Uso: return $this->viewWithHooks('clientes.index', ['clientes' => $clientes, 'title' => '...']);
     */
    protected function viewWithHooks(string $view, array $data = []): \Illuminate\View\View
    {
        $viewName = $view;

        if (function_exists('do_action')) {
            do_action('view.render.before', $viewName, $data);
        }

        if (function_exists('apply_filters')) {
            $data = apply_filters('view.data', $data, $viewName);
        }

        return view($view, $data);
    }
}
