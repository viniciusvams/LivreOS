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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Auditoria Financeira</h1>
        <p class="text-gray-600 dark:text-gray-400">Registro de criações/edições/exclusões em Contas a Receber e Contas a Pagar (antes e depois)</p>
    </div>
</div>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('admin.audit-financeiro.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Módulo</label>
            <select name="entidade_tipo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach(['conta_receber' => 'Conta a receber', 'conta_pagar' => 'Conta a pagar'] as $val => $label)
                    <option value="{{ $val }}" {{ request('entidade_tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Ação</label>
            <select name="acao" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                @foreach(['created' => 'Criação', 'updated' => 'Edição', 'deleted' => 'Exclusão', 'restored' => 'Restauração'] as $val => $label)
                    <option value="{{ $val }}" {{ request('acao') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">ID</label>
            <input type="number" name="entidade_id" value="{{ request('entidade_id') }}" class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
            <a href="{{ route('admin.audit-financeiro.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
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
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Módulo</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Ação</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">ID</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Alterações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\AuditFinanceiroMovimentacao::entidadeTipoLabel($item->entidade_tipo) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\AuditFinanceiroMovimentacao::acaoLabel($item->acao) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">#{{ $item->entidade_id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        @if(!empty($item->alteracoes) && is_array($item->alteracoes))
                            <details class="cursor-pointer">
                                <summary class="text-sm font-medium text-brand-600 dark:text-brand-300">Ver antes/depois ({{ count($item->alteracoes) }})</summary>
                                <div class="mt-2 space-y-2">
                                    @foreach($item->alteracoes as $campo => $val)
                                        <div class="rounded border border-gray-200 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                            <div class="font-medium text-gray-700 dark:text-gray-300">{{ $campo }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                                <span class="text-red-600 dark:text-red-400"><span class="font-medium">Antes:</span> {{ is_array($val['antes'] ?? null) ? json_encode($val['antes'], JSON_UNESCAPED_UNICODE) : ($val['antes'] ?? '(vazio)') }}</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="text-green-600 dark:text-green-400"><span class="font-medium">Depois:</span> {{ is_array($val['depois'] ?? null) ? json_encode($val['depois'], JSON_UNESCAPED_UNICODE) : ($val['depois'] ?? '(vazio)') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @else
                            —
                        @endif
                    </td>
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

