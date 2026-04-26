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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditAcessoCliente;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;

class AuditAcessoClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $items = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $clientes = Cliente::orderBy('nome')->get(['id', 'nome']);

        return erp_view('admin.audit-acesso-clientes.index', [
            'title' => 'Auditoria de Acesso de Clientes',
            'items' => $items,
            'users' => $users,
            'clientes' => $clientes,
        ]);
    }

    public function clear(Request $request)
    {
        $query = $this->buildFilteredQuery($request, false);
        $total = (clone $query)->count();
        $query->delete();

        return redirect()
            ->route('admin.audit-acesso-clientes.index')
            ->with('success', $total > 0
                ? "Foram removidos {$total} registro(s) da auditoria de acesso de clientes."
                : 'Nenhum registro encontrado para remoção.');
    }

    protected function buildFilteredQuery(Request $request, bool $withRelations = true)
    {
        $query = AuditAcessoCliente::query()->orderByDesc('created_at');
        if ($withRelations) {
            $query->with(['user', 'cliente']);
        }

        if ($request->filled('evento')) {
            $query->where('evento', $request->evento);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.trim((string) $request->ip).'%');
        }
        if ($request->filled('data_de')) {
            $query->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->whereDate('created_at', '<=', $request->data_ate);
        }

        return $query;
    }
}
