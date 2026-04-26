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

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Configuração de clientes habilitados para a Área do Cliente.
 * Lista clientes e permite habilitar/desabilitar o portal de forma prática.
 */
class ConfigClientesController
{
    public function index(Request $request)
    {
        if (!Schema::hasColumn('clientes', 'portal_suporte')) {
            return redirect()->route('configuracoes-sistema.plugins.index')
                ->with('error', 'A coluna portal_suporte não existe. Ative o plugin Área do Cliente primeiro.');
        }

        $query = Cliente::query()->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('razao_social', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%")
                    ->orWhere('cnpj', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('portal')) {
            if ($request->portal === 'habilitado') {
                $query->where('portal_suporte', true);
            } elseif ($request->portal === 'desabilitado') {
                $query->where(function ($q) {
                    $q->whereNull('portal_suporte')->orWhere('portal_suporte', false);
                });
            }
        }

        if ($request->filled('cliente')) {
            $cid = (int) $request->cliente;
            if ($cid > 0) {
                $query->where('id', $cid);
            }
        }

        $clientes = $query->paginate(25)->withQueryString();

        $usersPorCliente = \App\Models\User::whereIn('cliente_id', $clientes->pluck('id'))
            ->selectRaw('cliente_id, count(*) as total')
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id');

        return view('area-cliente::config-clientes', [
            'clientes' => $clientes,
            'usersPorCliente' => $usersPorCliente,
            'title' => 'Configuração — Área do Cliente',
        ]);
    }

    public function togglePortalSuporte(Request $request, Cliente $cliente)
    {
        if (!Schema::hasColumn('clientes', 'portal_suporte')) {
            return redirect()->back()->with('error', 'Coluna portal_suporte não encontrada.');
        }

        $request->validate([
            'habilitar' => 'required|in:0,1',
        ]);

        $habilitar = $request->input('habilitar') === '1';
        // Atualização direta: portal_suporte não está no $fillable do core (sem filtro cliente.fillable).
        Cliente::whereKey($cliente->getKey())->update(['portal_suporte' => $habilitar]);

        $msg = $habilitar
            ? 'Portal ativado para o cliente.'
            : 'Portal desativado para o cliente.';

        return redirect()->back()->with('success', $msg);
    }
}
