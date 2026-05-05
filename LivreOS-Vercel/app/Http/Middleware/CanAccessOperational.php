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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanAccessOperational
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('signin'));
        }

        if (auth()->user()->canAccessOperational()) {
            return $next($request);
        }

        // Usuário do portal (cliente): redireciona para área do cliente em vez de 403
        $redirect = function_exists('apply_filters') ? apply_filters('auth.login.redirect', null, auth()->user(), $request) : null;
        if ($redirect) {
            return redirect($redirect);
        }

        abort(403, 'Acesso negado. Você não tem permissão para acessar esta área.');
    }
}
