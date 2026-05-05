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
<div class="mx-auto max-w-2xl pb-6 lg:max-w-4xl">
    @if(session('success'))
    <div class="mb-4 rounded-2xl bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-4 rounded-2xl bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
        <ul class="list-disc space-y-1 pl-5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Hero perfil --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 p-6 text-white shadow-lg dark:from-brand-600 dark:to-brand-700 sm:mb-8">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur sm:h-20 sm:w-20">
                <svg class="h-9 w-9 sm:h-10 sm:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold sm:text-2xl">Meu Perfil</h1>
                <p class="mt-0.5 text-sm opacity-90">Dados da conta e alteração de senha</p>
                @if($user->email ?? null)
                <p class="mt-2 truncate text-xs opacity-80">Logado como {{ $user->email }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
        {{-- Dados do cliente --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-500/20">
                    <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                </div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Dados do Cliente</h2>
            </div>
            <dl class="space-y-4">
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                    <dd class="break-words text-base font-medium text-gray-800 dark:text-white/90">{{ $cliente->nome ?? $cliente->razao_social ?? '-' }}</dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">CPF/CNPJ</dt>
                    <dd class="break-all text-base text-gray-800 dark:text-white/90">{{ $cliente->cnpj ?? $cliente->cpf ?? $cliente->documento_estrangeiro ?? '-' }}</dd>
                </div>
                @if($cliente->email ?? null)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-mail</dt>
                    <dd class="break-all text-base text-gray-800 dark:text-white/90">{{ $cliente->email }}</dd>
                </div>
                @endif
                @if($cliente->telefone ?? null)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefone</dt>
                    <dd class="text-base text-gray-800 dark:text-white/90">{{ $cliente->telefone }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Trocar senha --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-500/20">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Trocar Senha</h2>
            </div>
            <form action="{{ route('plugin.area-cliente.trocar-senha') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="senha_atual" class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-400">Senha atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" required class="w-full rounded-xl border border-gray-300 px-4 py-3.5 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Digite sua senha atual">
                </div>
                <div>
                    <label for="nova_senha" class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-400">Nova senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required class="w-full rounded-xl border border-gray-300 px-4 py-3.5 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Digite a nova senha">
                </div>
                <div>
                    <label for="nova_senha_confirmation" class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-400">Confirmar nova senha</label>
                    <input type="password" id="nova_senha_confirmation" name="nova_senha_confirmation" required class="w-full rounded-xl border border-gray-300 px-4 py-3.5 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Confirme a nova senha">
                </div>
                <button type="submit" class="flex w-full min-h-[56px] items-center justify-center gap-2 rounded-2xl bg-brand-500 px-5 py-4 text-base font-semibold text-white shadow-lg shadow-brand-500/25 transition active:scale-[0.98] hover:bg-brand-600 dark:bg-brand-600 dark:shadow-brand-600/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Alterar senha
                </button>
            </form>
        </div>
    </div>

    {{-- Voltar: botão grande estilo app --}}
    <div class="mt-6 sm:mt-8">
        <a href="{{ route('plugin.area-cliente.index') }}" class="flex min-h-[52px] items-center justify-center gap-3 rounded-2xl border-2 border-gray-200 bg-white px-5 py-3.5 text-base font-semibold text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Voltar à Área do Cliente
        </a>
    </div>
</div>
@endsection
