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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Modelo de Checklist</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $checklistModel->nome }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('checklist-models.edit', $checklistModel) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('checklist-models.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações Básicas</h2>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90">{{ $checklistModel->nome }}</p>
            </div>
            @if($checklistModel->descricao)
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90">{{ $checklistModel->descricao }}</p>
            </div>
            @endif
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                <p class="mt-1">
                    @if($checklistModel->ativo)
                        <span class="inline-flex rounded-full bg-success-100 px-2 py-1 text-xs font-semibold text-success-800 dark:bg-success-500/20 dark:text-success-400">Ativo</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800 dark:bg-gray-500/20 dark:text-gray-400">Inativo</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Criado em</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90">{{ $checklistModel->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos do Formulário</h2>
        <div class="space-y-3">
            @forelse($checklistModel->campos ?? [] as $index => $campo)
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $campo['label'] ?? 'Campo ' . ($index + 1) }}
                            @if($campo['obrigatorio'] ?? false)
                                <span class="text-error-500">*</span>
                            @endif
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ ucfirst($campo['tipo'] ?? 'texto') }}
                        </span>
                    </div>
                    @if(($campo['tipo'] ?? '') === 'selecao' && !empty($campo['opcoes']))
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Opções: {{ implode(', ', $campo['opcoes']) }}
                        </p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum campo definido.</p>
            @endforelse
        </div>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('admin.checklist-models.show.extra', $checklistModel); ?>
@endif
@endsection
