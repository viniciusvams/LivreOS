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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plano de Contas</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie o plano de contas para DRE</p>
    </div>
    <div>
        <a href="{{ route('financeiro.plano-contas.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Conta
        </a>
    </div>
</div>

<div class="space-y-4">
    @foreach($contas as $conta)
        <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $conta->codigo ?? '-' }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $conta->nome }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($conta->tipo === 'receita') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @endif">
                                {{ ucfirst($conta->tipo) }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-500">({{ ucfirst($conta->categoria) }})</span>
                        </div>
                        @if($conta->descricao)
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $conta->descricao }}</p>
                        @endif
                        @if($conta->tipo === 'despesa' && (($conta->total_despesa_pago ?? 0) > 0 || ($conta->total_despesa_aberto ?? 0) > 0))
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Despesas: <span class="text-green-600 dark:text-green-400">R$ {{ number_format($conta->total_despesa_pago ?? 0, 2, ',', '.') }} pago</span>
                                @if(($conta->total_despesa_aberto ?? 0) > 0)
                                    · <span class="text-amber-600 dark:text-amber-400">R$ {{ number_format($conta->total_despesa_aberto ?? 0, 2, ',', '.') }} em aberto</span>
                                @endif
                            </p>
                        @endif
                        @if($conta->tipo === 'receita' && (($conta->total_receita_recebido ?? 0) > 0 || ($conta->total_receita_aberto ?? 0) > 0))
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Receitas: <span class="text-green-600 dark:text-green-400">R$ {{ number_format($conta->total_receita_recebido ?? 0, 2, ',', '.') }} recebido</span>
                                @if(($conta->total_receita_aberto ?? 0) > 0)
                                    · <span class="text-amber-600 dark:text-amber-400">R$ {{ number_format($conta->total_receita_aberto ?? 0, 2, ',', '.') }} a receber</span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('financeiro.plano-contas.edit', $conta) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                </div>
            </div>
            @if($conta->filhos->count() > 0)
                <div class="border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="space-y-2">
                        @foreach($conta->filhos as $filho)
                            <div class="flex items-center justify-between rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="text-xs text-gray-500">{{ $filho->codigo ?? '-' }}</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $filho->nome }}</span>
                                    @if($filho->tipo === 'despesa' && (($filho->total_despesa_pago ?? 0) > 0 || ($filho->total_despesa_aberto ?? 0) > 0))
                                        <span class="text-xs text-gray-500 truncate">
                                            — R$ {{ number_format($filho->total_despesa_pago ?? 0, 2, ',', '.') }} pago
                                            @if(($filho->total_despesa_aberto ?? 0) > 0) / R$ {{ number_format($filho->total_despesa_aberto ?? 0, 2, ',', '.') }} aberto @endif
                                        </span>
                                    @endif
                                    @if($filho->tipo === 'receita' && (($filho->total_receita_recebido ?? 0) > 0 || ($filho->total_receita_aberto ?? 0) > 0))
                                        <span class="text-xs text-gray-500 truncate">
                                            — R$ {{ number_format($filho->total_receita_recebido ?? 0, 2, ',', '.') }} recebido
                                            @if(($filho->total_receita_aberto ?? 0) > 0) / R$ {{ number_format($filho->total_receita_aberto ?? 0, 2, ',', '.') }} a receber @endif
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('financeiro.plano-contas.edit', $filho) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endsection
