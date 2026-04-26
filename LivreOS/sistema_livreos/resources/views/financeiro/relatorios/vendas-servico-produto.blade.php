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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Vendas por Serviço e Produto</h1>
        @php
            $crit = $criterio ?? 'operacional';
            $incPv = $incluir_pedidos ?? false;
            $tecnicoSel = $tecnico_id ?? null;
            $slaSel = $sla_filtro ?? null;
        @endphp
        @if($crit === 'operacional')
        <p class="mt-1 text-gray-600 dark:text-gray-400"><strong>Modo execução (sem financeiro):</strong> por padrão só <strong>ordens de serviço fechadas</strong> (Encerrada, Concluída, Finalizado/Faturado vindos de sistema externo, etc.) com <strong>data de conclusão</strong> no período — ou, se não houver, a <strong>última alteração</strong> no período (útil em importações). <strong>Pedidos de venda</strong> só entram se você marcar a opção abaixo. OS geradas a partir de pedido não são somadas de novo pela OS.</p>
        @else
        <p class="mt-1 text-gray-600 dark:text-gray-400">Baseado em <strong>recebimentos</strong> (contas a receber pagas ou parciais com data de recebimento no período): o valor recebido é distribuído pelas linhas faturáveis do <strong>pedido</strong> (confirmado/faturado/entregue) ou da <strong>OS</strong> (linhas sem quantidade/horas efetiva não entram — ex.: orçamento só de referência). Adiantamentos de OS não entram.</p>
        @endif
    </div>
    <form method="get" action="{{ route('financeiro.relatorios.vendas-servico-produto') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">De</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Até</label>
            <input type="date" name="data_fim" value="{{ $dataFim ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Critério</label>
            <select name="criterio" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="financeiro" @selected($crit === 'financeiro')>Recebimento (financeiro)</option>
                <option value="operacional" @selected($crit === 'operacional')>Execução (sem financeiro)</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Técnico</label>
            <select name="tecnico_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Todos</option>
                @foreach(($tecnicos ?? []) as $tec)
                    <option value="{{ $tec->id }}" @selected((int) $tecnicoSel === (int) $tec->id)>{{ $tec->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">SLA</label>
            <select name="sla_filtro" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Todos</option>
                <option value="cumprido" @selected($slaSel === 'cumprido')>Cumprido</option>
                <option value="nao_cumprido" @selected($slaSel === 'nao_cumprido')>Não cumprido</option>
                <option value="com_sla" @selected($slaSel === 'com_sla')>Com SLA</option>
                <option value="sem_sla" @selected($slaSel === 'sem_sla')>Sem SLA</option>
            </select>
        </div>
        <div class="flex items-center gap-2 pb-0.5">
            <input type="checkbox" name="incluir_pedidos" value="1" id="incluir_pedidos" @checked($incPv) class="rounded border-gray-300 text-brand-600 dark:border-gray-600">
            <label for="incluir_pedidos" class="text-xs text-gray-600 dark:text-gray-400">Incluir pedidos de venda (data do pedido; só afeta modo Execução)</label>
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filtrar</button>
    </form>
</div>

<div class="mb-6 flex gap-4">
    <a href="{{ route('financeiro.relatorios.vendas-servico-produto.pdf', request()->query()) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Período: {{ \Carbon\Carbon::parse($dataInicio ?? now())->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim ?? now())->format('d/m/Y') }} ·
@if($crit === 'operacional')
<strong>OS:</strong> COALESCE(conclusão, alteração) no período · @if($incPv)<strong>Pedidos:</strong> data do pedido @else<strong>Pedidos:</strong> fora @endif
@else
<strong>Contas a receber:</strong> data de recebimento (pago/parcial)
@endif
@if(!empty($tecnicoSel))
 · <strong>Técnico:</strong> {{ collect($tecnicos ?? [])->firstWhere('id', (int) $tecnicoSel)->name ?? ('#'.$tecnicoSel) }}
@endif
@if(!empty($slaSel))
 · <strong>SLA:</strong>
@switch($slaSel)
    @case('cumprido') Cumprido @break
    @case('nao_cumprido') Não cumprido @break
    @case('com_sla') Com SLA @break
    @case('sem_sla') Sem SLA @break
@endswitch
@endif
</p>

<div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Serviço</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Quantidade</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total (R$)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($servicos ?? [] as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['nome'] }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ number_format((float) ($row['quantidade'] ?? 0), 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(empty($servicos) || count($servicos) === 0)
    <div class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhum serviço no período.</div>
    @endif
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Produtos</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Quantidade</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total (R$)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($produtos ?? [] as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['nome'] }}</td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">{{ number_format((float) ($row['quantidade'] ?? 0), 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(empty($produtos) || count($produtos) === 0)
    <div class="p-6 text-center text-gray-500 dark:text-gray-400">Nenhum produto no período.</div>
    @endif
</div>

<div class="mt-4">
    <a href="{{ route('financeiro.relatorios.index') }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
@endsection
