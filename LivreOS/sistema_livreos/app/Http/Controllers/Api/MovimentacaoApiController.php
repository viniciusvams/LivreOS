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

use App\Models\MovimentacaoFinanceira;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Movimentações financeiras. Exige access-financeiro.
 */
class MovimentacaoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('access-financeiro');
        $query = MovimentacaoFinanceira::with(['contaBancaria', 'planoConta', 'centroCusto']);

        if ($request->filled('conta_bancaria_id')) {
            $query->where('conta_bancaria_id', $request->conta_bancaria_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('data_de')) {
            $query->where('data_movimentacao', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->where('data_movimentacao', '<=', $request->data_ate);
        }

        $query->orderByDesc('data_movimentacao')->orderByDesc('id');
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
        $this->authorizePermission('access-financeiro');
        $mov = MovimentacaoFinanceira::with(['contaBancaria', 'planoConta', 'centroCusto', 'contaReceber', 'contaPagar'])->find($id);
        if (! $mov) {
            return $this->error('Movimentação não encontrada.', 404);
        }

        return $this->success($mov);
    }
}
