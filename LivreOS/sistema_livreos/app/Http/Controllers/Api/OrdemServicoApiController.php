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

use App\Models\OrdemServico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdemServicoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-os-history');
        $query = OrdemServico::with(['cliente', 'contato', 'endereco']);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('codigo_interno')) {
            $query->where('codigo_interno', 'like', '%'.$request->codigo_interno.'%');
        }

        $query->orderByDesc('created_at');
        $query = $this->applyEntityQuery($query, 'ordem_servico');
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
        $this->authorizePermission('view-os-history');
        $os = OrdemServico::with(['cliente', 'contato', 'endereco', 'equipamento', 'produtos', 'servicos'])->find($id);
        if (! $os) {
            return $this->error('Ordem de serviço não encontrada.', 404);
        }

        return $this->success($os);
    }
}
