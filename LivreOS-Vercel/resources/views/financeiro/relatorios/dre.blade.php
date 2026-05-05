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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">DRE - Demonstrativo do Resultado do Exercício</h1>
        <p class="text-gray-600 dark:text-gray-400">Demonstrativo simplificado</p>
    </div>
    <a href="{{ route('financeiro.dre.pdf', array_filter(['data_inicio' => $dataInicio, 'data_fim' => $dataFim, 'centro_custo_id' => $centroCustoId])) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<!-- Filtros -->
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.dre') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
            <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de custo (opcional)</label>
            <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($centrosCusto ?? [] as $cc)
                    <option value="{{ $cc->id }}" {{ (isset($centroCustoId) && $centroCustoId == $cc->id) ? 'selected' : '' }}>{{ $cc->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        </div>
    </form>
</div>

<!-- DRE -->
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="p-6">
        <div class="space-y-4">
            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                <span class="font-semibold text-gray-800 dark:text-white/90">Receitas Operacionais</span>
                <span class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($receitasOperacionais, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">(-) Despesas Operacionais</span>
                <span class="text-red-600 dark:text-red-400">R$ {{ number_format($despesasOperacionais, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-b-2 border-gray-300 pb-2 dark:border-gray-600">
                <span class="font-semibold text-gray-800 dark:text-white/90">Receita Líquida</span>
                <span class="font-semibold {{ $receitaLiquida >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    R$ {{ number_format($receitaLiquida, 2, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">(+) Receitas Não Operacionais</span>
                <span class="text-green-600 dark:text-green-400">R$ {{ number_format($receitasNaoOperacionais, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                <span class="text-gray-700 dark:text-gray-300">(-) Despesas Financeiras</span>
                <span class="text-red-600 dark:text-red-400">R$ {{ number_format($despesasFinanceiras, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-t-2 border-gray-300 pt-2 dark:border-gray-600">
                <span class="text-lg font-bold text-gray-800 dark:text-white/90">Resultado Antes do IR</span>
                <span class="text-lg font-bold {{ $resultadoAntesIR >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    R$ {{ number_format($resultadoAntesIR, 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
