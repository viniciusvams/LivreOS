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
    $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($chamado->status);
    $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($chamado->prioridade);
@endphp
<div class="mx-auto max-w-4xl pb-6">
    @if(session('success'))
    <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-500/10 dark:text-success-300">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-800 dark:border-error-800 dark:bg-error-500/10 dark:text-error-300">{{ session('error') }}</div>
    @endif

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="hover:text-gray-900 dark:hover:text-white">Tickets</a>
        <span>/</span>
        <span class="font-medium text-gray-900 dark:text-white">#{{ $chamado->id }}</span>
    </nav>

    {{-- Cabeçalho do ticket (estilo sistema de tickets) --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">#{{ $chamado->id }}</span>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">{{ \AreaCliente\Chamado::statusLabel($chamado->status) }}</span>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $prioridadeColor }}">{{ \AreaCliente\Chamado::prioridadeLabel($chamado->prioridade) }}</span>
                </div>
                <h1 class="mt-1 text-lg font-bold text-gray-900 dark:text-white sm:text-xl">{{ $chamado->titulo }}</h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aberto em {{ $chamado->created_at->format('d/m/Y H:i') }} · Última atualização {{ $chamado->updated_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('plugin.area-cliente.chamados.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Voltar
                </a>
                <a href="{{ route('plugin.area-cliente.chamados.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo ticket
                </a>
            </div>
        </div>
    </div>

    @php
        $chamado->load(['respostas.anexos', 'anexos']);
        $respostasPublicas = $chamado->respostas->where('interno', false);
        $chamadoFechado = in_array($chamado->status, \AreaCliente\Chamado::STATUS_ENCERRADOS, true);
        $aguardandoConfirmacao = in_array($chamado->status, \AreaCliente\Chamado::STATUS_AGUARDANDO_CONFIRMACAO, true);
        $podeDesistir = in_array($chamado->status, \AreaCliente\Chamado::STATUS_PODE_DESISTIR, true);
        $anexosSemResposta = $chamado->anexos->whereNull('chamado_resposta_id');
        $timeline = collect([['type' => 'abertura', 'created_at' => $chamado->created_at, 'chamado' => $chamado]])
            ->concat($respostasPublicas->map(fn($r) => ['type' => 'resposta', 'created_at' => $r->created_at, 'item' => $r]))
            ->concat($anexosSemResposta->map(fn($a) => ['type' => 'anexo', 'created_at' => $a->created_at, 'item' => $a]))
            ->sortBy('created_at')->values();
    @endphp

    <div class="space-y-6">
        {{-- Conversa (thread do ticket) --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Conversa</h2>
            </div>
            <div class="p-4 sm:p-5">
            <ul class="space-y-4">
                @foreach($timeline as $entry)
                @if($entry['type'] === 'abertura')
                <li class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Abertura · {{ $entry['chamado']->created_at->format('d/m/Y H:i') }}
                        @if($entry['chamado']->user)
                            · {{ $entry['chamado']->user->name ?? $entry['chamado']->user->email }}
                        @endif
                    </p>
                    <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-400">
                        @if($entry['chamado']->descricao)
                            {!! $entry['chamado']->descricao !!}
                        @else
                            <p class="text-gray-500 dark:text-gray-400">Sem descrição adicional.</p>
                        @endif
                    </div>
                </li>
                @elseif($entry['type'] === 'resposta')
                <li class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $entry['item']->user ? ($entry['item']->user->name ?? $entry['item']->user->email) : 'Equipe' }} · {{ $entry['item']->created_at->format('d/m/Y H:i') }}</p>
                    @if($entry['item']->mensagem && $entry['item']->mensagem !== '—')
                    <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-400">{!! $entry['item']->mensagem !!}</div>
                    @endif
                    @if($entry['item']->anexos && $entry['item']->anexos->count() > 0)
                    <ul class="mt-3 space-y-2">
                        @foreach($entry['item']->anexos as $a)
                        <li>
                            <a href="{{ route('plugin.area-cliente.chamados.anexo', [$chamado->id, $a->id]) }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="truncate">{{ $a->nome_original ?: 'Anexo' }}</span>
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </li>
                @else
                <li>
                    <a href="{{ route('plugin.area-cliente.chamados.anexo', [$chamado->id, $entry['item']->id]) }}" target="_blank" rel="noopener" class="flex min-h-[52px] items-center gap-3 rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-base font-medium text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200">
                        <svg class="h-5 w-5 shrink-0 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="min-w-0 truncate">{{ $entry['item']->nome_original ?: 'Anexo' }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['item']->created_at->format('d/m/Y H:i') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </li>
                @endif
                @endforeach
            </ul>
            </div>
        </div>

        {{-- Ações do cliente conforme status --}}
        @if($aguardandoConfirmacao)
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-white/90">O problema foi resolvido?</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Confirme se a solução atendeu ou reabra o ticket se o problema persistir.</p>
            <div class="flex flex-wrap gap-3">
                <form action="{{ route('plugin.area-cliente.chamados.confirmar-solucao', $chamado->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex min-h-[48px] items-center gap-2 rounded-xl bg-success-500 px-5 py-3 text-base font-semibold text-white hover:bg-success-600">Confirmar solução</button>
                </form>
                <form action="{{ route('plugin.area-cliente.chamados.reabrir', $chamado->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex min-h-[48px] items-center gap-2 rounded-xl border-2 border-amber-300 bg-amber-50 px-5 py-3 text-base font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-200 dark:hover:bg-amber-900/30">Reabrir ticket</button>
                </form>
            </div>
        </div>
        @elseif($podeDesistir)
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="mb-4 text-lg font-bold text-gray-800 dark:text-white/90">Desistir da solicitação</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Resolveu sozinho ou não precisa mais do atendimento? Encerre o ticket por desistência.</p>
            <form action="{{ route('plugin.area-cliente.chamados.desistir', $chamado->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex min-h-[48px] items-center gap-2 rounded-xl border-2 border-gray-300 bg-white px-5 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Desistir da solicitação</button>
            </form>
        </div>
        @endif

        {{-- Mensagem e/ou anexos (cliente) – envio conjunto --}}
        @if(!$chamadoFechado)
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Responder</h2>
            <form action="{{ route('plugin.area-cliente.chamados.respostas.store', $chamado->id) }}" method="POST" enctype="multipart/form-data" id="form-resposta-portal" class="space-y-4">
                @csrf
                <div>
                    <label for="mensagem_portal" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Mensagem (opcional se enviar anexos)</label>
                    <textarea id="mensagem_portal" name="mensagem" class="js-jodit w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" rows="4" placeholder="Digite sua mensagem e/ou selecione arquivos abaixo...">{{ old('mensagem') }}</textarea>
                    @error('mensagem')<p class="mt-1 text-sm text-error-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Anexos (opcional)</label>
                    <input type="file" name="anexos[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 dark:border-gray-600 dark:bg-gray-900 dark:file:bg-brand-500/20 dark:file:text-brand-400">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Imagens ou PDF. Máx. 10 MB por arquivo. Envie mensagem e anexos juntos – aparecerão no histórico em um único bloco.</p>
                </div>
                <button type="submit" class="min-h-[48px] rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white hover:bg-brand-600">Enviar</button>
            </form>
        </div>
        @endif
    </div>
</div>

@include('area-cliente::partials.jodit-editor', ['formId' => 'form-resposta-portal', 'height' => 200])
@endsection
