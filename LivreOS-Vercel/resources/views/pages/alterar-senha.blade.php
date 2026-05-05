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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Alterar senha</h1>
    <p class="text-gray-600 dark:text-gray-400">Altere a senha da sua conta. É necessário informar a senha atual.</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
@endif

<div class="max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('alterar-senha.update') }}" method="POST" class="space-y-5">
        @csrf
        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label for="senha_atual" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha atual *</label>
            <input type="password" name="senha_atual" id="senha_atual" required autocomplete="current-password"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nova senha *</label>
            <input type="password" name="password" id="password" required autocomplete="new-password" minlength="8"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Mínimo de 8 caracteres.</p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar nova senha *</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" minlength="8"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                Alterar senha
            </button>
            <a href="javascript:history.back()" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Voltar
            </a>
        </div>
    </form>
</div>
@endsection
