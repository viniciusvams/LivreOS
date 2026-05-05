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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Auditoria de Login e Segurança</h1>
        <p class="text-gray-600 dark:text-gray-400">Registro de tentativas de login, bloqueios anti-bot e limite de tentativas</p>
    </div>
    <form method="POST" action="{{ route('admin.audit-login.clear') }}" onsubmit="return confirm('Tem certeza que deseja limpar os registros da auditoria de login? Esta ação não pode ser desfeita.');">
        @csrf
        <input type="hidden" name="evento" value="{{ request('evento') }}">
        <input type="hidden" name="resultado" value="{{ request('resultado') }}">
        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        <input type="hidden" name="email" value="{{ request('email') }}">
        <input type="hidden" name="ip" value="{{ request('ip') }}">
        <input type="hidden" name="data_de" value="{{ request('data_de') }}">
        <input type="hidden" name="data_ate" value="{{ request('data_ate') }}">
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
            Limpar registros
        </button>
    </form>
</div>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('admin.audit-login.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Evento</label>
            <select name="evento" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach(['login_success', 'invalid_credentials', 'rate_limited', 'honeypot', 'robot_token'] as $evento)
                    <option value="{{ $evento }}" {{ request('evento') === $evento ? 'selected' : '' }}>
                        {{ \App\Models\AuditLoginAcesso::eventoLabel($evento) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Resultado</label>
            <select name="resultado" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach(['success', 'failed', 'blocked'] as $resultado)
                    <option value="{{ $resultado }}" {{ request('resultado') === $resultado ? 'selected' : '' }}>
                        {{ \App\Models\AuditLoginAcesso::resultadoLabel($resultado) }}
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
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">E-mail</label>
            <input type="text" name="email" value="{{ request('email') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
            <a href="{{ route('admin.audit-login.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
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
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Evento</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Resultado</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">IP</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Mensagem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item->email ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\AuditLoginAcesso::eventoLabel($item->evento) }}</td>
                    <td class="px-6 py-4">
                        @if($item->resultado === 'success')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/20 dark:text-green-400">Sucesso</span>
                        @elseif($item->resultado === 'blocked')
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/20 dark:text-red-400">Bloqueado</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Falha</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item->ip_address ?: '—' }}</td>
                    <td class="max-w-sm px-6 py-4 text-sm text-gray-600 dark:text-gray-400" title="{{ $item->mensagem }}">{{ Str::limit($item->mensagem, 90) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum registro encontrado</td>
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
