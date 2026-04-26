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
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Status de Ordem de Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">Crie e personalize os status disponíveis para as ordens de serviço.</p>
    </div>
    <a href="{{ route('configuracoes-sistema.status-os.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <span class="flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Status
        </span>
    </a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    {{ session('error') }}
</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    @if($statusList->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ordem</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Cor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Comportamento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @foreach($statusList as $s)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $s->ordem }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            @if($s->cor)
                            <span class="inline-block h-4 w-4 rounded-full border border-gray-300" style="background-color: {{ $s->cor }}"></span>
                            @endif
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $s->nome }}</span>
                            @if($s->sistema)
                            <span class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400">sistema</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $s->cor ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                        @if($s->marca_inicio) Marca início @endif
                        @if($s->marca_conclusao) @if($s->marca_inicio) | @endif Marca conclusão @endif
                        @if(!$s->marca_inicio && !$s->marca_conclusao) - @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($s->ativo)
                            <span class="inline-flex rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">Ativo</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('configuracoes-sistema.status-os.edit', $s) }}" class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300">Editar</a>
                            @if(!$s->sistema)
                            <form action="{{ route('configuracoes-sistema.status-os.destroy', $s) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este status?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-600 hover:text-error-900 dark:text-error-400 dark:hover:text-error-300">Excluir</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
        {{ $statusList->links() }}
    </div>
    @else
    <div class="p-8 text-center">
        <p class="text-gray-500 dark:text-gray-400">Nenhum status cadastrado. Execute o seeder ou crie o primeiro status.</p>
        <a href="{{ route('configuracoes-sistema.status-os.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Criar primeiro status
        </a>
    </div>
    @endif
</div>

<div class="mt-4">
    <a href="{{ route('configuracoes-sistema.index') }}" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">← Voltar para Configurações</a>
</div>
@endsection
