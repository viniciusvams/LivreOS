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
{{-- Estilo app nativo: botões grandes, ícones, cards destacados --}}
<div class="mx-auto max-w-2xl pb-6 lg:max-w-5xl">
    @if(session('success'))
    <div class="mb-4 rounded-2xl bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
    @endif

    @php
        $totalGeral = collect($stats ?? [])->sum();
        $encerradas = ($stats['Encerrada'] ?? 0) + ($stats['Encerrado'] ?? 0);
        $emAndamento = $stats['Em andamento'] ?? 0;
        $aguardando = $stats['Aguardando aprovação'] ?? 0;
    @endphp

    {{-- Hero / Saudação --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 p-5 text-white shadow-lg dark:from-brand-600 dark:to-brand-700 sm:mb-8 sm:p-6">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur sm:h-16 sm:w-16">
                <svg class="h-8 w-8 sm:h-9 sm:w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">Olá, {{ $cliente->nome ?? $cliente->razao_social ?? 'Cliente' }}!</h1>
                <p class="mt-0.5 text-sm opacity-90 sm:text-base">Acompanhe suas ordens de serviço</p>
            </div>
        </div>
    </div>

    {{-- Cards de resumo com ícones --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:mb-8 sm:gap-4 lg:grid-cols-4">
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white/90 sm:text-3xl">{{ $totalGeral }}</p>
                <p class="text-xs text-gray-500">ordens</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-success-100 text-success-600 dark:bg-success-500/20 dark:text-success-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Encerradas</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 sm:text-3xl">{{ $encerradas }}</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Em andamento</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 sm:text-3xl">{{ $emAndamento }}</p>
            </div>
        </div>
        <a href="{{ $aguardando > 0 ? route('plugin.area-cliente.index', array_merge(request()->query(), ['status' => 'Aguardando aprovação'])) : '#' }}" class="{{ $aguardando > 0 ? 'ring-2 ring-amber-400 ring-offset-2 dark:ring-offset-gray-900' : '' }} flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-amber-200 dark:border-gray-700 dark:bg-gray-800 sm:p-5 {{ $aguardando > 0 ? '' : 'pointer-events-none opacity-80' }}">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Aguardando aprovação</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 sm:text-3xl">{{ $aguardando }}</p>
                @if($aguardando > 0)
                <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">Toque para filtrar e assinar</p>
                @endif
            </div>
        </a>
    </div>

    @if($aguardando > 0)
    <div class="mb-6 rounded-2xl border-2 border-amber-200 bg-amber-50/90 p-4 dark:border-amber-700 dark:bg-amber-500/10 sm:p-5">
        <p class="text-sm font-medium text-amber-900 dark:text-amber-200">Você tem <strong>{{ $aguardando }}</strong> ordem(ns) aguardando sua <strong>assinatura para aprovação</strong>. Abra a OS e use o botão <strong>Assinar e aprovar</strong>.</p>
        <a href="{{ route('plugin.area-cliente.index', array_merge(request()->query(), ['status' => 'Aguardando aprovação'])) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-amber-800 underline hover:no-underline dark:text-amber-300">Ver ordens aguardando aprovação →</a>
    </div>
    @endif

    {{-- Ações principais: botões grandes tipo app --}}
    <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:flex-wrap sm:gap-4">
        <a href="{{ route('plugin.area-cliente.exportar', request()->query()) }}" class="flex min-h-[52px] flex-1 items-center justify-center gap-3 rounded-2xl border-2 border-gray-200 bg-white px-5 py-3.5 text-base font-semibold text-gray-700 shadow-sm transition active:scale-[0.98] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 sm:min-h-[56px]">
            <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exportar CSV
        </a>
        <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="flex min-h-[52px] flex-1 items-center justify-center gap-3 rounded-2xl border-2 border-amber-200 bg-amber-50 px-5 py-3.5 text-base font-semibold text-amber-800 shadow-sm transition active:scale-[0.98] dark:border-amber-700 dark:bg-amber-500/20 dark:text-amber-200 sm:min-h-[56px]">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0h-4"/></svg>
            Tickets
        </a>
        <a href="{{ route('plugin.area-cliente.perfil') }}" class="flex min-h-[52px] flex-1 items-center justify-center gap-3 rounded-2xl bg-brand-500 px-5 py-3.5 text-base font-semibold text-white shadow-lg shadow-brand-500/25 transition active:scale-[0.98] hover:bg-brand-600 dark:bg-brand-600 dark:shadow-brand-600/20 sm:min-h-[56px]">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Meu Perfil
        </a>
    </div>

    {{-- Filtros em card recolhível visual --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:mb-8">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-700 sm:px-5">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <span class="font-semibold text-gray-800 dark:text-white/90">Filtrar ordens</span>
        </div>
        <form method="GET" action="{{ route('plugin.area-cliente.index') }}" class="flex flex-col gap-4 p-4 sm:flex-row sm:flex-wrap sm:items-end sm:gap-4 sm:p-5">
            <div class="w-full sm:min-w-[200px] sm:flex-1">
                <label for="busca" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Busca</label>
                <input type="text" id="busca" name="busca" value="{{ request('busca') }}" placeholder="Código, equipamento, relato..." class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="w-full sm:w-auto">
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($statusList ?? [] as $s)
                    <option value="{{ $s->nome }}" {{ request('status') == $s->nome ? 'selected' : '' }}>{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="ordem" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Ordenar</label>
                <select id="ordem" name="ordem" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="created_at" {{ request('ordem') === 'created_at' ? 'selected' : '' }}>Data</option>
                    <option value="total" {{ request('ordem') === 'total' ? 'selected' : '' }}>Total</option>
                    <option value="status" {{ request('ordem') === 'status' ? 'selected' : '' }}>Status</option>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="dir" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Ordem</label>
                <select id="dir" name="dir" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="desc" {{ request('dir') === 'asc' ? '' : 'selected' }}>Mais recente</option>
                    <option value="asc" {{ request('dir') === 'asc' ? 'selected' : '' }}>Mais antigo</option>
                </select>
            </div>
            <div class="flex w-full gap-3 sm:w-auto">
                <button type="submit" class="min-h-[48px] flex-1 rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white transition active:scale-[0.98] hover:bg-brand-600 sm:flex-none">
                    Aplicar
                </button>
                @if(request()->hasAny(['busca', 'status', 'ordem', 'dir']))
                <a href="{{ route('plugin.area-cliente.index') }}" class="min-h-[48px] flex-1 rounded-xl border-2 border-gray-200 px-5 py-3 text-center text-base font-semibold text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:text-gray-300 sm:flex-none">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Lista de ordens --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-500/20">
                <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Minhas Ordens</h2>
        </div>

        @if($ordens->count() > 0)
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($ordens as $ordem)
            @php $colorClass = \AreaCliente\AreaClienteHelper::statusColor($ordem->status); @endphp
            <a href="{{ route('plugin.area-cliente.os.show', $ordem) }}" class="flex min-h-[88px] items-center gap-4 px-4 py-4 transition active:bg-gray-50 dark:active:bg-gray-700/50 sm:px-6 sm:py-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5M17 14v2m0 2h2m-2 0v-2m0-2h-2"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-gray-800 dark:text-white/90">{{ format_os_display($ordem) }}</p>
                    <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">{{ $ordem->equipamento_nome ?: '-' }}</p>
                    <p class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ optional($ordem->data_abertura)->format('d/m/Y') ?: '-' }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $colorClass }}">{{ format_status_os_display($ordem->status) }}</span>
                    <p class="text-base font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_geral ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="flex shrink-0 items-center">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
            @endforeach
        </div>
        <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-6">
            {{ $ordens->withQueryString()->links() }}
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="mt-4 text-lg font-semibold text-gray-700 dark:text-gray-300">Nenhuma ordem encontrada</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if(request()->hasAny(['busca', 'status']))
                    Ajuste os filtros ou limpe a busca.
                @else
                    Suas ordens aparecerão aqui.
                @endif
            </p>
        </div>
        @endif
    </div>
</div>
@endsection
