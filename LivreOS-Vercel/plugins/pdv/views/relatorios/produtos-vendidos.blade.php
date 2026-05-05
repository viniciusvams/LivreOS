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
    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ url('/plugin/pdv/relatorios') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Relatórios PDV</a>
        <span>/</span>
        <span class="text-gray-700 dark:text-gray-200">Produtos/Serviços Mais Vendidos</span>
    </div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Produtos/Serviços Mais Vendidos</h1>
</div>

{{-- Filtros --}}
<form method="get" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">De</label>
            <input type="date" name="data_de" value="{{ $dataInicio }}"
                   class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Até</label>
            <input type="date" name="data_ate" value="{{ $dataFim }}"
                   class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label>
            <select name="tipo" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos</option>
                <option value="produto"  {{ $tipo === 'produto'  ? 'selected' : '' }}>Produtos</option>
                <option value="servico"  {{ $tipo === 'servico'  ? 'selected' : '' }}>Serviços</option>
            </select>
        </div>
        <div class="flex gap-2">
            <a href="{{ url('/plugin/pdv/relatorios/produtos-vendidos') }}"
               class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">Limpar</a>
            <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filtrar</button>
        </div>
        <div class="ml-auto">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir
            </button>
        </div>
    </div>
</form>

{{-- Cards resumo --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Itens distintos</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $itens->count() }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unidades vendidas</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalItensVendidos, 0, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Receita total</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($totalValorItens, 2, ',', '.') }}</p>
    </div>
</div>

{{-- Tabela de itens --}}
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    @if($itens->isEmpty())
        <p class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhum item vendido encontrado para os filtros selecionados.</p>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="w-10 px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Produto / Serviço</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Qtd vendida</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Preço médio</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">% receita</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($itens as $idx => $item)
                <tr class="bg-white dark:bg-gray-800">
                    <td class="px-4 py-2.5 text-center text-sm font-medium text-gray-500 dark:text-gray-400">{{ $idx + 1 }}</td>
                    <td class="px-4 py-2.5">
                        @if($item->tipo === 'produto')
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Produto</span>
                        @else
                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">Serviço</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="font-medium text-gray-800 dark:text-white">{{ $item->descricao }}</span>
                        @if($item->tipo === 'produto' && $item->produto)
                            <span class="ml-1 text-xs text-gray-400">SKU: {{ $item->produto->codigo ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right text-sm text-gray-700 dark:text-gray-200">
                        {{ number_format($item->qtd_total, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-2.5 text-right text-sm text-gray-600 dark:text-gray-300">
                        R$ {{ number_format($item->preco_medio, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-medium text-gray-800 dark:text-white">
                        R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-2.5 text-right text-sm text-gray-600 dark:text-gray-300">
                        @if($totalValorItens > 0)
                            {{ number_format(($item->valor_total / $totalValorItens) * 100, 1) }}%
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Total</td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white">
                        {{ number_format($totalItensVendidos, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                        R$ {{ number_format($totalValorItens, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

<style>
@media print {
    nav, aside, header, footer, form, button { display: none !important; }
    body { background: white !important; }
    * { color: black !important; border-color: #ddd !important; }
}
</style>
@endsection
