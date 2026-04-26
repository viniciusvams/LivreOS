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
@php
    $aba = $aba ?? 'contas';
    $_auditSvc    = app(\App\Services\AuditCancelExcluirService::class);
    $_podeBaixar  = $_auditSvc->canBaixar(auth()->user(), 'conta_receber');
    $_podeEstornar= $_auditSvc->canEstornar(auth()->user(), 'conta_receber');
    $_podeCancelar= $_auditSvc->canCancel(auth()->user(), 'conta_receber');
    $_podeExcluir = $_auditSvc->canExcluir(auth()->user(), 'conta_receber');
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Contas a Receber</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as contas a receber e os recebimentos recorrentes (aluguéis, assinaturas, contratos)</p>
    </div>
    <div class="flex items-center gap-2">
        @if($aba === 'contas')
            <a href="{{ route('financeiro.contas-receber.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="Exportar PDF com filtros atuais">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar PDF
            </a>
        @endif
        @if($aba === 'recorrentes')
            <a href="{{ route('financeiro.pagamentos-recorrentes.create') }}?voltar={{ urlencode(route('financeiro.contas-receber.index', ['aba' => 'recorrentes'])) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Novo Recorrente
            </a>
        @else
            <a href="{{ route('financeiro.contas-receber.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Conta
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
@endif

{{-- Indicadores / KPIs --}}
@if($aba === 'contas')
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    {{-- Total Pendente --}}
    <a href="{{ route('financeiro.contas-receber.index', ['pendentes' => 1]) }}" class="group rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pendente</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['totalPendente'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['qtdPendente'] }} título(s)</p>
    </a>

    {{-- Vencido --}}
    <a href="{{ route('financeiro.contas-receber.index', ['vencidas' => 1]) }}" class="group rounded-xl border border-red-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-red-900/50 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-red-600 dark:text-red-400">Vencido</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-red-600 dark:text-red-400">R$ {{ number_format($indicadores['totalVencido'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['qtdVencido'] }} título(s)</p>
    </a>

    {{-- A Vencer --}}
    <a href="{{ route('financeiro.contas-receber.index', ['a_vencer' => 1]) }}" class="group rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">A Vencer</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['totalAVencer'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['vencendo7dias'] }} vence(m) em 7 dias</p>
    </a>

    {{-- Recebido no Mês --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Recebido (mês)</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-green-600 dark:text-green-400">R$ {{ number_format($indicadores['recebidoMes'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('F/Y') }}</p>
    </div>

    {{-- Ticket Médio --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ticket Médio</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['ticketMedio'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Recebimentos do mês</p>
    </div>
</div>
@endif

<!-- Abas Contas | Recorrentes -->
<nav class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <div class="flex gap-1">
        <a href="{{ route('financeiro.contas-receber.index') }}" class="border-b-2 px-4 py-3 text-sm font-medium {{ $aba === 'contas' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Contas
        </a>
        <a href="{{ route('financeiro.contas-receber.index', ['aba' => 'recorrentes']) }}" class="border-b-2 px-4 py-3 text-sm font-medium {{ $aba === 'recorrentes' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Recorrentes
        </a>
    </div>
</nav>

@if($aba === 'contas')
<!-- Filtros -->
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.contas-receber.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <option value="aberto" {{ request('status') === 'aberto' ? 'selected' : '' }}>Aberto</option>
                    <option value="pago" {{ request('status') === 'pago' ? 'selected' : '' }}>Pago</option>
                    <option value="parcial" {{ request('status') === 'parcial' ? 'selected' : '' }}>Parcial</option>
                    <option value="vencido" {{ request('status') === 'vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                <select name="cliente_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nome ?: $cliente->razao_social }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Forma de pagamento</label>
                <select name="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($formasPagamento as $forma)
                        <option value="{{ $forma->id }}" {{ request('forma_pagamento_id') == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conta bancária</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($contasBancarias as $conta)
                        <option value="{{ $conta->id }}" {{ request('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="mb-1 flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Plano de contas</span>
                    <button type="button" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-gray-50 text-[10px] font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="O que é plano de contas?" aria-expanded="false" aria-controls="help-filtro-plano-receber" onclick="(function(el){ var p=document.getElementById('help-filtro-plano-receber'); p.classList.toggle('hidden'); el.setAttribute('aria-expanded', p.classList.contains('hidden')?'false':'true'); })(this)">?</button>
                </div>
                <p id="help-filtro-plano-receber" class="mb-2 hidden rounded-md border border-gray-100 bg-gray-50 p-2 text-xs leading-relaxed text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400" role="note">O <strong>plano de contas</strong> classifica a <em>natureza</em> da receita (ex.: vendas de produtos, prestação de serviços). Ajuda em relatórios e na visão por rubrica no resultado. O filtro mostra só títulos ligados à conta de receita escolhida.</p>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($planosContasFiltro ?? [] as $pl)
                        @php $plLabel = trim(($pl->codigo ? $pl->codigo.' — ' : '').$pl->nome); @endphp
                        <option value="{{ $pl->id }}" {{ (string) request('plano_conta_id') === (string) $pl->id ? 'selected' : '' }}>{{ $plLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="mb-1 flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Centro de custo</span>
                    <button type="button" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-gray-50 text-[10px] font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="O que é centro de custo?" aria-expanded="false" aria-controls="help-filtro-cc-receber" onclick="(function(el){ var p=document.getElementById('help-filtro-cc-receber'); p.classList.toggle('hidden'); el.setAttribute('aria-expanded', p.classList.contains('hidden')?'false':'true'); })(this)">?</button>
                </div>
                <p id="help-filtro-cc-receber" class="mb-2 hidden rounded-md border border-gray-100 bg-gray-50 p-2 text-xs leading-relaxed text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400" role="note">O <strong>centro de custo</strong> associa o recebimento a uma <em>área, projeto ou filial</em> quando você controla resultado por unidade. Não substitui o plano de contas: a receita continua classificada pela conta; o centro responde “de qual pedaço do negócio veio”.</p>
                <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($centrosCustoFiltro ?? [] as $cc)
                        @php $ccLabel = trim(($cc->codigo ? $cc->codigo.' — ' : '').$cc->nome); @endphp
                        <option value="{{ $cc->id }}" {{ (string) request('centro_custo_id') === (string) $cc->id ? 'selected' : '' }}>{{ $ccLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Categoria (classificação)</label>
                <select name="categoria_financeira_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($categoriasFinanceirasFiltroOpcoes ?? [] as $opt)
                        <option value="{{ $opt['id'] }}" {{ (string) request('categoria_financeira_id') === (string) $opt['id'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tag</label>
                <select name="tag_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($tagsFinanceirasFiltro ?? [] as $tg)
                        <option value="{{ $tg->id }}" {{ (string) request('tag_id') === (string) $tg->id ? 'selected' : '' }}>{{ $tg->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Vencimento de</label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Vencimento até</label>
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Buscar (descrição ou observações)</label>
                <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Digite para buscar..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor mín.</label>
                <input type="text" name="valor_min" value="{{ request('valor_min') }}" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor máx.</label>
                <input type="text" name="valor_max" value="{{ request('valor_max') }}" placeholder="0,00" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Ordenar por</label>
                <div class="flex gap-2">
                    <select name="ordenar" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="created_at" {{ request('ordenar', 'created_at') === 'created_at' ? 'selected' : '' }}>Data criação</option>
                        <option value="data_vencimento" {{ request('ordenar') === 'data_vencimento' ? 'selected' : '' }}>Vencimento</option>
                        <option value="valor" {{ request('ordenar') === 'valor' ? 'selected' : '' }}>Valor</option>
                        <option value="cliente_id" {{ request('ordenar') === 'cliente_id' ? 'selected' : '' }}>Cliente</option>
                    </select>
                    <select name="ordenar_direcao" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="desc" {{ request('ordenar_direcao', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
                        <option value="asc" {{ request('ordenar_direcao') === 'asc' ? 'selected' : '' }}>Asc</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('financeiro.contas-receber.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
            <span class="text-gray-400 dark:text-gray-500">|</span>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Atalhos:</span>
            <button type="submit" name="pendentes" value="1" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Pendentes</button>
            <button type="submit" name="a_vencer" value="1" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">A vencer</button>
            <button type="submit" name="vencidas" value="1" class="rounded-lg border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100 dark:border-orange-700 dark:bg-orange-900 dark:text-orange-300">Vencidas</button>
        </div>
        @if(request()->hasAny(['status', 'cliente_id', 'forma_pagamento_id', 'conta_bancaria_id', 'plano_conta_id', 'centro_custo_id', 'categoria_financeira_id', 'tag_id', 'data_inicio', 'data_fim', 'busca', 'valor_min', 'valor_max', 'vencidas', 'a_vencer', 'pendentes']))
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Filtros ativos. Use "Limpar" para ver todas as contas.</p>
        @endif
    </form>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'conta_receber'); ?>
@endif

<!-- Barra de Ações em Massa -->
<div id="barraAcoesMassa" class="mb-4 hidden rounded-lg border border-brand-200 bg-brand-50 p-4 shadow-theme-sm dark:border-brand-800 dark:bg-brand-900">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-brand-800 dark:text-brand-200">
                <span id="contadorSelecionados">0</span> conta(s) selecionada(s)
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($_podeBaixar)
            <button type="button" onclick="abrirModalBaixaMassa()" id="btnBaixarMassa" class="hidden rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 shadow-sm">
                Baixar Selecionadas
            </button>
            @endif
            @if($_podeCancelar)
            <button type="button" onclick="abrirModalCancelarMassa()" id="btnCancelarMassa" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm" style="background-color: #f97316; border-color: #f97316;" onmouseover="this.style.backgroundColor='#ea580c'; this.style.borderColor='#ea580c';" onmouseout="this.style.backgroundColor='#f97316'; this.style.borderColor='#f97316';">
                Cancelar Selecionadas
            </button>
            @endif
            @if($_podeExcluir)
            <button type="button" onclick="abrirModalExcluirMassa()" id="btnExcluirMassa" class="hidden rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 shadow-sm">
                Excluir Selecionadas
            </button>
            @endif
            <button type="button" onclick="agruparSelecionadasReceber()" id="btnAgruparMassa" class="hidden rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 shadow-sm">
                Agrupar (Espelho Total)
            </button>
            <button type="button" onclick="limparSelecao()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Limpar Seleção
            </button>
        </div>
    </div>
</div>

<!-- Modal Agrupar em Massa -->
<div id="modalAgruparMassaReceber" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Agrupar Contas Selecionadas</h2>
            <button type="button" onclick="fecharModalAgruparReceber()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formAgruparMassaReceber" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição do lote *</label>
                    <input type="text" id="agruparReceberDescricao" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de vencimento do lote *</label>
                    <input type="date" id="agruparReceberDataVencimento" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalAgruparReceber()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" id="btnConfirmarAgruparReceber" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600">Confirmar Agrupamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400" style="width: 40px;">
                        <input type="checkbox" id="selecionarTodos" onchange="selecionarTodos(this)" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Recebido</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Pendente</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($contas as $conta)
                    @php
                        $sugCr = \App\Http\Controllers\Financeiro\ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos((float)($conta->valor_original ?? $conta->valor), $conta->data_vencimento->format('Y-m-d'));
                        $desmembradoObrigatorios = '';
                        if (($conta->estrutura_tipo ?? '') === 'desmembrado_filho' && $conta->parent_id) {
                            $desmembradoObrigatorios = \App\Models\ContaReceber::query()
                                ->where('parent_id', $conta->parent_id)
                                ->where('estrutura_tipo', 'desmembrado_filho')
                                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                                ->orderBy('id')
                                ->pluck('id')
                                ->implode(',');
                        }
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        data-conta-id="{{ $conta->id }}"
                        data-status="{{ $conta->status }}"
                        data-estrutura-tipo="{{ $conta->estrutura_tipo ?? 'normal' }}"
                        data-status-estrutura="{{ $conta->status_estrutura ?? 'ativo' }}"
                        data-cliente-id="{{ $conta->cliente_id }}"
                        data-plano-conta-id="{{ $conta->plano_conta_id ?? '' }}"
                        data-conta-bancaria-id="{{ $conta->conta_bancaria_id ?? '' }}"
                        data-parent-id="{{ $conta->parent_id ?? '' }}"
                        data-desmembrado-obrigatorios="{{ $desmembradoObrigatorios }}"
                        data-valor-pendente="{{ $conta->valor_pendente }}"
                        data-juros-sugerido="{{ $sugCr['juros'] }}"
                        data-multa-sugerido="{{ $sugCr['multa'] }}"
                        data-total-parcelas="{{ (int) ($conta->total_parcelas ?? 1) }}"
                        data-forma-pagamento-id="{{ $conta->forma_pagamento_id ?? '' }}"
                        data-adquirente-id="{{ $conta->adquirente_id ?? '' }}"
                        data-bandeira="{{ $conta->bandeira ?? '' }}"
                        data-pode-baixar="{{ $conta->status !== 'pago' && $conta->status !== 'cancelado' ? '1' : '0' }}"
                        data-pode-cancelar="{{ $conta->status !== 'cancelado' && $conta->status !== 'pago' ? '1' : '0' }}"
                        data-pode-excluir="{{ ($conta->status !== 'pago' || $conta->valor_recebido == 0) ? '1' : '0' }}">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" name="contas_selecionadas[]" value="{{ $conta->id }}" class="checkbox-conta h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" onchange="atualizarBarraAcoes()">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $conta->cliente->nome ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $conta->descricao }}
                            @if($conta->total_parcelas > 1)
                                <span class="text-xs text-gray-500">({{ $conta->numero_parcela }}/{{ $conta->total_parcelas }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $conta->data_vencimento->format('d/m/Y') }}
                            @if($conta->esta_vencido)
                                <span class="ml-1 text-xs text-red-600 dark:text-red-400">(Vencida)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($conta->valor_recebido, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-orange-600 dark:text-orange-400">R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($conta->status === 'pago') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @elseif($conta->status === 'parcial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @elseif($conta->status === 'vencido') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @elseif($conta->status === 'cancelado') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @endif">
                                {{ ucfirst($conta->status) }}
                            </span>
                            @if($conta->estrutura_tipo !== 'normal')
                                <div class="mt-1">
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ str_replace('_', ' ', $conta->estrutura_tipo) }}
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('financeiro.contas-receber.edit', $conta) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($conta->status === 'pago')
                                    <a href="{{ route('financeiro.contas-receber.recibo', $conta) }}" target="_blank" class="inline-flex items-center gap-1 rounded border border-green-200 bg-green-50 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50" title="Imprimir recibo de pagamento em PDF">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span>Recibo</span>
                                    </a>
                                @endif
                                @if($_podeBaixar && $conta->status !== 'pago' && $conta->status !== 'cancelado')
                                    <button type="button" onclick="abrirModalBaixa({{ $conta->id }}, {{ $conta->valor_pendente }})" class="text-green-600 hover:text-green-800 dark:text-green-400" title="Baixar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                @endif
                                @if($_podeCancelar && $conta->status !== 'cancelado' && $conta->status !== 'pago')
                                    <a href="{{ route('financeiro.contas-receber.edit', $conta) }}#cancelar" class="text-orange-600 hover:text-orange-800 dark:text-orange-400" title="Cancelar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($_podeExcluir && ($conta->status !== 'pago' || $conta->valor_recebido == 0))
                                    <button type="button" onclick="abrirModalExcluirConta('{{ route('financeiro.contas-receber.destroy', $conta) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma conta a receber encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $contas->links() }}
    </div>
</div>

<!-- Modal Excluir Conta (motivo obrigatório) -->
<div id="modalExcluirConta" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Excluir Conta a Receber</h2>
            <button type="button" onclick="fecharModalExcluirConta()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formExcluirContaIndex" action="" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Exclusão * (mín. 10 caracteres)</label>
                <textarea name="motivo" id="motivoExcluirIndex" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalExcluirConta()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'conta_receber'); ?>
@endif

@else
{{-- Aba Recorrentes --}}
@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">{{ session('error') }}</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.contas-receber.index') }}" class="flex flex-wrap items-end gap-4">
        <input type="hidden" name="aba" value="recorrentes">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>
            <select name="rec_cliente_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ request('rec_cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nome ?: $c->razao_social }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <select name="rec_tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="aluguel" {{ request('rec_tipo') === 'aluguel' ? 'selected' : '' }}>Aluguel</option>
                <option value="contrato" {{ request('rec_tipo') === 'contrato' ? 'selected' : '' }}>Contrato</option>
                <option value="assinatura" {{ request('rec_tipo') === 'assinatura' ? 'selected' : '' }}>Assinatura</option>
                <option value="manutencao" {{ request('rec_tipo') === 'manutencao' ? 'selected' : '' }}>Manutenção</option>
                <option value="outro" {{ request('rec_tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Frequência</label>
            <select name="rec_frequencia" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                <option value="diaria" {{ request('rec_frequencia') === 'diaria' ? 'selected' : '' }}>Diária</option>
                <option value="semanal" {{ request('rec_frequencia') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                <option value="quinzenal" {{ request('rec_frequencia') === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                <option value="mensal" {{ request('rec_frequencia') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                <option value="bimestral" {{ request('rec_frequencia') === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                <option value="trimestral" {{ request('rec_frequencia') === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                <option value="semestral" {{ request('rec_frequencia') === 'semestral' ? 'selected' : '' }}>Semestral</option>
                <option value="anual" {{ request('rec_frequencia') === 'anual' ? 'selected' : '' }}>Anual</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="rec_ativo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="1" {{ request('rec_ativo') === '1' ? 'selected' : '' }}>Ativos</option>
                <option value="0" {{ request('rec_ativo') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('financeiro.contas-receber.index', ['aba' => 'recorrentes']) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Frequência</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Próxima geração</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recorrentes ?? [] as $r)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->cliente->nome ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->descricao }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\PagamentoRecorrente::tipoLabel($r->tipo) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($r->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\PagamentoRecorrente::frequenciaLabel($r->frequencia) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $r->proxima_geracao_em->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $r->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $r->ativo ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="abrirModalLancamentosRecorrente({{ $r->cliente_id }}, '{{ addslashes($r->cliente->nome ?? '') }}')" class="text-gray-600 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 mr-1" title="Ver todos os lançamentos (contas a receber) gerados por recorrentes deste cliente">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                <span class="sr-only">Ver lançamentos</span>
                            </button>
                            <a href="{{ route('financeiro.pagamentos-recorrentes.edit', $r) }}?voltar={{ urlencode(route('financeiro.contas-receber.index', ['aba' => 'recorrentes'])) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button type="button" onclick="abrirModalExcluirRecorrente('{{ route('financeiro.pagamentos-recorrentes.destroy', $r) }}?voltar={{ urlencode(route('financeiro.contas-receber.index', ['aba' => 'recorrentes'])) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                                    <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum pagamento recorrente cadastrado. Use &quot;Novo Recorrente&quot; para criar aluguéis, assinaturas ou contratos com geração automática de contas a receber.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recorrentes && $recorrentes->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $recorrentes->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<p class="mt-4 text-xs text-gray-500 dark:text-gray-400">As contas a receber são criadas automaticamente pelo agendamento diário (comando <code>financeiro:gerar-recorrentes</code>). Aluguéis, contratos e assinaturas podem usar frequências flexíveis (semanal a anual).</p>

<x-modal-excluir-motivo action="" idModal="modalExcluirRecorrente" idForm="formExcluirRecorrente" idMotivo="motivoExcluirRecorrente" titulo="Excluir pagamento recorrente" descricao="Tem certeza que deseja excluir este pagamento recorrente? Informe o motivo." />
<script>
function abrirModalExcluirRecorrente(url) {
    document.getElementById('formExcluirRecorrente').action = url;
    document.getElementById('motivoExcluirRecorrente').value = '';
    document.getElementById('modalExcluirRecorrente').classList.remove('hidden');
    document.getElementById('modalExcluirRecorrente').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
</script>
@endif

<!-- Modal Lançamentos Recorrentes do Cliente (aba Recorrentes) -->
<div id="modalLancamentosRecorrente" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 id="modalLancamentosRecorrenteTitulo" class="text-lg font-semibold text-gray-800 dark:text-white/90">Lançamentos recorrentes</h2>
            <button type="button" onclick="fecharModalLancamentosRecorrente()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modalLancamentosRecorrenteBody" class="p-6">
            <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Carregando...</p>
        </div>
    </div>
</div>

<!-- Modal de Baixa em Massa -->
<div id="modalBaixaMassa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Baixar Contas em Massa</h2>
            <button type="button" onclick="fecharModalBaixaMassa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formBaixaMassa" action="{{ route('financeiro.contas-receber.baixar-massa') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                <p><strong id="contadorBaixaMassa">0</strong> conta(s) serão baixadas.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Baixa *</label>
                    <input type="date" name="data_baixa" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                    <select name="conta_bancaria_id" id="conta_bancaria_baixa_massa" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(\App\Models\ContaBancaria::where('ativo', true)->orderBy('nome')->get() as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="forma_pagamento_id" id="forma_pagamento_baixa_massa" required onchange="calcularTaxaBaixaMassa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo }}" data-taxa-percentual="{{ $forma->taxa_percentual }}" data-taxa-fixa="{{ $forma->taxa_fixa }}" data-pix-chaves="{{ json_encode($forma->pix_chaves_ativas ?? [], JSON_UNESCAPED_UNICODE) }}">{{ $forma->nome }}@if($forma->taxa_percentual > 0 || $forma->taxa_fixa > 0) (Taxa: {{ number_format($forma->taxa_percentual, 2, ',', '.') }}% + R$ {{ number_format($forma->taxa_fixa, 2, ',', '.') }})@endif</option>
                        @endforeach
                    </select>
                    <div id="taxa_info_massa" class="mt-2 hidden rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        <p class="font-medium" id="taxa_info_massa_texto">Taxa será descontada automaticamente de cada conta.</p>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <input type="checkbox" name="ignorar_taxa" id="ignorar_taxa_baixa_massa" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600" onchange="calcularTaxaBaixaMassa()">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Não descontar taxa da forma</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Para valores já líquidos (ex.: importação MapOS).</span>
                        </span>
                    </label>
                </div>
                <div id="pix_chave_wrap_massa" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Chave PIX de recebimento</label>
                    <select name="pix_chave_id" id="pix_chave_baixa_massa" onchange="calcularTaxaBaixaMassa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                @include('financeiro.partials.conta-receber-cartao-bloco', [
                    'prefix' => 'cr_massa',
                    'bandeirasCartao' => $bandeirasCartao,
                    'formasCartaoMeta' => $formasCartaoMeta,
                    'cfg' => [
                        'formaSel' => '#forma_pagamento_baixa_massa',
                        'valorSel' => 'body',
                        'contaBancariaSel' => '#conta_bancaria_baixa_massa',
                        'previewDisable' => true,
                    ],
                ])
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Observações que serão aplicadas a todas as contas"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalBaixaMassa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Confirmar Baixa em Massa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Cancelamento em Massa -->
<div id="modalCancelarMassa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar Contas em Massa</h2>
            <button type="button" onclick="fecharModalCancelarMassa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formCancelarMassa" action="{{ route('financeiro.contas-receber.cancelar-massa') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4 rounded-lg bg-orange-50 p-3 text-sm text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                <p><strong id="contadorCancelarMassa">0</strong> conta(s) serão canceladas.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do Cancelamento *</label>
                <textarea name="motivo" rows="4" required placeholder="Descreva o motivo do cancelamento..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalCancelarMassa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Fechar</button>
                <button type="submit" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-700 shadow-sm">Confirmar Cancelamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Exclusão em Massa -->
<div id="modalExcluirMassa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Excluir Contas em Massa</h2>
            <button type="button" onclick="fecharModalExcluirMassa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formExcluirMassa" action="{{ route('financeiro.contas-receber.excluir-massa') }}" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900 dark:text-red-300">
                <p class="font-medium">Atenção! Esta ação não pode ser desfeita.</p>
                <p class="mt-1"><strong id="contadorExcluirMassa">0</strong> conta(s) serão excluídas permanentemente.</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalExcluirMassa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Baixa -->
<div id="modalBaixa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Baixar Conta</h2>
            <button type="button" onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formBaixa" method="POST" class="p-6">
            @csrf
            <input type="hidden" id="baixa_idx_total_parcelas" value="1">
            <input type="hidden" id="baixa_idx_adquirente_default" value="">
            <input type="hidden" id="baixa_idx_bandeira_default" value="">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da Baixa *</label>
                    <input type="number" step="0.01" name="valor_baixa" id="valor_baixa" required oninput="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500" id="max_valor_text"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Baixa *</label>
                    <input type="date" name="data_baixa" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                    <select name="conta_bancaria_id" id="conta_bancaria_baixa" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(\App\Models\ContaBancaria::where('ativo', true)->orderBy('nome')->get() as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="forma_pagamento_id" id="forma_pagamento_baixa" required onchange="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo }}" data-taxa-percentual="{{ $forma->taxa_percentual }}" data-taxa-fixa="{{ $forma->taxa_fixa }}" data-pix-chaves="{{ json_encode($forma->pix_chaves_ativas ?? [], JSON_UNESCAPED_UNICODE) }}">{{ $forma->nome }}@if($forma->taxa_percentual > 0 || $forma->taxa_fixa > 0) (Taxa: {{ number_format($forma->taxa_percentual, 2, ',', '.') }}% + R$ {{ number_format($forma->taxa_fixa, 2, ',', '.') }})@endif</option>
                        @endforeach
                    </select>
                    <div id="taxa_info" class="mt-2 hidden rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        <p class="font-medium">Taxa será descontada automaticamente:</p>
                        <p id="taxa_valor" class="mt-1"></p>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <input type="checkbox" name="ignorar_taxa" id="ignorar_taxa_baixa" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600" onchange="calcularTaxaBaixa()">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Não descontar taxa da forma de pagamento</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Valor já líquido (ex.: importação MapOS).</span>
                        </span>
                    </label>
                </div>
                <div id="pix_chave_wrap_baixa" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Chave PIX de recebimento</label>
                    <select name="pix_chave_id" id="pix_chave_baixa" onchange="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                @include('financeiro.partials.conta-receber-cartao-bloco', [
                    'prefix' => 'cr_idx_baixa',
                    'bandeirasCartao' => $bandeirasCartao,
                    'formasCartaoMeta' => $formasCartaoMeta,
                    'cfg' => [
                        'formaSel' => '#forma_pagamento_baixa',
                        'valorSel' => '#valor_baixa',
                        'parcelasHiddenSel' => '#baixa_idx_total_parcelas',
                        'dataRefSel' => '#formBaixa input[name="data_baixa"]',
                        'defaultAdquirenteHiddenSel' => '#baixa_idx_adquirente_default',
                        'defaultBandeiraHiddenSel' => '#baixa_idx_bandeira_default',
                        'contaBancariaSel' => '#conta_bancaria_baixa',
                    ],
                ])
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros</label>
                        <input type="number" step="0.01" name="juros" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Multa</label>
                        <input type="number" step="0.01" name="multa" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                        <input type="number" step="0.01" name="desconto" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalBaixa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Confirmar Baixa</button>
            </div>
        </form>
    </div>
</div>

<script>
let valorPendenteAtual = 0;
let agrupandoReceberEmAndamento = false;

/** @returns {string|null} mensagem de erro ou null se ok */
function validarPreAgrupamentoReceberRows(rows) {
    if (!rows || rows.length < 2) {
        return 'Selecione pelo menos 2 contas para agrupar.';
    }
    const allowedStatus = ['aberto', 'parcial', 'vencido'];
    const allowedEstrutura = ['normal', 'desmembrado_filho'];
    const selectedIds = rows.map((r) => parseInt(r.dataset.contaId, 10)).filter((n) => !isNaN(n));
    const selectedSet = new Set(selectedIds);

    for (const row of rows) {
        const st = row.dataset.status || '';
        if (!allowedStatus.includes(st)) {
            return 'Não é possível agrupar contas que não estejam em aberto, parciais ou vencidas (em aberto). Remova títulos pagos ou cancelados da seleção.';
        }
        if ((row.dataset.statusEstrutura || '') === 'agrupado') {
            return 'Não é possível agrupar contas já vinculadas a um lote. Desfaça o agrupamento primeiro ou remova essas contas da seleção.';
        }
        const et = row.dataset.estruturaTipo || 'normal';
        if (!allowedEstrutura.includes(et)) {
            return 'É possível agrupar apenas títulos avulsos ou as parcelas de um desmembramento. Retire da seleção lotes já agrupados e o título principal (pai) do desmembramento; mantenha só parcelas filhas ou contas comuns.';
        }
    }

    const clienteId = rows[0].dataset.clienteId;
    if (rows.some((r) => r.dataset.clienteId !== clienteId)) {
        return 'Somente contas do mesmo cliente podem ser agrupadas.';
    }

    const plano = rows[0].dataset.planoContaId ?? '';
    if (rows.some((r) => (r.dataset.planoContaId ?? '') !== plano)) {
        return 'Todas as contas devem ter o mesmo plano de conta para agrupar.';
    }

    const cb = rows[0].dataset.contaBancariaId ?? '';
    if (rows.some((r) => (r.dataset.contaBancariaId ?? '') !== cb)) {
        return 'Todas as contas devem ter a mesma conta bancária para agrupar.';
    }

    const paisDesmembramento = new Set();
    for (const row of rows) {
        if ((row.dataset.estruturaTipo || '') !== 'desmembrado_filho') continue;
        const pid = row.dataset.parentId || '';
        if (!pid) continue;
        paisDesmembramento.add(pid);
    }
    for (const pid of paisDesmembramento) {
        const ref = rows.find((r) => r.dataset.parentId === pid && (r.dataset.estruturaTipo || '') === 'desmembrado_filho');
        if (!ref) continue;
        const obr = (ref.dataset.desmembradoObrigatorios || '')
            .split(',')
            .map((s) => parseInt(String(s).trim(), 10))
            .filter((n) => !isNaN(n) && n > 0);
        if (obr.length === 0) continue;
        const faltando = obr.filter((id) => !selectedSet.has(id));
        if (faltando.length > 0) {
            return 'Para agrupar parcelas de um desmembramento, selecione todas as contas filhas em aberto desse desmembramento (' + obr.length + ' parcela(s), título pai #' + pid + ').';
        }
    }

    return null;
}

function abrirModalExcluirConta(destroyUrl) {
    document.getElementById('formExcluirContaIndex').action = destroyUrl;
    document.getElementById('motivoExcluirIndex').value = '';
    document.getElementById('modalExcluirConta').classList.remove('hidden');
    document.getElementById('modalExcluirConta').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalExcluirConta() {
    document.getElementById('modalExcluirConta').classList.add('hidden');
    document.getElementById('modalExcluirConta').classList.remove('flex');
    document.body.style.overflow = '';
}

// Funções de seleção
function selecionarTodos(checkbox) {
    const checkboxes = document.querySelectorAll('.checkbox-conta');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    atualizarBarraAcoes();
}

function limparSelecao() {
    document.getElementById('selecionarTodos').checked = false;
    const checkboxes = document.querySelectorAll('.checkbox-conta');
    checkboxes.forEach(cb => cb.checked = false);
    atualizarBarraAcoes();
}

function atualizarBarraAcoes() {
    const checkboxes = document.querySelectorAll('.checkbox-conta:checked');
    const contador = checkboxes.length;
    const barra = document.getElementById('barraAcoesMassa');
    const contadorSpan = document.getElementById('contadorSelecionados');
    
    if (contador > 0) {
        barra.classList.remove('hidden');
        contadorSpan.textContent = contador;
        
        // Verificar quais ações são possíveis
        let podeBaixar = false;
        let podeCancelar = false;
        let podeExcluir = false;
        
        checkboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (row.dataset.podeBaixar === '1') podeBaixar = true;
            if (row.dataset.podeCancelar === '1') podeCancelar = true;
            if (row.dataset.podeExcluir === '1') podeExcluir = true;
        });
        
        document.getElementById('btnBaixarMassa').classList.toggle('hidden', !podeBaixar);
        document.getElementById('btnCancelarMassa').classList.toggle('hidden', !podeCancelar);
        document.getElementById('btnExcluirMassa').classList.toggle('hidden', !podeExcluir);
        document.getElementById('btnAgruparMassa').classList.toggle('hidden', contador < 2);
    } else {
        barra.classList.add('hidden');
    }
}

function agruparSelecionadasReceber() {
    if (agrupandoReceberEmAndamento) return;
    const rows = Array.from(document.querySelectorAll('.checkbox-conta:checked'))
        .map((cb) => cb.closest('tr'))
        .filter(Boolean);
    const err = validarPreAgrupamentoReceberRows(rows);
    if (err) {
        if (typeof window.erpToast === 'function') window.erpToast(err, 'warning');
        else alert(err);
        return;
    }
    abrirModalAgruparReceber();
}

function abrirModalAgruparReceber() {
    document.getElementById('modalAgruparMassaReceber').classList.remove('hidden');
    document.getElementById('modalAgruparMassaReceber').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalAgruparReceber() {
    document.getElementById('modalAgruparMassaReceber').classList.add('hidden');
    document.getElementById('modalAgruparMassaReceber').classList.remove('flex');
    document.body.style.overflow = '';
}

const formAgruparMassaReceber = document.getElementById('formAgruparMassaReceber');
if (formAgruparMassaReceber) formAgruparMassaReceber.addEventListener('submit', function (e) {
    e.preventDefault();
    if (agrupandoReceberEmAndamento) return;

    const rows = Array.from(document.querySelectorAll('.checkbox-conta:checked'))
        .map((cb) => cb.closest('tr'))
        .filter(Boolean);
    const errPre = validarPreAgrupamentoReceberRows(rows);
    if (errPre) {
        if (typeof window.erpToast === 'function') window.erpToast(errPre, 'warning');
        else alert(errPre);
        fecharModalAgruparReceber();
        return;
    }
    const ids = rows.map((r) => String(r.dataset.contaId));

    const descricao = (document.getElementById('agruparReceberDescricao').value || '').trim();
    const dataVencimento = document.getElementById('agruparReceberDataVencimento').value;
    if (!descricao || !dataVencimento) {
        const msg = 'Preencha a descrição e a data de vencimento.';
        if (typeof window.erpToast === 'function') window.erpToast(msg, 'warning');
        else alert(msg);
        return;
    }

    agrupandoReceberEmAndamento = true;
    const botao = document.getElementById('btnAgruparMassa');
    if (botao) {
        botao.disabled = true;
        botao.classList.add('opacity-60', 'cursor-not-allowed');
    }
    const botaoConfirmar = document.getElementById('btnConfirmarAgruparReceber');
    if (botaoConfirmar) {
        botaoConfirmar.disabled = true;
        botaoConfirmar.classList.add('opacity-60', 'cursor-not-allowed');
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('financeiro.contas-receber.agrupar') }}';
    form.style.display = 'none';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    const d = document.createElement('input');
    d.type = 'hidden';
    d.name = 'descricao';
    d.value = descricao;
    form.appendChild(d);

    const dt = document.createElement('input');
    dt.type = 'hidden';
    dt.name = 'data_vencimento';
    dt.value = dataVencimento;
    form.appendChild(dt);

    ids.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
});

// Funções de ações em massa
function abrirModalBaixaMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-conta:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    const contador = ids.length;
    
    document.getElementById('contadorBaixaMassa').textContent = contador;
    
    // Adicionar campos hidden com os IDs
    const form = document.getElementById('formBaixaMassa');
    // Remover campos anteriores
    form.querySelectorAll('input[name="contas_ids[]"]').forEach(el => el.remove());
    // Adicionar novos
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'contas_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    const ignM = document.getElementById('ignorar_taxa_baixa_massa');
    if (ignM) ignM.checked = false;
    document.getElementById('modalBaixaMassa').classList.remove('hidden');
    document.getElementById('modalBaixaMassa').classList.add('flex');
    document.body.style.overflow = 'hidden';
    calcularTaxaBaixaMassa();
}

function fecharModalBaixaMassa() {
    document.getElementById('modalBaixaMassa').classList.add('hidden');
    document.getElementById('modalBaixaMassa').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalCancelarMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-conta:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    const contador = ids.length;
    
    document.getElementById('contadorCancelarMassa').textContent = contador;
    
    const form = document.getElementById('formCancelarMassa');
    form.querySelectorAll('input[name="contas_ids[]"]').forEach(el => el.remove());
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'contas_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.getElementById('modalCancelarMassa').classList.remove('hidden');
    document.getElementById('modalCancelarMassa').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalCancelarMassa() {
    document.getElementById('modalCancelarMassa').classList.add('hidden');
    document.getElementById('modalCancelarMassa').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalExcluirMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-conta:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    const contador = ids.length;
    
    document.getElementById('contadorExcluirMassa').textContent = contador;
    
    const form = document.getElementById('formExcluirMassa');
    form.querySelectorAll('input[name="contas_ids[]"]').forEach(el => el.remove());
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'contas_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.getElementById('modalExcluirMassa').classList.remove('hidden');
    document.getElementById('modalExcluirMassa').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalExcluirMassa() {
    document.getElementById('modalExcluirMassa').classList.add('hidden');
    document.getElementById('modalExcluirMassa').classList.remove('flex');
    document.body.style.overflow = '';
}

function calcularTaxaBaixaMassa() {
    const formaSelect = document.getElementById('forma_pagamento_baixa_massa');
    const taxaInfo = document.getElementById('taxa_info_massa');
    const taxaTxt = document.getElementById('taxa_info_massa_texto');
    const ignorarTaxa = document.getElementById('ignorar_taxa_baixa_massa')?.checked;

    if (!formaSelect.value) {
        taxaInfo.classList.add('hidden');
        return;
    }

    if (ignorarTaxa) {
        if (taxaTxt) taxaTxt.textContent = 'Taxa da forma não será descontada em nenhuma conta (valor tratado como líquido).';
        taxaInfo.classList.remove('hidden');
        return;
    }

    const selectedOption = formaSelect.options[formaSelect.selectedIndex];
    const pixWrap = document.getElementById('pix_chave_wrap_massa');
    const pixSelect = document.getElementById('pix_chave_baixa_massa');
    const tipoFp = (selectedOption.getAttribute('data-tipo') || '').trim().toLowerCase();
    if (pixWrap && pixSelect) {
        pixWrap.classList.toggle('hidden', tipoFp !== 'pix');
        const selectedPixId = pixSelect.value;
        if (tipoFp === 'pix') {
            let chaves = [];
            try { chaves = JSON.parse(selectedOption.getAttribute('data-pix-chaves') || '[]'); } catch (e) { chaves = []; }
            pixSelect.innerHTML = '<option value="">Selecione...</option>';
            chaves.forEach((k) => {
                const opt = document.createElement('option');
                opt.value = k.id || '';
                opt.textContent = `${k.nome || 'Chave PIX'} (${k.chave || ''})`;
                opt.dataset.contaBancariaId = k.conta_bancaria_id || '';
                opt.dataset.taxaPercentual = k.taxa_percentual || 0;
                opt.dataset.taxaFixa = k.taxa_fixa || 0;
                pixSelect.appendChild(opt);
            });
            if (selectedPixId) pixSelect.value = selectedPixId;
        }
        const pixOpt = pixSelect.selectedOptions?.[0];
        if (pixOpt?.dataset?.contaBancariaId) {
            const cb = document.getElementById('conta_bancaria_baixa_massa');
            if (cb) cb.value = pixOpt.dataset.contaBancariaId;
        }
    }
    if (tipoFp === 'cartao_credito' || tipoFp === 'cartao_debito') {
        if (taxaTxt) taxaTxt.textContent = 'Cartão: em cada conta a taxa segue adquirente/bandeira/parcelas cadastrados na própria conta (ou o informado abaixo), não a % genérica da forma.';
        taxaInfo.classList.remove('hidden');
        return;
    }

    let taxaPercentual = parseFloat(selectedOption.getAttribute('data-taxa-percentual') || 0);
    let taxaFixa = parseFloat(selectedOption.getAttribute('data-taxa-fixa') || 0);
    if (tipoFp === 'pix' && pixSelect && pixSelect.value) {
        const p = pixSelect.selectedOptions[0];
        taxaPercentual = parseFloat(p?.dataset?.taxaPercentual || taxaPercentual || 0);
        taxaFixa = parseFloat(p?.dataset?.taxaFixa || taxaFixa || 0);
    }

    if (taxaTxt) taxaTxt.textContent = 'Taxa será descontada automaticamente de cada conta.';

    if (taxaPercentual > 0 || taxaFixa > 0) {
        taxaInfo.classList.remove('hidden');
    } else {
        taxaInfo.classList.add('hidden');
    }
}

// Funções individuais
function abrirModalBaixa(contaId, valorPendente) {
    valorPendenteAtual = valorPendente;
    var row = document.querySelector('tr[data-conta-id="' + contaId + '"]');
    var jurosSug = row ? parseFloat(row.getAttribute('data-juros-sugerido') || 0) : 0;
    var multaSug = row ? parseFloat(row.getAttribute('data-multa-sugerido') || 0) : 0;
    document.getElementById('formBaixa').action = `/financeiro/contas-receber/${contaId}/baixar`;
    document.getElementById('valor_baixa').value = valorPendente.toFixed(2);
    document.getElementById('valor_baixa').max = valorPendente;
    document.getElementById('max_valor_text').textContent = 'Máximo: R$ ' + valorPendente.toFixed(2).replace('.', ',');
    var jurosInput = document.querySelector('#formBaixa input[name="juros"]');
    var multaInput = document.querySelector('#formBaixa input[name="multa"]');
    if (jurosInput) jurosInput.value = jurosSug.toFixed(2);
    if (multaInput) multaInput.value = multaSug.toFixed(2);
    const ign = document.getElementById('ignorar_taxa_baixa');
    if (ign) ign.checked = false;
    var hidTp = document.getElementById('baixa_idx_total_parcelas');
    if (hidTp) hidTp.value = row ? (row.getAttribute('data-total-parcelas') || '1') : '1';
    var ha = document.getElementById('baixa_idx_adquirente_default');
    if (ha) ha.value = row ? (row.getAttribute('data-adquirente-id') || '') : '';
    var hb = document.getElementById('baixa_idx_bandeira_default');
    if (hb) hb.value = row ? (row.getAttribute('data-bandeira') || '') : '';
    var fp = document.getElementById('forma_pagamento_baixa');
    var cb = document.getElementById('conta_bancaria_baixa');
    var formaId = row ? (row.getAttribute('data-forma-pagamento-id') || '') : '';
    var cbId = row ? (row.getAttribute('data-conta-bancaria-id') || '') : '';
    if (fp) {
        fp.value = '';
        if (formaId) {
            var opt = fp.querySelector('option[value="' + formaId + '"]');
            if (opt) fp.value = formaId;
        }
    }
    if (cb) {
        cb.value = '';
        if (cbId) {
            var ocb = cb.querySelector('option[value="' + cbId + '"]');
            if (ocb) cb.value = cbId;
        }
    }
    document.getElementById('modalBaixa').classList.remove('hidden');
    document.getElementById('modalBaixa').classList.add('flex');
    document.body.style.overflow = 'hidden';
    calcularTaxaBaixa();
    if (fp) fp.dispatchEvent(new Event('change'));
}

function fecharModalBaixa() {
    document.getElementById('modalBaixa').classList.add('hidden');
    document.getElementById('modalBaixa').classList.remove('flex');
    document.body.style.overflow = '';
    document.getElementById('taxa_info').classList.add('hidden');
}

function calcularTaxaBaixa() {
    const formaSelect = document.getElementById('forma_pagamento_baixa');
    const valorBaixaInput = document.getElementById('valor_baixa');
    const taxaInfo = document.getElementById('taxa_info');
    const taxaValor = document.getElementById('taxa_valor');
    const ignorarTaxa = document.getElementById('ignorar_taxa_baixa')?.checked;

    let selectedOption = null;
    let tipoFp = '';
    if (formaSelect && formaSelect.value) {
        selectedOption = formaSelect.options[formaSelect.selectedIndex];
        tipoFp = (selectedOption.getAttribute('data-tipo') || '').trim().toLowerCase();
    }

    const pixWrap = document.getElementById('pix_chave_wrap_baixa');
    const pixSelect = document.getElementById('pix_chave_baixa');
    if (pixWrap && pixSelect) {
        if (!formaSelect.value || !selectedOption) {
            pixWrap.classList.add('hidden');
            pixSelect.innerHTML = '<option value="">Selecione...</option>';
        } else {
            pixWrap.classList.toggle('hidden', tipoFp !== 'pix');
            const selectedPixId = pixSelect.value;
            if (tipoFp === 'pix') {
                let chaves = [];
                try { chaves = JSON.parse(selectedOption.getAttribute('data-pix-chaves') || '[]'); } catch (e) { chaves = []; }
                pixSelect.innerHTML = '<option value="">Selecione...</option>';
                chaves.forEach((k) => {
                    const opt = document.createElement('option');
                    opt.value = k.id || '';
                    opt.textContent = `${k.nome || 'Chave PIX'} (${k.chave || ''})`;
                    opt.dataset.contaBancariaId = k.conta_bancaria_id || '';
                    opt.dataset.taxaPercentual = k.taxa_percentual || 0;
                    opt.dataset.taxaFixa = k.taxa_fixa || 0;
                    pixSelect.appendChild(opt);
                });
                if (selectedPixId) pixSelect.value = selectedPixId;
            } else {
                pixSelect.innerHTML = '<option value="">Selecione...</option>';
            }
            const pixOpt = pixSelect.selectedOptions?.[0];
            if (pixOpt?.dataset?.contaBancariaId) {
                const cb = document.getElementById('conta_bancaria_baixa');
                if (cb) cb.value = pixOpt.dataset.contaBancariaId;
            }
        }
    }

    if (!formaSelect.value || !valorBaixaInput.value) {
        taxaInfo.classList.add('hidden');
        return;
    }

    const valorBaixa = parseFloat(valorBaixaInput.value || 0);

    if (ignorarTaxa) {
        taxaValor.innerHTML = `
            <strong>Sem desconto de taxa da forma.</strong> Entrada no caixa: R$ ${valorBaixa.toFixed(2).replace('.', ',')}
            <span class="mt-1 block text-xs">(Valor tratado como líquido — ex. importação MapOS.)</span>
        `;
        taxaInfo.classList.remove('hidden');
        return;
    }
    if (tipoFp === 'cartao_credito' || tipoFp === 'cartao_debito') {
        taxaValor.innerHTML = `
            <strong>Cartão:</strong> a taxa descontada na baixa <strong>não</strong> é a porcentagem genérica exibida no nome da forma; ela é calculada pelo <strong>adquirente</strong>, <strong>bandeira</strong> e <strong>número de parcelas</strong> da conta (igual ao cadastro). Veja a <strong>prévia</strong> no bloco &quot;Cartão&quot; abaixo ao confirmar adquirente e bandeira.
        `;
        taxaInfo.classList.remove('hidden');
        return;
    }

    let taxaPercentual = parseFloat(selectedOption.getAttribute('data-taxa-percentual') || 0);
    let taxaFixa = parseFloat(selectedOption.getAttribute('data-taxa-fixa') || 0);
    if (tipoFp === 'pix' && pixSelect && pixSelect.value) {
        const p = pixSelect.selectedOptions[0];
        taxaPercentual = parseFloat(p?.dataset?.taxaPercentual || taxaPercentual || 0);
        taxaFixa = parseFloat(p?.dataset?.taxaFixa || taxaFixa || 0);
    }

    if (taxaPercentual > 0 || taxaFixa > 0) {
        const taxa = (valorBaixa * taxaPercentual / 100) + taxaFixa;
        const valorLiquido = valorBaixa - taxa;

        taxaValor.innerHTML = `
            <strong>Valor bruto:</strong> R$ ${valorBaixa.toFixed(2).replace('.', ',')}<br>
            <strong>Taxa:</strong> R$ ${taxa.toFixed(2).replace('.', ',')}<br>
            <strong>Valor líquido a receber:</strong> R$ ${valorLiquido.toFixed(2).replace('.', ',')}
        `;
        taxaInfo.classList.remove('hidden');
    } else {
        taxaInfo.classList.add('hidden');
    }
}

// Modal Lançamentos Recorrentes do Cliente (aba Recorrentes)
function abrirModalLancamentosRecorrente(clienteId, clienteNome) {
    var modal = document.getElementById('modalLancamentosRecorrente');
    var titulo = document.getElementById('modalLancamentosRecorrenteTitulo');
    var body = document.getElementById('modalLancamentosRecorrenteBody');
    if (!modal || !titulo || !body) return;
    titulo.textContent = 'Lançamentos recorrentes — ' + (clienteNome || 'Cliente');
    body.innerHTML = '<p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Carregando...</p>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    fetch('{{ route("financeiro.contas-receber.lancamentos-recorrentes-cliente") }}?cliente_id=' + encodeURIComponent(clienteId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(data) {
        body.innerHTML = data.html || '<p class="py-4 text-center text-sm text-gray-500">Nenhum lançamento encontrado.</p>';
    }).catch(function() {
        body.innerHTML = '<p class="py-4 text-center text-sm text-red-600 dark:text-red-400">Erro ao carregar. Tente novamente.</p>';
    });
}
function fecharModalLancamentosRecorrente() {
    var modal = document.getElementById('modalLancamentosRecorrente');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.style.overflow = '';
}
</script>
@endsection
