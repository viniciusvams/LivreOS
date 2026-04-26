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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Movimentações Financeiras</h1>
        <p class="text-gray-600 dark:text-gray-400">
            @if(!empty($contaExtrato))
                Extrato: <strong>{{ $contaExtrato->nome }}</strong>
            @else
                Extrato e conciliação
            @endif
        </p>
    </div>
    @if(auth()->user()->is_admin || auth()->user()->hasPermission('financeiro.movimentacoes.create'))
    <a href="{{ route('financeiro.movimentacoes.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Nova Movimentação (Ajuste)</a>
    @endif
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="mb-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">{{ session('info') }}</div>
@endif

@php
    $g = $extratoGerencial ?? [];
@endphp

<!-- Resumo gerencial: totais do mesmo período da listagem (mês atual quando não há datas na URL) -->
<div class="mb-2 flex flex-wrap items-center justify-between gap-2">
    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Visão gerencial (vendas, ajustes e taxas)</span>
    <span class="text-xs text-gray-500 dark:text-gray-400">
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $quantidade }}</span> lançamentos
        @if($pendentesConciliacao > 0)
            <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
            <span class="font-medium text-amber-700 dark:text-amber-300">{{ $pendentesConciliacao }}</span> pendentes conciliação
        @endif
    </span>
</div>
<div class="mb-6 rounded-lg border border-brand-200 bg-brand-50/40 p-4 dark:border-brand-900/50 dark:bg-brand-950/20">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Faturamento bruto</p>
            <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">R$ {{ number_format($g['faturamento_bruto'] ?? 0, 2, ',', '.') }}</p>
            <p class="mt-1 text-[11px] leading-snug text-gray-500 dark:text-gray-400">Soma de entradas com origem conta a receber (recebimentos de vendas).</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Faturamento líquido</p>
            <p class="mt-0.5 text-base font-semibold text-brand-700 dark:text-brand-300">R$ {{ number_format($g['faturamento_liquido'] ?? 0, 2, ',', '.') }}</p>
            <p class="mt-1 text-[11px] leading-snug text-gray-500 dark:text-gray-400">Bruto − estornos/ajustes débito − taxas líquidas (taxas saída − créditos de estorno de taxa).</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Custos operacionais (taxas líq.)</p>
            <p class="mt-0.5 text-base font-semibold text-amber-800 dark:text-amber-200">R$ {{ number_format($g['custos_taxas_liquido'] ?? 0, 2, ',', '.') }}</p>
            <p class="mt-1 text-[11px] leading-snug text-gray-500 dark:text-gray-400">Saídas taxa/receb. vinculadas a CR − entradas de ajuste “Estorno: Taxa…”.</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Estornos / ajustes débito</p>
            <p class="mt-0.5 text-base font-semibold text-gray-800 dark:text-gray-200">R$ {{ number_format($g['ajustes_debito_saida'] ?? 0, 2, ',', '.') }}</p>
            <p class="mt-1 text-[11px] leading-snug text-gray-500 dark:text-gray-400">Saídas com origem ajuste (ex.: estorno de recebimento).</p>
        </div>
    </div>
    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 md:grid-cols-3 lg:grid-cols-6">
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Rec. vendas</p>
            <p class="text-sm font-semibold text-green-700 dark:text-green-300">R$ {{ number_format($g['recebimentos_vendas'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Ajustes crédito</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">R$ {{ number_format($g['ajustes_credito_entrada'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Outras entradas</p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">R$ {{ number_format($g['outras_entradas'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Taxas (bruto)</p>
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">R$ {{ number_format($g['taxas_saida_bruto'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Créd. est. taxa</p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">R$ {{ number_format($g['estorno_taxa_credito_ajuste'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-[11px] font-medium uppercase text-gray-500 dark:text-gray-400">Pagamentos (CP)</p>
            <p class="text-sm font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($g['pagamentos_conta_pagar'] ?? 0, 2, ',', '.') }}</p>
        </div>
    </div>
    @if($quantidade > 0 && ($totalEntradas + $totalSaidas) > 0)
    @php
        $maxValResumoGerencial = max($totalEntradas, $totalSaidas, 1);
        $pctEntradasGerencial = round(($totalEntradas / $maxValResumoGerencial) * 100);
        $pctSaidasGerencial = round(($totalSaidas / $maxValResumoGerencial) * 100);
    @endphp
    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
        <div class="rounded-lg border border-gray-200/80 bg-white/70 p-4 dark:border-gray-600 dark:bg-gray-900/35">
            <h3 class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Resumo visual do período</h3>
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Proporção entre totais contábeis de entradas e saídas (todas as origens).</p>
            <div class="flex gap-4">
                <div class="flex-1">
                    <p class="mb-1 text-xs text-green-600 dark:text-green-400">Entradas (contábil)</p>
                    <div class="h-6 overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded bg-green-500 dark:bg-green-600" style="width: {{ min($pctEntradasGerencial, 100) }}%"></div>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="mb-1 text-xs text-red-600 dark:text-red-400">Saídas (contábil)</p>
                    <div class="h-6 overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded bg-red-500 dark:bg-red-600" style="width: {{ min($pctSaidasGerencial, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Visão contábil: oculta por padrão; expandir para totais literais + gráfico -->
<details class="mov-extrato-contabil mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-gray-700 marker:content-none hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800/80 [&::-webkit-details-marker]:hidden">
        <span class="inline-flex items-center gap-2">
            <svg class="mov-extrato-contabil-chevron h-4 w-4 shrink-0 text-gray-500 transition-transform dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            Mostrar visão contábil (soma de todas as linhas, saldo e gráfico)
        </span>
    </summary>
    <div class="space-y-4 border-t border-gray-200 p-4 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">Totais literais de entradas e saídas no período filtrado, úteis para conferência contábil.</p>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total entradas</p>
                <p class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total saídas</p>
                <p class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saldo do período</p>
                <p class="mt-1 text-lg font-semibold {{ ($totalEntradas - $totalSaidas) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">R$ {{ number_format($totalEntradas - $totalSaidas, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Lançamentos</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $quantidade }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Pendentes conciliação</p>
                <p class="mt-1 text-lg font-semibold text-amber-600 dark:text-amber-400">{{ $pendentesConciliacao }}</p>
            </div>
        </div>
        @if($quantidade > 0 && ($totalEntradas + $totalSaidas) > 0)
        @php
            $maxVal = max($totalEntradas, $totalSaidas, 1);
            $pctEntradas = round(($totalEntradas / $maxVal) * 100);
            $pctSaidas = round(($totalSaidas / $maxVal) * 100);
        @endphp
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/30">
            <h3 class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Resumo visual do período</h3>
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Proporção entre totais contábeis de entradas e saídas (todas as origens).</p>
            <div class="flex gap-4">
                <div class="flex-1">
                    <p class="mb-1 text-xs text-green-600 dark:text-green-400">Entradas (contábil)</p>
                    <div class="h-6 overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded bg-green-500 dark:bg-green-600" style="width: {{ min($pctEntradas, 100) }}%"></div>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="mb-1 text-xs text-red-600 dark:text-red-400">Saídas (contábil)</p>
                    <div class="h-6 overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded bg-red-500 dark:bg-red-600" style="width: {{ min($pctSaidas, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</details>
<style>
.mov-extrato-contabil[open] .mov-extrato-contabil-chevron { transform: rotate(90deg); }
</style>

<!-- Filtros ativos -->
@if(count($filtrosAtivos) > 0)
<div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800/50">
    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Filtros:</span>
    @if(!empty($filtrosAtivos['data_inicio']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">De {{ \Carbon\Carbon::parse($filtrosAtivos['data_inicio'])->format('d/m/Y') }}</span>@endif
    @if(!empty($filtrosAtivos['data_fim']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">Até {{ \Carbon\Carbon::parse($filtrosAtivos['data_fim'])->format('d/m/Y') }}</span>@endif
    @if(!empty($filtrosAtivos['conta_bancaria_id']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">Conta selecionada</span>@endif
    @if(!empty($filtrosAtivos['tipo']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">{{ $filtrosAtivos['tipo'] === 'entrada' ? 'Entrada' : 'Saída' }}</span>@endif
    @if(isset($filtrosAtivos['conciliado']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">{{ $filtrosAtivos['conciliado'] === '1' ? 'Conciliadas' : 'Não conciliadas' }}</span>@endif
    @if(!empty($filtrosAtivos['origem']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">{{ ucfirst(str_replace('_',' ',$filtrosAtivos['origem'])) }}</span>@endif
    @if(!empty($filtrosAtivos['q']))<span class="rounded bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-700">Busca: {{ $filtrosAtivos['q'] }}</span>@endif
    <a href="{{ route('financeiro.movimentacoes.index') }}" class="text-xs text-brand-600 hover:underline dark:text-brand-400">Limpar tudo</a>
</div>
@endif

<!-- Filtros -->
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="get" action="{{ route('financeiro.movimentacoes.index') }}" id="formFiltros" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach($contasBancarias as $conta)
                        <option value="{{ $conta->id }}" {{ request('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <option value="entrada" {{ request('tipo') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                    <option value="saida" {{ request('tipo') === 'saida' ? 'selected' : '' }}>Saída</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conciliação</label>
                <select name="conciliado" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    <option value="1" {{ request('conciliado') === '1' ? 'selected' : '' }}>Conciliadas</option>
                    <option value="0" {{ request('conciliado') === '0' ? 'selected' : '' }}>Não Conciliadas</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Origem</label>
                <select name="origem" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    <option value="conta_pagar" {{ request('origem') === 'conta_pagar' ? 'selected' : '' }}>Conta a pagar</option>
                    <option value="conta_receber" {{ request('origem') === 'conta_receber' ? 'selected' : '' }}>Conta a receber</option>
                    <option value="transferencia" {{ request('origem') === 'transferencia' ? 'selected' : '' }}>Transferência</option>
                    <option value="ajuste" {{ request('origem') === 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                    <option value="outro" {{ request('origem') === 'outro' ? 'selected' : '' }}>Outro</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                <input type="date" name="data_inicio" value="{{ $dataInicioEfetivo ?? request('data_inicio') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
                <input type="date" name="data_fim" value="{{ $dataFimEfetivo ?? request('data_fim') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Atalho período</label>
                <select id="atalhoPeriodo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">—</option>
                    <option value="hoje">Hoje</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes">Este mês</option>
                    <option value="mes_anterior">Mês passado</option>
                    <option value="ano">Este ano</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Busca (descrição)</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor mín.</label>
                <input type="text" name="valor_min" value="{{ request('valor_min') }}" placeholder="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor máx.</label>
                <input type="text" name="valor_max" value="{{ request('valor_max') }}" placeholder="—" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conciliadas entre (data)</label>
                <input type="date" name="data_conciliacao_inicio" value="{{ request('data_conciliacao_inicio') }}" placeholder="Início" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" title="Filtra por data de conciliação">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">e</label>
                <input type="date" name="data_conciliacao_fim" value="{{ request('data_conciliacao_fim') }}" placeholder="Fim" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" title="Data fim conciliação">
            </div>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Ordenar</label>
                <div class="flex gap-2">
                    <select name="ordenar" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="data_movimentacao" {{ request('ordenar') === 'data_movimentacao' ? 'selected' : '' }}>Data</option>
                        <option value="valor" {{ request('ordenar') === 'valor' ? 'selected' : '' }}>Valor</option>
                        <option value="descricao" {{ request('ordenar') === 'descricao' ? 'selected' : '' }}>Descrição</option>
                    </select>
                    <select name="ordenar_direcao" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="desc" {{ request('ordenar_direcao') === 'desc' ? 'selected' : '' }}>Decrescente</option>
                        <option value="asc" {{ request('ordenar_direcao') === 'asc' ? 'selected' : '' }}>Crescente</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Por página</label>
                <select name="per_page" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('financeiro.movimentacoes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
            <a href="{{ route('financeiro.movimentacoes.export', request()->all()) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Exportar CSV</a>
            <a href="{{ route('financeiro.movimentacoes.export-pdf', request()->all()) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Exportar PDF
            </a>
        </div>
    </form>
</div>

<!-- Conciliação em lote -->
<form id="formConciliarLote" action="{{ route('financeiro.movimentacoes.conciliar-lote') }}" method="POST" class="mb-4">
    @csrf
    <button type="submit" id="btnConciliarLote" disabled class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600 disabled:opacity-50" onclick="return confirm('Conciliar as movimentações selecionadas?')">Conciliar selecionadas</button>
</form>

<!-- Tabela -->
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400 w-10">
                        <input type="checkbox" id="checkAll" class="rounded border-gray-300" title="Selecionar todas (não conciliadas)">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Conta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Origem</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($movimentacoes as $mov)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ ($mov->origem ?? '') === 'ajuste' ? 'bg-gray-100/90 border-l-[3px] border-gray-400 dark:bg-gray-800/70 dark:border-gray-500' : '' }}">
                        <td class="px-4 py-3">
                            @if(!$mov->conciliado)
                                <input type="checkbox" name="ids[]" form="formConciliarLote" value="{{ $mov->id }}" class="cb-conciliar rounded border-gray-300">
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $mov->data_movimentacao->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <button type="button" class="text-left hover:underline text-brand-600 dark:text-brand-400" onclick="verDetalhe({{ $mov->id }})" title="Ver detalhe">{{ Str::limit($mov->descricao, 40) }}</button>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $mov->contaBancaria->nome ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            @if($mov->origem === 'conta_pagar' && $mov->conta_pagar_id)
                                <a href="{{ route('financeiro.contas-pagar.edit', $mov->conta_pagar_id) }}" class="text-blue-600 hover:underline dark:text-blue-400">Conta a pagar #{{ $mov->conta_pagar_id }}</a>
                            @elseif($mov->origem === 'conta_receber' && $mov->conta_receber_id)
                                <a href="{{ route('financeiro.contas-receber.edit', $mov->conta_receber_id) }}" class="text-blue-600 hover:underline dark:text-blue-400">Conta a receber</a>
                            @elseif($mov->origem === 'ajuste')
                                <span class="inline-flex flex-wrap items-center gap-1">
                                    <span class="rounded bg-gray-300 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-800 dark:bg-gray-600 dark:text-gray-100">Ajuste</span>
                                    <span>{{ ucfirst(str_replace('_', ' ', $mov->origem ?? 'outro')) }}</span>
                                </span>
                            @else
                                <span>{{ ucfirst(str_replace('_', ' ', $mov->origem ?? 'outro')) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mov->tipo === 'entrada' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                {{ $mov->tipo === 'entrada' ? 'Entrada' : 'Saída' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ $mov->tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $mov->tipo === 'entrada' ? '+' : '-' }} R$ {{ number_format($mov->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $mov->conciliado ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}">
                                {{ $mov->conciliado ? 'Conciliado' : 'Pendente' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if(!$mov->conciliado)
                                <form action="{{ route('financeiro.movimentacoes.conciliar', $mov) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400" title="Conciliar" onclick="return confirm('Deseja conciliar esta movimentação?')">
                                        <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </form>
                            @else
                                @if($mov->origem === 'transferencia')
                                    {{-- Transferência: só permite cancelar (com motivo); permissão específica ou admin --}}
                                    @if(auth()->user()->is_admin || auth()->user()->hasPermission('financeiro.transferencias.cancelar'))
                                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Cancelar transferência" data-cancelar-url="{{ route('financeiro.transferencias.cancelar', $mov) }}" data-cancelar-desc="{{ e(Str::limit($mov->descricao, 50)) }}" onclick="abrirCancelarTransferencia(this)">
                                        <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    @else
                                        <span class="text-gray-300" title="Sem permissão">—</span>
                                    @endif
                                @else
                                    @if(auth()->user()->is_admin || auth()->user()->hasPermission('financeiro.movimentacoes.desconciliar'))
                                    <button type="button" class="text-amber-600 hover:text-amber-800 dark:text-amber-400" title="Desconciliar" data-desconciliar-url="{{ route('financeiro.movimentacoes.desconciliar', $mov) }}" data-desconciliar-desc="{{ e(Str::limit($mov->descricao, 50)) }}" onclick="abrirDesconciliar(this)">
                                        <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @else
                                        <span class="text-gray-300" title="Sem permissão para desconciliar">—</span>
                                    @endif
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma movimentação encontrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $movimentacoes->withQueryString()->links() }}
    </div>
</div>

<!-- Modal desconciliar -->
<div id="modalDesconciliar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Desconciliar movimentação</h2>
            <button type="button" onclick="fecharDesconciliar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formDesconciliar" method="POST" action="" class="p-6">
            @csrf
            <p id="desconciliarDescricao" class="mb-3 text-sm text-gray-600 dark:text-gray-400"></p>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da desconciliação <span class="text-red-500">*</span></label>
            <textarea name="motivo_desconciliacao" id="motivoDesconciliar" rows="4" required maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex.: Lançamento duplicado; valor incorreto; conta errada..."></textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máx. 2000 caracteres. Quem desconciliar ficará registrado.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="fecharDesconciliar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">Desconciliar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal cancelar transferência -->
<div id="modalCancelarTransferencia" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar transferência</h2>
            <button type="button" onclick="fecharCancelarTransferencia()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formCancelarTransferencia" method="POST" action="" class="p-6">
            @csrf
            <p id="cancelarTransferenciaDescricao" class="mb-3 text-sm text-gray-600 dark:text-gray-400"></p>
            <p class="mb-3 text-xs text-amber-600 dark:text-amber-400">As duas movimentações (saída e entrada) serão removidas. O motivo e seu usuário ficarão registrados.</p>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do cancelamento <span class="text-red-500">*</span></label>
            <textarea name="motivo_cancelamento_transferencia" id="motivoCancelarTransferencia" rows="4" required maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex.: Transferência feita por engano; valor incorreto..."></textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máx. 2000 caracteres.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="fecharCancelarTransferencia()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Cancelar transferência</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal detalhe -->
<div id="modalDetalhe" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Detalhe da movimentação</h2>
            <button type="button" onclick="fecharDetalhe()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modalDetalheConteudo" class="p-6 text-sm text-gray-700 dark:text-gray-300"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('checkAll');
    var cbs = document.querySelectorAll('.cb-conciliar');
    var btnLote = document.getElementById('btnConciliarLote');
    function updateLote() {
        var n = document.querySelectorAll('.cb-conciliar:checked').length;
        btnLote.disabled = n === 0;
    }
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            cbs.forEach(function(cb) { cb.checked = checkAll.checked; });
            updateLote();
        });
    }
    cbs.forEach(function(cb) {
        cb.addEventListener('change', updateLote);
    });
    updateLote();

    var atalho = document.getElementById('atalhoPeriodo');
    var form = document.getElementById('formFiltros');
    if (atalho && form) {
        atalho.addEventListener('change', function() {
            var v = this.value;
            if (!v) return;
            var hoje = new Date();
            var di = form.querySelector('input[name="data_inicio"]');
            var df = form.querySelector('input[name="data_fim"]');
            if (!di || !df) return;
            function pad(n) { return n < 10 ? '0' + n : n; }
            var i, f;
            if (v === 'hoje') {
                i = f = pad(hoje.getFullYear()) + '-' + pad(hoje.getMonth() + 1) + '-' + pad(hoje.getDate());
            } else if (v === 'semana') {
                var d = new Date(hoje); d.setDate(d.getDate() - d.getDay());
                i = pad(d.getFullYear()) + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                f = pad(hoje.getFullYear()) + '-' + pad(hoje.getMonth() + 1) + '-' + pad(hoje.getDate());
            } else if (v === 'mes') {
                i = pad(hoje.getFullYear()) + '-' + pad(hoje.getMonth() + 1) + '-01';
                f = pad(hoje.getFullYear()) + '-' + pad(hoje.getMonth() + 1) + '-' + pad(hoje.getDate());
            } else if (v === 'mes_anterior') {
                var m = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
                i = pad(m.getFullYear()) + '-' + pad(m.getMonth() + 1) + '-01';
                f = pad(m.getFullYear()) + '-' + pad(m.getMonth() + 1) + '-' + pad(m.getDate());
            } else if (v === 'ano') {
                i = pad(hoje.getFullYear()) + '-01-01';
                f = pad(hoje.getFullYear()) + '-' + pad(hoje.getMonth() + 1) + '-' + pad(hoje.getDate());
            }
            di.value = i;
            df.value = f;
            form.submit();
        });
    }
});

function verDetalhe(id) {
    var modal = document.getElementById('modalDetalhe');
    var content = document.getElementById('modalDetalheConteudo');
    content.innerHTML = '<p class="text-gray-500">Carregando...</p>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    fetch('{{ url("financeiro/movimentacoes") }}/' + id, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var linkCr = d.conta_receber_url ? '<a href="' + d.conta_receber_url + '" class="text-blue-600 hover:underline">Conta a receber #' + d.conta_receber_id + '</a>' : '—';
            var linkCp = d.conta_pagar_url ? '<a href="' + d.conta_pagar_url + '" class="text-blue-600 hover:underline">Conta a pagar #' + d.conta_pagar_id + '</a>' : '—';
            content.innerHTML = '<table class="w-full text-left"><tr><th class="pr-4 py-1 text-gray-500">Data</th><td>' + d.data_movimentacao + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Descrição</th><td>' + (d.descricao || '—') + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Observações</th><td>' + (d.observacoes || '—') + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Conta</th><td>' + (d.conta_bancaria || '—') + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Origem</th><td>' + (d.origem || '—') + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Tipo</th><td>' + d.tipo + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Valor</th><td>R$ ' + parseFloat(d.valor).toFixed(2).replace('.', ',') + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Conciliado</th><td>' + (d.conciliado ? 'Sim' : 'Não') + '</td></tr>' +
                (d.conciliado ? '<tr><th class="pr-4 py-1 text-gray-500">Data conciliação</th><td>' + (d.data_conciliacao || '—') + '</td></tr><tr><th class="pr-4 py-1 text-gray-500">Conciliado por</th><td>' + (d.conciliado_por || '—') + '</td></tr>' : '') +
                (d.motivo_desconciliacao ? '<tr><th class="pr-4 py-1 text-gray-500">Última desconciliação</th><td>' + (d.data_desconciliacao || '') + ' por ' + (d.desconciliado_por || '—') + '</td></tr><tr><th class="pr-4 py-1 text-gray-500 align-top">Motivo</th><td>' + (d.motivo_desconciliacao || '—') + '</td></tr>' : '') +
                '<tr><th class="pr-4 py-1 text-gray-500">Conta a receber</th><td>' + linkCr + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Conta a pagar</th><td>' + linkCp + '</td></tr>' +
                '<tr><th class="pr-4 py-1 text-gray-500">Criado em</th><td>' + (d.created_at || '—') + '</td></tr></table>';
        })
        .catch(function() {
            content.innerHTML = '<p class="text-red-500">Erro ao carregar.</p>';
        });
}

function fecharDetalhe() {
    document.getElementById('modalDetalhe').classList.add('hidden');
    document.getElementById('modalDetalhe').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirDesconciliar(btn) {
    var url = btn.getAttribute('data-desconciliar-url');
    var descricao = btn.getAttribute('data-desconciliar-desc') || '';
    var modal = document.getElementById('modalDesconciliar');
    var form = document.getElementById('formDesconciliar');
    var descEl = document.getElementById('desconciliarDescricao');
    var motivo = document.getElementById('motivoDesconciliar');
    form.action = url;
    descEl.textContent = 'Movimentação: ' + descricao;
    motivo.value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharDesconciliar() {
    document.getElementById('modalDesconciliar').classList.add('hidden');
    document.getElementById('modalDesconciliar').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirCancelarTransferencia(btn) {
    var url = btn.getAttribute('data-cancelar-url');
    var descricao = btn.getAttribute('data-cancelar-desc') || '';
    var modal = document.getElementById('modalCancelarTransferencia');
    var form = document.getElementById('formCancelarTransferencia');
    var descEl = document.getElementById('cancelarTransferenciaDescricao');
    var motivo = document.getElementById('motivoCancelarTransferencia');
    form.action = url;
    descEl.textContent = 'Transferência: ' + descricao;
    motivo.value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharCancelarTransferencia() {
    document.getElementById('modalCancelarTransferencia').classList.add('hidden');
    document.getElementById('modalCancelarTransferencia').classList.remove('flex');
    document.body.style.overflow = '';
}
</script>
@endsection
