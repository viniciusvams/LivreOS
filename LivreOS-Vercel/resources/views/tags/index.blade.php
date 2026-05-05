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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Cadastro de Tags</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as tags padrão e personalizadas do sistema</p>
    </div>
    <a href="{{ route('tags.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Tag</a>
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

@if(function_exists('do_action'))
<?php do_action('entity.index.before_table', 'tag'); ?>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Pré-visualização</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90">{{ $tag->nome }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tag->tipo === 'padrao' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($tag->tipo) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $tag->descricao ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <button type="button" class="rounded px-3 py-1 text-sm font-medium" style="background-color: {{ $tag->cor_fundo }}; color: {{ $tag->cor_fonte }};">
                            {{ $tag->nome }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $tag->ativo ? 'Sim' : 'Não' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            @if($tag->tipo !== 'padrao')
                            <a href="{{ route('tags.edit', $tag) }}" class="text-brand-500 hover:underline">Editar</a>
                            <form action="{{ route('tags.destroy', $tag) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta tag?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-500 hover:underline">Excluir</button>
                            </form>
                            @else
                            <span class="text-gray-400 text-sm">Não editável</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhuma tag encontrada</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $tags->links() }}
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('entity.index.after_table', 'tag'); ?>
@endif
@endsection
