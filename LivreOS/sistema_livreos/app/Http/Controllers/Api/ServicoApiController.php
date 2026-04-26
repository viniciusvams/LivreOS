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

use App\Models\Servico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view-services');
        $query = Servico::with('categoria');

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }
        if ($request->filled('codigo_sku')) {
            $query->where('codigo_sku', 'like', '%'.$request->codigo_sku.'%');
        }
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $ordenarPor = in_array($request->input('order_by'), ['nome', 'codigo_sku', 'preco', 'updated_at'], true)
            ? $request->input('order_by') : 'nome';
        $direcao = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($ordenarPor, $direcao);

        $query = $this->applyEntityQuery($query, 'servico');
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

    public function show(Servico $servico): JsonResponse
    {
        $this->authorizePermission('view-services');
        $servico->load('categoria');

        return $this->success($servico);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('create-services');
        $rules = [
            'nome' => 'required|string|max:255',
            'codigo_sku' => 'nullable|string|max:100',
            'preco' => 'required|numeric|min:0',
            'unidade' => 'nullable|string|max:20',
            'categoria_id' => 'nullable|integer|exists:categorias_servicos,id',
            'descricao' => 'nullable|string',
        ];
        $data = $this->validateRequest($request, $rules);
        $servico = Servico::create($data);

        return $this->success(Servico::with('categoria')->find($servico->id), 'Serviço criado.', 201);
    }

    public function update(Request $request, Servico $servico): JsonResponse
    {
        $this->authorizePermission('edit-services');
        $rules = [
            'nome' => 'sometimes|string|max:255',
            'codigo_sku' => 'nullable|string|max:100',
            'preco' => 'sometimes|numeric|min:0',
            'unidade' => 'nullable|string|max:20',
            'categoria_id' => 'nullable|integer|exists:categorias_servicos,id',
            'descricao' => 'nullable|string',
        ];
        $data = $this->validateRequest($request, $rules);
        $servico->update($data);

        return $this->success(Servico::with('categoria')->find($servico->id), 'Serviço atualizado.');
    }

    public function destroy(Servico $servico): JsonResponse
    {
        $this->authorizePermission('delete-services');
        $servico->delete();

        return $this->success(null, 'Serviço excluído.');
    }
}
