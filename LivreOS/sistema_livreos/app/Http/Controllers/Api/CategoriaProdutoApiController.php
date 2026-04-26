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

use App\Models\CategoriaProduto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaProdutoApiController extends ApiController
{
    /**
     * Lista categorias de produtos (para selects no app). Exige view-products.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-products');
        $query = CategoriaProduto::query()->orderBy('nome');

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
}
