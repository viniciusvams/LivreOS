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
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plugins</h1>
        <p class="text-gray-600 dark:text-gray-400">Instale e gerencie plugins para estender as funcionalidades do ERP. Envie um arquivo .zip contendo uma pasta com plugin.php na raiz. Plugins desativados podem ser excluídos.</p>
    </div>
    <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    {{ session('error') }}
</div>
@endif

@if(session('warning'))
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
    {{ session('warning') }}
</div>
@endif

{{-- Formulário de upload --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Instalar novo plugin</h2>
    <form action="{{ route('configuracoes-sistema.plugins.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
        @csrf
        <div class="min-w-[220px] flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo .zip do plugin</label>
            <input type="file" name="plugin_zip" accept=".zip" required class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-400">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">O ZIP deve conter uma pasta com plugin.php na raiz (ex: meu-plugin/plugin.php). Máx. 10 MB. Para atualizar: desative o plugin e envie o novo .zip. Metadados opcionais no cabeçalho: Requires PHP, Requires ERP.</p>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Instalar plugin
            </span>
        </button>
    </form>
</div>

{{-- Lista de plugins --}}
<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Plugins instalados</h2>
        @if(count($plugins) > 0)
        <form action="{{ route('configuracoes-sistema.plugins.bulk-action') }}" method="POST" id="bulk-form" class="flex items-center gap-2" onsubmit="if(!document.getElementById('bulk-action-select').value){alert('Selecione uma ação'); return false;} return true;">
            @csrf
            <select name="action" id="bulk-action-select" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Ações em lote</option>
                <option value="activate">Ativar selecionados</option>
                <option value="deactivate">Desativar selecionados</option>
            </select>
            <button type="submit" class="rounded-lg bg-gray-200 px-3 py-1.5 text-sm hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600">Aplicar</button>
        </form>
        @endif
    </div>
    @if(count($plugins) > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="w-10 px-4 py-3"></th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Plugin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Versão</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Autor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @foreach($plugins as $slug => $plugin)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-4">
                        @if(empty($plugin['requirements_error']))
                        <input type="checkbox" name="plugins[]" value="{{ $slug }}" form="bulk-form" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        @else
                        <span class="text-gray-300" title="{{ $plugin['requirements_error'] }}">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <a href="{{ route('configuracoes-sistema.plugins.show', $slug) }}" class="font-medium text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300">{{ $plugin['name'] ?? $slug }}</a>
                            @if(!empty($plugin['description']))
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($plugin['description'], 60) }}</p>
                            @endif
                            @if(!empty($plugin['requirements_error']))
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ $plugin['requirements_error'] }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $plugin['version'] ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $plugin['author'] ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(!empty($plugin['active']))
                            <span class="inline-flex rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">Ativo</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-3">
                            @if(!empty($plugin['active']))
                                <form action="{{ route('configuracoes-sistema.plugins.deactivate', $slug) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">Desativar</button>
                                </form>
                            @else
                                <form action="{{ route('configuracoes-sistema.plugins.activate', $slug) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300">Ativar</button>
                                </form>
                            @endif
                            @if(empty($plugin['active']))
                                <form action="{{ route('configuracoes-sistema.plugins.destroy', $slug) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este plugin? Os arquivos serão removidos permanentemente.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-error-600 hover:text-error-900 dark:text-error-400 dark:hover:text-error-300">Excluir</button>
                                </form>
                            @else
                                <span class="cursor-not-allowed text-gray-400 dark:text-gray-500" title="Desative o plugin antes de excluir">Excluir</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-8 text-center">
        <p class="text-gray-500 dark:text-gray-400">Nenhum plugin instalado. Use o formulário acima para fazer upload de um plugin .zip.</p>
    </div>
    @endif
</div>
@endsection
