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
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Gerencia usuários vinculados a clientes no contexto da Área do Cliente.
 * Modal: cadastrar novo, editar ou vincular usuário existente.
 */
class UsuarioClienteController
{
    public function modal(Cliente $cliente)
    {
        if (!Schema::hasColumn('users', 'cliente_id')) {
            return response()->json(['error' => 'Coluna cliente_id não encontrada.'], 400);
        }

        $usuarios = User::where('cliente_id', $cliente->id)->orderBy('name')->get();
        $usuariosDisponiveis = User::where(function ($q) use ($cliente) {
            $q->whereNull('cliente_id')->orWhere('cliente_id', '!=', $cliente->id);
        })
            ->where(function ($q) {
                $q->whereDoesntHave('roles')
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('slug', ['user', 'usuario']));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $html = view('area-cliente::partials.modal-usuario-cliente', [
            'cliente' => $cliente,
            'usuarios' => $usuarios,
            'usuariosDisponiveis' => $usuariosDisponiveis,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function store(Request $request, Cliente $cliente)
    {
        if (!Schema::hasColumn('users', 'cliente_id')) {
            return redirect()->back()->with('error', 'Coluna cliente_id não encontrada.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'ativo' => 'boolean',
            'cpf' => ['nullable', 'string', 'max:20'],
            'rg' => 'nullable|string|max:20',
            'rg_orgao_emissor' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|size:2',
        ];
        $rules = function_exists('apply_filters') ? apply_filters('admin.user.store.rules', $rules, $request) : $rules;
        $validated = $request->validate($rules);

        $cpf = $request->filled('cpf') ? preg_replace('/\D/', '', $request->cpf) : null;
        $cpf = ($cpf && strlen($cpf) === 11) ? $cpf : null;

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_admin' => false,
            'ativo' => $request->boolean('ativo', true),
            'cliente_id' => $cliente->id,
            'cpf' => $cpf,
            'rg' => $request->input('rg'),
            'rg_orgao_emissor' => $request->input('rg_orgao_emissor'),
            'telefone' => $request->input('telefone'),
            'cargo' => $request->input('cargo'),
            'departamento' => $request->input('departamento'),
            'cep' => $request->input('cep'),
            'logradouro' => $request->input('logradouro'),
            'numero' => $request->input('numero'),
            'complemento' => $request->input('complemento'),
            'bairro' => $request->input('bairro'),
            'cidade' => $request->input('cidade'),
            'uf' => $request->filled('uf') ? strtoupper(substr($request->uf, 0, 2)) : null,
        ];
        $request->merge(['cliente_id' => $cliente->id]);
        $extra = function_exists('apply_filters') ? apply_filters('admin.user.store.data', [], $request, $validated) : ['cliente_id' => $cliente->id];
        if (!isset($extra['cliente_id'])) {
            $extra['cliente_id'] = $cliente->id;
        }
        User::create(array_merge($data, $extra));

        return redirect()->route('plugin.area-cliente.configuracoes.clientes', request()->only(['busca', 'portal', 'cliente', 'page']))
            ->with('success', 'Usuário cadastrado e vinculado ao cliente com sucesso.');
    }

    public function update(Request $request, Cliente $cliente, User $user)
    {
        if ($user->cliente_id != $cliente->id) {
            abort(403, 'Usuário não pertence a este cliente.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'ativo' => 'boolean',
            'cpf' => ['nullable', 'string', 'max:20'],
            'rg' => 'nullable|string|max:20',
            'rg_orgao_emissor' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|size:2',
        ];
        $rules = function_exists('apply_filters') ? apply_filters('admin.user.update.rules', $rules, $request, $user) : $rules;
        $request->validate($rules);

        $cpf = $request->filled('cpf') ? preg_replace('/\D/', '', $request->cpf) : null;
        $cpf = ($cpf && strlen($cpf) === 11) ? $cpf : null;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->ativo = $request->boolean('ativo', true);
        $user->cpf = $cpf;
        $user->rg = $request->input('rg');
        $user->rg_orgao_emissor = $request->input('rg_orgao_emissor');
        $user->telefone = $request->input('telefone');
        $user->cargo = $request->input('cargo');
        $user->departamento = $request->input('departamento');
        $user->cep = $request->input('cep');
        $user->logradouro = $request->input('logradouro');
        $user->numero = $request->input('numero');
        $user->complemento = $request->input('complemento');
        $user->bairro = $request->input('bairro');
        $user->cidade = $request->input('cidade');
        $user->uf = $request->filled('uf') ? strtoupper(substr($request->uf, 0, 2)) : null;
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        return redirect()->route('plugin.area-cliente.configuracoes.clientes', request()->only(['busca', 'portal', 'cliente', 'page']))
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function vincular(Request $request, Cliente $cliente)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $user = User::findOrFail($request->user_id);
        $user->cliente_id = $cliente->id;
        $user->save();

        return redirect()->route('plugin.area-cliente.configuracoes.clientes', request()->only(['busca', 'portal', 'cliente', 'page']))
            ->with('success', 'Usuário vinculado ao cliente com sucesso.');
    }

    public function desvincular(Request $request, Cliente $cliente, User $user)
    {
        if ($user->cliente_id != $cliente->id) {
            abort(403, 'Usuário não pertence a este cliente.');
        }

        $user->cliente_id = null;
        $user->save();

        return redirect()->route('plugin.area-cliente.configuracoes.clientes', request()->only(['busca', 'portal', 'cliente', 'page']))
            ->with('success', 'Usuário desvinculado do cliente.');
    }
}
