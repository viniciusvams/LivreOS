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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Posição de Estoque e Curva ABC</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Valor financeiro do estoque e classificação por giro (A = 80% do valor, B até 95%, C restante).</p>
    </div>
    <a href="{{ route('financeiro.relatorios.estoque-posicao.pdf') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Valor total em estoque: R$ {{ number_format($valorTotalEstoque ?? 0, 2, ',', '.') }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ count($lista ?? []) }} produto(s) com controle de estoque.</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Código</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Qtd</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Custo unit.</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Curva ABC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($lista ?? [] as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $item['nome'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['codigo'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ number_format($item['quantidade'] ?? 0, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">R$ {{ number_format($item['custo_unit'] ?? 0, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($item['valor_total'] ?? 0, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        @if(($item['classe_abc'] ?? '') === 'A')
                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">A</span>
                        @elseif(($item['classe_abc'] ?? '') === 'B')
                        <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">B</span>
                        @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">C</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if(empty($lista) || count($lista) === 0)
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhum produto com controle de estoque.
</div>
@endif

<div class="mt-4">
    <a href="{{ route('financeiro.relatorios.index') }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
@endsection
