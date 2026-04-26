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
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Propostas comerciais</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Orçamentos formais com versões (diferente do PDV)</p>
    </div>
    @if(auth()->user()->is_admin || auth()->user()->hasPermission('create-propostas-comerciais'))
    <a href="{{ route('propostas-comerciais.create') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova proposta
    </a>
    @endif
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">{{ session('error') }}</div>
@endif

@php
    $alertaValProposta = request('alerta_proposta_validade');
@endphp
@if(in_array($alertaValProposta, ['hoje', 'vencida'], true))
<div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border px-4 py-3 text-sm {{ $alertaValProposta === 'vencida' ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300' : 'border-orange-200 bg-orange-50 text-orange-900 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-200' }}">
    <span>
        @if($alertaValProposta === 'hoje')
            Listando propostas em rascunho ou enviadas com <strong>validade hoje</strong>.
        @else
            Listando propostas em rascunho ou enviadas com <strong>validade vencida</strong>.
        @endif
    </span>
    <a href="{{ route('propostas-comerciais.index') }}" class="shrink-0 font-medium underline hover:no-underline">Ver todas</a>
</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" class="flex flex-wrap gap-3">
        @if(in_array($alertaValProposta, ['hoje', 'vencida'], true))
        <input type="hidden" name="alerta_proposta_validade" value="{{ $alertaValProposta }}">
        @endif
        <input type="text" name="search" placeholder="Número, ID, título ou cliente" value="{{ request('search') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 sm:w-64">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            <option value="">Todos os status</option>
            @foreach(['rascunho','enviada','aprovada','expirada','convertida','cancelada'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('propostas-comerciais.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Limpar</a>
    </form>
</div>

@if(function_exists('do_action'))
    @php do_action('entity.index.before_table', 'proposta_comercial') @endphp
@endif

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Ref.</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Título</th>
                    <th class="px-4 py-3">Emissão</th>
                    <th class="px-4 py-3">Validade</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($propostas as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                        @if($p->numero_sequencial)
                            {{ $p->numero_sequencial }} · v{{ $p->versao }}
                        @else
                            #{{ $p->id }} · v{{ $p->versao }}
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $p->cliente?->nome ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $p->titulo ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $p->data_emissao?->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">{{ $p->validade_em?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $p->status_label }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">R$ {{ number_format((float) $p->total_geral, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('propostas-comerciais.show', $p) }}" class="text-brand-600 hover:underline dark:text-brand-400">Abrir</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhuma proposta encontrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($propostas->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $propostas->links() }}</div>
    @endif
</div>
@endsection
