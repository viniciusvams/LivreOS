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

@php
    $isEdit = isset($deposito) && $deposito->exists;
    $deposito = $isEdit ? $deposito : new \App\Models\Deposito();
@endphp

<form action="{{ $isEdit ? route('depositos.update', $deposito) : route('depositos.store') }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                <input type="text" name="nome" required value="{{ old('nome', $deposito->nome) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Localização</label>
                <input type="text" name="localizacao" value="{{ old('localizacao', $deposito->localizacao) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $deposito->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
        </div>
    </div>

    @if(function_exists('do_action'))
    <?php do_action('admin.depositos.form.extra', $deposito ?? null); ?>
    @endif
    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>
