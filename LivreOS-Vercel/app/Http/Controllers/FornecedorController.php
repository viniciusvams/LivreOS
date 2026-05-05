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

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Cliente;
use App\Services\AuditCancelExcluirService;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Contato::where('is_fornecedor', true)->with('cliente');

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        $query = $this->applyEntityQuery($query->orderBy('nome'), 'fornecedor');
        $fornecedores = $query->paginate(15);

        return erp_view('fornecedores.index', [
            'title' => 'Fornecedores',
            'fornecedores' => $fornecedores,
        ]);
    }

    public function create()
    {
        return erp_view('fornecedores.create', [
            'title' => 'Novo Fornecedor',
            'clientes' => Cliente::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateWithFilters($request, [
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'nome' => 'required|string|max:100',
            'telefone' => 'nullable|string|max:20',
            'telefone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'email2' => 'nullable|email|max:255',
            'cargo' => 'nullable|string|max:100',
        ]);

        $data['is_fornecedor'] = true;

        // Contato exige cliente_id (NOT NULL). Se não foi informado, cria um novo cliente com o nome do fornecedor.
        if (empty($data['cliente_id'])) {
            $cliente = Cliente::create([
                'tipo_pessoa' => 'J',
                'nome' => $data['nome'],
                'razao_social' => $data['nome'],
            ]);
            $data['cliente_id'] = $cliente->id;
        }

        Contato::create($data);

        return redirect()->route('fornecedores.index')
            ->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit(Contato $fornecedor)
    {
        if (!$fornecedor->is_fornecedor) {
            abort(404);
        }

        return erp_view('fornecedores.edit', [
            'title' => 'Editar Fornecedor',
            'fornecedor' => $fornecedor,
            'clientes' => Cliente::orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Contato $fornecedor)
    {
        if (!$fornecedor->is_fornecedor) {
            abort(404);
        }

        $data = $this->validateWithFilters($request, [
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'nome' => 'required|string|max:100',
            'telefone' => 'nullable|string|max:20',
            'telefone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'email2' => 'nullable|email|max:255',
            'cargo' => 'nullable|string|max:100',
        ]);

        $data['is_fornecedor'] = true;
        $fornecedor->update($data);

        return redirect()->route('fornecedores.index')
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Request $request, Contato $fornecedor, AuditCancelExcluirService $auditService)
    {
        if (!$fornecedor->is_fornecedor) {
            abort(404);
        }

        if (!$auditService->canExcluir(auth()->user(), 'fornecedor')) {
            abort(403, 'Você não tem permissão para excluir fornecedores.');
        }

        $this->validateWithFilters($request, [
            'motivo' => 'required|string|min:10',
        ], [], ['motivo' => 'motivo da exclusão']);

        $descricao = $fornecedor->nome ?? 'Fornecedor #' . $fornecedor->id;
        $entityId = $fornecedor->id;

        $fornecedor->delete();

        $auditService->log('excluir', 'fornecedor', $entityId, $descricao, $request->motivo, $request);

        return redirect()->route('fornecedores.index')
            ->with('success', 'Fornecedor excluído com sucesso!');
    }
}
