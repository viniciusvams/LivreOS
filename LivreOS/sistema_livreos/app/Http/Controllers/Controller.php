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

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasEntityHooks;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controller base da aplicação LivreOS
 *
 * @author viniciusvams
 * @license GPL-3.0
 */
abstract class Controller extends BaseController
{
    use HasEntityHooks;

    /**
     * Exige que o usuário tenha a permissão (ou seja admin). Caso contrário, aborta 403.
     */
    protected function authorizePermission(string $permission): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            abort(403, 'Você não tem permissão para acessar este recurso.');
        }
    }

    /**
     * Valida a requisição aplicando filtros de plugins.
     * Plugins podem modificar regras via add_filter('request.validation.rules', ...)
     */
    protected function validateWithFilters(
        \Illuminate\Http\Request $request,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): array {
        if (function_exists('apply_filters')) {
            $rules = apply_filters(
                'request.validation.rules',
                $rules,
                $request->route()?->getName(),
                $request
            );
        }
        return $request->validate($rules, $messages, $attributes);
    }
}
