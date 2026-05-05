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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Numeração das Ordens de Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">Configure com segurança qual será o próximo número da OS.</p>
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

@if($errors->any())
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Última OS criada</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $ultimo_numero > 0 ? $ultimo_numero : 'Nenhuma' }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo natural</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $proximo_natural }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo efetivo</p>
        <p class="mt-1 text-2xl font-semibold text-brand-600 dark:text-brand-400">{{ $proximo_efetivo }}</p>
    </div>
</div>

<form action="{{ route('configuracoes-sistema.numeracao-os.update') }}" method="POST" class="mt-6">
    @csrf
    @method('PUT')
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <label for="proximo_numero" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Definir próximo número da OS</label>
        <input
            type="number"
            id="proximo_numero"
            name="proximo_numero"
            min="1"
            step="1"
            value="{{ old('proximo_numero', $proximo_configurado) }}"
            class="w-full max-w-sm rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Se já existe OS, o valor deve ser maior que a última criada. Exemplo: última OS 10, próximo número 1000.
        </p>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                Salvar próxima numeração
            </button>
        </div>
    </div>
</form>
@endsection

