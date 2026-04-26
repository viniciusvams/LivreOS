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

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller base para a API. Respostas em JSON e checagem de permissão com retorno 403 JSON.
 */
abstract class ApiController extends Controller
{
    /**
     * Exige permissão; caso contrário retorna 403 JSON.
     */
    protected function authorizePermission(string $permission): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para acessar este recurso.',
                    'error' => 'forbidden',
                    'permission_required' => $permission,
                ], 403)
            );
        }
    }

    /**
     * Retorna resposta JSON de sucesso (200).
     */
    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        $body = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $code);
    }

    /**
     * Retorna resposta JSON de erro (4xx/5xx).
     */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if (! empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $code);
    }

    /**
     * Valida a requisição e retorna dados validados; em falha retorna 422 JSON.
     */
    protected function validateRequest(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);
        if ($validator->fails()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Erro de validação.',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        return $validator->validated();
    }
}
