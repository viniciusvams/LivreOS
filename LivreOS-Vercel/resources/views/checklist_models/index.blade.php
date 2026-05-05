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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Modelos de Checklist</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie os modelos de checklist para uso nas Ordens de Serviço</p>
    </div>
    <a href="{{ route('checklist-models.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo Modelo</a>
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

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <input type="text" name="nome" placeholder="Buscar por nome" value="{{ request('nome') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <select name="ativo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">Todos os status</option>
            <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
            <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="{{ route('checklist-models.index') }}" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
        </div>
    </form>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'checklist_model'); ?>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Campos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Criado em</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($checklistModels as $model)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white/90">{{ $model->nome }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($model->descricao, 50) ?: '-' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ count($model->campos ?? []) }} campo(s)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($model->ativo)
                            <span class="inline-flex rounded-full bg-success-100 px-2 py-1 text-xs font-semibold text-success-800 dark:bg-success-500/20 dark:text-success-400">Ativo</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800 dark:bg-gray-500/20 dark:text-gray-400">Inativo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $model->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('checklist-models.edit', $model) }}" class="text-brand-600 hover:text-brand-900 dark:text-brand-400">Editar</a>
                            <form action="{{ route('checklist-models.destroy', $model) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este modelo?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-600 hover:text-error-900 dark:text-error-400">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhum modelo de checklist encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($checklistModels->hasPages())
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        {{ $checklistModels->links() }}
    </div>
    @endif
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'checklist_model'); ?>
@endif
@endsection
