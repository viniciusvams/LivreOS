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

use App\Models\NotificacaoUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notificações do usuário logado. Qualquer usuário operacional autenticado.
 */
class NotificacaoApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificacaoUsuario::where('user_id', auth()->id())
            ->orderByDesc('created_at');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
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

    public function marcarLida(int $id): JsonResponse
    {
        $updated = NotificacaoUsuario::where('user_id', auth()->id())
            ->where('id', $id)
            ->update(['lida' => true]);

        if (! $updated) {
            return $this->error('Notificação não encontrada.', 404);
        }

        return $this->success(null, 'Notificação marcada como lida.');
    }

    public function marcarTodasLidas(): JsonResponse
    {
        NotificacaoUsuario::where('user_id', auth()->id())
            ->where('lida', false)
            ->update(['lida' => true]);

        return $this->success(null, 'Todas as notificações foram marcadas como lidas.');
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = NotificacaoUsuario::where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();

        if (! $deleted) {
            return $this->error('Notificação não encontrada.', 404);
        }

        return $this->success(null, 'Notificação excluída.');
    }
}
