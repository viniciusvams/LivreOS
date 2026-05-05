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
<div class="print-vendas-periodo">
<h1 class="print-title mb-4 hidden text-xl font-bold">Vendas por Período</h1>
{{-- Breadcrumb / Título --}}
<div class="mb-6 no-print">
    <div class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ url('/plugin/pdv/relatorios') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Relatórios PDV</a>
        <span>/</span>
        <span class="text-gray-700 dark:text-gray-200">Vendas por Período</span>
    </div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Vendas por Período</h1>
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
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
            <select name="status" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos</option>
                <option value="finalizada" {{ $status === 'finalizada' ? 'selected' : '' }}>Concluída</option>
                <option value="cancelada"  {{ $status === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                <option value="aberta"     {{ $status === 'aberta'     ? 'selected' : '' }}>Aberta</option>
            </select>
        </div>
        @if($caixasParaFiltro->isNotEmpty())
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Caixa</label>
            <select name="caixa_id" class="min-w-[160px] rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Todos os caixas</option>
                @foreach($caixasParaFiltro as $c)
                    <option value="{{ $c->id }}" {{ (string)$caixaId === (string)$c->id ? 'selected' : '' }}>
                        Caixa #{{ $c->numero_caixa }} — {{ $c->user->name ?? '?' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex gap-2">
            <a href="{{ url('/plugin/pdv/relatorios/vendas-periodo') }}"
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

{{-- Cards de resumo --}}
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Vendas</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalVendas }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($totalValor, 2, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ticket Médio</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">R$ {{ number_format($ticketMedio, 2, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Descontos</p>
        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">R$ {{ number_format($totalDescontos, 2, ',', '.') }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Canceladas</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $totalCanceladas }}</p>
    </div>
</div>

{{-- Breakdown por forma de pagamento --}}
@if($pagamentos->isNotEmpty())
<div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Recebimentos por Forma de Pagamento</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Forma de Pagamento</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Transações</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">% do Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($pagamentos as $pag)
                <tr class="bg-white dark:bg-gray-800">
                    <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-white">{{ $pag->nome_forma }}</td>
                    <td class="px-5 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $pag->qtd }}</td>
                    <td class="px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white">R$ {{ number_format($pag->total, 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right text-sm text-gray-600 dark:text-gray-300">
                        @if($totalValor > 0)
                            {{ number_format(($pag->total / $totalValor) * 100, 1) }}%
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td class="px-5 py-3 text-sm font-semibold text-gray-800 dark:text-white">Total</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white">{{ $pagamentos->sum('qtd') }}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($pagamentos->sum('total'), 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Listagem de vendas --}}
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Vendas do Período</h2>
    </div>
    @if($vendas->isEmpty())
        <p class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhuma venda encontrada para os filtros selecionados.</p>
    @else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Nº</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Data/Hora</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Caixa / Operador</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Desconto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Pagamentos</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($vendas as $v)
                <tr class="bg-white dark:bg-gray-800">
                    <td class="whitespace-nowrap px-4 py-2.5 font-medium text-gray-800 dark:text-white">
                        #{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $v->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        @if($v->caixa)
                            Caixa #{{ $v->caixa->numero_caixa }}<br>
                            <span class="text-xs text-gray-400">{{ $v->caixa->user->name ?? '?' }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        {{ $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? '—') : 'Consumidor Final' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm text-orange-600 dark:text-orange-400">
                        @if($v->desconto > 0)
                            - R$ {{ number_format($v->desconto, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right font-medium {{ $v->status === 'cancelada' ? 'text-gray-400 line-through' : 'text-gray-800 dark:text-white' }}">
                        R$ {{ number_format($v->total_final, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                        @foreach($v->pagamentos as $pag)
                            <span class="mr-1 whitespace-nowrap">{{ $pag->formaPagamento->nome ?? '?' }} R$ {{ number_format($pag->valor, 2, ',', '.') }}</span>
                        @endforeach
                    </td>
                    <td class="whitespace-nowrap px-4 py-2.5">
                        @if($v->status === 'finalizada')
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">Concluída</span>
                        @elseif($v->status === 'cancelada')
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">Cancelada</span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $v->status }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Total ({{ $vendas->count() }} registros)</td>
                    <td class="px-4 py-3 text-right text-sm font-semibold text-green-600 dark:text-green-400">
                        R$ {{ number_format($vendas->where('status', 'finalizada')->sum('total_final'), 2, ',', '.') }}
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
    .print-vendas-periodo { padding: 0 !important; margin: 0 !important; max-width: none !important; }
    .print-vendas-periodo h1.print-title { display: block !important; margin-bottom: 12px !important; }
    .print-vendas-periodo table { font-size: 10px; width: 100%; }
    .print-vendas-periodo th, .print-vendas-periodo td { padding: 4px 6px !important; }
    .print-vendas-periodo .overflow-x-auto { overflow: visible !important; }
    .print-vendas-periodo .grid { break-inside: avoid; }
    .print-vendas-periodo .grid > div { break-inside: avoid; }
    tr { page-break-inside: avoid; }
}
</style>
@endsection
