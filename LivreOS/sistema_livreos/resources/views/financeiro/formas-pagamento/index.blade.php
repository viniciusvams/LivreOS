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
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Formas de Pagamento</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as formas de pagamento disponíveis</p>
    </div>
    <div>
        <a href="{{ route('financeiro.formas-pagamento.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Forma
        </a>
    </div>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa %</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa Fixa</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Dias Recebimento</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcelas</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($formas as $forma)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $forma->nome }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $forma->tipo)) }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($forma->taxa_percentual, 2, ',', '.') }}%</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($forma->taxa_fixa, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">{{ $forma->dias_recebimento }} dias</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            @if($forma->permite_parcela)
                                Até {{ $forma->max_parcelas ?? '-' }}x
                            @else
                                Não
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $forma->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $forma->ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('financeiro.formas-pagamento.edit', $forma) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma forma de pagamento encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $formas->links() }}
    </div>
</div>
@endsection
