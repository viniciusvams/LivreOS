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
use App\Models\AuditCancelExcluir;
use App\Models\User;
use Illuminate\Http\Request;

class AuditCancelExcluirController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditCancelExcluir::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('data_de')) {
            $query->whereDate('created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->whereDate('created_at', '<=', $request->data_ate);
        }

        $items = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        return erp_view('admin.audit-cancelar-excluir.index', [
            'title' => 'Histórico de Cancelamentos e Exclusões',
            'items' => $items,
            'users' => $users,
        ]);
    }
}
