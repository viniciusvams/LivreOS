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
<div class="mx-auto max-w-2xl pb-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <nav class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="hover:text-gray-900 dark:hover:text-white">Tickets</a>
                <span>/</span>
                <span class="text-gray-900 dark:text-white">Novo</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Abrir ticket</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Descreva o problema; você pode anexar fotos ou documentos.</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <form action="{{ route('plugin.area-cliente.chamados.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="form-chamado">
            @csrf

            @if($errors->any())
            <div class="rounded-xl bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div>
                <label for="titulo" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assunto <span class="text-error-500">*</span></label>
                <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" required maxlength="255" placeholder="Ex: Câmera 03 fora do ar"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 @error('titulo') border-error-500 @enderror">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Resuma o problema em poucas palavras.</p>
            </div>

            <div>
                <label for="descricao" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea id="descricao" name="descricao" class="js-jodit w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 @error('descricao') border-error-500 @enderror" placeholder="Descreva o problema com mais detalhes...">{!! old('descricao') !!}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional. Use o editor para formatação. Máx. 50.000 caracteres.</p>
            </div>

            <div>
                <label for="prioridade" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade <span class="text-error-500">*</span></label>
                <select id="prioridade" name="prioridade" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 @error('prioridade') border-error-500 @enderror">
                    @foreach(\AreaCliente\Chamado::PRIORIDADES as $v => $l)
                    <option value="{{ $v }}" {{ old('prioridade', 'media') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Urgente para falhas críticas; Baixa para dúvidas ou melhorias.</p>
            </div>

            <div>
                <label for="anexos" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anexos (fotos ou documentos)</label>
                <input type="file" id="anexos" name="anexos[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 dark:border-gray-600 dark:bg-gray-900 dark:file:bg-brand-500/20 dark:file:text-brand-400 @error('anexos.*') border-error-500 @enderror">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional. Você pode selecionar vários arquivos. Imagens (JPEG, PNG, GIF, WebP) ou PDF. Máx. 10 MB por arquivo.</p>
            </div>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:gap-4">
                <button type="submit" class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-500 px-5 py-3.5 text-base font-semibold text-white shadow-lg transition active:scale-[0.98] hover:bg-brand-600 dark:bg-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Enviar ticket
                </button>
                <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl border-2 border-gray-200 bg-white px-5 py-3.5 text-base font-semibold text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    Voltar
                </a>
            </div>
        </form>
    </div>
</div>

@include('area-cliente::partials.jodit-editor', ['formId' => 'form-chamado', 'height' => 220])
@endsection
