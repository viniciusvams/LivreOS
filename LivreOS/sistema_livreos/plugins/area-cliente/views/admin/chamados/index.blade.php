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
<div class="mx-auto max-w-6xl pb-6">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Central de tickets</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atenda os tickets do portal e converta em Ordem de Serviço quando necessário</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700 sm:px-5">
            <span class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Filtros</span>
        </div>
        <form method="GET" action="{{ route('plugin.area-cliente.admin.chamados.index') }}" class="flex flex-col gap-4 p-4 sm:flex-row sm:flex-wrap sm:items-end sm:gap-4 sm:p-5">
            <div class="w-full sm:min-w-[180px] sm:flex-1">
                <label for="busca" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Busca (ID, assunto, descrição)</label>
                <input type="text" id="busca" name="busca" value="{{ request('busca') }}" placeholder="Ex: 5 ou câmera" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="w-full sm:w-auto">
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach(\AreaCliente\Chamado::STATUS as $v => $l)
                    <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="prioridade" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Prioridade</label>
                <select id="prioridade" name="prioridade" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    @foreach(\AreaCliente\Chamado::PRIORIDADES as $v => $l)
                    <option value="{{ $v }}" {{ request('prioridade') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="cliente_id" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    @foreach($clientes ?? [] as $cli)
                    <option value="{{ $cli->id }}" {{ request('cliente_id') == $cli->id ? 'selected' : '' }}>{{ $cli->nome ?? $cli->razao_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex w-full gap-3 sm:w-auto">
                <button type="submit" class="min-h-[48px] flex-1 rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white transition hover:bg-brand-600 sm:flex-none">Aplicar</button>
                @if(request()->hasAny(['busca', 'status', 'prioridade', 'cliente_id']))
                <a href="{{ route('plugin.area-cliente.admin.chamados.index') }}" class="min-h-[48px] flex-1 rounded-xl border-2 border-gray-200 px-5 py-3 text-center text-base font-semibold text-gray-700 transition dark:border-gray-600 dark:text-gray-300 sm:flex-none">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Assunto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Atualizado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">OS</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($chamados as $c)
                    @php
                        $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($c->status);
                        $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($c->prioridade);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $c->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($c->titulo, 50) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->cliente ? ($c->cliente->nome ?? $c->cliente->razao_social) : '–' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ \AreaCliente\Chamado::statusLabel($c->status) }}</span></td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $prioridadeColor }}">{{ \AreaCliente\Chamado::prioridadeLabel($c->prioridade) }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $c->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if($c->ordem_servico_id && ($c->ordemServico ?? null))
                            <a href="{{ url('/ordens-servico/' . $c->ordem_servico_id) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ $c->ordemServico->codigo_interno ?? '#' . $c->ordem_servico_id }}</a>
                            @else
                            <span class="text-sm text-gray-400 dark:text-gray-500">–</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('plugin.area-cliente.admin.chamados.show', $c->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">Atender</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Nenhum ticket encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($chamados->hasPages())
        <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">{{ $chamados->links() }}</div>
        @endif
    </div>
</div>
@endsection
