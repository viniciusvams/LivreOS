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
<div class="print-resumo-caixas">
<h1 class="print-title mb-4 hidden text-xl font-bold">Resumo de Caixas</h1>
<div class="mb-6 no-print">
    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ url('/plugin/pdv/relatorios') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Relatórios PDV</a>
        <span>/</span>
        <span class="text-gray-700 dark:text-gray-200">Resumo de Caixas</span>
    </div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Resumo de Caixas</h1>
</div>

{{-- Filtros --}}
<form method="get" class="no-print mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
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
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status do caixa</label>
            <select name="status_caixa" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos</option>
                <option value="fechado" {{ $statusCaixa === 'fechado' ? 'selected' : '' }}>Fechado</option>
                <option value="aberto"  {{ $statusCaixa === 'aberto'  ? 'selected' : '' }}>Aberto</option>
            </select>
        </div>
        @if($operadoresParaFiltro->isNotEmpty())
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Operador</label>
            <select name="user_id" class="min-w-[160px] rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos</option>
                @foreach($operadoresParaFiltro as $op)
                    <option value="{{ $op->id }}" {{ (string)$userId === (string)$op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex gap-2">
            <a href="{{ url('/plugin/pdv/relatorios/resumo-caixas') }}"
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
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sessões</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $resumoTotal['caixas'] }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendas</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $resumoTotal['vendas'] }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Vendas</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($resumoTotal['valor'], 2, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Reforços</p>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">R$ {{ number_format($resumoTotal['reforcos'], 2, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sangrias</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">R$ {{ number_format($resumoTotal['sangrias'], 2, ',', '.') }}</p>
    </div>
</div>

{{-- Tabela de caixas --}}
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    @if($caixas->isEmpty())
        <p class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhuma sessão de caixa encontrada para os filtros selecionados.</p>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Caixa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Operador</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Abertura</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Fechamento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Fundo caixa</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Vendas</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total vendas</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Reforços</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Sangrias</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Diferença</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($caixas as $caixa)
                <tr class="bg-white dark:bg-gray-800">
                    <td class="whitespace-nowrap px-4 py-2.5 font-medium text-gray-800 dark:text-white">
                        #{{ $caixa->numero_caixa }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $caixa->user->name ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $caixa->opened_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $caixa->closed_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm text-gray-600 dark:text-gray-300">
                        R$ {{ number_format($caixa->valor_abertura, 2, ',', '.') }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm text-gray-700 dark:text-gray-200">
                        {{ $caixa->total_vendas_count }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right font-medium text-gray-800 dark:text-white">
                        R$ {{ number_format($caixa->total_vendas_valor, 2, ',', '.') }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm text-yellow-600 dark:text-yellow-400">
                        @if($caixa->total_reforcos > 0)
                            R$ {{ number_format($caixa->total_reforcos, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm text-red-600 dark:text-red-400">
                        @if($caixa->total_sangrias > 0)
                            R$ {{ number_format($caixa->total_sangrias, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-medium">
                        @if($caixa->diferenca !== null)
                            @if(abs($caixa->diferenca) < 0.01)
                                <span class="text-gray-400 dark:text-gray-500">Zero</span>
                            @elseif($caixa->diferenca > 0)
                                <span class="text-green-600 dark:text-green-400">+R$ {{ number_format($caixa->diferenca, 2, ',', '.') }}</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">-R$ {{ number_format(abs($caixa->diferenca), 2, ',', '.') }}</span>
                            @endif
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        @if($caixa->status === 'fechado')
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">Fechado</span>
                        @else
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">Aberto</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Total ({{ $caixas->count() }} sessões)
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white">{{ $resumoTotal['vendas'] }}</td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                        R$ {{ number_format($resumoTotal['valor'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                        R$ {{ number_format($resumoTotal['reforcos'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-red-600 dark:text-red-400">
                        R$ {{ number_format($resumoTotal['sangrias'], 2, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
</div>

<style>
@media print {
    @page { margin: 10mm 12mm; }
    nav, aside, header, footer, form, button, .no-print { display: none !important; }
    body, html { background: white !important; padding: 0 !important; margin: 0 !important; }
    body * { color: black !important; border-color: #ddd !important; }
    .flex-1 > div { padding: 0 !important; max-width: none !important; }
    .print-resumo-caixas { padding: 0 !important; margin: 0 !important; max-width: none !important; }
    .print-resumo-caixas h1.print-title { display: block !important; margin-bottom: 12px !important; }
    .print-resumo-caixas table { font-size: 10px; width: 100%; }
    .print-resumo-caixas th, .print-resumo-caixas td { padding: 4px 6px !important; }
    .print-resumo-caixas .overflow-x-auto { overflow: visible !important; }
    .print-resumo-caixas .grid { break-inside: avoid; }
    .print-resumo-caixas .grid > div { break-inside: avoid; }
    tr { page-break-inside: avoid; }
}
</style>
@endsection
