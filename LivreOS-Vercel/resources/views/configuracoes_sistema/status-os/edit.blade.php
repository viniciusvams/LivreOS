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
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Status</h1>
    <p class="text-gray-600 dark:text-gray-400">Atualize as informações do status</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('configuracoes-sistema.status-os.update', $status) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome', $status->nome) }}" {{ $status->sistema ? 'readonly' : '' }} required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 {{ $status->sistema ? 'bg-gray-100 dark:bg-gray-800' : '' }}">
                @if($status->sistema)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Status do sistema não pode ter o nome alterado.</p>
                @endif
                @error('nome')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor (hex)</label>
                <div class="flex gap-2">
                    <input type="color" name="cor_picker" value="{{ old('cor', $status->cor ?? '#6b7280') }}" class="h-10 w-14 cursor-pointer rounded border border-gray-300 dark:border-gray-600" onchange="this.nextElementSibling.value=this.value">
                    <input type="text" name="cor" value="{{ old('cor', $status->cor ?? '#6b7280') }}" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="#6b7280" maxlength="20">
                </div>
                @error('cor')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de exibição</label>
                <input type="number" name="ordem" value="{{ old('ordem', $status->ordem) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('ordem')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2 space-y-3">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Comportamento ao selecionar</label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marca_inicio" value="1" {{ old('marca_inicio', $status->marca_inicio) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Marcar início da execução</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marca_conclusao" value="1" {{ old('marca_conclusao', $status->marca_conclusao) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" {{ $status->sistema ? 'disabled' : '' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Marcar conclusão</span>
                    @if($status->sistema)
                    <input type="hidden" name="marca_conclusao" value="{{ $status->marca_conclusao ? '1' : '0' }}">
                    @endif
                </label>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $status->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Status ativo</span>
                </label>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('configuracoes.status-os.form.extra', $status); ?>
        @endif

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('configuracoes-sistema.status-os.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<div class="mt-4">
    <a href="{{ route('configuracoes-sistema.status-os.index') }}" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">← Voltar para Status</a>
</div>
@endsection
