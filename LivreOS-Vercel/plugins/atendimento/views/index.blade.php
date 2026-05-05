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
@php
    use Atendimento\Models\StatusAtendimento;
    use Atendimento\Models\PrioridadeAtendimento;
    $statusList = StatusAtendimento::orderBy('ordem')->orderBy('nome')->get();
    $prioridadesList = PrioridadeAtendimento::orderBy('ordem')->orderBy('nome')->get();
@endphp
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Atendimentos</h1>
        <p class="text-gray-600 dark:text-gray-400">Lista e filtros de atendimentos externos</p>
    </div>
    <a href="{{ route('plugin.atendimento.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Novo atendimento
    </a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('plugin.atendimento.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Busca</label>
            <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Cliente, problema, endereço..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
            <select name="cliente_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="status_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($statusList as $s)
                    <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
            <select name="prioridade_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                @foreach($prioridadesList as $p)
                    <option value="{{ $p->id }}" {{ request('prioridade_id') == $p->id ? 'selected' : '' }}>{{ $p->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data início</label>
            <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data fim</label>
            <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex items-end lg:col-span-6">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        </div>
    </form>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Data/Hora</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Técnico</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($atendimentos as $a)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">{{ $a->data_hora_atendimento->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">
                        @php
                            $contatosCliente = $a->cliente?->contatos ?? collect();
                            $contatoPrincipal = $contatosCliente->firstWhere('principal', true);
                            $contatoComTelefone = $contatosCliente
                                ->first(fn($c) => !empty($c->telefone) || !empty($c->telefone2));
                            $contatoPreferencial = $contatoPrincipal
                                ?? $contatoComTelefone
                                ?? $contatosCliente->sortBy('id')->first();
                            $whatsappContato = $contatosCliente
                                ->where('telefone_whatsapp', true)
                                ->sortByDesc(fn($c) => (int) $c->principal)
                                ->first()
                                ?? $contatoComTelefone;

                            $telefoneRaw = $contatoPreferencial->telefone
                                ?? $contatoPreferencial->telefone2
                                ?? '';
                            $telefoneDigits = $telefoneRaw ? preg_replace('/\D+/', '', $telefoneRaw) : '';
                            $telefoneExibicao = match (strlen($telefoneDigits)) {
                                11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $telefoneDigits),
                                10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $telefoneDigits),
                                9 => preg_replace('/^(\d{5})(\d{4})$/', '$1-$2', $telefoneDigits),
                                8 => preg_replace('/^(\d{4})(\d{4})$/', '$1-$2', $telefoneDigits),
                                default => ($telefoneRaw ?: '—'),
                            };

                            $whatsappRaw = $whatsappContato->telefone
                                ?? $whatsappContato->telefone2
                                ?? '';
                            $whatsappDigits = $whatsappRaw ? preg_replace('/\D+/', '', $whatsappRaw) : '';
                            $whatsappExibicao = match (strlen($whatsappDigits)) {
                                11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $whatsappDigits),
                                10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $whatsappDigits),
                                9 => preg_replace('/^(\d{5})(\d{4})$/', '$1-$2', $whatsappDigits),
                                8 => preg_replace('/^(\d{4})(\d{4})$/', '$1-$2', $whatsappDigits),
                                default => ($whatsappRaw ?: '—'),
                            };
                        @endphp
                        <div>{{ $a->cliente->nome ?? '—' }}</div>
                        <div class="mt-1 space-y-1 text-xs font-normal text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <span class="font-medium">Tel:</span>
                                @if($telefoneDigits)
                                    <a href="{{ 'tel:' . $telefoneDigits }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $telefoneExibicao }}</a>
                                @else
                                    <span>—</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-medium">Whats:</span>
                                @if($whatsappDigits)
                                    <a href="{{ 'https://wa.me/' . $whatsappDigits }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline dark:text-brand-400">{{ $whatsappExibicao }}</a>
                                @else
                                    <span>—</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $a->tecnico->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($a->status)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $a->status->cor ?: '#6b7280' }}20; color: {{ $a->status->cor ?: '#6b7280' }};">{{ $a->status->nome }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($a->prioridade)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $a->prioridade->cor ?: '#6b7280' }}20; color: {{ $a->prioridade->cor ?: '#6b7280' }};">{{ $a->prioridade->nome }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="max-w-[200px] truncate px-4 py-3 text-sm text-gray-600 dark:text-gray-400" title="{{ $a->endereco_para_mapa }}">{{ $a->endereco_para_mapa ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex flex-wrap items-center justify-end gap-1">
                            @if($a->endereco_para_mapa)
                                <a href="{{ $a->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300" title="Ver no mapa (Google Maps)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Mapa
                                </a>
                            @endif
                            <a href="{{ route('plugin.atendimento.show', $a->id) }}" class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Ver</a>
                            <a href="{{ route('plugin.atendimento.edit', $a->id) }}" class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Editar</a>
                            @if($a->ordem_servico_id)
                                <a href="{{ route('ordens-servico.show', $a->ordem_servico_id) }}" class="rounded border border-brand-300 bg-brand-50 px-2.5 py-1.5 text-xs font-medium text-brand-700 dark:border-brand-700 dark:bg-brand-900/30 dark:text-brand-400">Ver OS</a>
                            @else
                                <form action="{{ route('plugin.atendimento.converter-os', $a->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="rounded border border-brand-300 bg-brand-50 px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-700 dark:bg-brand-900/30 dark:text-brand-400">Converter em OS</button>
                                </form>
                            @endif
                            <form action="{{ route('plugin.atendimento.destroy', $a->id) }}" method="POST" class="inline" onsubmit="return confirm('Excluir este atendimento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum atendimento encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($atendimentos->hasPages())
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">{{ $atendimentos->links() }}</div>
    @endif
</div>
@endsection
