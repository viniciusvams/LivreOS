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
    $_auditProdExcluir = app(\App\Services\AuditCancelExcluirService::class);
    $_podeExcluirProduto = $_auditProdExcluir->canExcluir(auth()->user(), 'produto');
@endphp
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Produtos</h1>
        <p class="text-gray-600 dark:text-gray-400">Cadastro e controle de produtos e componentes</p>
    </div>
    <a href="{{ route('produtos.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo Produto</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <input type="text" name="nome" placeholder="Buscar por nome" value="{{ request('nome') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="text" name="codigo_sku" placeholder="Buscar por SKU" value="{{ request('codigo_sku') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <select name="categoria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">Categoria</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nome }}</option>
            @endforeach
        </select>
        <select name="formato" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">Formato</option>
            <option value="simples" {{ request('formato') === 'simples' ? 'selected' : '' }}>Simples</option>
            <option value="variacao" {{ request('formato') === 'variacao' ? 'selected' : '' }}>Variação</option>
            <option value="composicao" {{ request('formato') === 'composicao' ? 'selected' : '' }}>Composição</option>
        </select>
        <select name="estoque_status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">Estoque</option>
            <option value="ok" {{ request('estoque_status') === 'ok' ? 'selected' : '' }}>OK</option>
            <option value="baixo" {{ request('estoque_status') === 'baixo' ? 'selected' : '' }}>Baixo</option>
            <option value="zerado" {{ request('estoque_status') === 'zerado' ? 'selected' : '' }}>Zerado</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('produtos.index') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
        </div>

        <input type="number" step="0.01" min="0" name="preco_min" placeholder="Preço mín." value="{{ request('preco_min') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="number" step="0.01" min="0" name="preco_max" placeholder="Preço máx." value="{{ request('preco_max') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <select name="ordenar_por" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="nome" {{ request('ordenar_por', 'nome') === 'nome' ? 'selected' : '' }}>Ordenar por nome</option>
            <option value="codigo_sku" {{ request('ordenar_por') === 'codigo_sku' ? 'selected' : '' }}>Ordenar por SKU</option>
            <option value="preco_venda" {{ request('ordenar_por') === 'preco_venda' ? 'selected' : '' }}>Ordenar por preço</option>
            <option value="estoque_quantidade" {{ request('ordenar_por') === 'estoque_quantidade' ? 'selected' : '' }}>Ordenar por estoque</option>
            <option value="updated_at" {{ request('ordenar_por') === 'updated_at' ? 'selected' : '' }}>Ordenar por atualização</option>
        </select>
        <select name="direcao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="asc" {{ request('direcao', 'asc') === 'asc' ? 'selected' : '' }}>Crescente</option>
            <option value="desc" {{ request('direcao') === 'desc' ? 'selected' : '' }}>Decrescente</option>
        </select>
        <select name="por_pagina" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach([15,30,50,100] as $pp)
                <option value="{{ $pp }}" {{ (int) request('por_pagina', 15) === $pp ? 'selected' : '' }}>{{ $pp }} por página</option>
            @endforeach
        </select>
    </form>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'produto'); ?>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        <div class="flex flex-wrap items-center gap-3">
            <span>Total: {{ $produtos->total() }} produtos</span>
            <span>Página {{ $produtos->currentPage() }} de {{ $produtos->lastPage() }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('produtos.export', request()->all()) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Exportar CSV</a>
            <a href="{{ route('produtos.export-pdf', request()->all()) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Exportar PDF
            </a>
            <button type="button" id="toggle-columns-btn" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Colunas</button>
            <div id="columns-panel" class="hidden rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-700 shadow-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="nome" checked>Nome</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="sku" checked>SKU</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="formato" checked>Formato</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="unidade" checked>Unidade</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="preco" checked>Preço</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="estoque" checked>Estoque</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="deposito" checked>Depósito</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="categoria" checked>Categoria</label>
                    <label class="flex items-center gap-2"><input type="checkbox" class="col-toggle" data-col="acoes" checked>Ações</label>
                </div>
            </div>
        </div>
    </div>

    @if($_podeExcluirProduto)
    <div id="prod_bulk_panel" class="hidden border-b border-gray-200 bg-amber-50/80 px-4 py-3 dark:border-gray-700 dark:bg-amber-900/20">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700 dark:text-gray-300"><span id="prod_bulk_selected_count">0</span> produto(s) selecionado(s)</p>
            <button type="button" id="prod_bulk_destroy_btn" disabled onclick="abrirModalExcluirMotivoBulkProdutos()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                Excluir selecionados
            </button>
        </div>
    </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    @php
                        $sort = request('ordenar_por', 'nome');
                        $dir = request('direcao', 'asc');
                        $nextDir = fn($col) => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
                        $arrow = fn($col) => $sort === $col ? ($dir === 'asc' ? '▲' : '▼') : '';
                    @endphp
                    @if($_podeExcluirProduto)
                    <th class="w-10 px-2 py-3">
                        <input type="checkbox" id="prod_bulk_select_all" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" title="Selecionar todos desta página">
                    </th>
                    @endif
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="nome">
                        <a href="{{ request()->fullUrlWithQuery(['ordenar_por' => 'nome', 'direcao' => $nextDir('nome')]) }}" class="inline-flex items-center gap-1 hover:underline">
                            Nome <span class="text-xs">{{ $arrow('nome') }}</span>
                        </a>
                    </th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="sku">
                        <a href="{{ request()->fullUrlWithQuery(['ordenar_por' => 'codigo_sku', 'direcao' => $nextDir('codigo_sku')]) }}" class="inline-flex items-center gap-1 hover:underline">
                            SKU <span class="text-xs">{{ $arrow('codigo_sku') }}</span>
                        </a>
                    </th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="formato">Formato</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="unidade">Unidade</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="preco">
                        <a href="{{ request()->fullUrlWithQuery(['ordenar_por' => 'preco_venda', 'direcao' => $nextDir('preco_venda')]) }}" class="inline-flex items-center gap-1 hover:underline">
                            Preço <span class="text-xs">{{ $arrow('preco_venda') }}</span>
                        </a>
                    </th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="estoque">
                        <a href="{{ request()->fullUrlWithQuery(['ordenar_por' => 'estoque_quantidade', 'direcao' => $nextDir('estoque_quantidade')]) }}" class="inline-flex items-center gap-1 hover:underline">
                            Estoque <span class="text-xs">{{ $arrow('estoque_quantidade') }}</span>
                        </a>
                    </th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="deposito">Depósito</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="categoria">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400" data-col="acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($produtos as $produto)
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    @if($_podeExcluirProduto)
                    <td class="px-2 py-3">
                        <input type="checkbox" class="prod-bulk-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="{{ $produto->id }}">
                    </td>
                    @endif
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90" data-col="nome">{{ $produto->nome }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="sku">{{ $produto->codigo_sku ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="formato">{{ ucfirst($produto->formato) }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="unidade">{{ $produto->unidade }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="preco">{{ $produto->preco_venda ? number_format($produto->preco_venda, 2, ',', '.') : '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="estoque">{{ $produto->estoque_quantidade !== null && $produto->estoque_quantidade !== '' ? format_quantity($produto->estoque_quantidade) : '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="deposito">{{ $produto->deposito?->nome ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400" data-col="categoria">{{ $produto->categoria?->nome ?: '-' }}</td>
                    <td class="px-4 py-3" data-col="acoes">
                        <div class="flex gap-2">
                            <a href="{{ route('produtos.show', $produto) }}" class="text-brand-500 hover:underline">Ver</a>
                            <a href="{{ route('produtos.edit', $produto) }}" class="text-brand-500 hover:underline">Editar</a>
                            @if($_podeExcluirProduto)
                            <button type="button" onclick="abrirModalExcluirMotivo('{{ route('produtos.destroy', $produto) }}')" class="text-error-500 hover:underline">Excluir</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $_podeExcluirProduto ? 10 : 9 }}" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum produto encontrado</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $produtos->links() }}
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'produto'); ?>
@endif

<x-modal-excluir-motivo action="" titulo="Excluir produto" descricao="Tem certeza que deseja excluir este produto? Informe o motivo. Esta ação não pode ser desfeita." />
@if($_podeExcluirProduto)
<x-modal-excluir-motivo
    :action="route('produtos.bulk-destroy')"
    titulo="Excluir produtos em massa"
    descricao="Os produtos selecionados nesta página serão excluídos permanentemente. Informe o motivo (mínimo 10 caracteres). Esta ação não pode ser desfeita."
    idModal="modalExcluirMotivoBulkProdutos"
    idForm="formExcluirMotivoBulkProdutos"
    idMotivo="motivoExcluirBulkProdutos"
    :use-delete-method="false"
>
    <x-slot name="camposExtras">
        <input type="hidden" name="produto_ids_csv" id="produtos_bulk_ids_csv" value="">
    </x-slot>
</x-modal-excluir-motivo>
@endif
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
@if($_podeExcluirProduto)
function abrirModalExcluirMotivoBulkProdutos() {
    var checkboxes = Array.from(document.querySelectorAll('.prod-bulk-checkbox'));
    var selectedIds = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    if (selectedIds.length === 0) {
        alert('Selecione pelo menos 1 produto.');
        return;
    }
    var hidden = document.getElementById('produtos_bulk_ids_csv');
    if (hidden) hidden.value = selectedIds.join(',');
    document.getElementById('motivoExcluirBulkProdutos').value = '';
    document.getElementById('modalExcluirMotivoBulkProdutos').classList.remove('hidden');
    document.getElementById('modalExcluirMotivoBulkProdutos').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('prod_bulk_select_all');
    var checkboxes = Array.from(document.querySelectorAll('.prod-bulk-checkbox'));
    var countEl = document.getElementById('prod_bulk_selected_count');
    var panel = document.getElementById('prod_bulk_panel');
    var destroyBtn = document.getElementById('prod_bulk_destroy_btn');
    function updateProdBulk() {
        var selected = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = selected;
        if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        if (panel) panel.classList.toggle('hidden', selected === 0);
        if (destroyBtn) destroyBtn.disabled = selected === 0;
    }
    checkboxes.forEach(function (cb) { cb.addEventListener('change', updateProdBulk); });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateProdBulk();
        });
    }
    updateProdBulk();
});
@endif
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const panel = document.getElementById('columns-panel');
    const btn = document.getElementById('toggle-columns-btn');
    const toggles = document.querySelectorAll('.col-toggle');
    const storageKey = 'produtos_columns';

    const applyVisibility = (cols) => {
        toggles.forEach(toggle => {
            const col = toggle.dataset.col;
            const show = cols.includes(col);
            document.querySelectorAll(`[data-col="${col}"]`).forEach(el => {
                el.classList.toggle('hidden', !show);
            });
        });
    };

    const saved = localStorage.getItem(storageKey);
    if (saved) {
        const cols = JSON.parse(saved);
        toggles.forEach(t => t.checked = cols.includes(t.dataset.col));
        applyVisibility(cols);
    }

    btn.addEventListener('click', () => {
        panel.classList.toggle('hidden');
    });

    toggles.forEach(toggle => {
        toggle.addEventListener('change', () => {
            const cols = Array.from(toggles).filter(t => t.checked).map(t => t.dataset.col);
            localStorage.setItem(storageKey, JSON.stringify(cols));
            applyVisibility(cols);
        });
    });
});
</script>
@endsection
