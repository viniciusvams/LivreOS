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
    <a href="{{ route('propostas-comerciais.modelos-documento.index') }}" class="text-sm text-gray-500 hover:text-brand-600 dark:text-gray-400">← Voltar</a>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
</div>

@if($errors->any())
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="mb-6">
    @include('propostas-comerciais.modelos-documento._ajuda-docx')
</div>

<form method="POST" action="{{ route('propostas-comerciais.modelos-documento.store') }}" enctype="multipart/form-data" class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    @csrf
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome <span class="text-red-500">*</span></label>
        <input type="text" name="nome" value="{{ old('nome') }}" required maxlength="160" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição</label>
        <textarea name="descricao" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">{{ old('descricao') }}</textarea>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Ordem na lista</label>
        <input type="number" name="ordem" value="{{ old('ordem', 0) }}" min="0" max="65535" class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }} class="rounded border-gray-300">
        <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Arquivo (.docx, .html ou .htm) <span class="text-red-500">*</span></label>
        <input type="file" name="arquivo" accept=".docx,.html,.htm,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/html" required class="block w-full text-sm text-gray-600 dark:text-gray-400">
        <p class="mt-1 text-xs text-gray-500">Máx. 15 MB. O formato do modelo fica definido pela extensão do ficheiro. Use as variáveis <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${...}</code> da ajuda (proposta, cliente, empresa, totais, <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${itens_tabela_linhas}</code> no HTML, etc.).</p>
    </div>
    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        <a href="{{ route('propostas-comerciais.modelos-documento.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</a>
    </div>
</form>
@endsection
