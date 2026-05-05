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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Receita Recorrente Mensal (MRR)</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Quanto dinheiro "garantido" entra no mês com base nos contratos ativos (assinaturas/recorrentes).</p>
    </div>
    <a href="{{ route('financeiro.relatorios.mrr.pdf') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">MRR Total</p>
        <p class="text-2xl font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($mrrTotal ?? 0, 2, ',', '.') }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">Contratos ativos</p>
        <p class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $totalContratos ?? 0 }}</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Por cliente</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor mensal</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Contratos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($porCliente ?? [] as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['nome'] }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($row['valor_mensal'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $row['quantidade'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if(empty($porCliente) || count($porCliente) === 0)
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhum contrato recorrente ativo.
</div>
@endif

<div class="mt-4">
    <a href="{{ route('financeiro.relatorios.index') }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
@endsection
