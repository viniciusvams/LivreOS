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

use App\Models\CentroCusto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Centros de custo (para lançamentos financeiros). Exige access-financeiro.
 */
class CentroCustoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('access-financeiro');
        $query = CentroCusto::query()->orderBy('ordem')->orderBy('nome');

        if ($request->boolean('ativo_only')) {
            $query->where('ativo', true);
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $paginator = $query->paginate($perPage)->withQueryString();

        return $this->success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorizePermission('access-financeiro');
        $centro = CentroCusto::find($id);
        if (! $centro) {
            return $this->error('Centro de custo não encontrado.', 404);
        }

        return $this->success($centro);
    }
}
