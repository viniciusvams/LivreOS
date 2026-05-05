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
<div class="mb-6">
    <h1 class="mb-1 text-2xl font-semibold text-gray-800 dark:text-white/90">Relatórios PDV</h1>
    <p class="text-gray-600 dark:text-gray-400">Selecione um relatório para visualizar.</p>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

    {{-- Vendas por Período --}}
    <a href="{{ url('/plugin/pdv/relatorios/vendas-periodo') }}"
       class="group flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm transition hover:border-blue-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-500">
        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <h2 class="mb-1 text-base font-semibold text-gray-800 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">Vendas por Período</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Resumo de vendas com totais, ticket médio, descontos e breakdown por forma de pagamento.
            </p>
        </div>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-blue-600 dark:text-blue-400">
            Abrir relatório
            <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    </a>

    {{-- Produtos/Serviços Mais Vendidos --}}
    <a href="{{ url('/plugin/pdv/relatorios/produtos-vendidos') }}"
       class="group flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm transition hover:border-green-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-500">
        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <div>
            <h2 class="mb-1 text-base font-semibold text-gray-800 group-hover:text-green-600 dark:text-white dark:group-hover:text-green-400">Produtos/Serviços Mais Vendidos</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Ranking de itens vendidos por quantidade e valor total no período.
            </p>
        </div>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400">
            Abrir relatório
            <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    </a>

    {{-- Resumo de Caixas --}}
    <a href="{{ url('/plugin/pdv/relatorios/resumo-caixas') }}"
       class="group flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm transition hover:border-purple-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-500">
        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="mb-1 text-base font-semibold text-gray-800 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">Resumo de Caixas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Histórico de sessões de caixa com totais de vendas, reforços, sangrias e diferenças.
            </p>
        </div>
        <span class="mt-auto inline-flex items-center gap-1 text-sm font-medium text-purple-600 dark:text-purple-400">
            Abrir relatório
            <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    </a>

</div>
@endsection
