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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Termo de Garantia</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre um novo termo de garantia</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('configuracoes-sistema.termos-garantia.store') }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Termo de Garantia Padrão - 90 dias">
                @error('nome')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Termo de Garantia</label>
                <textarea name="termo" id="termo_garantia_editor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" rows="15">{{ old('termo') }}</textarea>
                @error('termo')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use o editor para formatar o texto do termo de garantia</p>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Termo ativo</span>
                </label>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('configuracoes.termos-garantia.form.extra', null); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('configuracoes-sistema.termos-garantia.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

@include('partials.jodit-assets')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = Jodit.make('#termo_garantia_editor', {
        height: 400,
        language: 'pt_br',
        toolbar: true,
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough',
            '|',
            'ul', 'ol',
            '|',
            'outdent', 'indent',
            '|',
            'font', 'fontsize', 'brush', 'paragraph',
            '|',
            'image', 'link', 'table',
            '|',
            'align', 'undo', 'redo',
            '|',
            'hr', 'eraser', 'copyformat',
            '|',
            'fullsize', 'selectall', 'print', 'about'
        ],
        uploader: {
            insertImageAsBase64URI: true
        }
    });
});
</script>
@endsection
