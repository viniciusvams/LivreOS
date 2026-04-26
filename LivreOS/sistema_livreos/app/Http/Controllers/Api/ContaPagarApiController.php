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

use App\Models\ContaPagar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContaPagarApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('access-financeiro');
        $query = ContaPagar::with(['fornecedor', 'formaPagamento', 'planoConta']);

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_vencimento_de')) {
            $query->where('data_vencimento', '>=', $request->data_vencimento_de);
        }
        if ($request->filled('data_vencimento_ate')) {
            $query->where('data_vencimento', '<=', $request->data_vencimento_ate);
        }

        $query->orderBy('data_vencimento')->orderBy('id');
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
        $conta = ContaPagar::with(['fornecedor', 'formaPagamento', 'planoConta', 'centroCusto', 'contaBancaria'])->find($id);
        if (! $conta) {
            return $this->error('Conta a pagar não encontrada.', 404);
        }

        return $this->success($conta);
    }
}
