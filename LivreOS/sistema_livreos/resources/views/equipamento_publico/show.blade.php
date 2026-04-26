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

@extends('layouts.fullscreen-layout')

@section('content')
@php
    $ordem = $ordemServico ?? $ordem;
    $nome = $ordem->equipamento_nome ?? $ordem->equipamento?->marca ?? 'Equipamento';
    if ($ordem->equipamento) {
        $nome = trim(($ordem->equipamento->marca ?? '') . ' ' . ($ordem->equipamento->modelo ?? '')) ?: $nome;
    }
    $marca = $ordem->equipamento_marca ?? $ordem->equipamento?->marca ?? '-';
    $modelo = $ordem->equipamento_modelo ?? $ordem->equipamento?->modelo ?? '-';
    $numeroSerie = $ordem->equipamento_numero_serie ?? $ordem->equipamento?->numero_serie ?? null;
    $numeroPatrimonio = $ordem->equipamento_numero_patrimonio ?? $ordem->equipamento?->numero_patrimonio ?? null;
    $acessorios = $ordem->equipamento_acessorios ?? $ordem->equipamento?->acessorios ?? null;
    $cliente = $ordem->cliente;
    $osDisplay = format_os_display($ordem);
    $empresa = $empresa ?? \App\Models\Empresa::first();
    $historico = $historico ?? collect();
    $telefone = $empresa->telefone ?? $empresa->celular ?? null;
    // Número do WhatsApp: usar o celular da empresa quando estiver marcado como WhatsApp
    $whatsapp = (!empty($empresa->celular_whatsapp) && !empty($empresa->celular)) ? $empresa->celular : null;
@endphp
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 px-4 py-6 sm:py-8 dark:from-gray-900 dark:to-gray-800">
    <div class="mx-auto w-full max-w-xl">
        {{-- Card principal --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            {{-- Ícone / identificação visual --}}
            <div class="mb-4 sm:mb-6 flex items-center gap-4">
                <div class="flex h-12 w-12 sm:h-14 sm:w-14 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-white/90 truncate">{{ $nome }}</h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Identificação do equipamento</p>
                </div>
            </div>

            {{-- Dados do equipamento --}}
            <div class="space-y-3 border-b border-gray-200 pb-4 dark:border-gray-700">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Equipamento</h2>
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Marca</dt><dd class="font-medium text-gray-800 dark:text-white/90 text-right break-all">{{ $marca }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Modelo</dt><dd class="font-medium text-gray-800 dark:text-white/90 text-right break-all">{{ $modelo }}</dd></div>
                    @if($numeroSerie)<div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Nº Série</dt><dd class="font-mono text-gray-800 dark:text-white/90 text-right break-all">{{ $numeroSerie }}</dd></div>@endif
                    @if($numeroPatrimonio)<div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Patrimônio</dt><dd class="font-mono text-gray-800 dark:text-white/90 text-right break-all">{{ $numeroPatrimonio }}</dd></div>@endif
                    @if($acessorios)<div class="border-t border-gray-100 pt-2 dark:border-gray-700"><dt class="text-gray-500 dark:text-gray-400">Acessórios</dt><dd class="mt-1 text-gray-700 dark:text-gray-300 break-words">{!! strip_tags($acessorios) !!}</dd></div>@endif
                </dl>
            </div>

            {{-- Cliente e OS atual --}}
            <div class="space-y-3 border-b border-gray-200 py-4 dark:border-gray-700">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Vínculo</h2>
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Cliente</dt><dd class="font-medium text-gray-800 dark:text-white/90 text-right break-all">{{ $cliente?->nome ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">OS vinculada</dt><dd class="font-medium text-gray-800 dark:text-white/90 text-right">{{ $osDisplay }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400 shrink-0">Status</dt><dd><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $ordem->status ?? '-' }}</span></dd></div>
                </dl>
            </div>

            {{-- Contato direto --}}
            @if($telefone || $whatsapp)
            <div class="space-y-3 border-b border-gray-200 py-4 dark:border-gray-700">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contato</h2>
                <div class="flex flex-wrap gap-2">
                    @if($whatsapp)
                    @php
                        $whatsappNum = preg_replace('/\D/', '', $whatsapp);
                        $whatsappNum = (substr($whatsappNum, 0, 2) === '55' ? '' : '55') . $whatsappNum;
                        $dig = preg_replace('/\D/', '', $whatsapp);
                        if (strlen($dig) >= 2 && substr($dig, 0, 2) === '55') { $dig = substr($dig, 2); }
                        if (strlen($dig) === 11) {
                            $whatsappFormatado = '(' . substr($dig, 0, 2) . ') ' . substr($dig, 2, 5) . '-' . substr($dig, 7);
                        } elseif (strlen($dig) === 10) {
                            $whatsappFormatado = '(' . substr($dig, 0, 2) . ') ' . substr($dig, 2, 4) . '-' . substr($dig, 6);
                        } else {
                            $whatsappFormatado = $whatsapp;
                        }
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNum }}?text={{ urlencode('Olá! Estou entrando em contato pelo equipamento: ' . $nome . ' - ' . $osDisplay) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-green-700 active:scale-[0.98] min-h-[44px] touch-manipulation">
                        <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>WhatsApp {{ $whatsappFormatado }}</span>
                    </a>
                    @endif
                    @if($telefone)
                    <a href="tel:{{ preg_replace('/\D/', '', $telefone) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 min-h-[44px] touch-manipulation">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Ligar
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Histórico de OS --}}
            @if($historico->isNotEmpty())
            <div class="space-y-3 border-b border-gray-200 py-4 dark:border-gray-700">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Histórico de atendimentos</h2>
                <ul class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($historico as $osAnt)
                    <li class="flex items-center justify-between gap-2 text-sm py-1.5 border-b border-gray-100 last:border-0 dark:border-gray-700">
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ format_os_display($osAnt) }}</span>
                        <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ $osAnt->data_abertura?->format('d/m/Y') ?? '-' }}</span>
                    </li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-400 dark:text-gray-500">Faça login para ver detalhes e abrir nova OS</p>
            </div>
            @endif

            @if(function_exists('do_action'))
            <?php do_action('equipamento_publico.show.extra', $ordem); ?>
            @endif

            {{-- Ações (requerem autenticação) --}}
            <div class="mt-6 space-y-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Para visualizar, editar ou abrir nova OS, acesse o sistema:</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('ordens-servico.edit', $ordem) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-blue-700 active:scale-[0.98] dark:bg-blue-500 dark:hover:bg-blue-600 min-h-[44px] touch-manipulation">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Ver / Editar OS atual
                    </a>
                    <a href="{{ route('ordens-servico.create', ['de_equipamento' => $ordem->id, 'step' => 3]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 min-h-[44px] touch-manipulation">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Abrir nova OS
                    </a>
                </div>
            </div>
        </div>

        {{-- Rodapé --}}
        @if($empresa)
        <p class="mt-4 text-center text-xs text-gray-400 dark:text-gray-500">
            {{ $empresa->nome ?? '' }}
        </p>
        @endif
    </div>
</div>
@endsection
