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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Taxas - {{ $adquirente->nome }}</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as taxas da adquirente</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('financeiro.taxas.create', $adquirente) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Taxa
        </a>
        <a href="{{ route('financeiro.adquirentes.edit', $adquirente) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Editar adquirente</a>
        <a href="{{ route('financeiro.adquirentes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Lista de adquirentes</a>
    </div>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bandeira</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Modalidade</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcelas</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa %</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa/Parcela</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa Fixa</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Antecipação</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($taxas as $taxa)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ ucfirst($taxa->bandeira) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $taxa->modalidade)) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            @if($taxa->parcelas_min || $taxa->parcelas_max)
                                {{ $taxa->parcelas_min ?? '1' }}x - {{ $taxa->parcelas_max ?? '∞' }}x
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($taxa->taxa_percentual, 2, ',', '.') }}%</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($taxa->taxa_por_parcela, 2, ',', '.') }}%</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($taxa->taxa_fixa, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            @if($taxa->usa_antecipacao)
                                <span class="text-green-600 dark:text-green-400">Sim</span>
                                @if($taxa->taxa_antecipacao_percentual)
                                    <span class="text-xs text-gray-500">(+{{ number_format($taxa->taxa_antecipacao_percentual, 2, ',', '.') }}%)</span>
                                @endif
                            @else
                                <span class="text-gray-400">Não</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $taxa->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $taxa->ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('financeiro.taxas.edit', [$adquirente, $taxa]) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('financeiro.taxas.destroy', [$adquirente, $taxa]) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta taxa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Nenhuma taxa cadastrada
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($taxas->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $taxas->links() }}
        </div>
    @endif
</div>
@endsection
