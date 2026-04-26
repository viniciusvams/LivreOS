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
    $_podeBaixar  = $_auditSvc->canBaixar(auth()->user(), 'conta_pagar');
    $_podeEstornar= $_auditSvc->canEstornar(auth()->user(), 'conta_pagar');
    $_podeCancelar= $_auditSvc->canCancel(auth()->user(), 'conta_pagar');
    $_podeExcluir = $_auditSvc->canExcluir(auth()->user(), 'conta_pagar');
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Contas a Pagar</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as contas a pagar e as despesas recorrentes (água, luz, aluguel, condomínio)</p>
    </div>
    <div class="flex items-center gap-2">
        @if($aba === 'contas')
            <a href="{{ route('financeiro.contas-pagar.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="Exportar PDF com filtros atuais">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar PDF
            </a>
        @endif
        @if($aba === 'recorrentes')
            <a href="{{ route('financeiro.contas-pagar-recorrentes.create', ['voltar' => route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])]) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nova despesa recorrente
            </a>
        @else
            <a href="{{ route('financeiro.contas-pagar.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
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

<!-- Abas Contas | Recorrentes -->
<nav class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <div class="flex gap-1">
        <a href="{{ route('financeiro.contas-pagar.index') }}" class="border-b-2 px-4 py-3 text-sm font-medium {{ $aba === 'contas' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Contas
        </a>
        <a href="{{ route('financeiro.contas-pagar.index', ['aba' => 'recorrentes']) }}" class="border-b-2 px-4 py-3 text-sm font-medium {{ $aba === 'recorrentes' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
            Despesas recorrentes
        </a>
    </div>
</nav>

@if($aba === 'contas')
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <a href="{{ route('financeiro.contas-pagar.index', ['pendentes' => 1]) }}" class="group rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pendente</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['totalPendente'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['qtdPendente'] }} título(s)</p>
    </a>

    <a href="{{ route('financeiro.contas-pagar.index', ['vencidas' => 1]) }}" class="group rounded-xl border border-red-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-red-900/50 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-red-600 dark:text-red-400">Vencido</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-red-600 dark:text-red-400">R$ {{ number_format($indicadores['totalVencido'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['qtdVencido'] }} título(s)</p>
    </a>

    <a href="{{ route('financeiro.contas-pagar.index', ['a_vencer' => 1]) }}" class="group rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">A Vencer</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['totalAVencer'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $indicadores['vencendo7dias'] }} vence(m) em 7 dias</p>
    </a>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Pago (mês)</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-green-600 dark:text-green-400">R$ {{ number_format($indicadores['pagoMes'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('F/Y') }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ticket Médio</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            </span>
        </div>
        <p class="text-lg font-bold text-gray-800 dark:text-white/90">R$ {{ number_format($indicadores['ticketMedio'], 2, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Média pagamentos do mês</p>
    </div>
</div>

<!-- Filtros -->
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.contas-pagar.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <option value="aberto" {{ request('status') === 'aberto' ? 'selected' : '' }}>Aberto</option>
                    <option value="parcial" {{ request('status') === 'parcial' ? 'selected' : '' }}>Parcial</option>
                    <option value="vencido" {{ request('status') === 'vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="pago" {{ request('status') === 'pago' ? 'selected' : '' }}>Pago</option>
                    <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
                <select name="fornecedor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($fornecedores as $fornecedor)
                        <option value="{{ $fornecedor->id }}" {{ request('fornecedor_id') == $fornecedor->id ? 'selected' : '' }}>{{ $fornecedor->nome ?? $fornecedor->razao_social ?? '#' . $fornecedor->id }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <option value="operacional" {{ request('tipo') === 'operacional' ? 'selected' : '' }}>Operacional</option>
                    <option value="insumo" {{ request('tipo') === 'insumo' ? 'selected' : '' }}>Insumo</option>
                    <option value="outro" {{ request('tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
                </select>
            </div>
            <div>
                <div class="mb-1 flex items-center gap-1.5">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Plano de contas</span>
                    <button type="button" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-gray-50 text-[10px] font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="O que é plano de contas?" aria-expanded="false" aria-controls="help-filtro-plano-pagar" onclick="(function(el){ var p=document.getElementById('help-filtro-plano-pagar'); p.classList.toggle('hidden'); el.setAttribute('aria-expanded', p.classList.contains('hidden')?'false':'true'); })(this)">?</button>
                </div>
                <p id="help-filtro-plano-pagar" class="mb-2 hidden rounded-md border border-gray-100 bg-gray-50 p-2 text-xs leading-relaxed text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400" role="note">O <strong>plano de contas</strong> classifica a <em>natureza</em> da despesa (ex.: material, serviços, impostos). Serve para relatórios gerenciais e visão por rubrica no resultado. Ao filtrar, você vê só títulos vinculados à conta analítica escolhida.</p>
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
                    <button type="button" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-gray-50 text-[10px] font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" title="O que é centro de custo?" aria-expanded="false" aria-controls="help-filtro-cc-pagar" onclick="(function(el){ var p=document.getElementById('help-filtro-cc-pagar'); p.classList.toggle('hidden'); el.setAttribute('aria-expanded', p.classList.contains('hidden')?'false':'true'); })(this)">?</button>
                </div>
                <p id="help-filtro-cc-pagar" class="mb-2 hidden rounded-md border border-gray-100 bg-gray-50 p-2 text-xs leading-relaxed text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400" role="note">O <strong>centro de custo</strong> indica <em>onde</em> o gasto se aloca (área, projeto ou departamento), e não a natureza contábil. Use para acompanhar despesas por unidade ou obra. É complementar ao plano de contas: um mesmo plano pode aparecer em vários centros.</p>
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
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Forma de pagamento</label>
                <select name="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($formasPagamento ?? [] as $forma)
                        <option value="{{ $forma->id }}" {{ request('forma_pagamento_id') == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conta bancária</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($contasBancarias ?? [] as $conta)
                        <option value="{{ $conta->id }}" {{ request('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
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
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Buscar (descrição, documento ou observações)</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Digite para buscar..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
                        <option value="data_vencimento" {{ request('ordenar', 'data_vencimento') === 'data_vencimento' ? 'selected' : '' }}>Vencimento</option>
                        <option value="valor" {{ request('ordenar') === 'valor' ? 'selected' : '' }}>Valor</option>
                        <option value="fornecedor_id" {{ request('ordenar') === 'fornecedor_id' ? 'selected' : '' }}>Fornecedor</option>
                        <option value="created_at" {{ request('ordenar') === 'created_at' ? 'selected' : '' }}>Data criação</option>
                    </select>
                    <select name="ordenar_direcao" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="asc" {{ request('ordenar_direcao', 'asc') === 'asc' ? 'selected' : '' }}>Asc</option>
                        <option value="desc" {{ request('ordenar_direcao') === 'desc' ? 'selected' : '' }}>Desc</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
            <span class="text-gray-400 dark:text-gray-500">|</span>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Atalhos:</span>
            <button type="submit" name="pendentes" value="1" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Pendentes</button>
            <button type="submit" name="a_vencer" value="1" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">A vencer</button>
            <button type="submit" name="vencidas" value="1" class="rounded-lg border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100 dark:border-orange-700 dark:bg-orange-900 dark:text-orange-300">Vencidas</button>
            <span class="text-gray-400 dark:text-gray-500">|</span>
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <span>Por página:</span>
                <select name="per_page" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </label>
        </div>
        @if(request()->hasAny(['status', 'fornecedor_id', 'tipo', 'plano_conta_id', 'centro_custo_id', 'categoria_financeira_id', 'tag_id', 'forma_pagamento_id', 'conta_bancaria_id', 'data_inicio', 'data_fim', 'q', 'valor_min', 'valor_max', 'vencidas', 'a_vencer', 'pendentes']))
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Filtros ativos. Use "Limpar" para ver todas as contas.</p>
        @endif
    </form>
</div>

<div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/30">
    <form method="POST" action="{{ route('financeiro.contas-pagar.agrupar') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4" onsubmit="return submeterAgrupamentoManualPagar(this)">
        @csrf
        <input type="text" name="ids_texto" placeholder="IDs para agrupar (ex: 10,11,12)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="text" name="descricao" placeholder="Descricao do lote" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="date" name="data_vencimento" value="{{ now()->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Agrupar (Espelho Total)</button>
    </form>
</div>

<div id="barraAcoesMassaPagar" class="mb-4 hidden rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-theme-sm dark:border-indigo-800 dark:bg-indigo-900">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
            <span id="contadorSelecionadosPagar">0</span> conta(s) selecionada(s)
        </span>
        <div class="flex items-center gap-2">
            <button type="button" onclick="agruparSelecionadasPagar()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Agrupar Selecionadas (Espelho Total)
            </button>
            <button type="button" onclick="limparSelecaoPagar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Limpar
            </button>
        </div>
    </div>
</div>

<!-- Modal Agrupar em Massa (Pagar) -->
<div id="modalAgruparMassaPagar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Agrupar Contas Selecionadas</h2>
            <button type="button" onclick="fecharModalAgruparPagar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formAgruparMassaPagar" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição do lote *</label>
                    <input type="text" id="agruparPagarDescricao" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de vencimento do lote *</label>
                    <input type="date" id="agruparPagarDataVencimento" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalAgruparPagar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" id="btnConfirmarAgruparPagar" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Confirmar Agrupamento</button>
            </div>
        </form>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'conta_pagar'); ?>
@endif

<!-- Tabela -->
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400" style="width: 40px;">
                        <input type="checkbox" id="selecionarTodosPagar" onchange="selecionarTodosPagar(this)" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Fornecedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Doc.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Pago</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Pendente</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($contas as $conta)
                    @php
                        $sugCp = \App\Http\Controllers\Financeiro\ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos((float)($conta->valor_original ?? $conta->valor), $conta->data_vencimento->format('Y-m-d'));
                        $desmembradoObrigatoriosCp = '';
                        if (($conta->estrutura_tipo ?? '') === 'desmembrado_filho' && $conta->parent_id) {
                            $desmembradoObrigatoriosCp = \App\Models\ContaPagar::query()
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
                        data-fornecedor-id="{{ $conta->fornecedor_id }}"
                        data-plano-conta-id="{{ $conta->plano_conta_id ?? '' }}"
                        data-conta-bancaria-id="{{ $conta->conta_bancaria_id ?? '' }}"
                        data-parent-id="{{ $conta->parent_id ?? '' }}"
                        data-desmembrado-obrigatorios="{{ $desmembradoObrigatoriosCp }}"
                        data-valor-pendente="{{ number_format($conta->valor_pendente, 2, '.', '') }}"
                        data-juros-sugerido="{{ $sugCp['juros'] }}"
                        data-multa-sugerido="{{ $sugCp['multa'] }}">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" class="checkbox-conta-pagar h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="{{ $conta->id }}" onchange="atualizarBarraAcoesPagar()">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $conta->fornecedor->nome ?? $conta->fornecedor->razao_social ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $conta->descricao }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $conta->numero_documento ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $conta->data_vencimento->format('d/m/Y') }}
                            @if($conta->esta_vencido)
                                <span class="ml-1 text-xs text-red-600 dark:text-red-400">(Vencida)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($conta->valor_pago, 2, ',', '.') }}</td>
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
                                <a href="{{ route('financeiro.contas-pagar.edit', $conta) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($_podeBaixar && $conta->status !== 'pago' && $conta->status !== 'cancelado')
                                    <button type="button" onclick="abrirModalBaixa({{ $conta->id }}, {{ number_format($conta->valor_pendente, 2, '.', '') }})" class="text-green-600 hover:text-green-800 dark:text-green-400" title="Baixar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                @endif
                                @if($_podeCancelar && $conta->status !== 'cancelado' && $conta->status !== 'pago')
                                    <a href="{{ route('financeiro.contas-pagar.edit', $conta) }}#cancelar" class="text-orange-600 hover:text-orange-800 dark:text-orange-400" title="Cancelar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </a>
                                @endif
                                @if($_podeExcluir && ($conta->status !== 'pago' || $conta->valor_pago == 0))
                                    <button type="button" onclick="abrirModalExcluirContaCP('{{ route('financeiro.contas-pagar.destroy', $conta) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
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
                        <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma conta a pagar encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $contas->links() }}
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'conta_pagar'); ?>
@endif

@else
{{-- Aba Despesas recorrentes --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.contas-pagar.index', ['aba' => 'recorrentes']) }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
            <select name="fornecedor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($fornecedores as $f)
                    <option value="{{ $f->id }}" {{ request('fornecedor_id') == $f->id ? 'selected' : '' }}>{{ $f->nome ?? $f->razao_social ?? '#' . $f->id }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <select name="tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="agua" {{ request('tipo') === 'agua' ? 'selected' : '' }}>Água</option>
                <option value="luz" {{ request('tipo') === 'luz' ? 'selected' : '' }}>Luz</option>
                <option value="gas" {{ request('tipo') === 'gas' ? 'selected' : '' }}>Gás</option>
                <option value="aluguel" {{ request('tipo') === 'aluguel' ? 'selected' : '' }}>Aluguel</option>
                <option value="condominio" {{ request('tipo') === 'condominio' ? 'selected' : '' }}>Condomínio</option>
                <option value="telefone" {{ request('tipo') === 'telefone' ? 'selected' : '' }}>Telefone</option>
                <option value="internet" {{ request('tipo') === 'internet' ? 'selected' : '' }}>Internet</option>
                <option value="outro" {{ request('tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Frequência</label>
            <select name="frequencia" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                <option value="diaria" {{ request('frequencia') === 'diaria' ? 'selected' : '' }}>Diária</option>
                <option value="semanal" {{ request('frequencia') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                <option value="quinzenal" {{ request('frequencia') === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                <option value="mensal" {{ request('frequencia') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                <option value="bimestral" {{ request('frequencia') === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                <option value="trimestral" {{ request('frequencia') === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                <option value="semestral" {{ request('frequencia') === 'semestral' ? 'selected' : '' }}>Semestral</option>
                <option value="anual" {{ request('frequencia') === 'anual' ? 'selected' : '' }}>Anual</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="ativo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativos</option>
                <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('financeiro.contas-pagar.index', ['aba' => 'recorrentes']) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Fornecedor</th>
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
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->fornecedor->nome ?? $r->fornecedor->razao_social ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->descricao }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\ContaPagarRecorrente::tipoLabel($r->tipo) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($r->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\ContaPagarRecorrente::frequenciaLabel($r->frequencia) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $r->proxima_geracao_em->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $r->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $r->ativo ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="abrirModalLancamentosRecorrenteCP({{ $r->id }}, '{{ addslashes($r->descricao) }}')" class="text-gray-600 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 mr-1" title="Ver títulos (contas a pagar) gerados por esta despesa recorrente">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </button>
                            <a href="{{ route('financeiro.contas-pagar-recorrentes.edit', $r) }}?voltar={{ urlencode(route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button type="button" onclick="abrirModalExcluirMotivo('{{ route('financeiro.contas-pagar-recorrentes.destroy', $r) }}?voltar={{ urlencode(route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma despesa recorrente cadastrada. Crie uma para gerar contas a pagar automaticamente (água, luz, aluguel, etc.).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ ($recorrentes ?? null)?->links() }}
    </div>
</div>

<p class="mt-4 text-xs text-gray-500 dark:text-gray-400">As contas a pagar são criadas automaticamente pelo agendamento diário (comando <code>financeiro:gerar-contas-pagar-recorrentes</code>). Em hospedagem compartilhada sem cron, habilite <strong>Configurações → Tarefas agendadas → Executar tarefas ao acessar o sistema</strong> para que a geração rode na primeira visita do dia.</p>

<!-- Modal Lançamentos (títulos) da despesa recorrente -->
<div id="modalLancamentosRecorrenteCP" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 id="modalLancamentosRecorrenteCPTitulo" class="text-lg font-semibold text-gray-800 dark:text-white/90">Títulos gerados</h2>
            <button type="button" onclick="fecharModalLancamentosRecorrenteCP()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modalLancamentosRecorrenteCPBody" class="p-6">
            <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Carregando...</p>
        </div>
    </div>
</div>

<x-modal-excluir-motivo action="" titulo="Excluir despesa recorrente" descricao="Tem certeza que deseja excluir esta despesa recorrente? Informe o motivo." />
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function abrirModalLancamentosRecorrenteCP(recorrenteId, descricao) {
    var modal = document.getElementById('modalLancamentosRecorrenteCP');
    var titulo = document.getElementById('modalLancamentosRecorrenteCPTitulo');
    var body = document.getElementById('modalLancamentosRecorrenteCPBody');
    if (!modal || !titulo || !body) return;
    titulo.textContent = 'Títulos a pagar: ' + (descricao || '');
    body.innerHTML = '<p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Carregando...</p>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    fetch('{{ route("financeiro.contas-pagar.lancamentos-recorrentes-recorrente") }}?conta_pagar_recorrente_id=' + encodeURIComponent(recorrenteId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(data) {
        body.innerHTML = data.html || '<p class="py-4 text-center text-sm text-gray-500">Nenhum título encontrado.</p>';
    }).catch(function() {
        body.innerHTML = '<p class="py-4 text-center text-sm text-red-500">Erro ao carregar.</p>';
    });
}
function fecharModalLancamentosRecorrenteCP() {
    var modal = document.getElementById('modalLancamentosRecorrenteCP');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}
</script>
@endif

@if($aba === 'contas')
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
        <form id="formBaixa" method="POST" class="p-6" data-base-url="{{ url('financeiro/contas-pagar') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da Baixa *</label>
                    <input type="number" step="0.01" name="valor_baixa" id="valor_baixa" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500" id="valor_baixa_max"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Baixa *</label>
                    <input type="date" name="data_baixa" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                    <select name="conta_bancaria_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(\App\Models\ContaBancaria::where('ativo', true)->orderBy('nome')->get() as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="forma_pagamento_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach(\App\Models\FormaPagamento::where('ativo', true)->orderBy('nome')->get() as $forma)
                            <option value="{{ $forma->id }}">{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>
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
function abrirModalBaixa(contaId, valorPendente) {
    var row = document.querySelector('tr[data-conta-id="' + contaId + '"]');
    var jurosSug = row ? parseFloat(row.getAttribute('data-juros-sugerido') || 0) : 0;
    var multaSug = row ? parseFloat(row.getAttribute('data-multa-sugerido') || 0) : 0;
    var form = document.getElementById('formBaixa');
    form.action = form.dataset.baseUrl + '/' + contaId + '/baixar';
    document.getElementById('valor_baixa').value = valorPendente;
    document.getElementById('valor_baixa').max = valorPendente;
    document.getElementById('valor_baixa_max').textContent = 'Máximo: R$ ' + parseFloat(valorPendente).toFixed(2).replace('.', ',');
    var jurosInput = form.querySelector('input[name="juros"]');
    var multaInput = form.querySelector('input[name="multa"]');
    if (jurosInput) jurosInput.value = jurosSug.toFixed(2);
    if (multaInput) multaInput.value = multaSug.toFixed(2);
    document.getElementById('modalBaixa').classList.remove('hidden');
    document.getElementById('modalBaixa').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalBaixa() {
    document.getElementById('modalBaixa').classList.add('hidden');
    document.getElementById('modalBaixa').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalExcluirContaCP(url) {
    document.getElementById('formExcluirContaCP').action = url;
    document.getElementById('motivoExcluirContaCP').value = '';
    document.getElementById('modalExcluirContaCP').classList.remove('hidden');
    document.getElementById('modalExcluirContaCP').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function fecharModalExcluirContaCP() {
    document.getElementById('modalExcluirContaCP').classList.add('hidden');
    document.getElementById('modalExcluirContaCP').classList.remove('flex');
    document.body.style.overflow = '';
}

let agrupandoPagarEmAndamento = false;

/** @returns {string|null} mensagem de erro ou null se ok */
function validarPreAgrupamentoPagarRows(rows) {
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

    const fornecedorId = rows[0].dataset.fornecedorId;
    if (rows.some((r) => r.dataset.fornecedorId !== fornecedorId)) {
        return 'Somente contas do mesmo fornecedor podem ser agrupadas.';
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

function submeterAgrupamentoManualPagar(form) {
    if (agrupandoPagarEmAndamento) return false;
    const raw = (form.querySelector('[name="ids_texto"]')?.value || '').trim();
    const idsParsed = raw.split(/[\s,;]+/).map((s) => parseInt(String(s).trim(), 10)).filter((n) => !isNaN(n) && n > 0);
    const idsUnicos = [...new Set(idsParsed)];
    if (idsUnicos.length < 2) {
        const msg = 'Digite pelo menos 2 IDs válidos separados por vírgula.';
        if (typeof window.erpToast === 'function') window.erpToast(msg, 'warning');
        else alert(msg);
        return false;
    }
    const rows = [];
    const faltandoNaPagina = [];
    for (const id of idsUnicos) {
        const row = document.querySelector('tr[data-conta-id="' + id + '"]');
        if (!row) faltandoNaPagina.push(id);
        else rows.push(row);
    }
    if (faltandoNaPagina.length > 0) {
        const msg =
            'Alguns IDs não estão visíveis na lista atual (' +
            faltandoNaPagina.join(', ') +
            '). Ajuste os filtros/página ou use as caixas de seleção; a validação completa só ocorre no servidor se o título não aparecer na tela.';
        if (typeof window.erpToast === 'function') window.erpToast(msg, 'warning');
        else alert(msg);
        return false;
    }
    const errPre = validarPreAgrupamentoPagarRows(rows);
    if (errPre) {
        if (typeof window.erpToast === 'function') window.erpToast(errPre, 'warning');
        else alert(errPre);
        return false;
    }
    agrupandoPagarEmAndamento = true;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-60', 'cursor-not-allowed');
    }
    return true;
}

function selecionarTodosPagar(checkbox) {
    document.querySelectorAll('.checkbox-conta-pagar').forEach((cb) => {
        cb.checked = checkbox.checked;
    });
    atualizarBarraAcoesPagar();
}

function limparSelecaoPagar() {
    var master = document.getElementById('selecionarTodosPagar');
    if (master) master.checked = false;
    document.querySelectorAll('.checkbox-conta-pagar').forEach((cb) => {
        cb.checked = false;
    });
    atualizarBarraAcoesPagar();
}

function atualizarBarraAcoesPagar() {
    const selecionadas = document.querySelectorAll('.checkbox-conta-pagar:checked');
    const barra = document.getElementById('barraAcoesMassaPagar');
    const contador = document.getElementById('contadorSelecionadosPagar');
    if (!barra || !contador) return;
    if (selecionadas.length > 0) {
        barra.classList.remove('hidden');
        contador.textContent = selecionadas.length;
    } else {
        barra.classList.add('hidden');
    }
}

function agruparSelecionadasPagar() {
    if (agrupandoPagarEmAndamento) return;
    const rows = Array.from(document.querySelectorAll('.checkbox-conta-pagar:checked'))
        .map((cb) => cb.closest('tr'))
        .filter(Boolean);
    const err = validarPreAgrupamentoPagarRows(rows);
    if (err) {
        if (typeof window.erpToast === 'function') window.erpToast(err, 'warning');
        else alert(err);
        return;
    }
    abrirModalAgruparPagar();
}

function abrirModalAgruparPagar() {
    document.getElementById('modalAgruparMassaPagar').classList.remove('hidden');
    document.getElementById('modalAgruparMassaPagar').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalAgruparPagar() {
    document.getElementById('modalAgruparMassaPagar').classList.add('hidden');
    document.getElementById('modalAgruparMassaPagar').classList.remove('flex');
    document.body.style.overflow = '';
}

const formAgruparMassaPagar = document.getElementById('formAgruparMassaPagar');
if (formAgruparMassaPagar) formAgruparMassaPagar.addEventListener('submit', function (e) {
    e.preventDefault();
    if (agrupandoPagarEmAndamento) return;

    const rows = Array.from(document.querySelectorAll('.checkbox-conta-pagar:checked'))
        .map((cb) => cb.closest('tr'))
        .filter(Boolean);
    const errPre = validarPreAgrupamentoPagarRows(rows);
    if (errPre) {
        if (typeof window.erpToast === 'function') window.erpToast(errPre, 'warning');
        else alert(errPre);
        fecharModalAgruparPagar();
        return;
    }
    const selecionadas = rows.map((r) => String(r.dataset.contaId));

    const descricao = (document.getElementById('agruparPagarDescricao').value || '').trim();
    const dataVencimento = document.getElementById('agruparPagarDataVencimento').value;
    if (!descricao || !dataVencimento) {
        const msg = 'Preencha a descrição e a data de vencimento.';
        if (typeof window.erpToast === 'function') window.erpToast(msg, 'warning');
        else alert(msg);
        return;
    }

    agrupandoPagarEmAndamento = true;
    const btns = Array.from(document.querySelectorAll('button')).filter((b) => (b.textContent || '').includes('Agrupar'));
    btns.forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'cursor-not-allowed');
    });
    const btnConfirmar = document.getElementById('btnConfirmarAgruparPagar');
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.classList.add('opacity-60', 'cursor-not-allowed');
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('financeiro.contas-pagar.agrupar') }}';
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

    selecionadas.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
});
</script>

<!-- Modal Excluir Conta a Pagar -->
<x-modal-excluir-motivo action="" idModal="modalExcluirContaCP" idForm="formExcluirContaCP" idMotivo="motivoExcluirContaCP" titulo="Excluir conta a pagar" descricao="Tem certeza que deseja excluir esta conta a pagar? Informe o motivo (mín. 10 caracteres)." />
@endif
@endsection
