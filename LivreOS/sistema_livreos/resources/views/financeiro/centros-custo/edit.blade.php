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
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Centro de Custo</h1>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Altere os dados do centro de custo.</p>
</div>

<form action="{{ route('financeiro.centros-custo.update', $centro) }}" method="POST" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="codigo" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" id="codigo" name="codigo" value="{{ old('codigo', $centro->codigo) }}" maxlength="30" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
            <div>
                <label for="ordem" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem</label>
                <input type="number" id="ordem" name="ordem" value="{{ old('ordem', $centro->ordem) }}" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
        <div class="mt-4">
            <label for="nome" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
            <input type="text" id="nome" name="nome" value="{{ old('nome', $centro->nome) }}" required maxlength="200" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white @error('nome') border-red-500 @enderror">
            @error('nome')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>
        <div class="mt-4">
            <label for="descricao" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
            <textarea id="descricao" name="descricao" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">{{ old('descricao', $centro->descricao) }}</textarea>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <input type="checkbox" id="ativo" name="ativo" value="1" {{ old('ativo', $centro->ativo) ? 'checked' : '' }} class="rounded border-gray-300">
            <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Salvar</button>
        <a href="{{ route('financeiro.centros-custo.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Cancelar</a>
    </div>
</form>
@endsection
