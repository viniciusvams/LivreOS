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
    $_auditCliExcluir = app(\App\Services\AuditCancelExcluirService::class);
    $_podeExcluirCliente = $_auditCliExcluir->canExcluir(auth()->user(), 'cliente');
    $_podeExcluirClienteMassa = $_podeExcluirCliente && \Illuminate\Support\Facades\Route::has('clientes.bulk-destroy');
@endphp
<div class="mb-4 flex flex-col gap-4 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <h1 class="mb-1 text-xl font-semibold text-gray-800 dark:text-white/90 sm:text-2xl">Cadastro de Clientes</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 sm:text-base">Gerencie todos os clientes do sistema</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto sm:py-2.5 touch-manipulation">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Novo Cliente
    </a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400 sm:mb-6 sm:p-4">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-lg border border-error-200 bg-error-50 p-3 text-sm text-error-700 dark:border-error-800 dark:bg-error-500/10 dark:text-error-400 sm:mb-6 sm:p-4">
    {{ session('error') }}
</div>
@endif

<!-- Filtros -->
<div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800 sm:mb-6">
    <form method="GET" action="{{ route('clientes.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Pessoa</label>
            <select name="tipo_pessoa" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="F" {{ request('tipo_pessoa') == 'F' ? 'selected' : '' }}>Pessoa Física</option>
                <option value="J" {{ request('tipo_pessoa') == 'J' ? 'selected' : '' }}>Pessoa Jurídica</option>
                <option value="E" {{ request('tipo_pessoa') == 'E' ? 'selected' : '' }}>Estrangeira</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
            <input type="text" name="nome" value="{{ request('nome') }}" placeholder="Buscar por nome..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Documento</label>
            <input type="text" name="documento" value="{{ request('documento') }}" placeholder="CPF/CNPJ..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Busca geral</label>
            <input type="text" name="busca_geral" value="{{ request('busca_geral') }}" placeholder="Nome, contato, e-mail, telefone, endereço..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-2">
            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 touch-manipulation sm:w-auto">Filtrar</button>
            @if(request()->hasAny(['tipo_pessoa', 'nome', 'documento', 'busca_geral']))
                <a href="{{ route('clientes.index') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 sm:w-auto">Limpar</a>
            @endif
        </div>
    </form>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'cliente'); ?>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total: {{ $clientes->total() }} clientes</span>
        <div class="flex flex-wrap items-center gap-2 clientes-toolbar-buttons">
            <a href="{{ route('clientes.export', request()->all()) }}" class="clientes-btn-export inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 touch-manipulation sm:py-1.5">Exportar CSV</a>
            <a href="{{ route('clientes.export-pdf', request()->all()) }}" class="clientes-btn-export inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 touch-manipulation sm:py-1.5">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                PDF
            </a>
        </div>
    </div>

    @if($_podeExcluirClienteMassa)
    <div id="cli_bulk_panel" class="hidden border-b border-gray-200 bg-amber-50/80 px-4 py-3 dark:border-gray-700 dark:bg-amber-900/20">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700 dark:text-gray-300"><span id="cli_bulk_selected_count">0</span> cliente(s) selecionado(s)</p>
            <button type="button" id="cli_bulk_destroy_btn" disabled onclick="abrirModalExcluirMotivoBulkClientes()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                Excluir selecionados
            </button>
        </div>
    </div>
    @endif

    @if($clientes->isEmpty())
        <div class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
            <p class="text-sm">Nenhum cliente encontrado.</p>
            <a href="{{ route('clientes.index') }}" class="mt-2 inline-block text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">Limpar filtros</a>
        </div>
    @else
        {{-- Mobile: lista em cards --}}
        <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($clientes as $cliente)
            <div class="p-4">
                @if($_podeExcluirClienteMassa)
                <div class="mb-2 flex items-center gap-2">
                    <input type="checkbox" class="cli-bulk-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="{{ $cliente->id }}" aria-label="Selecionar cliente">
                </div>
                @endif
                <a href="{{ route('clientes.show', $cliente) }}" class="block rounded-lg active:bg-gray-50 dark:active:bg-gray-700/50 -m-2 p-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ $cliente->nome }}</p>
                            @if($cliente->razao_social)
                                <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">{{ $cliente->razao_social }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $cliente->tipo_pessoa == 'F' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' : '' }}
                                    {{ $cliente->tipo_pessoa == 'J' ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400' : '' }}
                                    {{ $cliente->tipo_pessoa == 'E' ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400' : '' }}">
                                    {{ $cliente->tipo_pessoa_texto }}
                                </span>
                                @if($cliente->documento_principal)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $cliente->documento_principal }}</span>
                                @endif
                            </div>
                            @if($cliente->enderecos->count() > 0)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $cliente->enderecos->first()->cidade }}/{{ $cliente->enderecos->first()->estado }}</p>
                            @endif
                            <div class="mt-1.5 flex items-center gap-0.5">
                                @for($i = 0; $i < 4; $i++)
                                <svg class="h-3.5 w-3.5 {{ $i < $cliente->classificacao_rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                @endfor
                            </div>
                        </div>
                        <span class="shrink-0 text-gray-400 dark:text-gray-500" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
                <div class="mt-3 flex gap-2 clientes-card-actions">
                    <a href="{{ route('clientes.show', $cliente) }}" class="flex-1 rounded-lg bg-brand-100 py-2.5 text-center text-sm font-medium text-brand-700 dark:bg-brand-500 dark:text-white dark:hover:bg-brand-400 touch-manipulation">Ver</a>
                    <a href="{{ route('clientes.edit', $cliente) }}" class="flex-1 rounded-lg bg-gray-100 py-2.5 text-center text-sm font-medium text-gray-700 touch-manipulation clientes-btn-editar">Editar</a>
                    @if($_podeExcluirCliente)
                    <button type="button" onclick="abrirModalExcluirMotivo('{{ route('clientes.destroy', $cliente) }}')" class="rounded-lg bg-red-50 py-2.5 px-3 text-sm font-medium text-red-700 dark:bg-gray-700 dark:text-red-300 dark:hover:bg-gray-600 touch-manipulation">Deletar</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop: tabela --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        @if($_podeExcluirClienteMassa)
                        <th class="w-10 px-2 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            <input type="checkbox" id="cli_bulk_select_all" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" title="Selecionar todos desta página">
                        </th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Endereço</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Rating</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-400">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($clientes as $cliente)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        @if($_podeExcluirClienteMassa)
                        <td class="px-2 py-3">
                            <input type="checkbox" class="cli-bulk-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="{{ $cliente->id }}">
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $cliente->nome }}</div>
                            @if($cliente->razao_social)
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $cliente->razao_social }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $cliente->tipo_pessoa == 'F' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' : '' }}
                                {{ $cliente->tipo_pessoa == 'J' ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400' : '' }}
                                {{ $cliente->tipo_pessoa == 'E' ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400' : '' }}">
                                {{ $cliente->tipo_pessoa_texto }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $cliente->documento_principal }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            @if($cliente->enderecos->count() > 0)
                            {{ $cliente->enderecos->first()->cidade }}/{{ $cliente->enderecos->first()->estado }}
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-0.5">
                                @for($i = 0; $i < 4; $i++)
                                <svg class="h-4 w-4 {{ $i < $cliente->classificacao_rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                @endfor
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('clientes.show', $cliente) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">Ver</a>
                                <a href="{{ route('clientes.edit', $cliente) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-500 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">Editar</a>
                                @if($_podeExcluirCliente)
                                <button type="button" onclick="abrirModalExcluirMotivo('{{ route('clientes.destroy', $cliente) }}')" class="rounded-lg border border-error-300 bg-white px-3 py-1.5 text-sm font-medium text-error-700 hover:bg-error-50 dark:border-error-600 dark:bg-gray-700 dark:text-error-300 dark:hover:bg-gray-600">Deletar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
            {{ $clientes->links() }}
        </div>
    @endif
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'cliente'); ?>
@endif

<x-modal-excluir-motivo action="" titulo="Excluir cliente" descricao="Tem certeza que deseja excluir este cliente? Informe o motivo. Esta ação não pode ser desfeita." />
@if($_podeExcluirClienteMassa)
<x-modal-excluir-motivo
    :action="route('clientes.bulk-destroy')"
    titulo="Excluir clientes em massa"
    descricao="Os clientes selecionados nesta página serão excluídos permanentemente. Informe o motivo (mínimo 10 caracteres). Esta ação não pode ser desfeita."
    idModal="modalExcluirMotivoBulkClientes"
    idForm="formExcluirMotivoBulkClientes"
    idMotivo="motivoExcluirBulkClientes"
    :use-delete-method="false"
>
    <x-slot name="camposExtras">
        <input type="hidden" name="cliente_ids_csv" id="clientes_bulk_ids_csv" value="">
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
@if($_podeExcluirClienteMassa)
function abrirModalExcluirMotivoBulkClientes() {
    var checkboxes = Array.from(document.querySelectorAll('.cli-bulk-checkbox'));
    var selectedIds = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    if (selectedIds.length === 0) {
        alert('Selecione pelo menos 1 cliente.');
        return;
    }
    var hidden = document.getElementById('clientes_bulk_ids_csv');
    if (hidden) hidden.value = selectedIds.join(',');
    document.getElementById('motivoExcluirBulkClientes').value = '';
    document.getElementById('modalExcluirMotivoBulkClientes').classList.remove('hidden');
    document.getElementById('modalExcluirMotivoBulkClientes').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('cli_bulk_select_all');
    var checkboxes = Array.from(document.querySelectorAll('.cli-bulk-checkbox'));
    var countEl = document.getElementById('cli_bulk_selected_count');
    var panel = document.getElementById('cli_bulk_panel');
    var destroyBtn = document.getElementById('cli_bulk_destroy_btn');
    function updateCliBulk() {
        var selected = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = selected;
        if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        if (panel) panel.classList.toggle('hidden', selected === 0);
        if (destroyBtn) destroyBtn.disabled = selected === 0;
    }
    checkboxes.forEach(function (cb) { cb.addEventListener('change', updateCliBulk); });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateCliBulk();
        });
    }
    updateCliBulk();
});
@endif
</script>
@endsection
