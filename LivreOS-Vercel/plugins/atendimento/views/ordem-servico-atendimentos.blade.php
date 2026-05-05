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

@php
    $atendimentos = \Atendimento\Models\Atendimento::where('ordem_servico_id', $ordem->id)->with(['fotos', 'cliente'])->orderBy('data_hora_atendimento')->get();
@endphp
@if($atendimentos->isNotEmpty())
<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Atendimentos vinculados</h2>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Estes atendimentos externos foram convertidos nesta Ordem de Serviço.</p>
    <div class="space-y-6">
        @foreach($atendimentos as $atendimento)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="font-medium text-gray-800 dark:text-white/90">Atendimento #{{ $atendimento->id }}</span>
                    <span class="mx-2 text-gray-400">·</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $atendimento->cliente->nome ?? '—' }}</span>
                    <span class="mx-2 text-gray-400">·</span>
                    <span class="text-sm text-gray-500 dark:text-gray-500">{{ $atendimento->data_hora_atendimento->format('d/m/Y H:i') }}</span>
                </div>
                <a href="{{ route('plugin.atendimento.show', $atendimento->id) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Ver detalhes →</a>
            </div>
            @if($atendimento->problema_reportado || $atendimento->diagnostico_tecnico)
            <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                @if($atendimento->problema_reportado)
                    <p><span class="font-medium text-gray-700 dark:text-gray-300">Problema:</span> {{ Str::limit($atendimento->problema_reportado, 200) }}</p>
                @endif
                @if($atendimento->diagnostico_tecnico)
                    <p class="mt-1"><span class="font-medium text-gray-700 dark:text-gray-300">Diagnóstico:</span> {{ Str::limit($atendimento->diagnostico_tecnico, 200) }}</p>
                @endif
            </div>
            @endif
            @if($atendimento->fotos && $atendimento->fotos->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($atendimento->fotos->sortBy('ordem') as $foto)
                <a href="{{ route('plugin.atendimento.foto.file', $foto->id) }}" target="_blank" rel="noopener noreferrer" class="block">
                    <img src="{{ route('plugin.atendimento.foto.file', $foto->id) }}" alt="{{ $foto->legenda }}" class="h-20 w-20 rounded-lg border border-gray-200 object-cover dark:border-gray-600" title="{{ $foto->tipo }}@if($foto->legenda): {{ $foto->legenda }}@endif">
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
