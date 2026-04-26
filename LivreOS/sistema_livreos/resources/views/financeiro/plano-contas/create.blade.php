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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Conta</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova conta no plano de contas</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.plano-contas.store') }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Pai</label>
                <select name="pai_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhuma (Conta Principal)</option>
                    @foreach($contasPai as $pai)
                        <option value="{{ $pai->id }}" {{ old('pai_id') == $pai->id ? 'selected' : '' }}>{{ $pai->codigo ?? '' }} - {{ $pai->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" name="codigo" value="{{ old('codigo') }}" placeholder="Ex: 1.1.1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                <select name="tipo" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="receita" {{ old('tipo') === 'receita' ? 'selected' : '' }}>Receita</option>
                    <option value="despesa" {{ old('tipo') === 'despesa' ? 'selected' : '' }}>Despesa</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria *</label>
                <select name="categoria" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="operacional" {{ old('categoria') === 'operacional' ? 'selected' : '' }}>Operacional</option>
                    <option value="nao_operacional" {{ old('categoria') === 'nao_operacional' ? 'selected' : '' }}>Não Operacional</option>
                    <option value="financeira" {{ old('categoria') === 'financeira' ? 'selected' : '' }}>Financeira</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem</label>
                <input type="number" name="ordem" value="{{ old('ordem', 0) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea name="descricao" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('descricao') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Conta ativa</span>
                </label>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.plano-contas.form.extra', null); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.plano-contas.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
@endsection
