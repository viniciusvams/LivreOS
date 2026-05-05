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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Atendimento #{{ $atendimento->id }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $atendimento->cliente->nome ?? '—' }} — {{ $atendimento->data_hora_atendimento->format('d/m/Y H:i') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('plugin.atendimento.edit', $atendimento->id) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Editar</a>
        @if($atendimento->google_maps_url)
            <a href="{{ $atendimento->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border-2 border-brand-500 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Abrir no Google Maps
            </a>
        @endif
        @if($atendimento->ordem_servico_id)
            <a href="{{ route('ordens-servico.show', $atendimento->ordem_servico_id) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Ver Ordem de Serviço</a>
        @elseif($atendimento->fase !== 'convertido_os')
            <form action="{{ route('plugin.atendimento.converter-os', $atendimento->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Converter em OS</button>
            </form>
        @endif
        <form action="{{ route('plugin.atendimento.destroy', $atendimento->id) }}" method="POST" class="inline" onsubmit="return confirm('Excluir este atendimento?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/30 dark:text-red-400">Excluir</button>
        </form>
        <a href="{{ route('plugin.atendimento.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Voltar</a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
@endif
@if(session('info'))
<div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">{{ session('info') }}</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados gerais</h2>
        @php
            $clienteAt = $atendimento->cliente;
            $contatosCliente = $clienteAt?->contatos ?? collect();
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
            $enderecoTexto = $atendimento->endereco_para_mapa ?: '—';
            $mapsHref = ($enderecoTexto && $enderecoTexto !== '—')
                ? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoTexto))
                : '#';
            $telHref = $telefoneDigits ? ('tel:' . $telefoneDigits) : '#';
            $waHref = $whatsappDigits ? ('https://wa.me/' . $whatsappDigits) : '#';
        @endphp
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Cliente</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->cliente->nome ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Data e hora</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->data_hora_atendimento->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Técnico responsável</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->tecnico->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Tempo estimado</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->tempo_estimado_minutos ? $atendimento->tempo_estimado_minutos . ' min' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd>
                    @if($atendimento->status)
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $atendimento->status->cor ?: '#6b7280' }}20; color: {{ $atendimento->status->cor ?: '#6b7280' }};">{{ $atendimento->status->nome }}</span>
                    @else — @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Prioridade</dt>
                <dd>
                    @if($atendimento->prioridade)
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: {{ $atendimento->prioridade->cor ?: '#6b7280' }}20; color: {{ $atendimento->prioridade->cor ?: '#6b7280' }};">{{ $atendimento->prioridade->nome }}</span>
                    @else — @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Endereço</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">
                    <a href="{{ $mapsHref }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline dark:text-brand-400">
                        {{ $enderecoTexto }}
                    </a>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Telefone</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">
                    <a href="{{ $telHref }}" class="text-brand-600 hover:underline dark:text-brand-400">
                        {{ $telefoneExibicao }}
                    </a>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">WhatsApp</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">
                    <a href="{{ $waHref }}" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:underline dark:text-brand-400">
                        {{ $whatsappContato?->telefone ?? '—' }}
                    </a>
                </dd>
            </div>
        </dl>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Problema e diagnóstico</h2>
        <div class="space-y-4 text-sm">
            <div>
                <dt class="mb-1 text-gray-500 dark:text-gray-400">Problema reportado pelo cliente</dt>
                <dd class="whitespace-pre-wrap text-gray-800 dark:text-white/90">{{ $atendimento->problema_reportado ?: '—' }}</dd>
            </div>
            <div>
                <dt class="mb-1 text-gray-500 dark:text-gray-400">Diagnóstico do técnico</dt>
                <dd class="whitespace-pre-wrap text-gray-800 dark:text-white/90">{{ $atendimento->diagnostico_tecnico ?: '—' }}</dd>
            </div>
            @if($atendimento->observacoes_tecnicas)
            <div>
                <dt class="mb-1 text-gray-500 dark:text-gray-400">Observações técnicas</dt>
                <dd class="whitespace-pre-wrap text-gray-800 dark:text-white/90">{{ $atendimento->observacoes_tecnicas }}</dd>
            </div>
            @endif
        </div>
    </div>
</div>

@if(($atendimento->checklist_respostas && count($atendimento->checklist_respostas) > 0) || ($atendimento->checklist_campos_adhoc && count($atendimento->checklist_campos_adhoc) > 0))
<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Checklist</h2>
    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        @if($atendimento->checklistModel)
            Modelo: {{ $atendimento->checklistModel->nome }}
        @else
            Itens ad-hoc
        @endif
    </p>
    <dl class="space-y-2 text-sm">
        @if($atendimento->checklistModel && $atendimento->checklistModel->campos && is_array($atendimento->checklistModel->campos))
            @foreach($atendimento->checklistModel->campos as $idx => $campo)
                @if(isset($atendimento->checklist_respostas[$idx]))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ $campo['label'] ?? 'Item ' . ($idx + 1) }}</dt>
                    <dd class="font-medium text-gray-800 dark:text-white/90">
                        @if(($campo['tipo'] ?? '') === 'checkbox')
                            {{ ($atendimento->checklist_respostas[$idx] ?? '') === '1' ? 'Sim' : 'Não' }}
                        @else
                            {{ $atendimento->checklist_respostas[$idx] ?? '—' }}
                        @endif
                    </dd>
                </div>
                @endif
            @endforeach
        @elseif($atendimento->checklist_campos_adhoc && is_array($atendimento->checklist_campos_adhoc))
            @foreach($atendimento->checklist_campos_adhoc as $item)
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ is_array($item) ? ($item['label'] ?? 'Item') : $item }}</dt>
                    <dd class="font-medium text-gray-800 dark:text-white/90">{{ is_array($item) ? ($item['valor'] ?? '—') : '—' }}</dd>
                </div>
            @endforeach
        @else
            @foreach($atendimento->checklist_respostas ?? [] as $key => $valor)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Item {{ $key }}</dt>
                <dd class="font-medium text-gray-800 dark:text-white/90">{{ $valor === '1' ? 'Sim' : ($valor === '0' ? 'Não' : $valor) }}</dd>
            </div>
            @endforeach
        @endif
    </dl>
</div>
@endif

@if($atendimento->fotos && $atendimento->fotos->count() > 0)
<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Fotos do atendimento</h2>
    @php $fotosPorTipo = $atendimento->fotos->sortBy('ordem')->groupBy('tipo'); @endphp
    @foreach(['antes' => 'Antes', 'depois' => 'Depois', 'evidencia' => 'Evidência'] as $tipo => $label)
        @if(isset($fotosPorTipo[$tipo]) && $fotosPorTipo[$tipo]->count() > 0)
        <div class="mb-4">
            <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</h3>
            <div class="flex flex-wrap gap-3">
                @foreach($fotosPorTipo[$tipo] as $foto)
                <div class="flex flex-col">
                    <a href="{{ route('plugin.atendimento.foto.file', $foto->id) }}" target="_blank" rel="noopener noreferrer" class="block">
                        <img src="{{ route('plugin.atendimento.foto.file', $foto->id) }}" alt="{{ $foto->legenda }}" class="h-28 w-28 rounded-lg border border-gray-200 object-cover dark:border-gray-600">
                    </a>
                    @if($foto->legenda)
                    <span class="mt-1 max-w-[7rem] truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $foto->legenda }}">{{ $foto->legenda }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>
@endif

@if($atendimento->assinatura_cliente_path)
<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Assinatura do cliente</h2>
    @if($atendimento->assinatura_data || $atendimento->assinatura_local)
    <dl class="mb-3 space-y-1 text-sm">
        @if($atendimento->assinatura_data)
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Data e hora da assinatura</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->assinatura_data->format('d/m/Y H:i') }}</dd>
        </div>
        @endif
        @if($atendimento->assinatura_local)
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Local da assinatura</dt>
            <dd class="font-medium text-gray-800 dark:text-white/90">{{ $atendimento->assinatura_local }}</dd>
        </div>
        @endif
    </dl>
    @endif
    <img src="{{ route('plugin.atendimento.assinatura.file', $atendimento->id) }}" alt="Assinatura do cliente" class="max-h-32 rounded border border-gray-200 dark:border-gray-600">
</div>
@endif
@endsection
