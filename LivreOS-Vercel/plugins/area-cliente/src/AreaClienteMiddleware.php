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

namespace AreaCliente;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o usuário autenticado é um cliente do portal e possui acesso à OS.
 */
class AreaClienteMiddleware
{
    public function handle(Request $request, Closure $next, string $param = 'ordemServico'): Response
    {
        if (!auth()->check()) {
            return redirect()->route('signin');
        }

        $user = auth()->user();
        $clienteId = $user->cliente_id ?? null;

        if (!$clienteId) {
            abort(403, 'Acesso restrito à área do cliente.');
        }

        $cliente = \App\Models\Cliente::find($clienteId);
        if (!$cliente || !($cliente->portal_suporte ?? false)) {
            abort(403, 'O portal do cliente não está habilitado para sua conta.');
        }

        $ordem = $request->route($param);
        if ($ordem) {
            $ids = [$clienteId];
            if ($cliente->grupo_economico_id) {
                $ids = array_merge($ids, \App\Models\Cliente::where('grupo_economico_id', $cliente->grupo_economico_id)->pluck('id')->toArray());
            }
            $ids = array_merge($ids, \App\Models\Cliente::where('matriz_id', $clienteId)->pluck('id')->toArray());
            $ids = array_unique($ids);
            if (!in_array($ordem->cliente_id, $ids)) {
                abort(404, 'Ordem de serviço não encontrada.');
            }
        }

        return $next($request);
    }
}
