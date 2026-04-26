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
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('configuracoes-sistema.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Configurações — Propostas comerciais</h1>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('configuracoes-sistema.propostas-comerciais.update') }}">
    @csrf @method('PUT')

    <div class="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        <div class="mb-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Numeração das propostas</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Defina máscara, quantidade de dígitos e o próximo número, no mesmo conceito dos <a href="{{ route('configuracoes-sistema.pedidos-venda.edit') }}" class="text-brand-600 hover:underline dark:text-brand-400">pedidos de venda</a> e da <a href="{{ route('configuracoes-sistema.numeracao-os') }}" class="text-brand-600 hover:underline dark:text-brand-400">numeração de OS</a>. O código do <strong>grupo</strong> fica em até <strong>30 caracteres</strong>; todas as versões (v1, v2…) do mesmo grupo partilham o mesmo número.</p>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Última proposta (mais recente)</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $ultimo_codigo_recente ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Maior índice na sequência</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ ($maior_indice_numerico ?? 0) > 0 ? $maior_indice_numerico : 'Nenhum' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo natural</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $proximo_natural ?? 1 }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo efetivo</p>
                    <p class="mt-1 text-lg font-semibold text-brand-600 dark:text-brand-400">{{ $proximo_efetivo ?? 1 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="mascara_numero" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Máscara do número</label>
                    <input type="text" id="mascara_numero" name="mascara_numero" value="{{ old('mascara_numero', $mascara_numero ?? 'PC{numero}') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                           placeholder="PC{numero}" autocomplete="off">
                    <p class="mt-1 text-xs text-gray-400">Use o texto fixo desejado e <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">{numero}</code> onde entram os dígitos (ex.: <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">PROP-{numero}</code>).</p>
                </div>
                <div>
                    <label for="numero_pad" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade de dígitos</label>
                    <input type="number" id="numero_pad" name="numero_pad" min="1" max="12" step="1"
                           value="{{ old('numero_pad', $numero_pad ?? 6) }}"
                           class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                </div>
                <div>
                    <label for="proximo_numero" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Próximo número da sequência</label>
                    <input type="number" id="proximo_numero" name="proximo_numero" min="1" max="99999999" step="1"
                           value="{{ old('proximo_numero', $proximo_configurado ?? 1) }}"
                           class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-400">Se já existem propostas numeradas, deve ser maior que o <strong>maior índice</strong> acima (ex.: pular para 1000).</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Voltar</a>
        </div>
    </div>
</form>
@endsection
