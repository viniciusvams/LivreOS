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

namespace App\Http\Controllers\ConfiguracoesSistema;

use App\Http\Controllers\Controller;
use App\Models\NotificacaoOsEnvio;
use Illuminate\Http\Request;

class FilaNotificacoesController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificacaoOsEnvio::with('ordemServico')->orderByDesc('created_at');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('ordem_id')) {
            $query->where('ordem_servico_id', $request->ordem_id);
        }

        $envios = $query->paginate(20)->withQueryString();

        return erp_view('configuracoes_sistema.fila_notificacoes', [
            'title' => 'Fila de E-mails e Notificações',
            'envios' => $envios,
        ]);
    }

    public function destroy(NotificacaoOsEnvio $envio)
    {
        $envio->delete();
        return redirect()->back()->with('success', 'Registro excluído.');
    }
}
