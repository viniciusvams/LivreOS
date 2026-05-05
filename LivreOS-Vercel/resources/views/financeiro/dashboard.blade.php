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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Financeiro</h1>
    <p class="text-gray-600 dark:text-gray-400">Visão geral do módulo financeiro</p>
</div>

<!-- Cards de Resumo -->
<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">A Receber (pendente)</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">R$ {{ number_format($totalReceber, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Já recebido: R$ {{ number_format($totalRecebido, 2, ',', '.') }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">A Pagar (pendente)</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">R$ {{ number_format($totalPagar, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-full bg-red-100 p-3 dark:bg-red-900">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Já pago: R$ {{ number_format($totalPago, 2, ',', '.') }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Saldo Conciliado</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">R$ {{ number_format($saldoTotal, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
        @php $emTransito = $movPendentesEntrada - $movPendentesSaida; @endphp
        <p class="mt-2 text-xs {{ $emTransito >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            Em trânsito: {{ $emTransito >= 0 ? '+' : '' }}R$ {{ number_format($emTransito, 2, ',', '.') }}
        </p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Vencidos</p>
                <p class="mt-1 text-2xl font-semibold text-orange-600 dark:text-orange-400">R$ {{ number_format($totalVencido, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900">
                <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">A pagar: R$ {{ number_format($totalVencidoPagar, 2, ',', '.') }}</p>
    </div>
</div>

<!-- Saldo Projetado -->
<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-5 shadow-theme-sm dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Saldo Atual</p>
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
            </svg>
        </div>
        <p class="mt-2 text-2xl font-bold {{ $saldoTotal >= 0 ? 'text-blue-800 dark:text-blue-100' : 'text-red-700 dark:text-red-300' }}">
            R$ {{ number_format($saldoTotal, 2, ',', '.') }}
        </p>
        @if($movPendentesEntrada > 0 || $movPendentesSaida > 0)
        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
            Base proj.: R$ {{ number_format($saldoBaseProjetado, 2, ',', '.') }}
            <span class="text-gray-500 dark:text-gray-400">(+trânsito)</span>
        </p>
        @else
        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">{{ $contasBancarias->count() }} conta(s) bancária(s)</p>
        @endif
    </div>

    <div class="rounded-lg border p-5 shadow-theme-sm {{ $saldoProjetado7d >= 0 ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }}">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium {{ $saldoProjetado7d >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">Projetado +7 dias</p>
            <svg class="h-5 w-5 {{ $saldoProjetado7d >= 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <p class="mt-2 text-2xl font-bold {{ $saldoProjetado7d >= 0 ? 'text-green-800 dark:text-green-100' : 'text-red-700 dark:text-red-300' }}">
            R$ {{ number_format($saldoProjetado7d, 2, ',', '.') }}
        </p>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
            <span class="text-green-600 dark:text-green-400">+R$ {{ number_format($crProj7, 2, ',', '.') }}</span>
            &nbsp;/&nbsp;
            <span class="text-red-600 dark:text-red-400">-R$ {{ number_format($cpProj7, 2, ',', '.') }}</span>
            <span class="ml-1 text-gray-400">pendentes</span>
        </p>
    </div>

    <div class="rounded-lg border p-5 shadow-theme-sm {{ $saldoProjetado30d >= 0 ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }}">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium {{ $saldoProjetado30d >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">Projetado +30 dias</p>
            <svg class="h-5 w-5 {{ $saldoProjetado30d >= 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <p class="mt-2 text-2xl font-bold {{ $saldoProjetado30d >= 0 ? 'text-green-800 dark:text-green-100' : 'text-red-700 dark:text-red-300' }}">
            R$ {{ number_format($saldoProjetado30d, 2, ',', '.') }}
        </p>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
            <span class="text-green-600 dark:text-green-400">+R$ {{ number_format($crProj30, 2, ',', '.') }}</span>
            &nbsp;/&nbsp;
            <span class="text-red-600 dark:text-red-400">-R$ {{ number_format($cpProj30, 2, ',', '.') }}</span>
            <span class="ml-1 text-gray-400">pendentes</span>
        </p>
    </div>
</div>

@if(!empty($pdvAvisos) && count($pdvAvisos) > 0)
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 shadow-theme-sm dark:border-amber-800 dark:bg-amber-900/20">
    <div class="border-b border-amber-200 p-4 dark:border-amber-800">
        <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Avisos PDV – Quebra / Sobra de Caixa</h3>
        <p class="text-sm text-amber-800 dark:text-amber-200">Fechamentos com diferença entre valor informado e valor em sistema.</p>
    </div>
    <div class="p-4">
        <div class="space-y-3" x-data="{ expandidoId: null }">
            @foreach($pdvAvisos as $aviso)
            <div class="rounded-lg border border-amber-200 bg-white dark:border-amber-800 dark:bg-gray-800" data-aviso-id="{{ $aviso['id'] }}">
                <div class="flex flex-wrap items-center justify-between gap-2 p-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($aviso['valor_quebra_sobra'] ?? 0) >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                            {{ ($aviso['valor_quebra_sobra'] ?? 0) >= 0 ? 'Sobra' : 'Quebra' }}: R$ {{ number_format(abs($aviso['valor_quebra_sobra'] ?? 0), 2, ',', '.') }}
                        </span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Caixa {{ $aviso['caixa_numero'] ?? '—' }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $aviso['data_fechamento'] ?? '' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="expandidoId = expandidoId === {{ $aviso['id'] }} ? null : {{ $aviso['id'] }}" class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            <span x-text="expandidoId === {{ $aviso['id'] }} ? 'Ocultar detalhes' : 'Ver detalhes'"></span>
                        </button>
                        <button type="button" class="ocultar-aviso-pdv rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" data-aviso-id="{{ $aviso['id'] }}" data-url-ocultar="{{ $aviso['url_ocultar'] ?? '' }}">
                            Ocultar aviso
                        </button>
                    </div>
                </div>
                <div x-show="expandidoId === {{ $aviso['id'] }}" x-cloak class="border-t border-amber-200 px-3 py-3 text-sm dark:border-amber-800" style="display: none;">
                    <div class="grid grid-cols-2 gap-2 text-gray-700 dark:text-gray-300 md:grid-cols-4">
                        <p><span class="text-gray-500 dark:text-gray-400">Valor informado:</span> R$ {{ number_format($aviso['valor_informado'] ?? 0, 2, ',', '.') }}</p>
                        <p><span class="text-gray-500 dark:text-gray-400">Valor sistema:</span> R$ {{ number_format($aviso['valor_sistema'] ?? 0, 2, ',', '.') }}</p>
                        @if(!empty($aviso['aprovado_por_nome']))
                        <p class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Aprovado por:</span> {{ $aviso['aprovado_por_nome'] }}</p>
                        @endif
                    </div>
                    @if(!empty($aviso['dados_formas']) && is_array($aviso['dados_formas']))
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead><tr class="text-left text-gray-500 dark:text-gray-400"><th class="pr-2">Forma</th><th class="text-right pr-2">Informado</th><th class="text-right pr-2">Sistema</th><th class="text-right">Dif.</th></tr></thead>
                            <tbody>
                                @foreach($aviso['dados_formas'] as $f)
                                <tr>
                                    <td class="pr-2">{{ $f['nome'] ?? '—' }}</td>
                                    <td class="text-right pr-2">R$ {{ number_format($f['informado'] ?? 0, 2, ',', '.') }}</td>
                                    <td class="text-right pr-2">R$ {{ number_format($f['sistema'] ?? 0, 2, ',', '.') }}</td>
                                    <td class="text-right font-medium {{ ($f['diferenca'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">R$ {{ number_format($f['diferenca'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @if(!empty($aviso['link_caixas']))
                    <p class="mt-2"><a href="{{ $aviso['link_caixas'] }}" class="text-brand-600 hover:underline dark:text-brand-400">Abrir Gerenciar caixas PDV →</a></p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.ocultar-aviso-pdv').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-aviso-id');
        var url = this.getAttribute('data-url-ocultar');
        if (!url || !id) return;
        var row = document.querySelector('[data-aviso-id="' + id + '"]');
        if (row && confirm('Ocultar este aviso? Ele deixará de aparecer na sua lista.')) {
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({}) })
                .then(function(r) { return r.json(); })
                .then(function() { if (row) row.remove(); })
                .catch(function() { alert('Erro ao ocultar.'); });
        }
    });
});
</script>
@endif

@if(!empty($dashboardCards) && is_array($dashboardCards))
@php $dashboardCards = collect($dashboardCards)->sortBy('order'); @endphp
<div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
    @foreach($dashboardCards as $card)
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        @if(!empty($card['title']))
        <h3 class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $card['title'] }}</h3>
        @endif
        <div class="text-gray-800 dark:text-white/90">{!! $card['content'] ?? '' !!}</div>
    </div>
    @endforeach
</div>
@endif

<style>
[x-cloak] { display: none !important; }
</style>

@if($contasReceberHoje->isNotEmpty() || $contasPagarHoje->isNotEmpty())
<!-- Vence Hoje -->
<div class="mb-6 rounded-lg border border-red-300 bg-red-50 shadow-theme-sm dark:border-red-700 dark:bg-red-900/20">
    <div class="flex flex-wrap items-center gap-3 border-b border-red-200 p-4 dark:border-red-800">
        <svg class="h-5 w-5 shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Vence Hoje — {{ now()->format('d/m/Y') }}</h3>
        <div class="ml-auto flex flex-wrap gap-4 text-sm">
            @if($venceHojeReceber > 0)
            <span class="text-green-700 dark:text-green-400">
                Receber: <strong>R$ {{ number_format($venceHojeReceber, 2, ',', '.') }}</strong>
                ({{ $contasReceberHoje->count() }})
            </span>
            @endif
            @if($venceHojePagar > 0)
            <span class="text-red-700 dark:text-red-300">
                Pagar: <strong>R$ {{ number_format($venceHojePagar, 2, ',', '.') }}</strong>
                ({{ $contasPagarHoje->count() }})
            </span>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 divide-y divide-red-200 dark:divide-red-800 md:grid-cols-2 md:divide-x md:divide-y-0">
        <div class="p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-400">A Receber</p>
            @forelse($contasReceberHoje as $conta)
            <div class="mb-2 flex items-center justify-between gap-2 rounded border border-red-200 bg-white px-3 py-2 dark:border-red-800 dark:bg-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $conta->cliente->nome ?? '-' }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $conta->descricao }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-semibold text-green-700 dark:text-green-400">R$ {{ number_format($conta->valor, 2, ',', '.') }}</p>
                    <a href="{{ route('contas-receber.edit', $conta->id) }}" class="text-xs text-brand-600 hover:underline dark:text-brand-400">Abrir →</a>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta a receber hoje</p>
            @endforelse
        </div>
        <div class="p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-400">A Pagar</p>
            @forelse($contasPagarHoje as $conta)
            <div class="mb-2 flex items-center justify-between gap-2 rounded border border-red-200 bg-white px-3 py-2 dark:border-red-800 dark:bg-gray-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $conta->fornecedor->nome ?? '-' }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $conta->descricao }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400">R$ {{ number_format($conta->valor, 2, ',', '.') }}</p>
                    <a href="{{ route('contas-pagar.edit', $conta->id) }}" class="text-xs text-brand-600 hover:underline dark:text-brand-400">Abrir →</a>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta a pagar hoje</p>
            @endforelse
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Contas a Receber Vencendo -->
    <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Contas a Receber - Próximos 7 dias</h3>
        </div>
        <div class="p-4">
            @forelse($contasVencendo as $conta)
                <div class="mb-3 flex items-center justify-between rounded border border-gray-200 p-3 dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $conta->cliente->nome ?? '-' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $conta->descricao }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Vencimento: {{ $conta->data_vencimento->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</p>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                            {{ ucfirst($conta->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma conta vencendo nos próximos 7 dias</p>
            @endforelse
        </div>
    </div>

    <!-- Contas a Pagar Vencendo -->
    <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Contas a Pagar - Próximos 7 dias</h3>
        </div>
        <div class="p-4">
            @forelse($contasPagarVencendo as $conta)
                <div class="mb-3 flex items-center justify-between rounded border border-gray-200 p-3 dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $conta->fornecedor->nome ?? '-' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $conta->descricao }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Vencimento: {{ $conta->data_vencimento->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</p>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                            {{ ucfirst($conta->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400">Nenhuma conta vencendo nos próximos 7 dias</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Movimentações Recentes -->
<div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Movimentações Recentes</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Conta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($movimentacoesRecentes as $mov)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $mov->data_movimentacao->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $mov->descricao }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $mov->contaBancaria->nome ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $mov->tipo === 'entrada' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                {{ $mov->tipo === 'entrada' ? 'Entrada' : 'Saída' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ $mov->tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $mov->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($mov->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $mov->conciliado ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $mov->conciliado ? 'Conciliado' : 'Pendente' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma movimentação encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
