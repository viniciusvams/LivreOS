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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Equipamento</h1>
        <p class="text-gray-600 dark:text-gray-400">Cliente: {{ $equipamento->cliente->nome ?? '—' }}</p>
    </div>
    <a href="{{ route('clientes.show', $equipamento->cliente_id) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar ao cliente</a>
</div>

@if($errors->any())
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('equipamentos.update', $equipamento) }}" method="POST" class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
            <input type="text" name="marca" value="{{ old('marca', $equipamento->marca) }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @error('marca')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo</label>
            <input type="text" name="modelo" value="{{ old('modelo', $equipamento->modelo) }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @error('modelo')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº Série</label>
            <input type="text" name="numero_serie" value="{{ old('numero_serie', $equipamento->numero_serie) }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @error('numero_serie')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nº Patrimônio</label>
            <input type="text" name="numero_patrimonio" value="{{ old('numero_patrimonio', $equipamento->numero_patrimonio) }}" maxlength="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @error('numero_patrimonio')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acessórios</label>
            <textarea name="acessorios" rows="3" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('acessorios', $equipamento->acessorios) }}</textarea>
            @error('acessorios')<p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('clientes.show', $equipamento->cliente_id) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600">Salvar</button>
    </div>
</form>
@endsection
