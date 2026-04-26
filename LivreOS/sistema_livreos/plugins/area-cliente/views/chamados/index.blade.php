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
<div class="mx-auto max-w-5xl pb-6">
    @if(session('success'))
    <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-500/10 dark:text-success-300">{{ session('success') }}</div>
    @endif

    {{-- Cabeçalho tipo central de tickets --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tickets</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Abra um ticket ou acompanhe o atendimento</p>
        </div>
        <a href="{{ route('plugin.area-cliente.chamados.create') }}" class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo ticket
        </a>
    </div>

    {{-- Resumo rápido (abertos x encerrados) --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:flex sm:gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contagemStatus['abertos'] ?? 0 }}</p>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Em aberto</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contagemStatus['encerrados'] ?? 0 }}</p>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Encerrados</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="GET" action="{{ route('plugin.area-cliente.chamados.index') }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3">
            <div class="min-w-0 flex-1 sm:max-w-[220px]">
                <label for="busca" class="sr-only">Busca</label>
                <input type="text" id="busca" name="busca" value="{{ request('busca') }}" placeholder="Buscar por assunto..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="w-full sm:w-[140px]">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos os status</option>
                    @foreach(\AreaCliente\Chamado::STATUS as $v => $l)
                    <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-[120px]">
                <label for="prioridade" class="sr-only">Prioridade</label>
                <select id="prioridade" name="prioridade" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach(\AreaCliente\Chamado::PRIORIDADES as $v => $l)
                    <option value="{{ $v }}" {{ request('prioridade') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Filtrar</button>
                @if(request()->hasAny(['busca', 'status', 'prioridade']))
                <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Lista de tickets (tabela) --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        @if($chamados->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">#</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Assunto</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Prioridade</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Atualizado</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Abrir</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($chamados as $c)
                    @php
                        $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($c->status);
                        $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($c->prioridade);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $c->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('plugin.area-cliente.chamados.show', $c->id) }}" class="font-medium text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                {{ Str::limit($c->titulo, 50) }}
                            </a>
                            @if($c->anexos->count() > 0)
                            <span class="ml-1 text-gray-400" title="{{ $c->anexos->count() }} anexo(s)">📎</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ \AreaCliente\Chamado::statusLabel($c->status) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $prioridadeColor }}">{{ \AreaCliente\Chamado::prioridadeLabel($c->prioridade) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $c->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="relative whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('plugin.area-cliente.chamados.show', $c->id) }}" class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium text-sm">Abrir</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $chamados->links() }}
        </div>
        @else
        <div class="px-4 py-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">Nenhum ticket encontrado.</p>
            <a href="{{ route('plugin.area-cliente.chamados.create') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Abrir novo ticket</a>
        </div>
        @endif
    </div>
</div>
@endsection
