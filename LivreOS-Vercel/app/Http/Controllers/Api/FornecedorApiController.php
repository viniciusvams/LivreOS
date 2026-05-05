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

use App\Models\Contato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fornecedores = Contatos com is_fornecedor = true. Exige view-products.
 */
class FornecedorApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-products');
        $query = Contato::where('is_fornecedor', true)->with('cliente');

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $query = $this->applyEntityQuery($query->orderBy('nome'), 'fornecedor');
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
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
        $this->authorizePermission('view-products');
        $fornecedor = Contato::where('is_fornecedor', true)->with('cliente')->find($id);
        if (! $fornecedor) {
            return $this->error('Fornecedor não encontrado.', 404);
        }

        return $this->success($fornecedor);
    }
}
