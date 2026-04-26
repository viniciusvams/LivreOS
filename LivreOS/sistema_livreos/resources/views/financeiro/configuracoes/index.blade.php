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
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Configurações Financeiro</h1>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Parâmetros globais para juros e multa por atraso. Usados na baixa de títulos e no relatório de inadimplência.</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
@endif

<form action="{{ route('financeiro.configuracoes.update') }}" method="POST" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-medium text-gray-800 dark:text-white/90">Juros e multa por atraso</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Estes valores são usados para sugerir multa e juros na tela de baixa e no relatório de inadimplência. O usuário pode editar antes de confirmar.</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="multa_percentual" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Multa (% sobre o valor)</label>
                <input type="number" id="multa_percentual" name="multa_percentual" value="{{ old('multa_percentual', $multa_percentual) }}" min="0" max="100" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex.: 2 = 2% do valor original (após carência)</p>
            </div>
            <div>
                <label for="juros_mensal_percentual" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros (% ao mês)</label>
                <input type="number" id="juros_mensal_percentual" name="juros_mensal_percentual" value="{{ old('juros_mensal_percentual', $juros_mensal_percentual) }}" min="0" max="100" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Proporcional aos dias de atraso</p>
            </div>
            <div>
                <label for="dias_carencia_multa" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias de carência para multa</label>
                <input type="number" id="dias_carencia_multa" name="dias_carencia_multa" value="{{ old('dias_carencia_multa', $dias_carencia_multa) }}" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Multa só após este número de dias de atraso</p>
            </div>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Salvar configurações</button>
        <a href="{{ route('financeiro.dashboard') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Voltar</a>
    </div>
</form>
@endsection
