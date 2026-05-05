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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Rota do dia</h1>
        <p class="text-gray-600 dark:text-gray-400">Atendimentos agrupados por técnico</p>
    </div>
    <a href="{{ route('plugin.atendimento.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Voltar</a>
</div>

<form method="GET" action="{{ route('plugin.atendimento.rota-do-dia') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data</label>
        <input type="date" name="data" value="{{ $data }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Técnico</label>
        <select name="tecnico_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            <option value="">Todos</option>
            @foreach($tecnicosParaFiltro as $t)
                <option value="{{ $t->id }}" {{ (string)($tecnicoIdFiltro ?? '') === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
    <div class="ml-auto flex flex-wrap items-center gap-2">
        <a href="{{ route('plugin.atendimento.rota-do-dia.export.csv', ['data' => $data, 'tecnico_id' => $tecnicoIdFiltro]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" title="Exportar rota do dia em CSV">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-7-8h6a2 2 0 012 2v12a2 2 0 01-2 2H9a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            CSV (dia)
        </a>
        <a href="{{ route('plugin.atendimento.rota-do-dia.export.pdf', ['data' => $data, 'tecnico_id' => $tecnicoIdFiltro]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" title="Exportar rota do dia em PDF">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            PDF (dia)
        </a>
        @if(!empty($tecnicoIdFiltro))
            @php $mesParam = \Carbon\Carbon::parse($data)->format('Y-m'); @endphp
            <a href="{{ route('plugin.atendimento.rota-do-mes.export.csv', ['mes' => $mesParam, 'tecnico_id' => $tecnicoIdFiltro]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" title="Exportar rota do mês (técnico selecionado) em CSV">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-7-8h6a2 2 0 012 2v12a2 2 0 01-2 2H9a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                CSV (mês)
            </a>
            <a href="{{ route('plugin.atendimento.rota-do-mes.export.pdf', ['mes' => $mesParam, 'tecnico_id' => $tecnicoIdFiltro]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" title="Exportar rota do mês (técnico selecionado) em PDF">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF (mês)
            </a>
        @endif
        <a href="{{ route('plugin.atendimento.rota-do-dia.mapa', ['data' => $data, 'tecnico_id' => $tecnicoIdFiltro]) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V5.618a2 2 0 011.105-1.789l4-2A2 2 0 019 1.999l6 2.999 4.553-2.276A2 2 0 0121 4.382v9.764a2 2 0 01-1.105 1.789l-4 2A2 2 0 0115 17.999L9 15z"/></svg>
            Ver mapa
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 18h12v-5H6v5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2"/></svg>
            Imprimir
        </button>
    </div>
</form>
<p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Distância e tempo entre endereços vêm da API Google Distance Matrix. Ative e informe a chave em <a href="{{ route('configuracoes-sistema.plugins.show', 'atendimento') }}" class="text-brand-600 hover:underline dark:text-brand-400">Configurações do plugin Atendimento</a>.</p>

@forelse($porTecnico as $tecnicoId => $lista)
    @php
        $nomeTecnico = $tecnicoId ? ($tecnicos->get($tecnicoId)->name ?? 'Sem técnico') : 'Sem técnico';
        $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
        $segmentos = $segmentosPorTecnico[$tecnicoId] ?? [];
    @endphp
    <div class="mb-8 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="border-b border-gray-200 px-4 py-3 text-lg font-semibold text-gray-800 dark:border-gray-700 dark:text-white/90">{{ $nomeTecnico }}</h2>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($listaOrdenada as $index => $atendimento)
            {{-- Trecho entre o endereço anterior e este (distância e tempo de deslocamento) --}}
            @if($index > 0 && isset($segmentos[$index - 1]))
            <li class="flex items-center gap-3 border-l-4 border-brand-200 bg-brand-50/50 px-4 py-2 dark:border-brand-800 dark:bg-brand-900/20">
                <span class="text-sm text-gray-500 dark:text-gray-400">↳ Deslocamento</span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $segmentos[$index - 1]['distance'] }}</span>
                <span class="text-gray-400 dark:text-gray-500">·</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">~{{ $segmentos[$index - 1]['duration'] }}</span>
            </li>
            @endif
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                <div class="min-w-0 flex-1">
                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->data_hora_atendimento->format('H:i') }}</span>
                    <span class="mx-2 text-gray-400">·</span>
                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->cliente->nome ?? '—' }}</span>
                    @if($atendimento->endereco_para_mapa)
                        <span class="block truncate text-sm text-gray-600 dark:text-gray-400" title="{{ $atendimento->endereco_para_mapa }}">{{ $atendimento->endereco_para_mapa }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($atendimento->status)
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $atendimento->status->cor ?: '#6b7280' }}20; color: {{ $atendimento->status->cor ?: '#6b7280' }};">{{ $atendimento->status->nome }}</span>
                    @endif
                    @if($atendimento->endereco_para_mapa)
                        <a href="{{ $atendimento->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300" title="Ver no mapa (Google Maps)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ver no mapa
                        </a>
                    @endif
                    <a href="{{ route('plugin.atendimento.show', $atendimento->id) }}" class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Ver</a>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
@empty
    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
        Nenhum atendimento para esta data.
    </div>
@endforelse
@endsection
