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

@props([
    'action' => '',
    'titulo' => 'Excluir',
    'descricao' => 'Tem certeza que deseja excluir? Esta ação não pode ser desfeita.',
    'idModal' => 'modalExcluirMotivo',
    'idForm' => 'formExcluirMotivo',
    'idMotivo' => 'motivoExcluir',
    /** Quando false, envia POST simples (ex.: exclusão em massa). */
    'useDeleteMethod' => true,
])
<div id="{{ $idModal }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $titulo }}</h2>
            <button type="button" onclick="document.getElementById('{{ $idModal }}').classList.add('hidden'); document.getElementById('{{ $idModal }}').classList.remove('flex'); document.body.style.overflow = '';" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="{{ $idForm }}" action="{{ $action }}" method="POST" class="p-6">
            @csrf
            @if($useDeleteMethod)
            @method('DELETE')
            @endif
            {{ $camposExtras ?? '' }}
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $descricao }}</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da exclusão * (mín. 10 caracteres)</label>
                <textarea name="motivo" id="{{ $idMotivo }}" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('{{ $idModal }}').classList.add('hidden'); document.getElementById('{{ $idModal }}').classList.remove('flex'); document.body.style.overflow = '';" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar exclusão</button>
            </div>
        </form>
    </div>
</div>
