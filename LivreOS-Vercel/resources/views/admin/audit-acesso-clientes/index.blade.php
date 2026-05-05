{{--
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
 --}}

@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Auditoria de Acesso de Clientes</h1>
        <p class="text-gray-600 dark:text-gray-400">Registro de quem visualizou ou acessou edição no cadastro de clientes</p>
    </div>
    <form method="POST" action="{{ route('admin.audit-acesso-clientes.clear') }}" onsubmit="return confirm('Tem certeza que deseja limpar os registros de acesso de clientes? Esta ação não pode ser desfeita.');">
        @csrf
        <input type="hidden" name="evento" value="{{ request('evento') }}">
        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        <input type="hidden" name="cliente_id" value="{{ request('cliente_id') }}">
        <input type="hidden" name="ip" value="{{ request('ip') }}">
        <input type="hidden" name="data_de" value="{{ request('data_de') }}">
        <input type="hidden" name="data_ate" value="{{ request('data_ate') }}">
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
            Limpar registros
        </button>
    </form>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('admin.audit-acesso-clientes.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Evento</label>
            <select name="evento" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach(['acesso_index', 'acesso_show', 'acesso_edit'] as $evento)
                    <option value="{{ $evento }}" {{ request('evento') === $evento ? 'selected' : '' }}>
                        {{ \App\Models\AuditAcessoCliente::eventoLabel($evento) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Usuário</label>
            <select name="user_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>
            <select name="cliente_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IP</label>
            <input type="text" name="ip" value="{{ request('ip') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data de</label>
            <input type="date" name="data_de" value="{{ request('data_de') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data até</label>
            <input type="date" name="data_ate" value="{{ request('data_ate') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('admin.audit-acesso-clientes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
        </div>
    </form>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Data/Hora</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Evento</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">IP</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Mensagem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item->cliente?->nome ?? ('#' . $item->cliente_id) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\AuditAcessoCliente::eventoLabel($item->evento) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item->ip_address ?: '—' }}</td>
                    <td class="max-w-sm px-6 py-4 text-sm text-gray-600 dark:text-gray-400" title="{{ $item->mensagem }}">{{ \Illuminate\Support\Str::limit($item->mensagem, 90) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum registro encontrado</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        {{ $items->links() }}
    </div>
</div>
@endsection
