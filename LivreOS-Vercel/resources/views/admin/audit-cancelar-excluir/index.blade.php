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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Histórico de Cancelamentos e Exclusões</h1>
        <p class="text-gray-600 dark:text-gray-400">Registro de todas as ações de cancelar e excluir com motivo e permissão</p>
    </div>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('admin.audit-cancelar-excluir.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Ação</label>
            <select name="action" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                <option value="cancelar" {{ request('action') === 'cancelar' ? 'selected' : '' }}>Cancelamento</option>
                <option value="excluir" {{ request('action') === 'excluir' ? 'selected' : '' }}>Exclusão</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Módulo</label>
            <select name="entity_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach(['conta_receber' => 'Conta a receber', 'conta_pagar' => 'Conta a pagar', 'ordem_servico' => 'Ordem de serviço', 'cliente' => 'Cliente', 'fornecedor' => 'Fornecedor', 'pagamento_recorrente' => 'Pagamento recorrente', 'conta_pagar_recorrente' => 'Pagamento fixo (conta a pagar)', 'produto' => 'Produto', 'servico' => 'Serviço', 'usuario' => 'Usuário', 'role' => 'Role', 'permission' => 'Permissão'] as $val => $label)
                    <option value="{{ $val }}" {{ request('entity_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data de</label>
            <input type="date" name="data_de" value="{{ request('data_de') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Data até</label>
            <input type="date" name="data_ate" value="{{ request('data_ate') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('admin.audit-cancelar-excluir.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
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
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Ação</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Módulo</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">ID / Descrição</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 dark:text-white/90">{{ $item->user?->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($item->action === 'cancelar')
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-500/20 dark:text-orange-400">Cancelamento</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/20 dark:text-red-400">Exclusão</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\AuditCancelExcluir::entityTypeLabel($item->entity_type) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        #{{ $item->entity_id }}
                        @if($item->entity_descricao)
                            <br><span class="text-gray-500 dark:text-gray-500" title="{{ $item->entity_descricao }}">{{ Str::limit($item->entity_descricao, 40) }}</span>
                        @endif
                    </td>
                    <td class="max-w-xs px-6 py-4 text-sm text-gray-600 dark:text-gray-400" title="{{ $item->motivo }}">{{ Str::limit($item->motivo, 80) }}</td>
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
