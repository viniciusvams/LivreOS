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
use App\Models\AuditFinanceiroMovimentacao;
use App\Models\User;
use Illuminate\Http\Request;

class AuditFinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditFinanceiroMovimentacao::query()
            ->with(['user'])
            ->orderByDesc('created_at');

        if ($request->filled('entidade_tipo')) {
            $query->where('entidade_tipo', $request->entidade_tipo);
        }
        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('entidade_id')) {
            $query->where('entidade_id', (int) $request->entidade_id);
        }
        if ($request->filled('data_de')) {
            $query->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->whereDate('created_at', '<=', $request->data_ate);
        }

        $items = $query->paginate(30)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return erp_view('admin.audit-financeiro.index', [
            'title' => 'Auditoria Financeira (Contas a Receber / Pagar)',
            'items' => $items,
            'users' => $users,
        ]);
    }
}

