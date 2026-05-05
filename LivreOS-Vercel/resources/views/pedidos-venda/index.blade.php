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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Pedidos de Venda</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie os pedidos de venda</p>
    </div>
    @if(auth()->user()->is_admin || auth()->user()->hasPermission('create-pedidos-venda'))
    <a href="{{ route('pedidos-venda.create') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Pedido
    </a>
    @endif
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    {{ session('error') }}
</div>
@endif

{{-- Filtros --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" placeholder="Nº pedido ou cliente" value="{{ request('search') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 sm:w-56">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            <option value="">Todos os status</option>
            @foreach(['rascunho','confirmado','faturado','entregue','cancelado'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <div class="flex w-full flex-col gap-1 sm:w-auto">
            <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prev. entrega (só faturados)</span>
            <div class="flex flex-wrap items-center gap-2">
                <input type="date" name="prev_entrega_de" value="{{ request('prev_entrega_de') }}" title="Previsão de entrega — de"
                       class="rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <span class="text-xs text-gray-400">até</span>
                <input type="date" name="prev_entrega_ate" value="{{ request('prev_entrega_ate') }}" title="Previsão de entrega — até"
                       class="rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            </div>
        </div>
        <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
        <input type="date" name="data_fim" value="{{ request('data_fim') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('pedidos-venda.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Limpar</a>
    </form>
    @if(request()->filled('prev_entrega_de') || request()->filled('prev_entrega_ate'))
    <p class="mt-2 text-xs text-amber-700 dark:text-amber-400/90">Filtro ativo: apenas pedidos <strong>faturados</strong> com previsão de entrega no intervalo (o filtro de status acima fica desativado).</p>
    @endif
</div>

@if(function_exists('do_action'))
    @php do_action('pedidos_venda.index.table.before', $pedidos ?? null) @endphp
@endif

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Nº Pedido</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Prev. entrega</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($pedidos as $pedido)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 font-medium text-brand-600 dark:text-brand-400">
                        <a href="{{ route('pedidos-venda.show', $pedido) }}">{{ $pedido->numero_sequencial }}</a>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $pedido->cliente?->nome ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $pedido->data_pedido?->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                        @if($pedido->data_prevista_entrega)
                            {{ $pedido->data_prevista_entrega->format('d/m/Y') }}
                            @if($pedido->status === 'faturado' && $pedido->data_prevista_entrega->lt(now()->startOfDay()))
                                <span class="ml-1 inline-flex rounded px-1 py-0.5 text-[10px] font-semibold bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300">Atraso</span>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $badges = ['rascunho'=>'gray','confirmado'=>'sky','faturado'=>'amber','entregue'=>'green','cancelado'=>'red'];
                            $cor = $badges[$pedido->status] ?? 'gray';
                        @endphp
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold
                            @if($cor==='gray') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                            @elseif($cor==='sky') bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300
                            @elseif($cor==='amber') bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300
                            @elseif($cor==='green') bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300
                            @elseif($cor==='red') bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300
                            @endif">
                            {{ ucfirst($pedido->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">
                        R$ {{ number_format($pedido->total_geral, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('pedidos-venda.show', $pedido) }}" class="rounded bg-brand-100 px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-200 dark:bg-brand-500/20 dark:text-brand-300">Ver</a>
                            @if($pedido->podeEditar())
                            <a href="{{ route('pedidos-venda.edit', $pedido) }}" class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Editar</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Nenhum pedido encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pedidos->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
        {{ $pedidos->links() }}
    </div>
    @endif
</div>
@endsection
