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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Gerenciar Permissões</h1>
        <p class="text-gray-600 dark:text-gray-400">Lista de todas as permissões do sistema</p>
    </div>
    <a href="{{ route('admin.permissions.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nova Permissão
    </a>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Nome</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Slug</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</th>
                    <th class="px-6 py-4 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($permissions as $permission)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $permission->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $permission->slug }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $permission->description ?? '-' }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.permissions.edit', $permission) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Editar</a>
                            <button type="button" onclick="abrirModalExcluirMotivo('{{ route('admin.permissions.destroy', $permission) }}')" class="rounded-lg border border-error-300 bg-white px-3 py-1.5 text-sm font-medium text-error-700 hover:bg-error-50 dark:border-error-700 dark:bg-gray-800 dark:text-error-400 dark:hover:bg-error-500/10">Deletar</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma permissão encontrada</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        {{ $permissions->links() }}
    </div>
</div>

<x-modal-excluir-motivo action="" titulo="Excluir permissão" descricao="Tem certeza que deseja excluir esta permissão? Informe o motivo." />
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
</script>
@endsection
