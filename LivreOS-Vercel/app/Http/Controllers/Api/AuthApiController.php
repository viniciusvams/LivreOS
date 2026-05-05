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

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthApiController extends ApiController
{
    /**
     * Login: email + password. Retorna token e dados do usuário.
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $this->ensureApiLoginNotRateLimited($request);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($this->apiLoginThrottleKey($request), 300);

            return $this->error('Credenciais inválidas.', 401);
        }

        if (! $user->ativo) {
            RateLimiter::hit($this->apiLoginThrottleKey($request), 300);

            return $this->error('Usuário inativo.', 403);
        }

        RateLimiter::clear($this->apiLoginThrottleKey($request));

        $token = $user->createApiToken();

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userResource($user),
        ], 'Login realizado com sucesso.', 200);
    }

    /**
     * Logout: invalida o token do usuário atual.
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user) {
            $user->revokeApiToken();
        }

        return $this->success(null, 'Logout realizado com sucesso.');
    }

    /**
     * Retorna o usuário autenticado.
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return $this->error('Não autenticado.', 401);
        }

        return $this->success($this->userResource($user), 'OK');
    }

    /**
     * Alterar senha do usuário logado (senha atual + nova senha).
     * POST /api/auth/alterar-senha
     */
    public function alterarSenha(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'senha_atual' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ], [], [
            'senha_atual' => 'senha atual',
            'password' => 'nova senha',
        ]);

        $user = auth()->user();
        if (! $user || ! Hash::check($request->senha_atual, $user->password)) {
            return $this->error('A senha atual informada está incorreta.', 422);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return $this->success(null, 'Senha alterada com sucesso.');
    }

    private function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'ativo' => (bool) $user->ativo,
            'roles' => $user->roles->pluck('slug')->toArray(),
        ];
    }

    /**
     * Mesma ideia do login web: limite por e-mail + IP (força bruta na API).
     */
    protected function ensureApiLoginNotRateLimited(Request $request): void
    {
        $maxAttempts = 5;
        $key         = $this->apiLoginThrottleKey($request);

        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em '.$seconds.' segundos.',
                'error'   => 'too_many_attempts',
            ], 429)->withHeaders(['Retry-After' => (string) max(1, $seconds)])
        );
    }

    protected function apiLoginThrottleKey(Request $request): string
    {
        $email = Str::lower((string) $request->input('email'));

        return Str::transliterate($email.'|'.$request->ip());
    }
}
