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

use App\Models\NotificacaoUsuario;
use App\Services\NotificacaoFinanceiroService;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    public function __construct(private NotificacaoFinanceiroService $service) {}

    /** Página de histórico de notificações */
    public function index()
    {
        $user = auth()->user();
        try {
            $this->service->garantirNotificacoesVenceHoje($user);
        } catch (\Throwable $e) {
            report($e);
        }

        $notificacoes = NotificacaoUsuario::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return erp_view('notificacoes.index', [
            'title' => 'Notificações',
            'notificacoes' => $notificacoes,
        ]);
    }

    /** Marcar uma notificação como lida (AJAX ou redirect) */
    public function marcarLida(Request $request, int $id)
    {
        NotificacaoUsuario::where('user_id', auth()->id())
            ->where('id', $id)
            ->update(['lida' => true]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /** Marcar todas as notificações do usuário como lidas */
    public function marcarTodasLidas(Request $request)
    {
        NotificacaoUsuario::where('user_id', auth()->id())
            ->where('lida', false)
            ->update(['lida' => true]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }

    /** Excluir uma notificação (soft delete — remove só para este usuário) */
    public function destroy(Request $request, int $id)
    {
        NotificacaoUsuario::where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Notificação removida.');
    }

    /** Excluir todas as notificações do usuário (exclusão permanente para não reaparecerem) */
    public function destroyAll(Request $request)
    {
        NotificacaoUsuario::where('user_id', auth()->id())->forceDelete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Todas as notificações foram removidas.');
    }
}
