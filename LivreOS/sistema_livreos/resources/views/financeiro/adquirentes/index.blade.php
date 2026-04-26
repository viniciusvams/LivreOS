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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Adquirentes</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as adquirentes (maquininhas/gateways) do sistema</p>
    </div>
    <div>
        <a href="{{ route('financeiro.adquirentes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Adquirente
        </a>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'adquirente'); ?>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Código</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo Antecipação</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Contas</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxas</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($adquirentes as $adquirente)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $adquirente->nome }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $adquirente->codigo ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                @if($adquirente->tipo_antecipacao === 'fluxo')
                                    <span class="text-blue-800 dark:text-blue-300">Fluxo Normal</span>
                                @elseif($adquirente->tipo_antecipacao === 'antecipado')
                                    <span class="text-green-800 dark:text-green-300">Antecipado</span>
                                @else
                                    <span class="text-purple-800 dark:text-purple-300">Ambos</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            {{ $adquirente->contas->count() }} conta(s)
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                            <a href="{{ route('financeiro.taxas.index', $adquirente) }}" class="text-brand-500 hover:text-brand-600">
                                {{ $adquirente->taxas->where('ativo', true)->count() }} ativa(s)
                            </a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $adquirente->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $adquirente->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('financeiro.adquirentes.show', $adquirente) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" title="Ver detalhes">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('financeiro.adquirentes.edit', $adquirente) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('financeiro.adquirentes.destroy', $adquirente) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta adquirente?');">
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
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Nenhuma adquirente cadastrada
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($adquirentes->hasPages())
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $adquirentes->links() }}
        </div>
    @endif
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'adquirente'); ?>
@endif
@endsection
