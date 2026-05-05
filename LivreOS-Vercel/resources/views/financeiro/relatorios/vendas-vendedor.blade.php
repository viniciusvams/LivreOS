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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Vendas por Vendedor</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Receita recebida no período (data de recebimento): <strong>OS</strong> com rateio por técnico nos itens/apontamentos e <strong>pedidos de venda</strong> atribuídos ao vendedor do pedido.</p>
    </div>
    <form method="get" action="{{ route('financeiro.relatorios.vendas-vendedor') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">De</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Até</label>
            <input type="date" name="data_fim" value="{{ $dataFim ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filtrar</button>
    </form>
</div>

<div class="mb-6 flex gap-4">
    <a href="{{ route('financeiro.relatorios.vendas-vendedor.pdf', request()->query()) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Total recebido no período: R$ {{ number_format($totalGeral ?? 0, 2, ',', '.') }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Período: {{ \Carbon\Carbon::parse($dataInicio ?? now())->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim ?? now())->format('d/m/Y') }}</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vendedor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Títulos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($porVendedor ?? [] as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['vendedor_nome'] }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($row['valor'], 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $row['quantidade'] }}</td>
                </tr>
                @if(!empty($row['ordens'] ?? []))
                <tr class="bg-gray-50/60 dark:bg-gray-900/40">
                    <td colspan="3" class="px-4 pb-4 pt-2">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Detalhamento por OS e pedidos de venda (itens e total atribuído)</div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/40">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/80">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">OS</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Cliente</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Serviços</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Produtos</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total atribuído</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Serviços</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Produtos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($row['ordens'] as $os)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                            @if(($os['origem'] ?? '') === 'pedido_venda')
                                                <span class="mr-1 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">PV</span>
                                            @endif
                                            {{ $os['os_codigo'] ?? (($os['os_id'] ?? null) ? 'OS #'.$os['os_id'] : '—') }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $os['cliente'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">R$ {{ number_format($os['valor_servicos'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">R$ {{ number_format($os['valor_produtos'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-100">R$ {{ number_format($os['valor'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            @if(!empty($os['servicos'] ?? []))
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    @foreach($os['servicos'] as $s)
                                                    <li>
                                                        <span class="font-medium">{{ $s['nome'] }}</span>
                                                        <span class="text-gray-500 dark:text-gray-400">– Qtd {{ number_format($s['quantidade'] ?? 0, 2, ',', '.') }}, R$ {{ number_format($s['total'] ?? 0, 2, ',', '.') }}</span>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-gray-400">Sem serviços detalhados</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            @if(!empty($os['produtos'] ?? []))
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    @foreach($os['produtos'] as $p)
                                                    <li>
                                                        <span class="font-medium">{{ $p['nome'] }}</span>
                                                        <span class="text-gray-500 dark:text-gray-400">– Qtd {{ number_format($p['quantidade'] ?? 0, 2, ',', '.') }}, R$ {{ number_format($p['total'] ?? 0, 2, ',', '.') }}</span>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-gray-400">Sem produtos detalhados</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if(empty($porVendedor) || count($porVendedor) === 0)
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhuma receita no período.
</div>
@endif

<div class="mt-4">
    <a href="{{ route('financeiro.relatorios.index') }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
@endsection
