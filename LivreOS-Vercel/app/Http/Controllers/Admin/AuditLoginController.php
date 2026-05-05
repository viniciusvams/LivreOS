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
use App\Models\AuditLoginAcesso;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLoginController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $items = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return erp_view('admin.audit-login.index', [
            'title' => 'Auditoria de Login e Segurança',
            'items' => $items,
            'users' => $users,
        ]);
    }

    public function clear(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $total = (clone $query)->count();
        $query->delete();

        return redirect()
            ->route('admin.audit-login.index')
            ->with('success', $total > 0
                ? "Foram removidos {$total} registro(s) da auditoria de login."
                : 'Nenhum registro encontrado para remoção.');
    }

    protected function buildFilteredQuery(Request $request)
    {
        $query = AuditLoginAcesso::query()->orderByDesc('created_at');

        if ($request->filled('evento')) {
            $query->where('evento', $request->evento);
        }
        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.trim((string) $request->email).'%');
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
