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

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica requisições da API via token Bearer (campo api_token do usuário).
 * Retorna 401 JSON se não autenticado.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Token de autenticação não informado.',
                'error' => 'unauthenticated',
            ], 401, ['Content-Type' => 'application/json']);
        }

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Token inválido ou expirado.',
                'error' => 'unauthenticated',
            ], 401, ['Content-Type' => 'application/json']);
        }

        if (! $user->ativo) {
            return response()->json([
                'message' => 'Usuário inativo.',
                'error' => 'unauthenticated',
            ], 401, ['Content-Type' => 'application/json']);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
