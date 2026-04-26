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
    $_auditOsExcluir = app(\App\Services\AuditCancelExcluirService::class);
    $_podeExcluirOs = $_auditOsExcluir->canExcluir(auth()->user(), 'ordem_servico');
@endphp
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Ordens de Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as ordens cadastradas</p>
        @if(request('busca_geral'))
            <p class="mt-1 text-sm text-brand-600 dark:text-brand-400">
                Buscando por: <strong>{{ request('busca_geral') }}</strong>
                <a href="{{ route('ordens-servico.index') }}" class="ml-2 text-gray-500 hover:underline">Limpar busca</a>
            </p>
        @endif
    </div>
    <a href="{{ route('ordens-servico.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova OS</a>
</div>

@if(request('sla_filtro'))
    <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-900/20 dark:text-indigo-200">
        @php
            $slaFiltroLabel = [
                'cumprido' => 'Cumprido',
                'nao_cumprido' => 'Não cumprido',
                'com_sla' => 'Com SLA',
                'sem_sla' => 'Sem SLA',
            ][request('sla_filtro')] ?? request('sla_filtro');
        @endphp
        Filtro ativo: <strong>SLA = {{ $slaFiltroLabel }}</strong>.
        A coluna <strong>Status</strong> mostra o selo de SLA de cada OS listada.
    </div>
@endif

<!-- Busca rápida (sempre visível) -->
<form method="GET" action="{{ route('ordens-servico.index') }}" class="mb-6 flex max-w-xl items-center gap-2">
    <div class="flex-1" style="min-width: 200px;">
        <input type="text" name="busca_geral" value="{{ request('busca_geral') }}"
            placeholder="Buscar em código, cliente, relato, diagnóstico, equipamento..."
            autocomplete="off"
            style="width: 100%; box-sizing: border-box;"
            class="block rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
    </div>
    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        Buscar
    </button>
</form>

<div x-data="{
    showFilters: false,
    showColumns: false,
    visibleColumns: {
        codigo: true,
        cliente: true,
        tags: true,
        status: true,
        prioridade: true,
        abertura: true,
        conclusao: true,
        total: true,
        acoes: true
    },
    filters: {
        codigo: '{{ request('codigo', '') }}',
        cliente_id: '{{ request('cliente_id', '') }}',
        cliente_nome: '{{ request('cliente_nome', '') }}',
        status: '{{ request('status', '') }}',
        prioridade: '{{ request('prioridade', '') }}',
        tecnico_id: '{{ request('tecnico_id', '') }}',
        sla_filtro: '{{ request('sla_filtro', '') }}',
        tag_id: '{{ request('tag_id', '') }}',
        data_inicio: '{{ request('data_inicio', '') }}',
        data_fim: '{{ request('data_fim', '') }}',
        busca_geral: '{{ request('busca_geral', '') }}',
        per_page: '{{ request('per_page', 15) }}'
    },
    clienteAutocomplete: {
        open: false,
        results: [],
        selected: null,
        searchTerm: '{{ request('cliente_nome', '') }}',
        loading: false
    },
    async buscarClientes(termo) {
        if (termo.length < 2) {
            this.clienteAutocomplete.results = [];
            this.clienteAutocomplete.open = false;
            return;
        }
        this.clienteAutocomplete.loading = true;
        try {
            const response = await fetch(`/api/ordens-servico/buscar-clientes?q=${encodeURIComponent(termo)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            this.clienteAutocomplete.results = data;
            this.clienteAutocomplete.open = data.length > 0;
        } catch (error) {
            console.error('Erro ao buscar clientes:', error);
            this.clienteAutocomplete.results = [];
            this.clienteAutocomplete.open = false;
        } finally {
            this.clienteAutocomplete.loading = false;
        }
    },
    selecionarCliente(cliente) {
        this.filters.cliente_id = cliente.id;
        this.clienteAutocomplete.searchTerm = cliente.text;
        this.clienteAutocomplete.selected = cliente;
        this.clienteAutocomplete.open = false;
    },
    limparCliente() {
        this.filters.cliente_id = '';
        this.clienteAutocomplete.searchTerm = '';
        this.clienteAutocomplete.selected = null;
        this.clienteAutocomplete.results = [];
        this.clienteAutocomplete.open = false;
    },
    init() {
        const saved = localStorage.getItem('os_columns');
        if (saved) {
            this.visibleColumns = { ...this.visibleColumns, ...JSON.parse(saved) };
        }
        // Mostrar filtros se algum estiver preenchido
        if (this.filters.codigo || this.filters.cliente_id || this.filters.status || this.filters.prioridade || this.filters.tecnico_id || this.filters.sla_filtro || this.filters.tag_id || this.filters.data_inicio || this.filters.data_fim || this.filters.busca_geral) {
            this.showFilters = true;
        }
        
        // Carregar preferência de itens por página
        const savedPerPage = localStorage.getItem('os_per_page');
        if (savedPerPage && !this.filters.per_page) {
            this.filters.per_page = savedPerPage;
        }
        // Carregar nome do cliente se cliente_id estiver preenchido
        @if(request('cliente_id') && request('cliente_nome'))
            this.clienteAutocomplete.searchTerm = '{{ request('cliente_nome') }}';
            this.clienteAutocomplete.selected = { id: {{ request('cliente_id') }}, text: '{{ request('cliente_nome') }}' };
        @endif
        
        // Watcher para salvar no localStorage quando visibleColumns mudar
        this.$watch('visibleColumns', (newVal) => {
            localStorage.setItem('os_columns', JSON.stringify(newVal));
        }, { deep: true });
    }
}">
    <!-- Filtros -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Filtros</h3>
            <button @click="showFilters = !showFilters" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showFilters ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'"/>
                </svg>
            </button>
        </div>
        <form method="GET" x-show="showFilters" x-transition class="p-4">
            <!-- Busca Geral -->
            <div class="mb-4">
                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Busca Geral</label>
                <div class="relative">
                    <input type="text" 
                           name="busca_geral" 
                           x-model="filters.busca_geral" 
                           placeholder="Buscar em código, cliente, relato, diagnóstico, equipamento, etc..." 
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pl-10 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Código</label>
                    <input type="text" name="codigo" x-model="filters.codigo" placeholder="Código" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                    <div class="relative" x-data="{ open: false }">
                        <input type="text" 
                               x-model="clienteAutocomplete.searchTerm"
                               @input.debounce.300ms="buscarClientes($event.target.value)"
                               @focus="if(clienteAutocomplete.searchTerm.length >= 2 && clienteAutocomplete.results.length > 0) clienteAutocomplete.open = true"
                               @blur="setTimeout(() => clienteAutocomplete.open = false, 200)"
                               placeholder="Digite para buscar cliente..."
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <input type="hidden" name="cliente_id" x-model="filters.cliente_id">
                        <input type="hidden" name="cliente_nome" x-model="clienteAutocomplete.searchTerm">
                        <div x-show="clienteAutocomplete.loading" class="absolute right-2 top-1/2 -translate-y-1/2">
                            <svg class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <button x-show="filters.cliente_id && !clienteAutocomplete.loading" 
                                type="button" 
                                @click="limparCliente()" 
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <!-- Dropdown de resultados -->
                        <div x-show="clienteAutocomplete.open && clienteAutocomplete.results.length > 0" 
                             x-transition
                             x-cloak
                             class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                             style="display: none;">
                            <ul class="max-h-60 overflow-auto py-1">
                                <template x-for="cliente in clienteAutocomplete.results" :key="cliente.id">
                                    <li>
                                        <button type="button"
                                                @click="selecionarCliente(cliente)"
                                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <div class="font-medium" x-text="cliente.nome"></div>
                                            <div class="text-xs text-gray-500" x-text="cliente.documento || ''"></div>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" x-model="filters.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Todos</option>
                        @foreach(!empty($statusOptions) ? $statusOptions : ['Aberta','Em análise/orçamento','Aguardando aprovação','Aprovado pelo cliente','Aguardando peças','Em execução','Em teste','Concluída','Cancelada','Encerrada'] as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
                    <select name="prioridade" x-model="filters.prioridade" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Todas</option>
                        <option value="baixa">Baixa</option>
                        <option value="normal">Normal</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Técnico</label>
                    <select name="tecnico_id" x-model="filters.tecnico_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Todos</option>
                        @foreach($tecnicos ?? [] as $tec)
                            <option value="{{ $tec->id }}">{{ $tec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">SLA</label>
                    <select name="sla_filtro" x-model="filters.sla_filtro" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Todos</option>
                        <option value="cumprido">Cumprido</option>
                        <option value="nao_cumprido">Não cumprido</option>
                        <option value="com_sla">Com SLA</option>
                        <option value="sem_sla">Sem SLA</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tag</label>
                    <select name="tag_id" x-model="filters.tag_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Todas</option>
                        @foreach($tags ?? [] as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                    <input type="date" name="data_inicio" x-model="filters.data_inicio" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
                    <input type="date" name="data_fim" x-model="filters.data_fim" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
                <a href="{{ route('ordens-servico.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
            </div>
        </form>
    </div>

    <!-- Controles do Topo -->
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('ordens-servico.index') }}" class="flex items-center gap-2" id="perPageForm">
            @foreach(request()->except('per_page', 'page') as $key => $value)
                @if($value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Itens por página:</label>
            <select name="per_page" 
                    value="{{ request('per_page', 15) }}"
                    @change="localStorage.setItem('os_per_page', $event.target.value); $event.target.closest('form').submit();"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="5" {{ (int)request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ (int)request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                <option value="15" {{ (int)request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                <option value="25" {{ (int)request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ (int)request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ (int)request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
            </select>
        </form>
    </div>

    <!-- Tabela -->
    <div id="resultados" class="relative rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <!-- Header da Tabela com Controles -->
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Lista de OS</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $ordens->total() }} registro(s)</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ordens-servico.export', request()->except('per_page', 'page')) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Exportar CSV</a>
                <a href="{{ route('ordens-servico.export-pdf', request()->except('per_page', 'page')) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar PDF
                </a>
                <div class="relative">
                <button @click.stop="showColumns = !showColumns" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Colunas
                </button>

                <!-- Menu de Colunas -->
                <div x-show="showColumns" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     @click.away="showColumns = false"
                     @click.stop
                     x-cloak
                     class="absolute right-0 top-full mt-2 z-50 w-56 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                     style="display: none;">
                    <div class="p-2">
                        <div class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300 px-2 py-1">Exibir Colunas</div>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.codigo" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Código</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.cliente" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Cliente</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.tags" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Tags</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.status" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Status</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.prioridade" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Prioridade</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.abertura" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Abertura</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.conclusao" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Conclusão</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.total" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Total</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" x-model="visibleColumns.acoes" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span>Ações</span>
                        </label>
                    </div>
                </div>
                </div>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('entity.index.before_table', 'ordem_servico'); ?>
        @endif

        <!-- Ações em massa: status e prioridade -->
        <div id="os_bulk_panel" class="mb-4 hidden rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Ações em massa</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="os_bulk_selected_count">0 OS selecionada(s)</p>
                </div>

                <form id="formOsBulkStatusPrioridade" method="POST" action="{{ route('ordens-servico.bulk-status-prioridade') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                    @csrf
                    <input type="hidden" name="ordem_ids_csv" id="os_bulk_ids_csv" value="">

                    <div class="min-w-[220px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Manter status atual</option>
                            @foreach($statusOptions ?? [] as $st)
                                @php
                                    $stNorm = (string) $st;
                                    $remover = in_array($stNorm, ['Encerrado', 'Encerrada', 'Cancelada', 'Cancelado'], true);
                                @endphp
                                @if(!$remover)
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-[220px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
                        <select name="prioridade" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Manter prioridade atual</option>
                            <option value="baixa">Baixa</option>
                            <option value="normal">Normal</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>

                    <div class="min-w-[260px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Selecionar Tags</label>
                        <select name="tag_ids[]" multiple size="5" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach($tags ?? [] as $tag)
                                @php
                                    $nomeTag = (string) ($tag->nome ?? '');
                                    $remover = in_array($nomeTag, ['Pago', 'Pagamento Pendente'], true);
                                @endphp
                                @if(!$remover)
                                    <option value="{{ $tag->id }}">{{ $tag->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                        <button type="submit" id="os_bulk_submit_btn" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                            Aplicar em selecionadas
                        </button>
                        @if($_podeExcluirOs)
                        <button type="button" id="os_bulk_destroy_btn" onclick="abrirModalExcluirMotivoBulk()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                            Excluir selecionadas
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">
                            <input type="checkbox" id="os_bulk_select_all" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        </th>
                        <th x-show="visibleColumns.codigo" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'codigo', 'sort_dir' => (request('sort_by') === 'codigo' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Código
                                @if(request('sort_by') === 'codigo')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.cliente" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'cliente', 'sort_dir' => (request('sort_by') === 'cliente' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Cliente
                                @if(request('sort_by') === 'cliente')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.tags" class="px-4 py-3 font-medium">Tags</th>
                        <th x-show="visibleColumns.status" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'status', 'sort_dir' => (request('sort_by') === 'status' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Status
                                @if(request('sort_by') === 'status')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.prioridade" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'prioridade', 'sort_dir' => (request('sort_by') === 'prioridade' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Prioridade
                                @if(request('sort_by') === 'prioridade')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.abertura" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'abertura', 'sort_dir' => (request('sort_by') === 'abertura' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Abertura
                                @if(request('sort_by') === 'abertura')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.conclusao" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'conclusao', 'sort_dir' => (request('sort_by') === 'conclusao' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Conclusão
                                @if(request('sort_by') === 'conclusao')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.total" class="px-4 py-3 font-medium">
                            <a href="{{ route('ordens-servico.index', array_merge(request()->query(), ['sort_by' => 'total', 'sort_dir' => (request('sort_by') === 'total' && request('sort_dir') !== 'asc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-brand-600 dark:hover:text-brand-400">
                                Total
                                @if(request('sort_by') === 'total')
                                    <span class="text-[10px]">{{ request('sort_dir') === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </a>
                        </th>
                        <th x-show="visibleColumns.acoes" class="px-4 py-3 font-medium text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($ordens as $ordem)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                    class="os-bulk-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                    value="{{ $ordem->id }}">
                            </td>
                            <td x-show="visibleColumns.codigo" class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ format_os_display($ordem) }}</td>
                            <td x-show="visibleColumns.cliente" class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $ordem->cliente?->nome ?? '-' }}</td>
                            <td x-show="visibleColumns.tags" class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($ordem->tags ?? [] as $tag)
                                        @if($tag->ativo ?? true)
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium" style="background-color: {{ $tag->cor_fundo ?? '#e5e7eb' }}; color: {{ $tag->cor_fonte ?? '#374151' }};">
                                            {{ $tag->nome }}
                                        </span>
                                        @endif
                                    @empty
                                        <span class="text-xs text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td x-show="visibleColumns.status" class="px-4 py-3">
                                @php
                                    $statusOs = isset($statusOsPorNome) && $ordem->status ? ($statusOsPorNome[$ordem->status] ?? null) : null;
                                    $corFundo = $statusOs?->cor ?? '#e5e7eb';
                                    $corFonte = $statusOs?->cor ? '#ffffff' : '#374151';
                                    $statusAtual = strtolower((string) ($ordem->status ?? ''));
                                    $osFinalizada = in_array($statusAtual, ['encerrado', 'encerrada', 'concluido', 'concluida', 'concluída'], true);
                                    $temSla = !is_null($ordem->sla_valor);
                                    $temDatasSla = !is_null($ordem->prevista_conclusao) && !is_null($ordem->real_conclusao);
                                    $slaCumpridoEfetivo = !is_null($ordem->sla_cumprido)
                                        ? (bool) $ordem->sla_cumprido
                                        : ($temDatasSla ? $ordem->real_conclusao->lessThanOrEqualTo($ordem->prevista_conclusao) : null);
                                @endphp
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $corFundo }}; color: {{ $corFonte }};">
                                        {{ format_status_os_display($ordem->status) }}
                                    </span>

                                    @if(!is_null($slaCumpridoEfetivo))
                                        @if($slaCumpridoEfetivo)
                                            <span class="relative inline-flex items-center gap-1" x-data="{open:false}" @mouseleave="open=false">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 cursor-help"
                                                    @mouseenter="open=true"
                                                    @click.prevent="open=!open"
                                                >
                                                    SLA: Cumprido
                                                    <button type="button" class="text-[11px] leading-none opacity-80 hover:opacity-100" @click.stop.prevent="open=!open" aria-label="Detalhes do SLA">ⓘ</button>
                                                </span>
                                                <span x-cloak x-show="open" x-transition class="pointer-events-none absolute bottom-full left-0 z-20 mb-1 w-72 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] font-medium text-white shadow-lg dark:bg-gray-950">
                                                    SLA cumprido: conclusão em {{ optional($ordem->real_conclusao)->format('d/m/Y H:i') ?: '-' }} dentro do prazo previsto {{ optional($ordem->prevista_conclusao)->format('d/m/Y H:i') ?: '-' }}.
                                                </span>
                                            </span>
                                        @else
                                            <span class="relative inline-flex items-center gap-1" x-data="{open:false}" @mouseleave="open=false">
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300 cursor-help"
                                                    @mouseenter="open=true"
                                                    @click.prevent="open=!open"
                                                >
                                                    SLA: Não cumprido
                                                    <button type="button" class="text-[11px] leading-none opacity-80 hover:opacity-100" @click.stop.prevent="open=!open" aria-label="Detalhes do SLA">ⓘ</button>
                                                </span>
                                                <span x-cloak x-show="open" x-transition class="pointer-events-none absolute bottom-full left-0 z-20 mb-1 w-72 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] font-medium text-white shadow-lg dark:bg-gray-950">
                                                    SLA não cumprido: conclusão em {{ optional($ordem->real_conclusao)->format('d/m/Y H:i') ?: '-' }} após o prazo previsto {{ optional($ordem->prevista_conclusao)->format('d/m/Y H:i') ?: '-' }}.
                                                </span>
                                            </span>
                                        @endif
                                    @elseif($temSla && $osFinalizada)
                                        <span class="relative inline-flex items-center gap-1" x-data="{open:false}" @mouseleave="open=false">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 cursor-help"
                                                @mouseenter="open=true"
                                                @click.prevent="open=!open"
                                            >
                                                SLA sem conclusão registrada
                                                <button type="button" class="text-[11px] leading-none opacity-80 hover:opacity-100" @click.stop.prevent="open=!open" aria-label="Detalhes do SLA">ⓘ</button>
                                            </span>
                                            <span x-cloak x-show="open" x-transition class="pointer-events-none absolute bottom-full left-0 z-20 mb-1 w-72 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] font-medium text-white shadow-lg dark:bg-gray-950">
                                                OS finalizada com SLA definido, mas falta data prevista e/ou data real de conclusão para calcular cumprimento.
                                            </span>
                                        </span>
                                    @elseif($temSla)
                                        <span class="relative inline-flex items-center gap-1" x-data="{open:false}" @mouseleave="open=false">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 cursor-help"
                                                @mouseenter="open=true"
                                                @click.prevent="open=!open"
                                            >
                                                SLA definido (aguardando conclusão)
                                                <button type="button" class="text-[11px] leading-none opacity-80 hover:opacity-100" @click.stop.prevent="open=!open" aria-label="Detalhes do SLA">ⓘ</button>
                                            </span>
                                            <span x-cloak x-show="open" x-transition class="pointer-events-none absolute bottom-full left-0 z-20 mb-1 w-72 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] font-medium text-white shadow-lg dark:bg-gray-950">
                                                SLA definido para esta OS. O cálculo de cumprimento será feito quando houver data real de conclusão.
                                            </span>
                                        </span>
                                    @else
                                        <span class="relative inline-flex items-center gap-1" x-data="{open:false}" @mouseleave="open=false">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300 cursor-help"
                                                @mouseenter="open=true"
                                                @click.prevent="open=!open"
                                            >
                                                Sem SLA
                                                <button type="button" class="text-[11px] leading-none opacity-80 hover:opacity-100" @click.stop.prevent="open=!open" aria-label="Detalhes do SLA">ⓘ</button>
                                            </span>
                                            <span x-cloak x-show="open" x-transition class="pointer-events-none absolute bottom-full left-0 z-20 mb-1 w-64 rounded-md bg-gray-900 px-2 py-1.5 text-[11px] font-medium text-white shadow-lg dark:bg-gray-950">
                                                Esta OS não possui SLA definido.
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td x-show="visibleColumns.prioridade" class="px-4 py-3">
                                @if($ordem->prioridade)
                                    @php
                                        $prioridadeClasses = [
                                            'baixa' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'normal' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                            'alta' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'critica' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        ];
                                        $prioridadeLabels = [
                                            'baixa' => 'Baixa',
                                            'normal' => 'Normal',
                                            'alta' => 'Alta',
                                            'critica' => 'Crítica',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $prioridadeClasses[$ordem->prioridade] ?? $prioridadeClasses['normal'] }}">
                                        {{ $prioridadeLabels[$ordem->prioridade] ?? ucfirst($ordem->prioridade) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td x-show="visibleColumns.abertura" class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ optional($ordem->data_abertura)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td x-show="visibleColumns.conclusao" class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ optional($ordem->real_conclusao)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td x-show="visibleColumns.total" class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_geral ?? 0, 2, ',', '.') }}</td>
                            <td x-show="visibleColumns.acoes" class="px-4 py-3 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('ordens-servico.show', $ordem) }}" class="inline-flex items-center justify-center rounded p-1.5 text-brand-500 hover:bg-brand-50 hover:text-brand-600 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" title="Ver" aria-label="Ver OS">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('ordens-servico.print', $ordem) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" title="Imprimir / PDF" aria-label="Imprimir OS">
                                        {{-- Mesmo ícone de impressora que show/edit (Heroicons outline) --}}
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                    <form action="{{ route('ordens-servico.duplicate', $ordem) }}" method="POST" class="inline" onsubmit="return confirm('Duplicar esta OS? Serão copiados cliente, relato, diagnóstico, serviços realizados, observações, equipamento, itens, tipo, origem, prioridade. A nova OS será criada com status Aberta.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" title="Duplicar OS" aria-label="Duplicar OS">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2h-2"/></svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('ordens-servico.edit', $ordem) }}" class="inline-flex items-center justify-center rounded p-1.5 text-brand-500 hover:bg-brand-50 hover:text-brand-600 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-300" title="Editar" aria-label="Editar OS">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if($_podeExcluirOs)
                                    <button type="button" onclick="abrirModalExcluirMotivo('{{ route('ordens-servico.destroy', $ordem) }}')" class="inline-flex items-center justify-center rounded p-1.5 text-error-600 hover:bg-error-50 hover:text-error-700 dark:text-error-400 dark:hover:bg-error-500/10 dark:hover:text-error-300" title="Excluir" aria-label="Excluir OS">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td :colspan="(Object.values(visibleColumns).filter(v => v).length || 9) + 1" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma OS encontrada</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Mostrando {{ $ordens->firstItem() ?? 0 }} até {{ $ordens->lastItem() ?? 0 }} de {{ $ordens->total() }} resultados
                </div>
                <div>
                    {{ $ordens->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'ordem_servico'); ?>
@endif

<x-modal-excluir-motivo action="" titulo="Excluir ordem de serviço" descricao="Tem certeza que deseja excluir esta OS? Informe o motivo. Esta ação não pode ser desfeita." />
@if($_podeExcluirOs)
<x-modal-excluir-motivo
    :action="route('ordens-servico.bulk-destroy')"
    titulo="Excluir ordens de serviço em massa"
    descricao="As OS selecionadas na página atual serão excluídas permanentemente. Informe o motivo (mínimo 10 caracteres). Esta ação não pode ser desfeita."
    idModal="modalExcluirMotivoBulk"
    idForm="formExcluirMotivoBulk"
    idMotivo="motivoExcluirBulk"
    :use-delete-method="false"
>
    <x-slot name="camposExtras">
        <input type="hidden" name="ordem_ids_csv" id="os_bulk_destroy_ids_csv" value="">
    </x-slot>
</x-modal-excluir-motivo>
@endif
<script>
// ================================
// Ações em massa (status/prioridade)
// ================================
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('formOsBulkStatusPrioridade');
    var selectAll = document.getElementById('os_bulk_select_all');
    var checkboxes = Array.from(document.querySelectorAll('.os-bulk-checkbox'));
    var countEl = document.getElementById('os_bulk_selected_count');
    var idsInput = document.getElementById('os_bulk_ids_csv');
    var panel = document.getElementById('os_bulk_panel');
    var submitBtn = document.getElementById('os_bulk_submit_btn');
    var destroyBtn = document.getElementById('os_bulk_destroy_btn');

    function updateCount() {
        var selected = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = selected + ' OS selecionada(s)';
        if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        if (panel) panel.classList.toggle('hidden', selected === 0);
        if (submitBtn) submitBtn.disabled = selected === 0;
        if (destroyBtn) destroyBtn.disabled = selected === 0;
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateCount);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateCount();
        });
    }

    updateCount();

    if (form) {
        form.addEventListener('submit', function (e) {
            var selectedIds = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
            if (selectedIds.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos 1 OS para aplicar as alterações em massa.');
                return false;
            }
            if (idsInput) idsInput.value = selectedIds.join(',');
        });
    }
});

function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

@if($_podeExcluirOs)
function abrirModalExcluirMotivoBulk() {
    var checkboxes = Array.from(document.querySelectorAll('.os-bulk-checkbox'));
    var selectedIds = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    if (selectedIds.length === 0) {
        alert('Selecione pelo menos 1 OS para excluir.');
        return;
    }
    var hidden = document.getElementById('os_bulk_destroy_ids_csv');
    if (hidden) hidden.value = selectedIds.join(',');
    document.getElementById('motivoExcluirBulk').value = '';
    document.getElementById('modalExcluirMotivoBulk').classList.remove('hidden');
    document.getElementById('modalExcluirMotivoBulk').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
@endif
</script>
@endsection
