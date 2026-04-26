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
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Carregue <strong>.docx</strong> (Word) ou <strong>.html</strong> / <strong>.htm</strong> com tags <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${nome_variavel}</code> (ex.: <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${proposta_descricao}</code>, <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${proposta_numero_sequencial}</code>, <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${empresa_endereco}</code> — lista completa na ajuda abaixo). Na proposta: Word → descarregar ficheiro; HTML → abrir página no browser.</p>
    </div>
    <a href="{{ route('propostas-comerciais.modelos-documento.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo modelo</a>
</div>

@include('propostas-comerciais.modelos-documento._ajuda-docx')

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">{{ session('error') }}</div>
@endif

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Ordem</th>
                    <th class="px-4 py-3 text-left">Nome</th>
                    <th class="px-4 py-3 text-left">Formato</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($modelos as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">{{ $m->ordem }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $m->nome }}</span>
                        @if($m->descricao)
                        <p class="mt-0.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($m->descricao, 80) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($m->isHtml())
                        <span class="inline-flex items-center rounded-md bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-500/20 dark:text-sky-300" title="Modelo em HTML">HTML</span>
                        @else
                        <span class="inline-flex items-center rounded-md bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-500/20 dark:text-violet-300" title="Modelo Microsoft Word">DOC</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($m->ativo)
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-500/20 dark:text-green-300">Ativo</span>
                        @else
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('propostas-comerciais.modelos-documento.edit', $m) }}" class="text-brand-600 hover:underline dark:text-brand-400">Editar</a>
                        <form method="POST" action="{{ route('propostas-comerciais.modelos-documento.destroy', $m) }}" class="ml-3 inline" onsubmit="return confirm('Remover este modelo?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline dark:text-red-400">Excluir</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhum modelo cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($modelos->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">{{ $modelos->links() }}</div>
    @endif
</div>
@endsection
