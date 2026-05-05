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
@if(function_exists('do_action'))
    @php do_action('dashboard.main.before'); @endphp
@endif

<div class="dashboard-page">
<div class="mb-6">
    <h1 class="mb-1 text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400">Visão geral do sistema {{ config('app.name') }}</p>
</div>

{{-- Estatísticas do Sistema (plugins podem alterar via filtro dashboard.stats) --}}
<section class="mb-8">
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Estatísticas do Sistema</h2>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8">
        @isset($dashboard_stats)
            @foreach($dashboard_stats as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                    @if(($stat['format'] ?? '') === 'currency')
                        <p class="text-2xl font-bold {{ $stat['class'] ?? 'text-gray-900 dark:text-white' }}">R$ {{ number_format((float)($stat['value'] ?? 0), 2, ',', '.') }}</p>
                    @else
                        <p class="text-2xl font-bold {{ $stat['class'] ?? 'text-gray-900 dark:text-white' }}">{{ number_format((int)($stat['value'] ?? 0)) }}</p>
                    @endif
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] ?? '' }}</p>
                </div>
            @endforeach
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalClientes ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clientes</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalProdutos ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Produtos</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalServicos ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Serviços</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOrdens ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ordens</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalGarantias ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Garantias</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalVendas ?? 0) }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vendas</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($receitaDia ?? 0, 2, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Receita do dia</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">R$ {{ number_format($despesaDia ?? 0, 2, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Despesa do dia</p>
            </div>
        @endisset
    </div>
</section>

@if(function_exists('do_action'))
    @php do_action('dashboard.main.after_stats'); @endphp
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    @if(function_exists('do_action'))
        @php do_action('dashboard.cards.before'); @endphp
    @endif

    {{-- Card: Agenda das OS em formato mensal (plugins podem remover definindo $agendaPorDia = null em dashboard.main.data) --}}
    @if(isset($agendaPorDia) && $agendaPorDia !== null)
    @php
        $statusCores = [
            'Aberta' => 'bg-sky-500 dark:bg-sky-400',
            'Em andamento' => 'bg-amber-500 dark:bg-amber-400',
            'Em execução' => 'bg-amber-500 dark:bg-amber-400',
            'Encerrada' => 'bg-gray-400 dark:bg-gray-500',
            'Encerrado' => 'bg-gray-400 dark:bg-gray-500',
            'Cancelada' => 'bg-red-400 dark:bg-red-500',
        ];
    @endphp
    <div x-data="agendaOsCard({
        agendaUrl: @js(route('dashboard.agenda')),
        mesNome: @js(ucfirst($agendaMesNome ?? '')),
        ano: {{ (int) ($agendaMesAno ?? now()->year) }},
        mes: {{ (int) ($agendaMesMes ?? now()->month) }},
        diasNoMes: {{ (int) ($agendaDiasNoMes ?? 31) }},
        primeiroDiaSemana: {{ (int) ($agendaPrimeiroDiaSemana ?? 0) }},
        agenda: @js($agendaParaModal ?? []),
        pendentes: @js($agendaPendentes ?? []),
        agendaMesAnoPrev: {{ (int) ($agendaMesAnoPrev ?? now()->year) }},
        agendaMesMesPrev: {{ (int) ($agendaMesMesPrev ?? 1) }},
        agendaMesAnoNext: {{ (int) ($agendaMesAnoNext ?? now()->year) }},
        agendaMesMesNext: {{ (int) ($agendaMesMesNext ?? 1) }},
        statusCores: @js($statusCores),
    })" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-600 dark:bg-gray-800">
        {{-- Header --}}
        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-600 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/25 dark:text-brand-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Agenda das OS</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Clique no dia para ver as OS. Troque o mês sem recarregar.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <nav class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-600 dark:bg-gray-700/50" aria-label="Navegação de mês">
                    <button type="button"
                        @click="carregarMes(agendaMesMesPrev, agendaMesAnoPrev)"
                        :disabled="loading"
                        class="flex h-9 w-9 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                        title="Mês anterior" aria-label="Mês anterior">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="min-w-[160px] px-3 py-1.5 text-center text-sm font-semibold text-gray-800 dark:text-white" x-text="mesNome + ' ' + ano"></span>
                    <button type="button"
                        @click="carregarMes(agendaMesMesNext, agendaMesAnoNext)"
                        :disabled="loading"
                        class="flex h-9 w-9 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white"
                        title="Próximo mês" aria-label="Próximo mês">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </nav>
                <a href="{{ route('ordens-servico.index') }}" data-dashboard-ver-todas class="dashboard-btn-ver-todas shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:hover:text-white">Ver todas</a>
            </div>
        </div>

        {{-- Calendário: atualizado via Alpine a partir de getCalendarRows() --}}
        <div class="overflow-x-auto border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800" :class="{ 'opacity-60 pointer-events-none': loading }">
            <table class="w-full min-w-[360px] table-fixed border-collapse text-sm" role="grid">
                <thead>
                    <tr>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Dom</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Seg</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ter</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Qua</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Qui</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sex</th>
                        <th scope="col" class="w-[14.28%] py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sáb</th>
                    </tr>
                </thead>
                <tbody class="border-t border-gray-200 dark:border-gray-600">
                    <template x-for="(row, ri) in getCalendarRows()" :key="ri">
                        <tr>
                            <template x-for="(cell, ci) in row" :key="ci">
                                <td class="align-top border-b border-r border-gray-200 last:border-r-0 dark:border-gray-600"
                                    :class="cell.empty ? '' : (cell.ehHoje ? 'dark:bg-brand-500/20' : (cell.ehFimDeSemana ? 'dark:bg-gray-600/70' : 'dark:bg-gray-700'))"
                                    style="height: 88px; vertical-align: top;">
                                    <template x-if="cell.empty">
                                        <div class="h-[80px] rounded bg-gray-100 dark:bg-gray-600/90" aria-hidden="true"></div>
                                    </template>
                                    <template x-if="!cell.empty">
                                        <button type="button"
                                            @click="abrirDia(cell.chaveDia)"
                                            class="flex h-[80px] w-full flex-col rounded-lg p-1.5 text-left transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                            :class="cell.ehHoje ? 'bg-brand-50 ring-1 ring-brand-200 dark:bg-transparent dark:ring-brand-500/50 dark:hover:bg-brand-500/20' : (cell.ehFimDeSemana ? 'bg-gray-100 hover:bg-gray-200/80 dark:bg-transparent dark:hover:bg-gray-600' : 'bg-white hover:bg-gray-50 dark:bg-transparent dark:hover:bg-gray-600/90')">
                                            <div class="flex shrink-0 justify-center">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                                                    :class="cell.ehHoje ? 'bg-brand-500 text-white dark:bg-brand-400 dark:text-gray-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-500 dark:text-gray-100'"
                                                    x-text="cell.dia"></span>
                                            </div>
                                            <div class="mt-1 flex min-h-0 flex-1 flex-wrap items-center justify-center gap-0.5">
                                                <template x-for="os in cell.osDoDia" :key="os.id">
                                                    <span class="inline-block h-2 w-2 shrink-0 rounded-full"
                                                        :class="statusCores[os.status] || 'bg-brand-500 dark:bg-brand-400'"
                                                        :title="os.codigo + ' — ' + os.status"></span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p class="mt-3 text-center text-[11px] text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-sky-500 dark:bg-sky-400"></span> Aberta</span>
                <span class="ml-4 inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span> Em andamento</span>
                <span class="ml-4 inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-gray-400 dark:bg-gray-500"></span> Encerrada</span>
                <span class="ml-4 inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-brand-500 dark:bg-brand-400"></span> Outros</span>
            </p>
        </div>

        {{-- Seção: OS pendentes de meses anteriores --}}
        <template x-if="pendentes && pendentes.length > 0">
            <div x-data="{ aberto: false }" class="border-t border-amber-200 bg-amber-50 dark:border-amber-700/50 dark:bg-amber-900/20">
                <button type="button"
                    @click="aberto = !aberto"
                    class="flex w-full items-center justify-between px-5 py-3 text-left transition hover:bg-amber-100 dark:hover:bg-amber-800/20">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            <span x-text="pendentes.length"></span> OS pendente<span x-show="pendentes.length !== 1">s</span> de meses anteriores
                        </span>
                        <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-700 dark:text-amber-200" x-text="pendentes.length"></span>
                    </div>
                    <svg class="h-4 w-4 shrink-0 text-amber-600 transition-transform dark:text-amber-400"
                        :class="aberto ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="aberto"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="max-h-72 overflow-y-auto border-t border-amber-200 dark:border-amber-700/50">
                    <ul class="divide-y divide-amber-100 dark:divide-amber-800/30">
                        <template x-for="os in pendentes" :key="os.id">
                            <li class="flex flex-wrap items-center gap-2 px-5 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <a :href="os.show_url" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="os.codigo"></a>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="os.cliente"></p>
                                </div>
                                <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500" x-text="os.data_ref ? 'desde ' + os.data_ref : ''"></span>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                    :class="{
                                        'bg-sky-100 text-sky-800 dark:bg-sky-500/30 dark:text-sky-200': corStatus(os.status) === 'sky',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-500/30 dark:text-amber-200': corStatus(os.status) === 'amber',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-500/30 dark:text-gray-200': corStatus(os.status) === 'gray',
                                        'bg-red-100 text-red-800 dark:bg-red-500/30 dark:text-red-200': corStatus(os.status) === 'red',
                                        'bg-brand-100 text-brand-800 dark:bg-brand-500/30 dark:text-brand-200': corStatus(os.status) === 'brand'
                                    }"
                                    x-text="os.status"></span>
                                <div class="flex shrink-0 gap-1.5">
                                    <a :href="os.show_url" class="rounded bg-brand-100 px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-200 dark:bg-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/50">Ver</a>
                                    <a x-show="os.pode_editar" :href="os.edit_url" class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Editar</a>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>

        {{-- Modal: OS do dia --}}
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @keydown.escape.window="modalOpen = false">
                <div x-show="modalOpen" @click="modalOpen = false" class="absolute inset-0 bg-black/50 dark:bg-black/60 backdrop-blur-sm"></div>
                <div x-show="modalOpen" @click.stop
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-600 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="modalDateLabel"></h3>
                            <button type="button" @click="modalOpen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto p-4">
                        <template x-if="modalOs.length === 0">
                            <p class="text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma OS neste dia.</p>
                        </template>
                        <ul class="space-y-2" x-show="modalOs.length > 0">
                            <template x-for="os in modalOs" :key="os.id">
                                <li class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50">
                                    <div class="min-w-0 flex-1">
                                        <a :href="os.show_url" class="font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="os.codigo"></a>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="os.cliente"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="{
                                            'bg-sky-100 text-sky-800 dark:bg-sky-500/30 dark:text-sky-200': corStatus(os.status) === 'sky',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-500/30 dark:text-amber-200': corStatus(os.status) === 'amber',
                                            'bg-gray-100 text-gray-700 dark:bg-gray-500/30 dark:text-gray-200': corStatus(os.status) === 'gray',
                                            'bg-red-100 text-red-800 dark:bg-red-500/30 dark:text-red-200': corStatus(os.status) === 'red',
                                            'bg-brand-100 text-brand-800 dark:bg-brand-500/30 dark:text-brand-200': corStatus(os.status) === 'brand'
                                        }"
                                        x-text="os.status"></span>
                                    <div class="flex w-full shrink-0 gap-2 sm:w-auto">
                                        <a :href="os.show_url" class="rounded-lg bg-brand-100 px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-200 dark:bg-brand-500/30 dark:text-brand-200 dark:hover:bg-brand-500/50">Resumo</a>
                                        <a x-show="os.pode_editar" :href="os.edit_url" class="rounded-lg bg-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Editar</a>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
        <style>[x-cloak]{display:none!important}</style>
    </div>
    @endif

    {{-- Card: Contas que vencem hoje --}}
    @if(isset($contasReceberHoje) && isset($contasPagarHoje))
    <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Contas que vencem hoje</h2>
                <div class="flex gap-4 text-xs">
                    <a href="{{ route('financeiro.contas-receber.index') }}" class="font-medium text-green-600 hover:underline dark:text-green-400">A receber</a>
                    <a href="{{ route('financeiro.contas-pagar.index') }}" class="font-medium text-red-600 hover:underline dark:text-red-400">A pagar</a>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
            <div>
                <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">A receber</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">R$ {{ number_format($totalReceberHoje, 2, ',', '.') }}</p>
                @if($contasReceberHoje->isEmpty())
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nenhuma conta vence hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($contasReceberHoje->take(5) as $cr)
                            <li class="flex justify-between text-sm">
                                <span class="truncate text-gray-600 dark:text-gray-300">{{ $cr->cliente ? ($cr->cliente->nome ?? $cr->cliente->razao_social) : $cr->descricao }}</span>
                                <span class="shrink-0 font-medium">R$ {{ number_format($cr->valor, 2, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">A pagar</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">R$ {{ number_format($totalPagarHoje, 2, ',', '.') }}</p>
                @if($contasPagarHoje->isEmpty())
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nenhuma conta vence hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($contasPagarHoje->take(5) as $cp)
                            <li class="flex justify-between text-sm">
                                <span class="truncate text-gray-600 dark:text-gray-300">{{ $cp->fornecedor ? ($cp->fornecedor->nome ?? $cp->fornecedor->razao_social) : $cp->descricao }}</span>
                                <span class="shrink-0 font-medium">R$ {{ number_format($cp->valor, 2, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Cards adicionados por plugins (dashboard.extra_cards) --}}
@if(!empty($dashboard_extra_cards))
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    @foreach($dashboard_extra_cards as $card)
        <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
            @if(!empty($card['title']))
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $card['title'] }}</h2>
                </div>
            @endif
            <div class="p-5">
                @if(!empty($card['html']))
                    {!! $card['html'] !!}
                @elseif(!empty($card['view']))
                    @include($card['view'], $card['data'] ?? [])
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif

@if(function_exists('do_action'))
    @php do_action('dashboard.cards.after'); @endphp
@endif

{{-- Card: Últimas OS (plugins podem remover definindo $ultimasOs = null em dashboard.main.data) --}}
@if(isset($ultimasOs) && $ultimasOs !== null)
<div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-4 py-3 sm:px-5 sm:py-4 dark:border-gray-700">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Últimas Ordens de Serviço</h2>
            <a href="{{ route('ordens-servico.index') }}" class="shrink-0 rounded-lg px-3 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/20">Ver todas</a>
        </div>
    </div>
    @if($ultimasOs->isEmpty())
        <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma ordem de serviço cadastrada.</p>
    @else
        {{-- Mobile: lista em cards (sem scroll horizontal, toque fácil) --}}
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($ultimasOs as $os)
            @php
                $podeEditarOs = !in_array($os->status ?? '', ['Encerrada', 'Encerrado']);
                $prioridadeClasses = ['baixa' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', 'normal' => 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200', 'alta' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300', 'critica' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'];
                $prioridadeLabels = ['baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'critica' => 'Crítica'];
            @endphp
            <div class="p-4">
                <a href="{{ route('ordens-servico.show', $os) }}" class="block rounded-lg active:bg-gray-50 dark:active:bg-gray-700/50 -m-2 p-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ function_exists('format_os_display') ? format_os_display($os) : ($os->codigo_interno ?? 'OS #' . $os->id) }}
                            </p>
                            <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                                {{ $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social ?? '—') : '—' }}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                    {{ function_exists('format_status_os_display') ? format_status_os_display($os->status) : $os->status }}
                                </span>
                                @if(!empty($os->prioridade))
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $prioridadeClasses[$os->prioridade] ?? $prioridadeClasses['normal'] }}">
                                        {{ $prioridadeLabels[$os->prioridade] ?? ucfirst($os->prioridade) }}
                                    </span>
                                @endif
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                    R$ {{ number_format($os->total_geral ?? 0, 2, ',', '.') }}
                                </span>
                            </div>
                            @if($os->data_abertura || $os->prevista_conclusao)
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    @if($os->data_abertura)
                                        <span>Abertura: {{ \Carbon\Carbon::parse($os->data_abertura)->format('d/m/Y') }}</span>
                                    @endif
                                    @if($os->data_abertura && $os->prevista_conclusao) · @endif
                                    @if($os->prevista_conclusao)
                                        <span>Previsão: {{ \Carbon\Carbon::parse($os->prevista_conclusao)->format('d/m/Y') }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <span class="shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('ordens-servico.show', $os) }}" class="rounded-lg bg-brand-100 px-3 py-2 text-center text-sm font-medium text-brand-700 dark:bg-brand-500/30 dark:text-brand-200 touch-manipulation">Resumo</a>
                    <a href="{{ route('ordens-servico.print', $os) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-700 dark:bg-gray-600 dark:text-gray-200 touch-manipulation" title="Imprimir / PDF">Imprimir</a>
                    @if($podeEditarOs)
                        <a href="{{ route('ordens-servico.edit', $os) }}" class="rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-medium text-gray-700 dark:bg-gray-600 dark:text-gray-200 touch-manipulation">Editar</a>
                        <form action="{{ route('ordens-servico.duplicate', $os) }}" method="POST" class="inline" onsubmit="return confirm('Duplicar esta OS? Serão copiados cliente, relato, diagnóstico, serviços realizados, observações, equipamento, itens, tipo, origem, prioridade. A nova OS será criada com status Aberta.');">
                            @csrf
                            <button type="submit" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 dark:bg-gray-600 dark:text-gray-200 touch-manipulation">Duplicar</button>
                        </form>
                    @endif
                    <button type="button" onclick="abrirModalExcluirMotivoOs('{{ route('ordens-servico.destroy', $os) }}')" class="rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300 touch-manipulation">Excluir</button>
                </div>
            </div>
            @endforeach
        </div>
        <x-modal-excluir-motivo idModal="modalExcluirMotivoOs" idForm="formExcluirMotivoOs" idMotivo="motivoExcluirOs" titulo="Excluir ordem de serviço" descricao="Tem certeza que deseja excluir esta OS? Informe o motivo. Esta ação não pode ser desfeita." />
        <script>
        function abrirModalExcluirMotivoOs(url) {
            var f = document.getElementById('formExcluirMotivoOs');
            var m = document.getElementById('modalExcluirMotivoOs');
            if (f) f.action = url;
            if (document.getElementById('motivoExcluirOs')) document.getElementById('motivoExcluirOs').value = '';
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden'; }
        }
        </script>
        {{-- Desktop: tabela --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">OS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($ultimasOs as $os)
                    @php $podeEditarOs = !in_array($os->status ?? '', ['Encerrada', 'Encerrado']); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="whitespace-nowrap px-5 py-3">
                            <a href="{{ route('ordens-servico.show', $os) }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400" title="Ver resumo">
                                {{ function_exists('format_os_display') ? format_os_display($os) : ($os->codigo_interno ?? 'OS #' . $os->id) }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $os->cliente ? ($os->cliente->nome ?? $os->cliente->razao_social ?? '—') : '—' }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ function_exists('format_status_os_display') ? format_status_os_display($os->status) : $os->status }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white">
                            R$ {{ number_format($os->total_geral ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex justify-end items-center gap-1">
                                <a href="{{ route('ordens-servico.show', $os) }}" class="inline-flex items-center justify-center rounded p-1.5 text-brand-500 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" title="Resumo" aria-label="Ver OS">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('ordens-servico.print', $os) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" title="Imprimir / PDF" aria-label="Imprimir OS">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                @if($podeEditarOs)
                                    <a href="{{ route('ordens-servico.edit', $os) }}" class="inline-flex items-center justify-center rounded p-1.5 text-brand-500 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" title="Editar" aria-label="Editar OS">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('ordens-servico.duplicate', $os) }}" method="POST" class="inline" onsubmit="return confirm('Duplicar esta OS?');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" title="Duplicar OS" aria-label="Duplicar OS">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2h-2"/></svg>
                                        </button>
                                    </form>
                                @endif
                                <button type="button" onclick="abrirModalExcluirMotivoOs('{{ route('ordens-servico.destroy', $os) }}')" class="inline-flex items-center justify-center rounded p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300" title="Excluir" aria-label="Excluir OS">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endif
</div>{{-- .dashboard-page --}}

@push('scripts')
<style>
/* Dark mode: botões/links no dashboard (in-page para garantir aplicação) */
html.dark a[data-dashboard-ver-todas],
html.dark .dashboard-btn-ver-todas,
html.dark .dashboard-page a.bg-white,
html.dark .dashboard-page a.bg-gray-100,
html.dark .dashboard-page button.bg-gray-100 {
  background-color: rgb(75, 85, 99) !important;
  color: rgb(255, 255, 255) !important;
  border-color: rgb(75, 85, 99) !important;
}
html.dark a[data-dashboard-ver-todas]:hover,
html.dark .dashboard-btn-ver-todas:hover,
html.dark .dashboard-page a.bg-white:hover,
html.dark .dashboard-page a.bg-gray-100:hover,
html.dark .dashboard-page button.bg-gray-100:hover {
  background-color: rgb(107, 114, 128) !important;
  border-color: rgb(107, 114, 128) !important;
  color: rgb(255, 255, 255) !important;
}
html.dark .dashboard-page a.bg-brand-100 {
  background-color: rgb(70, 95, 255) !important;
  color: rgb(255, 255, 255) !important;
}
html.dark .dashboard-page a.bg-brand-100:hover {
  background-color: rgb(86, 111, 255) !important;
  color: rgb(255, 255, 255) !important;
}
html.dark .dashboard-page button.bg-red-50,
html.dark .dashboard-page a.bg-red-50 {
  background-color: rgb(75, 85, 99) !important;
  color: rgb(248, 113, 113) !important;
  border-color: rgb(75, 85, 99) !important;
}
html.dark .dashboard-page button.bg-red-50:hover,
html.dark .dashboard-page a.bg-red-50:hover {
  background-color: rgb(107, 114, 128) !important;
  border-color: rgb(107, 114, 128) !important;
}
</style>
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('agendaOsCard', function(config) {
        return {
            ...config,
            loading: false,
            modalOpen: false,
            modalDate: '',
            modalDateLabel: '',
            modalOs: [],
            abrirDia(chaveDia) {
                this.modalOs = this.agenda[chaveDia] || [];
                this.modalDate = chaveDia;
                var d = new Date(chaveDia + 'T12:00:00');
                this.modalDateLabel = d.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
                this.modalOpen = true;
            },
            corStatus(status) {
                var map = { 'Aberta': 'sky', 'Em andamento': 'amber', 'Em execução': 'amber', 'Encerrada': 'gray', 'Encerrado': 'gray', 'Cancelada': 'red' };
                return map[status] || 'brand';
            },
            getCalendarRows() {
                var total = this.primeiroDiaSemana + this.diasNoMes;
                var numRows = Math.ceil(total / 7);
                var ano = this.ano;
                var mes = this.mes;
                var agenda = this.agenda || {};
                var hoje = new Date().toISOString().slice(0, 10);
                var rows = [];
                var dia = 1;
                for (var r = 0; r < numRows; r++) {
                    var row = [];
                    for (var c = 0; c < 7; c++) {
                        var cellIndex = r * 7 + c;
                        var empty = cellIndex < this.primeiroDiaSemana || dia > this.diasNoMes;
                        var diaAtual = empty ? 0 : dia;
                        if (!empty) dia++;
                        var chaveDia = empty ? null : ano + '-' + String(mes).padStart(2, '0') + '-' + String(diaAtual).padStart(2, '0');
                        var osDoDia = (chaveDia && agenda[chaveDia]) ? agenda[chaveDia] : [];
                        row.push({
                            empty: empty,
                            dia: diaAtual,
                            chaveDia: chaveDia,
                            osDoDia: osDoDia,
                            ehHoje: chaveDia === hoje,
                            ehFimDeSemana: !empty && (c === 0 || c === 6)
                        });
                    }
                    rows.push(row);
                }
                return rows;
            },
            carregarMes(mes, ano) {
                var self = this;
                if (this.loading) return;
                this.loading = true;
                var url = this.agendaUrl + '?agenda_mes=' + mes + '&agenda_ano=' + ano;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        self.mesNome = data.mesNome || '';
                        self.ano = data.ano;
                        self.mes = data.mes;
                        self.diasNoMes = data.diasNoMes;
                        self.primeiroDiaSemana = data.primeiroDiaSemana;
                        self.agenda = data.agendaParaModal || {};
                        self.pendentes = data.agendaPendentes || [];
                        self.agendaMesAnoPrev = data.agendaMesAnoPrev;
                        self.agendaMesMesPrev = data.agendaMesMesPrev;
                        self.agendaMesAnoNext = data.agendaMesAnoNext;
                        self.agendaMesMesNext = data.agendaMesMesNext;
                    })
                    .catch(function() {})
                    .finally(function() { self.loading = false; });
            }
        };
    });
});
</script>
<script>
(function() {
  function applyDarkDashboardButtons() {
    if (!document.documentElement.classList.contains('dark')) return;
    var gray600 = 'rgb(75, 85, 99)';
    var gray500 = 'rgb(107, 114, 128)';
    var white = 'rgb(255, 255, 255)';
    document.querySelectorAll('.dashboard-page a.bg-white, .dashboard-page a.bg-gray-100, .dashboard-page button.bg-gray-100, a[data-dashboard-ver-todas]').forEach(function(el) {
      el.style.setProperty('background-color', gray600, 'important');
      el.style.setProperty('color', white, 'important');
      el.style.setProperty('border-color', gray600, 'important');
    });
    document.querySelectorAll('.dashboard-page a.bg-brand-100').forEach(function(el) {
      el.style.setProperty('background-color', 'rgb(70, 95, 255)', 'important');
      el.style.setProperty('color', white, 'important');
    });
    document.querySelectorAll('.dashboard-page button.bg-red-50, .dashboard-page a.bg-red-50').forEach(function(el) {
      el.style.setProperty('background-color', gray600, 'important');
      el.style.setProperty('color', 'rgb(248, 113, 113)', 'important');
      el.style.setProperty('border-color', gray600, 'important');
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applyDarkDashboardButtons);
  else applyDarkDashboardButtons();
  setTimeout(applyDarkDashboardButtons, 800);
})();
</script>
@endpush
@endsection
