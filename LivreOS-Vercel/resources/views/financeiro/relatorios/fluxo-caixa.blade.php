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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Fluxo de Caixa</h1>
        <p class="text-gray-600 dark:text-gray-400">Previsto vs. Realizado e saldo projetado consolidado</p>
    </div>
    <a href="{{ route('financeiro.fluxo-caixa.pdf', ['data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
    <strong>Saldo consolidado ao início do período:</strong> R$ {{ number_format($saldoInicialConsolidado ?? 0, 2, ',', '.') }}
</div>

<!-- Filtros -->
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.fluxo-caixa') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
            <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex items-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Receita Prevista</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Receita Realizada</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Despesa Prevista</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Despesa Realizada</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saldo do Dia</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saldo Acumulado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saldo Projetado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($dias as $dia)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ ($dia['saldo_projetado_negativo'] ?? false) ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $dia['data_formatada'] }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($dia['receita_prevista'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-green-600 dark:text-green-400">R$ {{ number_format($dia['receita_realizada'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($dia['despesa_prevista'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-red-600 dark:text-red-400">R$ {{ number_format($dia['despesa_realizada'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ $dia['saldo_realizado'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($dia['saldo_realizado'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold {{ $dia['saldo_acumulado'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($dia['saldo_acumulado'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold {{ ($dia['saldo_projetado'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" title="{{ ($dia['saldo_projetado_negativo'] ?? false) ? 'Saldo projetado negativo' : '' }}">
                            R$ {{ number_format($dia['saldo_projetado'] ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
