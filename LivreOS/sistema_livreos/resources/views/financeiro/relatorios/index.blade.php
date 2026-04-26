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
<div class="mx-auto max-w-6xl">
    <header class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Relatórios e Inteligência de Dados</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Central de relatórios financeiros e indicadores em tempo real</p>
    </header>

    {{-- Inteligência de Dados --}}
    <section class="mb-10">
        <h2 class="mb-4 text-base font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Inteligência de Dados
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('financeiro.contas-receber.index') }}" class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-500">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">A Receber</span>
                <span class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">R$ {{ number_format($totalReceber ?? 0, 2, ',', '.') }}</span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Total em aberto</span>
            </a>
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-500">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">A Pagar</span>
                <span class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">R$ {{ number_format($totalPagar ?? 0, 2, ',', '.') }}</span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Total em aberto</span>
            </a>
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo Contas</span>
                <span class="mt-2 text-xl font-bold tabular-nums {{ ($saldoContas ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    R$ {{ number_format($saldoContas ?? 0, 2, ',', '.') }}
                </span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Contas bancárias ativas</span>
            </div>
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Mês atual</span>
                <div class="mt-2 space-y-1 text-sm">
                    <p class="tabular-nums text-green-600 dark:text-green-400">Receitas: R$ {{ number_format($receitasMes ?? 0, 2, ',', '.') }}</p>
                    <p class="tabular-nums text-red-600 dark:text-red-400">Despesas: R$ {{ number_format($despesasMes ?? 0, 2, ',', '.') }}</p>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                    Resultado: R$ {{ number_format(($receitasMes ?? 0) - ($despesasMes ?? 0), 2, ',', '.') }}
                </span>
            </div>
        </div>

        @if(($inadimplenteReceber ?? 0) > 0 || ($inadimplentePagar ?? 0) > 0 || ($contasVencendo7Receber ?? 0) > 0 || ($contasVencendo7Pagar ?? 0) > 0)
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            @if(($inadimplenteReceber ?? 0) > 0 || ($inadimplentePagar ?? 0) > 0)
            <div class="flex-1 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Inadimplência</h3>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    Receber vencido: <strong>R$ {{ number_format($inadimplenteReceber ?? 0, 2, ',', '.') }}</strong>
                    @if(($inadimplentePagar ?? 0) > 0)
                        · Pagar vencido: <strong>R$ {{ number_format($inadimplentePagar ?? 0, 2, ',', '.') }}</strong>
                    @endif
                </p>
                <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="mt-2 inline-block text-xs font-medium text-amber-700 underline dark:text-amber-300">Ver Aging de Cobrança</a>
            </div>
            @endif
            @if(($contasVencendo7Receber ?? 0) > 0 || ($contasVencendo7Pagar ?? 0) > 0)
            <div class="flex-1 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Próximos 7 dias</h3>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                    {{ $contasVencendo7Receber ?? 0 }} conta(s) a receber · {{ $contasVencendo7Pagar ?? 0 }} conta(s) a pagar
                </p>
                <a href="{{ route('financeiro.dashboard') }}" class="mt-2 inline-block text-xs font-medium text-blue-700 underline dark:text-blue-300">Ver no dashboard</a>
            </div>
            @endif
        </div>
        @endif
    </section>

    {{-- Relatórios por Categoria --}}
    @foreach($categorias ?? [] as $categoria)
    <section class="mb-10">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $categoria['titulo'] }}</h2>
        @if(!empty($categoria['subtitulo']))
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ $categoria['subtitulo'] }}</p>
        @endif
        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($categoria['reportes'] ?? [] as $rel)
            <li class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                            @include('financeiro.relatorios.partials.icon-relatorio', ['icon' => $rel['icon'] ?? 'default'])
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-gray-800 dark:text-white/90">{{ $rel['name'] }}</h3>
                            <p class="mt-1 text-sm leading-snug text-gray-600 dark:text-gray-400">{{ $rel['description'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                    <a href="{{ route($rel['route']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Abrir
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @if(!empty($rel['pdf_route']))
                    <a href="{{ route($rel['pdf_route']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        PDF
                    </a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </section>
    @endforeach

    @if(function_exists('do_action'))
    <?php do_action('financeiro.relatorios.index.after'); ?>
    @endif
</div>
@endsection
