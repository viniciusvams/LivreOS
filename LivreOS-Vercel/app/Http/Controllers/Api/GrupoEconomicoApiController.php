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

use App\Models\GrupoEconomico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Grupos econômicos (para formulário de clientes). Exige view-clients.
 */
class GrupoEconomicoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-clients');
        $query = GrupoEconomico::query()->orderBy('nome');

        if ($request->boolean('ativo_only')) {
            $query->where('ativo', true);
        }
        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
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
        $this->authorizePermission('view-clients');
        $grupo = GrupoEconomico::find($id);
        if (! $grupo) {
            return $this->error('Grupo econômico não encontrado.', 404);
        }

        return $this->success($grupo);
    }
}
