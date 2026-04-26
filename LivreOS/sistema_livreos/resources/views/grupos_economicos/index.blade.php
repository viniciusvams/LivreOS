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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Grupos Econômicos</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie grupos econômicos e matrizes</p>
    </div>
    <a href="{{ route('grupos-economicos.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo Grupo</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">CNPJ Matriz</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grupos as $grupo)
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90">{{ $grupo->nome }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $grupo->cnpj_matriz ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $grupo->ativo ? 'Ativo' : 'Inativo' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('grupos-economicos.edit', $grupo) }}" class="text-brand-500 hover:underline">Editar</a>
                            <form action="{{ route('grupos-economicos.destroy', $grupo) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este grupo?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-500 hover:underline">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum grupo encontrado</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
