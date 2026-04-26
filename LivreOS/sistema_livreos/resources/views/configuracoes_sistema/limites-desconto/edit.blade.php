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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Limites de Desconto</h1>
        <p class="text-gray-600 dark:text-gray-400">Defina os limites máximos de desconto (em valor e percentual) e os perfis (Roles) que devem respeitá-los. O administrador não está sujeito a esses limites e é o único que pode alterar esta configuração.</p>
    </div>
    <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
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

<form action="{{ route('configuracoes-sistema.limites-desconto.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Valores dos limites</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Estes limites se aplicam a descontos por item (produtos/serviços), ao desconto global da OS e ao desconto no encerramento. Deixe em branco para não limitar.</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Limite em valor (R$)</label>
                <input type="number" step="0.01" min="0" name="limite_valor" value="{{ old('limite_valor', $config->limite_valor) }}" placeholder="Ex: 100,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Desconto máximo em reais por item ou no total/encerramento.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Limite em percentual (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="limite_percentual" value="{{ old('limite_percentual', $config->limite_percentual) }}" placeholder="Ex: 15" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Desconto máximo em % sobre o valor do item ou do total.</p>
            </div>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Bloquear alteração de preço</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Quando ativo, os perfis sujeitos aos limites não poderão alterar o preço unitário de produtos ou serviços que vierem do cadastro (somente descontos permitidos pelos limites acima).</p>
            <label class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                <input type="checkbox" name="bloquear_alteracao_preco" value="1" {{ old('bloquear_alteracao_preco', $config->bloquear_alteracao_preco ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="font-medium text-gray-800 dark:text-gray-200">Bloquear alteração de preço de produtos/serviços cadastrados</span>
            </label>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Perfis (Roles) sujeitos aos limites</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Selecione os perfis que devem respeitar os limites acima. Usuários com esses perfis não poderão aplicar descontos acima do permitido em itens, total ou encerramento. O administrador nunca está sujeito.</p>
            <div class="space-y-2">
                @forelse($roles as $role)
                    <label class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" {{ in_array($role->id, old('role_ids', $config->roles->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $role->name }}</span>
                        @if($role->description)<span class="text-sm text-gray-500 dark:text-gray-400">— {{ $role->description }}</span>@endif
                    </label>
                @empty
                    <p class="text-sm text-amber-600 dark:text-amber-400">Nenhum perfil cadastrado. Cadastre em Admin &gt; Roles.</p>
                @endforelse
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('configuracoes.limites-desconto.form.extra', $config); ?>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </div>
</form>
@endsection
