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

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-clients');
        $query = Cliente::with(['enderecos', 'contatos']);

        if ($request->filled('tipo_pessoa')) {
            $query->where('tipo_pessoa', $request->tipo_pessoa);
        }
        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }
        if ($request->filled('documento')) {
            $query->where(function ($q) use ($request) {
                $q->where('cpf', 'like', '%'.$request->documento.'%')
                    ->orWhere('cnpj', 'like', '%'.$request->documento.'%')
                    ->orWhere('documento_estrangeiro', 'like', '%'.$request->documento.'%');
            });
        }

        $query = $this->applyEntityQuery($query->orderBy('nome'), 'cliente');
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

    public function show(Cliente $cliente): JsonResponse
    {
        $this->authorizePermission('view-clients');
        $cliente->load(['enderecos', 'contatos', 'vendedor', 'grupoEconomico']);

        return $this->success($cliente);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('create-clients');
        $rules = [
            'tipo_pessoa' => 'required|in:F,J,E',
            'nome' => 'required|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'observacoes' => 'nullable|string',
        ];
        $data = $this->validateRequest($request, $rules);
        $cliente = Cliente::create($data);
        if ($request->has('enderecos') && is_array($request->enderecos)) {
            foreach ($request->enderecos as $end) {
                $cliente->enderecos()->create($end);
            }
        }
        if ($request->has('contatos') && is_array($request->contatos)) {
            foreach ($request->contatos as $cont) {
                $cliente->contatos()->create($cont);
            }
        }

        return $this->success(Cliente::with(['enderecos', 'contatos'])->find($cliente->id), 'Cliente criado.', 201);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorizePermission('edit-clients');
        $rules = [
            'tipo_pessoa' => 'sometimes|in:F,J,E',
            'nome' => 'sometimes|string|max:255',
            'razao_social' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'cnpj' => 'nullable|string|max:18',
            'observacoes' => 'nullable|string',
        ];
        $data = $this->validateRequest($request, $rules);
        $cliente->update($data);

        return $this->success(Cliente::with(['enderecos', 'contatos'])->find($cliente->id), 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->authorizePermission('delete-clients');
        $cliente->delete();

        return $this->success(null, 'Cliente excluído.');
    }
}
