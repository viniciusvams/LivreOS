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

/**
 * Exige que o usuário tenha acesso operacional (área operacional). Retorna 403 JSON na API.
 */
class CanAccessOperationalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Não autenticado.',
                'error' => 'unauthenticated',
            ], 401, ['Content-Type' => 'application/json']);
        }

        if (! auth()->user()->canAccessOperational()) {
            return response()->json([
                'message' => 'Acesso negado. Você não tem permissão para acessar esta área.',
                'error' => 'forbidden',
            ], 403, ['Content-Type' => 'application/json']);
        }

        return $next($request);
    }
}
