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
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar {{ format_os_display($ordem) }}</h1>
        <p class="text-gray-600 dark:text-gray-400">Atualize as informações da ordem de serviço</p>
    </div>
    <div class="flex w-full flex-wrap items-center gap-3 lg:w-auto lg:justify-end">
        <div class="flex w-full flex-wrap items-center gap-2 lg:w-auto">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tags:</label>
            <div class="flex flex-wrap gap-2" id="tags-selecionadas-container">
                @php
                    $tagsSelecionadas = old('tags', $ordem->tags()->where('tags.tipo', 'personalizado')->pluck('tags.id')->toArray() ?: []);
                @endphp
                @if(!empty($tagsSelecionadas) && !empty($tags))
                    @foreach($tags as $tag)
                        @if(in_array($tag->id, $tagsSelecionadas))
                            <span class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-medium" style="background-color: {{ $tag->cor_fundo }}; color: {{ $tag->cor_fonte }};">
                                {{ $tag->nome }}
                                <button type="button" class="ml-1 hover:opacity-75" onclick="removerTag({{ $tag->id }})" aria-label="Remover tag">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </span>
                        @endif
                    @endforeach
                @endif
            </div>
            <button type="button" onclick="abrirModalTags()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Adicionar
            </button>
        </div>
        @if($ordem->status !== 'Encerrado' && $ordem->status !== 'Encerrada')
            <button type="button" onclick="abrirModalEncerrarComDadosAtualizados()" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Encerrar OS
            </button>
        @else
            <a href="{{ route('ordens-servico.comprovante', $ordem) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill="currentColor" d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/>
                </svg>
                Imprimir Comprovante
            </a>
        @endif
        <a href="{{ route('ordens-servico.print', $ordem) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true">
                <path fill="currentColor" d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/>
            </svg>
            Imprimir OS
        </a>
        @if($ordem->equipamento_nome || $ordem->equipamento_id)
        <a href="{{ route('ordens-servico.equipamento-etiqueta', $ordem) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" title="Etiqueta com QR Code para colar no equipamento">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Imprimir QR do Equipamento
        </a>
        @endif
        <button type="button" onclick="abrirModalNotificarCliente({{ $ordem->id }})" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Notificar cliente
        </button>
        <button type="button" onclick="compartilharWhatsappOs({{ $ordem->id }})" title="Abre o WhatsApp Web ou app com o número do contato e a mensagem do template configurado" class="inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 dark:focus:ring-offset-gray-900">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.883 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Compartilhar WhatsApp
        </button>
    </div>
</div>

@include('ordens_servico._modal_notificar_cliente', ['ordem' => $ordem])

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($ordem->status === 'Encerrado' || $ordem->status === 'Encerrada')
<div class="mb-6 rounded-lg border border-blue-300 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-900/20">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="mb-1 text-sm font-semibold text-blue-800 dark:text-blue-200">
                OS Encerrada
            </h3>
            <p class="mb-4 text-sm text-blue-700 dark:text-blue-300">
                @if($podeEditarOsEncerrada ?? false)
                    Esta ordem está encerrada, mas você tem permissão para alterar dados, anexos e demais abas. O status continua fixo em Encerrada; use reimpressão quando precisar do comprovante.
                @else
                    Esta ordem de serviço foi encerrada e não pode mais ser editada. Você pode apenas reimprimir a OS ou o comprovante de entrega.
                @endif
            </p>

            @if($ordem->contasReceber->count() > 0)
            <div class="rounded-lg border border-blue-200 bg-white p-4 dark:border-blue-600 dark:bg-gray-800/50">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Pagamento(s) realizado(s)</h4>
                <div class="space-y-3">
                    @foreach($ordem->contasReceber as $cr)
                    <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Forma:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->formaPagamento?->nome ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Valor:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">R$ {{ number_format((float)$cr->valor, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($cr->status === 'pago') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @elseif($cr->status === 'aberto') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ ucfirst($cr->status ?? '-') }}
                                </span>
                            </div>
                            @if($cr->total_parcelas > 1)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Parcelamento:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">Parcela {{ $cr->numero_parcela }}/{{ $cr->total_parcelas }}</span>
                            </div>
                            @endif
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Adquirente:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->adquirente?->nome ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Bandeira:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->bandeira ? ucfirst($cr->bandeira) : '-' }}</span>
                            </div>
                            @if($cr->contaBancaria)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Conta:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->contaBancaria->nome ?? $cr->contaBancaria->banco }} - {{ $cr->contaBancaria->agencia ?? '' }}/{{ $cr->contaBancaria->conta ?? '' }}</span>
                            </div>
                            @endif
                            @if($cr->data_vencimento)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Vencimento:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->data_vencimento->format('d/m/Y') }}</span>
                            </div>
                            @endif
                            @if($cr->data_recebimento)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Recebido em:</span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $cr->data_recebimento->format('d/m/Y') }}</span>
                            </div>
                            @endif
                        </div>
                        @if($cr->observacoes)
                        <div class="mt-2 rounded border border-gray-100 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            {{ Str::limit($cr->observacoes, 200) }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @elseif($ordem->total_geral <= 0)
            <p class="text-sm text-blue-700 dark:text-blue-300 italic">OS sem cobrança (garantia/contrato ou valor zerado).</p>
            @endif
        </div>
    </div>
</div>
@endif

@if(!$empresa)
<div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="mb-1 text-sm font-semibold text-amber-800 dark:text-amber-200">
                Dados da empresa não cadastrados
            </h3>
            <p class="mb-2 text-sm text-amber-700 dark:text-amber-300">
                Para que os documentos impressos da ordem de serviço exibam corretamente os dados da empresa (nome, CNPJ, endereço, logo, etc.), é necessário cadastrar essas informações primeiro.
            </p>
            <a href="{{ route('configuracoes-sistema.empresa') }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Cadastrar dados da empresa
            </a>
        </div>
    </div>
</div>
@endif

<!-- Container para inputs hidden das tags selecionadas (será movido para dentro do form via JS) -->
<div id="tags-inputs-container"></div>

@if((($ordem->status !== 'Encerrado' && $ordem->status !== 'Encerrada') || ($podeEditarOsEncerrada ?? false)) && ($ordem->total_geral ?? 0) > 0)
{{-- Seção Adiantamentos --}}
@php $adiantamentos = $ordem->adiantamentosAtivos()->with(['formaPagamento'])->orderByDesc('data_recebimento')->orderByDesc('id')->get(); @endphp
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800" id="secao-adiantamentos" data-total-geral="{{ (float)($ordem->total_geral ?? 0) }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Adiantamentos</h3>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Valor já recebido antecipadamente será descontado no encerramento. Transferência/boleto ficam abertos até confirmação no financeiro.</p>
        </div>
        <div class="flex items-center gap-3">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Total adiantado: <strong class="text-lg text-green-600 dark:text-green-400" id="total-adiantado-display">R$ {{ number_format($ordem->total_adiantado, 2, ',', '.') }}</strong>
            </p>
            <p class="text-sm text-amber-600 dark:text-amber-400 hidden" id="adiantamento-coberto-aviso">Valor da OS já coberto por adiantamentos.</p>
            <button type="button" id="btnRegistrarAdiantamento" onclick="abrirModalAdiantamento()" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Registrar adiantamento
            </button>
        </div>
    </div>
    <div class="mt-4 overflow-x-auto">
        @if($adiantamentos->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400" id="adiantamentos-empty-msg">Nenhum adiantamento registrado.</p>
        @endif
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="adiantamentos-table" @if($adiantamentos->isEmpty()) style="display:none" @endif>
            <thead>
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Forma de pagamento</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody id="adiantamentos-tbody">
                @foreach($adiantamentos as $ad)
                <tr class="border-t border-gray-200 dark:border-gray-700" data-adiantamento-id="{{ $ad->id }}">
                    <td class="px-3 py-2 text-sm text-gray-800 dark:text-white/90">{{ $ad->data_recebimento ? $ad->data_recebimento->format('d/m/Y') : ($ad->data_vencimento ? $ad->data_vencimento->format('d/m/Y') . ' (venc.)' : '-') }}</td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format((float)$ad->valor, 2, ',', '.') }}</td>
                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $ad->formaPagamento->nome ?? '-' }}</td>
                    <td class="px-3 py-2">
                        @if($ad->status === 'pago')
                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">Pago</span>
                        @elseif($ad->status === 'aberto')
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Aguardando confirmação</span>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($ad->status ?? '-') }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right">
                        <button type="button" onclick="abrirModalEstornarAdiantamento({{ $ad->id }}, '{{ addslashes($ad->descricao) }}', {{ (float)$ad->valor }})" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Estornar</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
(function(){
    var secao = document.getElementById('secao-adiantamentos');
    if (!secao) return;
    var totalGeral = parseFloat(secao.getAttribute('data-total-geral')) || 0;
    var totalAdiantadoEl = document.getElementById('total-adiantado-display');
    var totalAdiantado = 0;
    if (totalAdiantadoEl && totalAdiantadoEl.textContent) {
        var m = totalAdiantadoEl.textContent.replace(/\s/g,'').match(/R\$?([\d.,]+)/);
        if (m) totalAdiantado = parseFloat(m[1].replace(/\./g,'').replace(',','.')) || 0;
    }
    if (totalAdiantado >= totalGeral - 0.01) {
        var btn = document.getElementById('btnRegistrarAdiantamento');
        var aviso = document.getElementById('adiantamento-coberto-aviso');
        if (btn) { btn.disabled = true; btn.classList.add('opacity-60', 'cursor-not-allowed'); }
        if (aviso) aviso.classList.remove('hidden');
    }
})();
</script>
@endif

@include('ordens_servico._form', ['ordem' => $ordem, 'podeEditarOsEncerrada' => $podeEditarOsEncerrada ?? false])

<!-- Modal de Seleção de Tags -->
<div id="modalTags" class="fixed inset-0 z-[2147483001] hidden items-center justify-center bg-black bg-opacity-50" style="z-index:2147483647 !important;" onclick="fecharModalTags(event)">
    <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Selecionar Tags</h2>
            <button type="button" onclick="fecharModalTags()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4 flex flex-wrap gap-2" id="modal-tags-container">
                @php
                    $tagsSelecionadasModal = old('tags', $ordem->tags()->where('tags.tipo', 'personalizado')->pluck('tags.id')->toArray() ?? []);
                @endphp
                @forelse($tags ?? [] as $tag)
                    <label class="inline-flex items-center gap-1.5 cursor-pointer group">
                        <input type="checkbox" 
                               name="tags_modal[]" 
                               value="{{ $tag->id }}" 
                               data-tag-id="{{ $tag->id }}"
                               data-tag-nome="{{ $tag->nome }}"
                               data-tag-cor-fundo="{{ $tag->cor_fundo }}"
                               data-tag-cor-fonte="{{ $tag->cor_fonte }}"
                               {{ in_array($tag->id, $tagsSelecionadasModal) ? 'checked' : '' }} 
                               class="sr-only peer">
                        <span class="rounded px-3 py-1.5 text-xs font-medium transition-all peer-checked:ring-2 peer-checked:ring-brand-500 peer-checked:ring-offset-1 peer-checked:shadow-md opacity-75 peer-checked:opacity-100 group-hover:opacity-100" style="background-color: {{ $tag->cor_fundo }}; color: {{ $tag->cor_fonte }};">
                            {{ $tag->nome }}
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma tag personalizada disponível</p>
                @endforelse
            </div>
        </div>
        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
            <button type="button" onclick="fecharModalTags()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Cancelar
            </button>
            <button type="button" onclick="salvarTags()" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                Salvar
            </button>
        </div>
    </div>
</div>

<script>
let tagsSelecionadas = new Set(@json($tagsSelecionadas ?? []));

function abrirModalTags() {
    sincronizarCheckboxesModal();
    document.getElementById('modalTags').classList.remove('hidden');
    document.getElementById('modalTags').classList.add('flex');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(true);
    document.body.style.overflow = 'hidden';
}

function fecharModalTags(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('modalTags').classList.add('hidden');
    document.getElementById('modalTags').classList.remove('flex');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(false);
    document.body.style.overflow = '';
}

function atualizarTagsSelecionadas() {
    const checkboxes = document.querySelectorAll('input[name="tags_modal[]"]:checked');
    tagsSelecionadas.clear();
    checkboxes.forEach(checkbox => {
        tagsSelecionadas.add(parseInt(checkbox.value));
    });
}

// Sincronizar checkboxes do modal quando abrir
function sincronizarCheckboxesModal() {
    const checkboxes = document.querySelectorAll('input[name="tags_modal[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = tagsSelecionadas.has(parseInt(checkbox.value));
    });
}

async function salvarTags() {
    // Atualizar tags selecionadas
    atualizarTagsSelecionadas();
    
    // Salvar via AJAX
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
        const ordemId = {{ $ordem->id }};
        const tagsArray = Array.from(tagsSelecionadas);
        
        const response = await fetch(`/ordens-servico/${ordemId}/tags`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ tags: tagsArray }),
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Erro ao salvar tags' }));
            if (typeof showToast === 'function') {
                showToast(error.message || 'Erro ao salvar tags', 'error');
            } else {
                alert(error.message || 'Erro ao salvar tags');
            }
            return;
        }

        const result = await response.json();
        
        // Garantir que o container está dentro do formulário
        const form = document.getElementById('osForm');
        const container = document.getElementById('tags-inputs-container');
        if (form && container && container.parentNode !== form) {
            form.appendChild(container);
        }
        
        // Atualizar container de inputs hidden
        container.innerHTML = '';
        
        tagsSelecionadas.forEach(tagId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tagId;
            container.appendChild(input);
        });
        
        // Atualizar visualização das tags selecionadas
        atualizarVisualizacaoTags();
        
        // Mostrar mensagem de sucesso
        if (typeof showToast === 'function') {
            showToast(result.message || 'Tags salvas com sucesso!', 'success');
        }
        
        // Fechar modal
        fecharModalTags();
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('Erro ao salvar tags: ' + error.message, 'error');
        } else {
            alert('Erro ao salvar tags: ' + error.message);
        }
    }
}

function atualizarVisualizacaoTags() {
    const container = document.getElementById('tags-selecionadas-container');
    container.innerHTML = '';
    
    tagsSelecionadas.forEach(tagId => {
        const checkbox = document.querySelector(`input[data-tag-id="${tagId}"]`);
        if (checkbox) {
            const tagNome = checkbox.getAttribute('data-tag-nome');
            const tagCorFundo = checkbox.getAttribute('data-tag-cor-fundo');
            const tagCorFonte = checkbox.getAttribute('data-tag-cor-fonte');
            
            const tagElement = document.createElement('span');
            tagElement.className = 'inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-medium';
            tagElement.style.backgroundColor = tagCorFundo;
            tagElement.style.color = tagCorFonte;
            tagElement.innerHTML = `
                ${tagNome}
                <button type="button" class="ml-1 hover:opacity-75" onclick="removerTag(${tagId})" aria-label="Remover tag">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            container.appendChild(tagElement);
        }
    });
}

async function removerTag(tagId) {
    tagsSelecionadas.delete(tagId);
    
    // Atualizar checkbox no modal
    const checkbox = document.querySelector(`input[data-tag-id="${tagId}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Salvar via AJAX
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
        const ordemId = {{ $ordem->id }};
        const tagsArray = Array.from(tagsSelecionadas);
        
        const response = await fetch(`/ordens-servico/${ordemId}/tags`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ tags: tagsArray }),
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Erro ao remover tag' }));
            if (typeof showToast === 'function') {
                showToast(error.message || 'Erro ao remover tag', 'error');
            }
            // Reverter a remoção em caso de erro
            tagsSelecionadas.add(tagId);
            if (checkbox) checkbox.checked = true;
            return;
        }

        // Garantir que o container está dentro do formulário
        const form = document.getElementById('osForm');
        const container = document.getElementById('tags-inputs-container');
        if (form && container && container.parentNode !== form) {
            form.appendChild(container);
        }
        
        // Atualizar inputs hidden
        container.innerHTML = '';
        
        tagsSelecionadas.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = id;
            container.appendChild(input);
        });
        
        // Atualizar visualização
        atualizarVisualizacaoTags();
    } catch (error) {
        // Reverter a remoção em caso de erro
        tagsSelecionadas.add(tagId);
        if (checkbox) checkbox.checked = true;
    }
}

// Inicializar inputs hidden ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Mover container para dentro do formulário
    const form = document.getElementById('osForm');
    const container = document.getElementById('tags-inputs-container');
    if (form && container && container.parentNode !== form) {
        form.appendChild(container);
    }
    
    // Criar inputs hidden para tags selecionadas
    tagsSelecionadas.forEach(tagId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tags[]';
        input.value = tagId;
        container.appendChild(input);
    });
});
</script>

<!-- Modal Registrar Adiantamento (mesma lógica de formas de pagamento do modal Encerrar) -->
<div id="modalAdiantamento" class="fixed inset-0 z-[2147483001] hidden items-center justify-center bg-black/50 p-4" style="z-index:2147483647 !important;" onclick="if(event.target===this)fecharModalAdiantamento()">
    <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar adiantamento</h3>
            <button type="button" onclick="fecharModalAdiantamento()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
        </div>
        <form id="formAdiantamento" class="p-6 space-y-6">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data do adiantamento *</label>
                    <input type="date" name="data_adiantamento" id="adiantamento_data" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-end">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Valor máximo: <strong id="adiantamento_limite_display">R$ 0,00</strong>
                    </p>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Formas de Pagamento</h4>
                    <button type="button" onclick="adicionarPagamentoAdiantamento()" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">+ Adicionar</button>
                </div>
                <div id="adiantamento_pagamentos_container" class="space-y-3"></div>
                <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total do adiantamento:</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white" id="adiantamento_total_pagamentos">R$ 0,00</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400" id="adiantamento_validacao_pagamentos"></div>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" id="adiantamento_obs" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalAdiantamento()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" id="btnAdiantamentoSubmit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Registrar</button>
            </div>
        </form>
    </div>
</div>
<div id="modalEstornarAdiantamento" class="fixed inset-0 z-[2147483001] hidden items-center justify-center bg-black/50 p-4" style="z-index:2147483647 !important;">
    <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Estornar adiantamento</h3>
            <button type="button" onclick="fecharModalEstornarAdiantamento()" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>
        <form id="formEstornarAdiantamento" class="p-4 space-y-4">
            @csrf
            <input type="hidden" id="estorno_conta_receber_id" value="">
            <p class="text-sm text-gray-600 dark:text-gray-300" id="estorno_resumo">...</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do estorno * (mín. 10 caracteres)</label>
                <textarea name="motivo" id="estorno_motivo" rows="4" required minlength="10" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="fecharModalEstornarAdiantamento()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Cancelar</button>
                <button type="submit" id="btnEstornarAdiantamentoSubmit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Confirmar estorno</button>
            </div>
        </form>
    </div>
</div>
<script>
let adiantamentoPagamentoIndex = 0;
let _estornoContaReceberId = null;
function abrirModalEstornarAdiantamento(contaReceberId, descricao, valor) {
    _estornoContaReceberId = contaReceberId;
    document.getElementById('estorno_conta_receber_id').value = contaReceberId;
    document.getElementById('estorno_resumo').textContent = 'Adiantamento: ' + (descricao || '') + ' — Valor: R$ ' + (typeof valor === 'number' ? valor.toFixed(2).replace('.', ',') : valor);
    document.getElementById('estorno_motivo').value = '';
    document.getElementById('modalEstornarAdiantamento').classList.remove('hidden');
    document.getElementById('modalEstornarAdiantamento').classList.add('flex');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(true);
    document.body.style.overflow = 'hidden';
}
function fecharModalEstornarAdiantamento() {
    document.getElementById('modalEstornarAdiantamento').classList.add('hidden');
    document.getElementById('modalEstornarAdiantamento').classList.remove('flex');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(false);
    document.body.style.overflow = '';
    _estornoContaReceberId = null;
}
document.getElementById('formEstornarAdiantamento')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    var motivo = (document.getElementById('estorno_motivo').value || '').trim();
    if (motivo.length < 10) {
        if (typeof showToast === 'function') showToast('Informe o motivo do estorno (mínimo 10 caracteres).', 'error'); else alert('Informe o motivo do estorno (mínimo 10 caracteres).');
        return;
    }
    var urlEstorno = '{{ url("ordens-servico/" . $ordem->id . "/adiantamentos/estornar") }}/' + _estornoContaReceberId;
    var btn = document.getElementById('btnEstornarAdiantamentoSubmit');
    if (btn) { btn.disabled = true; btn.textContent = 'Processando...'; }
    try {
        var fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('#formEstornarAdiantamento input[name="_token"]')?.value);
        fd.append('motivo', motivo);
        var res = await fetch(urlEstorno, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin', body: fd });
        var data = await res.json().catch(function() { return {}; });
        if (!res.ok) {
            if (typeof showToast === 'function') showToast(data.message || 'Erro ao estornar.', 'error'); else alert(data.message || 'Erro ao estornar.');
            return;
        }
        if (typeof showToast === 'function') showToast(data.message || 'Estorno realizado.', 'success');
        fecharModalEstornarAdiantamento();
        var totalEl = document.getElementById('total-adiantado-display');
        if (data.total_adiantado !== undefined && totalEl) totalEl.textContent = 'R$ ' + parseFloat(data.total_adiantado).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var row = document.querySelector('[data-adiantamento-id="' + _estornoContaReceberId + '"]');
        if (row) row.remove();
        var tbody = document.getElementById('adiantamentos-tbody');
        if (tbody && tbody.querySelectorAll('tr').length === 0) {
            var emptyMsg = document.getElementById('adiantamentos-empty-msg');
            var tbl = document.getElementById('adiantamentos-table');
            if (emptyMsg) emptyMsg.style.display = '';
            if (tbl) tbl.style.display = 'none';
        }
        var secao = document.getElementById('secao-adiantamentos');
        if (secao && data.total_adiantado !== undefined) {
            var totalGeral = parseFloat(secao.getAttribute('data-total-geral')) || 0;
            var totalAdiantado = parseFloat(data.total_adiantado) || 0;
            var btnReg = document.getElementById('btnRegistrarAdiantamento');
            var aviso = document.getElementById('adiantamento-coberto-aviso');
            if (totalAdiantado >= totalGeral - 0.01 && btnReg && aviso) { btnReg.disabled = true; btnReg.classList.add('opacity-60', 'cursor-not-allowed'); aviso.classList.remove('hidden'); }
            else if (btnReg && aviso) { btnReg.disabled = false; btnReg.classList.remove('opacity-60', 'cursor-not-allowed'); aviso.classList.add('hidden'); }
        }
    } catch (err) {
        if (typeof showToast === 'function') showToast('Erro: ' + (err.message || 'tente novamente.'), 'error'); else alert('Erro: ' + (err.message || 'tente novamente.'));
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Confirmar estorno'; }
    }
});
function abrirModalAdiantamento() {
    document.getElementById('adiantamento_data').value = new Date().toISOString().slice(0, 10);
    var secao = document.getElementById('secao-adiantamentos');
    var totalGeral = secao ? parseFloat(secao.getAttribute('data-total-geral')) || 0 : 0;
    var totalAdiantadoEl = document.getElementById('total-adiantado-display');
    var totalAdiantado = 0;
    if (totalAdiantadoEl && totalAdiantadoEl.textContent) {
        var m = totalAdiantadoEl.textContent.replace(/\s/g,'').match(/R\$?([\d.,]+)/);
        if (m) totalAdiantado = parseFloat(m[1].replace(/\./g,'').replace(',','.')) || 0;
    }
    var limite = Math.max(0, totalGeral - totalAdiantado);
    document.getElementById('adiantamento_limite_display').textContent = 'R$ ' + limite.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    document.getElementById('adiantamento_pagamentos_container').innerHTML = '';
    adiantamentoPagamentoIndex = 0;
    adicionarPagamentoAdiantamento();
    document.getElementById('modalAdiantamento').classList.remove('hidden');
    document.getElementById('modalAdiantamento').classList.add('flex');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(true);
    document.body.style.overflow = 'hidden';
}
function fecharModalAdiantamento() {
    document.getElementById('modalAdiantamento').classList.add('hidden');
    document.getElementById('modalAdiantamento').classList.remove('flex');
    document.getElementById('adiantamento_pagamentos_container').innerHTML = '';
    adiantamentoPagamentoIndex = 0;
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(false);
    document.body.style.overflow = '';
}
function adicionarPagamentoAdiantamento() {
    const container = document.getElementById('adiantamento_pagamentos_container');
    const index = adiantamentoPagamentoIndex++;
    const html = `
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-adiantamento-pagamento-index="${index}">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pagamento ${index + 1}</span>
                <button type="button" onclick="removerPagamentoAdiantamento(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="pagamentos[${index}][forma_pagamento_id]" required onchange="atualizarFormaPagamentoAdiantamento(this, ${index})" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento ?? [] as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo ?? '' }}" data-permite-parcela="{{ $forma->permite_parcela ? '1' : '0' }}" data-dias-recebimento="{{ $forma->dias_recebimento }}" data-conta-bancaria-id="{{ $forma->conta_bancaria_id ?? '' }}" data-pix-chaves="{{ json_encode($forma->pix_chaves_ativas ?? [], JSON_UNESCAPED_UNICODE) }}">{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                    <input type="text" inputmode="decimal" name="pagamentos[${index}][valor]" required placeholder="0,00" oninput="validarPagamentosAdiantamento()" onblur="formatValorBrInputOnBlur(this)" class="js-valor-br-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="parcelas-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Parcelas</label>
                    <input type="number" step="1" min="1" name="pagamentos[${index}][parcelas]" value="1" onchange="atualizarPixParcelasVencRow(this.closest('[data-adiantamento-pagamento-index]'), document.getElementById('adiantamento_data')?.value)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="cartao-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Adquirente *</label>
                    <select name="pagamentos[${index}][adquirente_id]" class="cartao-required w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" onchange="atualizarContaPorAdquirenteAdiantamento(this, ${index})">
                        <option value="">Selecione...</option>
                        @foreach($adquirentes ?? [] as $adq)
                            <option value="{{ $adq->id }}" data-conta-padrao-id="{{ $adq->contaPadrao()?->id ?? '' }}">{{ $adq->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cartao-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Bandeira do cartão *</label>
                    <select name="pagamentos[${index}][bandeira]" class="cartao-required w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="master">Mastercard</option>
                        <option value="visa">Visa</option>
                        <option value="elo">Elo</option>
                        <option value="amex">American Express</option>
                        <option value="hipercard">Hipercard</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                <div class="hidden" aria-hidden="true">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                    <select name="pagamentos[${index}][conta_bancaria_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Usar conta da forma de pagamento</option>
                        @foreach($contasBancarias ?? [] as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pix-wrap hidden md:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Chave PIX de recebimento</label>
                    <select name="pagamentos[${index}][pix_chave_id]" onchange="aplicarPixChaveAdiantamento(this); atualizarPixParcelasVencRow(this.closest('[data-adiantamento-pagamento-index]'), document.getElementById('adiantamento_data')?.value)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="pix-parcelas-venc-wrap hidden md:col-span-3" data-role="pix-parcelas-venc">
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Datas de cada parcela PIX. Vencimento até a data do adiantamento: baixa automática nessa parcela. Datas futuras: ficam em aberto no financeiro até confirmar o recebimento.</p>
                    <div class="pix-parcelas-venc-list space-y-2"></div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const lastAd = container.lastElementChild;
    if (lastAd) atualizarPixParcelasVencRow(lastAd, document.getElementById('adiantamento_data')?.value);
    validarPagamentosAdiantamento();
}
function removerPagamentoAdiantamento(btn) {
    btn.closest('[data-adiantamento-pagamento-index]').remove();
    validarPagamentosAdiantamento();
}
function atualizarFormaPagamentoAdiantamento(select, index) {
    const wrapper = select.closest('[data-adiantamento-pagamento-index]');
    const parcelasWrap = wrapper.querySelector('.parcelas-wrap');
    const cartaoWrap = wrapper.querySelectorAll('.cartao-wrap');
    const contaBancariaSelect = wrapper.querySelector('select[name*="[conta_bancaria_id]"]');
    const pixWrap = wrapper.querySelector('.pix-wrap');
    const pixSelect = wrapper.querySelector('select[name*="[pix_chave_id]"]');
    const option = select.selectedOptions[0];
    if (option && option.value) {
        const tipo = option.dataset.tipo || '';
        const permiteParcela = option.dataset.permiteParcela === '1';
        const contaBancariaId = option.dataset.contaBancariaId;
        if (permiteParcela) parcelasWrap.classList.remove('hidden'); else { parcelasWrap.classList.add('hidden'); const p = wrapper.querySelector('input[name*="[parcelas]"]'); if (p) p.value = 1; }
        const ehCartao = tipo === 'cartao_credito' || tipo === 'cartao_debito';
        const ehPix = tipo === 'pix';
        cartaoWrap.forEach(el => { if (ehCartao) { el.classList.remove('hidden'); el.querySelectorAll('.cartao-required').forEach(i => i.setAttribute('required', 'required')); } else { el.classList.add('hidden'); el.querySelectorAll('.cartao-required').forEach(i => i.removeAttribute('required')); const a = wrapper.querySelector('select[name*="[adquirente_id]"]'); const b = wrapper.querySelector('select[name*="[bandeira]"]'); if (a) a.value = ''; if (b) b.value = ''; } });
        if (contaBancariaId && !contaBancariaSelect.value) contaBancariaSelect.value = contaBancariaId;
        if (pixWrap && pixSelect) {
            pixWrap.classList.toggle('hidden', !ehPix);
            pixSelect.innerHTML = '<option value="">Selecione...</option>';
            if (ehPix) {
                const chaves = parsePixChavesData(option.dataset.pixChaves || '[]');
                chaves.forEach(ch => {
                    const opt = document.createElement('option');
                    opt.value = ch.id || '';
                    opt.textContent = `${ch.nome || 'Chave PIX'} (${ch.chave || ''})`;
                    opt.dataset.contaBancariaId = ch.conta_bancaria_id || '';
                    pixSelect.appendChild(opt);
                });
                if (!chaves.length) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'Nenhuma chave PIX cadastrada nesta forma';
                    pixSelect.appendChild(opt);
                }
                if (chaves.length) pixSelect.setAttribute('required', 'required');
                else pixSelect.removeAttribute('required');
            } else {
                pixSelect.value = '';
                pixSelect.removeAttribute('required');
            }
        }
        if (ehCartao && contaBancariaSelect) { const adq = wrapper.querySelector('select[name*="[adquirente_id]"]'); if (adq && adq.value) { const o = adq.selectedOptions[0]; if (o && o.dataset.contaPadraoId) contaBancariaSelect.value = o.dataset.contaPadraoId; } }
        atualizarPixParcelasVencRow(wrapper, document.getElementById('adiantamento_data')?.value);
    } else {
        parcelasWrap.classList.add('hidden');
        cartaoWrap.forEach(el => el.classList.add('hidden'));
        if (pixWrap) pixWrap.classList.add('hidden');
        const pixVenc = wrapper.querySelector('[data-role="pix-parcelas-venc"]');
        if (pixVenc) { pixVenc.classList.add('hidden'); const pl = pixVenc.querySelector('.pix-parcelas-venc-list'); if (pl) pl.innerHTML = ''; }
    }
}
function aplicarPixChaveAdiantamento(select) {
    const wrapper = select.closest('[data-adiantamento-pagamento-index]');
    const contaBancariaSelect = wrapper?.querySelector('select[name*="[conta_bancaria_id]"]');
    const option = select.selectedOptions[0];
    if (option && option.dataset.contaBancariaId && contaBancariaSelect) {
        contaBancariaSelect.value = option.dataset.contaBancariaId;
    }
}
function atualizarContaPorAdquirenteAdiantamento(select, index) {
    const wrapper = select.closest('[data-adiantamento-pagamento-index]');
    const contaBancariaSelect = wrapper.querySelector('select[name*="[conta_bancaria_id]"]');
    const option = select.selectedOptions[0];
    if (option && option.value && option.dataset.contaPadraoId && contaBancariaSelect) contaBancariaSelect.value = option.dataset.contaPadraoId;
}
function validarPagamentosAdiantamento() {
    const limiteEl = document.getElementById('adiantamento_limite_display');
    let limite = 0;
    if (limiteEl && limiteEl.textContent) { const m = limiteEl.textContent.replace(/\s/g,'').match(/R\$?([\d.,]+)/); if (m) limite = parseFloat(m[1].replace(/\./g,'').replace(',','.')) || 0; }
    const inputs = document.querySelectorAll('#adiantamento_pagamentos_container input[name*="[valor]"]');
    let total = 0;
    inputs.forEach(i => { total += parseBrNumber(i.value || 0); });
    document.getElementById('adiantamento_total_pagamentos').textContent = 'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const validacao = document.getElementById('adiantamento_validacao_pagamentos');
    if (total <= 0) { validacao.textContent = 'Adicione pelo menos um pagamento.'; validacao.className = 'mt-2 text-xs text-gray-500 dark:text-gray-400'; return; }
    if (total > limite + 0.01) { validacao.textContent = 'Total não pode ultrapassar o valor máximo do adiantamento (R$ ' + limite.toFixed(2).replace('.', ',') + ').'; validacao.className = 'mt-2 text-xs text-red-600 dark:text-red-400'; return; }
    validacao.textContent = '✓ Valores conferem'; validacao.className = 'mt-2 text-xs text-green-600 dark:text-green-400';
}
document.getElementById('formAdiantamento')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('btnAdiantamentoSubmit');
    var url = '{{ route("ordens-servico.adiantamentos.store", $ordem) }}';
    var limiteEl = document.getElementById('adiantamento_limite_display');
    var limite = 0;
    if (limiteEl && limiteEl.textContent) { var m = limiteEl.textContent.replace(/\s/g,'').match(/R\$?([\d.,]+)/); if (m) limite = parseFloat(m[1].replace(/\./g,'').replace(',','.')) || 0; }
    var inputs = document.querySelectorAll('#adiantamento_pagamentos_container input[name*="[valor]"]');
    var total = 0;
    inputs.forEach(function(i){ total += parseBrNumber(i.value || 0); });
    if (total <= 0) { if (typeof showToast === 'function') showToast('Adicione pelo menos um pagamento com valor.', 'error'); else alert('Adicione pelo menos um pagamento com valor.'); return; }
    if (total > limite + 0.01) { if (typeof showToast === 'function') showToast('Total não pode ultrapassar o valor máximo do adiantamento.', 'error'); else alert('Total não pode ultrapassar o valor máximo do adiantamento.'); return; }
    var rowsAd = document.querySelectorAll('#adiantamento_pagamentos_container [data-adiantamento-pagamento-index]');
    for (var ri = 0; ri < rowsAd.length; ri++) {
        var row = rowsAd[ri];
        var fs = row.querySelector('select[name*="[forma_pagamento_id]"]');
        var o = fs && fs.selectedOptions[0];
        var tipoPg = o && o.dataset.tipo ? String(o.dataset.tipo) : '';
        var parc = parseInt(row.querySelector('input[name*="[parcelas]"]') && row.querySelector('input[name*="[parcelas]"]').value, 10) || 1;
        var pixSel = row.querySelector('select[name*="[pix_chave_id]"]');
        if (tipoPg === 'pix' && parc > 1) {
            if (!pixSel || !pixSel.value) {
                if (typeof showToast === 'function') showToast('Selecione a chave PIX em todo pagamento parcelado.', 'error'); else alert('Selecione a chave PIX em todo pagamento parcelado.');
                return;
            }
            var datas = row.querySelectorAll('input[name*="[pix_parcela_vencimentos]"]');
            if (datas.length !== parc || Array.prototype.some.call(datas, function (d) { return !d.value; })) {
                if (typeof showToast === 'function') showToast('Informe a data de vencimento de cada parcela do PIX.', 'error'); else alert('Informe a data de vencimento de cada parcela do PIX.');
                return;
            }
        }
    }
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    try {
        var fd = new FormData(form);
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value;
        var res = await fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, credentials: 'same-origin', body: fd });
        var data = await res.json().catch(function() { return {}; });
        if (!res.ok) { if (typeof showToast === 'function') showToast(data.message || 'Erro ao registrar adiantamento.', 'error'); else alert(data.message || 'Erro ao registrar adiantamento.'); return; }
        if (typeof showToast === 'function') showToast(data.message || 'Adiantamento registrado.', 'success');
        fecharModalAdiantamento();
        form.reset();
        var totalEl = document.getElementById('total-adiantado-display');
        if (data.total_adiantado !== undefined && totalEl) totalEl.textContent = 'R$ ' + parseFloat(data.total_adiantado).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (data.contas_criadas && document.getElementById('adiantamentos-tbody')) {
            var tbody = document.getElementById('adiantamentos-tbody');
            var tbl = document.getElementById('adiantamentos-table');
            var emptyMsg = document.getElementById('adiantamentos-empty-msg');
            (data.linhas || []).forEach(function(linha) {
                var tr = document.createElement('tr');
                tr.className = 'border-t border-gray-200 dark:border-gray-700';
                tr.setAttribute('data-adiantamento-id', linha.id || '');
                var statusHtml = (linha.status === 'pago')
                    ? '<span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">Pago</span>'
                    : (linha.status === 'aberto')
                        ? '<span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Aguardando confirmação</span>'
                        : '<span class="text-xs text-gray-500 dark:text-gray-400">' + (linha.status ? linha.status : '-') + '</span>';
                var descEsc = (linha.descricao || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                var valorNum = (linha.valor_num != null && linha.valor_num !== '') ? parseFloat(linha.valor_num) : 0;
                tr.innerHTML = '<td class="px-3 py-2 text-sm text-gray-800 dark:text-white/90">' + (linha.data || '-') + '</td><td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">' + (linha.valor || '-') + '</td><td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">' + (linha.forma || '-') + '</td><td class="px-3 py-2">' + statusHtml + '</td><td class="px-3 py-2 text-right"><button type="button" onclick="abrirModalEstornarAdiantamento(' + (linha.id || 0) + ', \'' + descEsc + '\', ' + valorNum + ')" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Estornar</button></td>';
                tbody.appendChild(tr);
            });
            if (emptyMsg) emptyMsg.style.display = 'none';
            if (tbl) tbl.style.display = '';
        }
        var secao = document.getElementById('secao-adiantamentos');
        if (secao && data.total_adiantado !== undefined) {
            var totalGeral = parseFloat(secao.getAttribute('data-total-geral')) || 0;
            var totalAdiantado = parseFloat(data.total_adiantado) || 0;
            var btnReg = document.getElementById('btnRegistrarAdiantamento');
            var aviso = document.getElementById('adiantamento-coberto-aviso');
            if (totalAdiantado >= totalGeral - 0.01 && btnReg && aviso) { btnReg.disabled = true; btnReg.classList.add('opacity-60', 'cursor-not-allowed'); aviso.classList.remove('hidden'); }
        }
    } catch (err) { if (typeof showToast === 'function') showToast('Erro: ' + (err.message || 'tente novamente.'), 'error'); else alert('Erro: ' + (err.message || 'tente novamente.')); }
    finally { if (btn) { btn.disabled = false; btn.textContent = 'Registrar'; } }
});
document.getElementById('adiantamento_data')?.addEventListener('change', function () {
    const v = this.value;
    document.querySelectorAll('#adiantamento_pagamentos_container [data-adiantamento-pagamento-index]').forEach(function (w) {
        atualizarPixParcelasVencRow(w, v);
    });
});
</script>

<!-- Modal de Encerramento de OS -->
<div id="modalEncerrar" class="fixed inset-0 z-[2147483001] hidden" style="z-index:2147483647 !important;">
    <div class="absolute inset-0 bg-gray-900/50" onclick="fecharModalEncerrar()"></div>
    <div class="relative mx-auto mt-8 w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Encerrar Ordem de Serviço</h3>
            <button type="button" onclick="fecharModalEncerrar()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✕</button>
        </div>
        
        <form id="formEncerrar" action="{{ route('ordens-servico.encerrar', $ordem) }}" method="POST" class="p-6">
            @csrf
            
            @php
                $ehGarantiaOuContrato = in_array($ordem->garantia_tipo, ['dentro', 'estendida']) || $ordem->coberto_por_contrato;
                $valorTotal = $ehGarantiaOuContrato ? 0 : (float)$ordem->total_geral;
                $totalAdiantadoEncerrar = (float) $ordem->total_adiantado_confirmado;
                $adiantamentoAbertoVal = max(0, (float) $ordem->total_adiantado - $totalAdiantadoEncerrar);
                $valorAReceberBase = max(0, $valorTotal - $totalAdiantadoEncerrar);
            @endphp
            
            <!-- Etapa 1: Checagem de Cobertura -->
            <div class="mb-6">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">1. Verificação de Cobertura</h4>
                @if($ehGarantiaOuContrato)
                    <div class="rounded-lg border border-green-300 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-medium text-green-800 dark:text-green-200">OS coberta por garantia/contrato</p>
                                <p class="mt-1 text-sm text-green-700 dark:text-green-300">O valor a cobrar do cliente será <strong>R$ 0,00</strong>. O sistema registrará apenas a saída de estoque e contabilizará o custo interno.</p>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="valor_total" value="0">
                @else
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total da OS:</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($valorTotal, 2, ',', '.') }}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Desconto</label>
                                <select name="desconto_tipo" id="desconto_tipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="valor">R$ (Valor Fixo)</option>
                                    <option value="percentual">% (Percentual)</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto (R$ ou %)</label>
                                <input type="text" inputmode="decimal" name="desconto_valor" id="desconto_valor" value="{{ old('desconto_valor', '0') }}" placeholder="0,00" class="js-valor-br-input w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" oninput="calcularValorAReceber()" onblur="formatValorBrInputOnBlur(this)">
                                @if(!empty($usuarioSujeitoALimiteDesconto) && isset($limiteDesconto) && ($limiteDesconto->limite_valor ?? $limiteDesconto->limite_percentual))
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Limite: @if($limiteDesconto->limite_valor) R$ {{ number_format($limiteDesconto->limite_valor, 2, ',', '.') }} @endif @if($limiteDesconto->limite_valor && $limiteDesconto->limite_percentual) | @endif @if($limiteDesconto->limite_percentual) {{ number_format($limiteDesconto->limite_percentual, 1, ',', '.') }}% @endif</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-700 dark:bg-blue-900/20">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Valor a Receber:</p>
                            @if($totalAdiantadoEncerrar > 0)
                                <p class="text-xs text-blue-700 dark:text-blue-300">(Total da OS menos R$ {{ number_format($totalAdiantadoEncerrar, 2, ',', '.') }} já adiantados e confirmados no financeiro)</p>
                            @endif
                            @if($adiantamentoAbertoVal > 0)
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">R$ {{ number_format($adiantamentoAbertoVal, 2, ',', '.') }} em adiantamentos aguardando confirmação no financeiro não entram no valor a receber.</p>
                            @endif
                            <p class="text-xl font-bold text-blue-900 dark:text-blue-100" id="valor_a_receber">R$ {{ number_format($valorAReceberBase, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    <input type="hidden" name="valor_total" value="{{ $valorTotal }}">
                @endif
            </div>

            <!-- Etapa 2: Seleção de Prazo e Pagamento -->
            @if(!$ehGarantiaOuContrato)
            <div class="mb-6">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">2. Forma de Pagamento</h4>
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Pagamento</label>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-gray-200 p-4 hover:border-brand-300 dark:border-gray-700 dark:hover:border-brand-500">
                            <input type="radio" name="tipo_pagamento" value="agora" checked onchange="atualizarTipoPagamento()" class="h-4 w-4 text-brand-500">
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-white/90">Pagamento Agora</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Recebimento imediato (split permitido)</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 border-gray-200 p-4 hover:border-brand-300 dark:border-gray-700 dark:hover:border-brand-500">
                            <input type="radio" name="tipo_pagamento" value="faturado" onchange="atualizarTipoPagamento()" class="h-4 w-4 text-brand-500">
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 dark:text-white/90">Faturado/Fiado</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Gerar conta a receber futura</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Pagamento Agora -->
                <div id="pagamento_agora" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300">Formas de Pagamento</h5>
                        <button type="button" onclick="adicionarPagamento()" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            + Adicionar
                        </button>
                    </div>
                    <div id="pagamentos_container" class="space-y-3">
                        <!-- Pagamentos serão adicionados aqui via JS -->
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total dos Pagamentos:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white" id="total_pagamentos">R$ 0,00</span>
                        </div>
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400" id="validacao_pagamentos"></div>
                    </div>
                </div>

                <!-- Faturado/Fiado -->
                <div id="pagamento_faturado" class="hidden">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-900/20">
                        <p class="text-sm text-blue-800 dark:text-blue-200">Uma conta a receber será criada automaticamente para o valor a receber calculado acima.</p>
                    </div>
                </div>
            </div>
            @else
                <input type="hidden" name="tipo_pagamento" value="faturado">
            @endif

            <!-- Opção de Impressão -->
            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="imprimir_comprovante" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Imprimir comprovante de entrega após encerrar</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button type="button" onclick="fecharModalEncerrar()" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600">Confirmar Encerramento</button>
            </div>
        </form>
    </div>
</div>

<script>
let pagamentoIndex = 0;
let formasPagamento = @json($formasPagamento ?? []);
let contasBancarias = @json($contasBancarias ?? []);

function parseBrNumber(val) {
    if (val == null || val === '') return 0;
    const str = String(val).trim();
    const lastComma = str.lastIndexOf(',');
    const lastDot = str.lastIndexOf('.');
    let s = str;
    if (lastComma !== -1 && (lastDot === -1 || lastComma > lastDot)) {
        s = str.replace(/\./g, '').replace(',', '.');
    } else {
        s = str.replace(/,/g, '');
    }
    return parseFloat(s) || 0;
}

/** Formata número como moeda BR (1.234,56) para exibição em inputs */
function formatBrDecimal(num) {
    const n = Number(num);
    if (isNaN(n)) return '';
    return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatValorBrInputOnBlur(el) {
    if (!el || el.readOnly) return;
    const raw = String(el.value || '').trim();
    if (raw === '') return;
    const n = parseBrNumber(raw);
    el.value = formatBrDecimal(n);
}

function localYmd(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function addDaysYmd(ymd, days) {
    const p = String(ymd).split('-');
    if (p.length !== 3) return ymd;
    const d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    d.setDate(d.getDate() + days);
    return localYmd(d);
}

function atualizarPixParcelasVencRow(wrapper, dataBaseYmd) {
    if (!wrapper) return;
    const formaSel = wrapper.querySelector('select[name*="[forma_pagamento_id]"]');
    const parcInp = wrapper.querySelector('input[name*="[parcelas]"]');
    const wrap = wrapper.querySelector('[data-role="pix-parcelas-venc"]');
    const list = wrapper.querySelector('.pix-parcelas-venc-list');
    if (!formaSel || !parcInp || !wrap || !list) return;
    const opt = formaSel.selectedOptions[0];
    const tipo = (opt && opt.dataset.tipo) ? String(opt.dataset.tipo) : '';
    const n = Math.max(1, parseInt(parcInp.value, 10) || 1);
    if (tipo === 'pix' && n > 1) {
        wrap.classList.remove('hidden');
        list.innerHTML = '';
        const idxMatch = formaSel.name.match(/\[(\d+)\]/);
        const idx = idxMatch ? idxMatch[1] : '0';
        const base = (dataBaseYmd && String(dataBaseYmd).length >= 10) ? String(dataBaseYmd).slice(0, 10) : localYmd(new Date());
        let hoje = base;
        for (let i = 1; i <= n; i++) {
            const venc = i === 1 ? hoje : addDaysYmd(hoje, (i - 1) * 30);
            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center gap-2';
            row.innerHTML = `
                <label class="text-xs text-gray-600 dark:text-gray-400 shrink-0">Parcela ${i}/${n} — vencimento</label>
                <input type="date" name="pagamentos[${idx}][pix_parcela_vencimentos][]" value="${venc}" required
                    class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            `;
            list.appendChild(row);
        }
    } else {
        wrap.classList.add('hidden');
        list.innerHTML = '';
    }
}

function abrirModalEncerrar() {
    document.getElementById('modalEncerrar').classList.remove('hidden');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(true);
    const dv = document.getElementById('desconto_valor');
    if (dv && String(dv.value || '').trim() !== '') formatValorBrInputOnBlur(dv);
    @if(!$ehGarantiaOuContrato)
    atualizarTipoPagamento();
    @endif
}

// Recarrega a página com dados atualizados da OS e abre o modal (evita valores desatualizados após salvar itens)
// Antes de redirecionar, salva o formulário da OS (garantia e outros campos) para não perder dados
async function abrirModalEncerrarComDadosAtualizados() {
    const url = new URL(window.location.href);
    if (url.searchParams.get('abrir_encerrar') === '1') {
        abrirModalEncerrar();
        url.searchParams.delete('abrir_encerrar');
        window.history.replaceState({}, '', url.toString());
    } else {
        try {
            if (typeof window.salvarOsFormAsync === 'function') {
                await window.salvarOsFormAsync(true);
            }
        } catch (e) {
            if (typeof showToast === 'function') {
                showToast('Salve as alterações antes de encerrar: ' + (e.message || ''), 'error');
            } else {
                alert('Salve as alterações antes de encerrar: ' + (e.message || ''));
            }
            return;
        }
        url.searchParams.set('abrir_encerrar', '1');
        window.location.href = url.toString();
    }
}

function fecharModalEncerrar() {
    document.getElementById('modalEncerrar').classList.add('hidden');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(false);
    document.getElementById('pagamentos_container').innerHTML = '';
    pagamentoIndex = 0;
}

function calcularValorAReceber() {
    const valorTotal = {{ $valorTotal }};
    const totalAdiantado = {{ $totalAdiantadoEncerrar }};
    const descontoTipo = document.getElementById('desconto_tipo').value;
    const descontoValor = parseBrNumber(document.getElementById('desconto_valor')?.value || 0);
    
    let desconto = 0;
    if (descontoTipo === 'percentual') {
        desconto = (valorTotal * descontoValor) / 100;
    } else {
        desconto = descontoValor;
    }
    desconto = Math.min(desconto, valorTotal);
    
    const valorAReceber = Math.max(0, valorTotal - totalAdiantado - desconto);
    document.getElementById('valor_a_receber').textContent = 'R$ ' + valorAReceber.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    validarPagamentos();
}

function atualizarTipoPagamento() {
    const tipo = document.querySelector('input[name="tipo_pagamento"]:checked').value;
    const agora = document.getElementById('pagamento_agora');
    const faturado = document.getElementById('pagamento_faturado');
    
    if (tipo === 'agora') {
        agora.classList.remove('hidden');
        faturado.classList.add('hidden');
        if (pagamentoIndex === 0) {
            adicionarPagamento();
        }
    } else {
        agora.classList.add('hidden');
        faturado.classList.remove('hidden');
        document.getElementById('pagamentos_container').innerHTML = '';
        pagamentoIndex = 0;
    }
    validarPagamentos();
}

function adicionarPagamento() {
    const container = document.getElementById('pagamentos_container');
    const index = pagamentoIndex++;
    
    const html = `
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-pagamento-index="${index}">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pagamento ${index + 1}</span>
                <button type="button" onclick="removerPagamento(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="pagamentos[${index}][forma_pagamento_id]" required onchange="atualizarFormaPagamento(this, ${index})" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento ?? [] as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo ?? '' }}" data-permite-parcela="{{ $forma->permite_parcela ? '1' : '0' }}" data-dias-recebimento="{{ $forma->dias_recebimento }}" data-conta-bancaria-id="{{ $forma->conta_bancaria_id ?? '' }}" data-pix-chaves="{{ json_encode($forma->pix_chaves_ativas ?? [], JSON_UNESCAPED_UNICODE) }}">{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                    <input type="text" inputmode="decimal" name="pagamentos[${index}][valor]" required placeholder="0,00" oninput="validarPagamentos()" onblur="formatValorBrInputOnBlur(this)" class="js-valor-br-input w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="parcelas-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Parcelas</label>
                    <input type="number" step="1" min="1" name="pagamentos[${index}][parcelas]" value="1" onchange="atualizarPixParcelasVencRow(this.closest('[data-pagamento-index]'))" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="cartao-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Adquirente *</label>
                    <select name="pagamentos[${index}][adquirente_id]" class="cartao-required w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" onchange="atualizarContaPorAdquirente(this, ${index})">
                        <option value="">Selecione...</option>
                        @foreach($adquirentes ?? [] as $adq)
                            <option value="{{ $adq->id }}" data-conta-padrao-id="{{ $adq->contaPadrao()?->id ?? '' }}">{{ $adq->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cartao-wrap hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Bandeira do cartão *</label>
                    <select name="pagamentos[${index}][bandeira]" class="cartao-required w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="master">Mastercard</option>
                        <option value="visa">Visa</option>
                        <option value="elo">Elo</option>
                        <option value="amex">American Express</option>
                        <option value="hipercard">Hipercard</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                <div class="hidden" aria-hidden="true">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                    <select name="pagamentos[${index}][conta_bancaria_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Usar conta da forma de pagamento</option>
                        @foreach($contasBancarias ?? [] as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pix-wrap hidden md:col-span-3">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Chave PIX de recebimento</label>
                    <select name="pagamentos[${index}][pix_chave_id]" onchange="aplicarPixChave(this); atualizarPixParcelasVencRow(this.closest('[data-pagamento-index]'))" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="pix-parcelas-venc-wrap hidden md:col-span-3" data-role="pix-parcelas-venc">
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Datas de cada parcela PIX. Vencimento até hoje: baixa automática nessa parcela. Datas futuras: ficam em aberto no financeiro até confirmar o recebimento.</p>
                    <div class="pix-parcelas-venc-list space-y-2"></div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const lastRow = container.lastElementChild;
    if (lastRow) atualizarPixParcelasVencRow(lastRow);
    validarPagamentos();
}

function removerPagamento(btn) {
    btn.closest('[data-pagamento-index]').remove();
    validarPagamentos();
}

function parsePixChavesData(raw) {
    if (!raw) return [];
    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e1) {
        try {
            const txt = String(raw).replace(/&quot;/g, '"').replace(/&#34;/g, '"').replace(/&amp;/g, '&');
            const parsed = JSON.parse(txt);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e2) {
            return [];
        }
    }
}

function atualizarFormaPagamento(select, index) {
    const wrapper = select.closest('[data-pagamento-index]');
    const parcelasWrap = wrapper.querySelector('.parcelas-wrap');
    const cartaoWrap = wrapper.querySelectorAll('.cartao-wrap');
    const contaBancariaSelect = wrapper.querySelector('select[name*="[conta_bancaria_id]"]');
    const pixWrap = wrapper.querySelector('.pix-wrap');
    const pixSelect = wrapper.querySelector('select[name*="[pix_chave_id]"]');
    const option = select.selectedOptions[0];
    
    if (option && option.value) {
        const tipo = option.dataset.tipo || '';
        const permiteParcela = option.dataset.permiteParcela === '1';
        const contaBancariaId = option.dataset.contaBancariaId;
        
        if (permiteParcela) {
            parcelasWrap.classList.remove('hidden');
        } else {
            parcelasWrap.classList.add('hidden');
            const parcelasInput = wrapper.querySelector('input[name*="[parcelas]"]');
            if (parcelasInput) parcelasInput.value = 1;
        }
        
        const ehCartaoCreditoOuDebito = tipo === 'cartao_credito' || tipo === 'cartao_debito';
        const ehPix = tipo === 'pix';
        cartaoWrap.forEach(el => {
            if (ehCartaoCreditoOuDebito) {
                el.classList.remove('hidden');
                el.querySelectorAll('.cartao-required').forEach(input => { input.setAttribute('required', 'required'); });
            } else {
                el.classList.add('hidden');
                el.querySelectorAll('.cartao-required').forEach(input => { input.removeAttribute('required'); });
                const adq = wrapper.querySelector('select[name*="[adquirente_id]"]');
                const band = wrapper.querySelector('select[name*="[bandeira]"]');
                if (adq) adq.value = '';
                if (band) band.value = '';
            }
        });
        
        if (contaBancariaId && !contaBancariaSelect.value) {
            contaBancariaSelect.value = contaBancariaId;
        }
        if (pixWrap && pixSelect) {
            pixWrap.classList.toggle('hidden', !ehPix);
            pixSelect.innerHTML = '<option value="">Selecione...</option>';
            if (ehPix) {
                const chaves = parsePixChavesData(option.dataset.pixChaves || '[]');
                chaves.forEach(ch => {
                    const opt = document.createElement('option');
                    opt.value = ch.id || '';
                    opt.textContent = `${ch.nome || 'Chave PIX'} (${ch.chave || ''})`;
                    opt.dataset.contaBancariaId = ch.conta_bancaria_id || '';
                    pixSelect.appendChild(opt);
                });
                if (!chaves.length) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'Nenhuma chave PIX cadastrada nesta forma';
                    pixSelect.appendChild(opt);
                }
                if (chaves.length) pixSelect.setAttribute('required', 'required');
                else pixSelect.removeAttribute('required');
            } else {
                pixSelect.value = '';
                pixSelect.removeAttribute('required');
            }
        }
        // Se for cartão de crédito ou débito e já tiver adquirente selecionado, preencher conta padrão do adquirente
        if (ehCartaoCreditoOuDebito) {
            const adqSelect = wrapper.querySelector('select[name*="[adquirente_id]"]');
            if (adqSelect && adqSelect.value) {
                const opt = adqSelect.selectedOptions[0];
                const contaPadraoId = opt && opt.dataset.contaPadraoId;
                if (contaPadraoId && contaBancariaSelect) {
                    contaBancariaSelect.value = contaPadraoId;
                }
            }
        }
        atualizarPixParcelasVencRow(wrapper);
    } else {
        parcelasWrap.classList.add('hidden');
        cartaoWrap.forEach(el => el.classList.add('hidden'));
        if (pixWrap) pixWrap.classList.add('hidden');
        const pixVenc = wrapper.querySelector('[data-role="pix-parcelas-venc"]');
        if (pixVenc) { pixVenc.classList.add('hidden'); const pl = pixVenc.querySelector('.pix-parcelas-venc-list'); if (pl) pl.innerHTML = ''; }
    }
}
function aplicarPixChave(select) {
    const wrapper = select.closest('[data-pagamento-index]');
    const contaBancariaSelect = wrapper?.querySelector('select[name*="[conta_bancaria_id]"]');
    const option = select.selectedOptions[0];
    if (option && option.dataset.contaBancariaId && contaBancariaSelect) {
        contaBancariaSelect.value = option.dataset.contaBancariaId;
    }
}

function atualizarContaPorAdquirente(select, index) {
    const wrapper = select.closest('[data-pagamento-index]');
    const contaBancariaSelect = wrapper.querySelector('select[name*="[conta_bancaria_id]"]');
    const option = select.selectedOptions[0];
    if (option && option.value && option.dataset.contaPadraoId && contaBancariaSelect) {
        contaBancariaSelect.value = option.dataset.contaPadraoId;
    }
}

function validarPagamentos() {
    const tipoPagamento = document.querySelector('input[name="tipo_pagamento"]:checked')?.value;
    if (tipoPagamento !== 'agora') {
        document.getElementById('total_pagamentos').textContent = 'R$ 0,00';
        document.getElementById('validacao_pagamentos').textContent = '';
        return;
    }
    
    @php
        $ehGarantiaOuContrato = in_array($ordem->garantia_tipo, ['dentro', 'estendida']) || $ordem->coberto_por_contrato;
        $valorTotal = $ehGarantiaOuContrato ? 0 : (float)$ordem->total_geral;
        $totalAdiantadoValidacao = (float) $ordem->total_adiantado_confirmado;
    @endphp
    const valorTotal = {{ $valorTotal }};
    const totalAdiantadoValidacao = {{ $totalAdiantadoValidacao }};
    const descontoTipo = document.getElementById('desconto_tipo')?.value || 'valor';
    const descontoValor = parseBrNumber(document.getElementById('desconto_valor')?.value || 0);
    
    let desconto = 0;
    if (descontoTipo === 'percentual') {
        desconto = (valorTotal * descontoValor) / 100;
    } else {
        desconto = descontoValor;
    }
    desconto = Math.min(desconto, valorTotal);
    const valorAReceber = Math.max(0, valorTotal - totalAdiantadoValidacao - desconto);
    
    const pagamentos = document.querySelectorAll('#pagamentos_container input[name*="[valor]"]');
    let totalPagamentos = 0;
    pagamentos.forEach(input => {
        totalPagamentos += parseBrNumber(input.value || 0);
    });
    
    document.getElementById('total_pagamentos').textContent = 'R$ ' + totalPagamentos.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    const diferenca = Math.abs(totalPagamentos - valorAReceber);
    const validacao = document.getElementById('validacao_pagamentos');
    
    if (valorAReceber <= 0.01) {
        validacao.textContent = '✓ Valor já coberto por adiantamento(s). Nenhum pagamento adicional necessário.';
        validacao.className = 'mt-2 text-xs text-green-600 dark:text-green-400';
    } else if (diferenca <= 0.01) {
        validacao.textContent = '✓ Valores conferem';
        validacao.className = 'mt-2 text-xs text-green-600 dark:text-green-400';
    } else if (totalPagamentos < valorAReceber) {
        validacao.textContent = `⚠ Faltam R$ ${(valorAReceber - totalPagamentos).toFixed(2).replace('.', ',')}`;
        validacao.className = 'mt-2 text-xs text-orange-600 dark:text-orange-400';
    } else {
        validacao.textContent = `⚠ Excedem R$ ${(totalPagamentos - valorAReceber).toFixed(2).replace('.', ',')}`;
        validacao.className = 'mt-2 text-xs text-red-600 dark:text-red-400';
    }
}

document.getElementById('formEncerrar')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const tipoPagamento = document.querySelector('input[name="tipo_pagamento"]:checked')?.value;
    @php
        $ehGarantiaOuContrato = in_array($ordem->garantia_tipo, ['dentro', 'estendida']) || $ordem->coberto_por_contrato;
        $valorTotal = $ehGarantiaOuContrato ? 0 : (float)$ordem->total_geral;
        $totalAdiantadoSubmit = (float) $ordem->total_adiantado_confirmado;
    @endphp
    const valorTotal = {{ $valorTotal }};
    const totalAdiantadoSubmit = {{ $totalAdiantadoSubmit }};
    const descontoTipo = document.getElementById('desconto_tipo')?.value || 'valor';
    const descontoValor = parseBrNumber(document.getElementById('desconto_valor')?.value || 0);
    let desconto = 0;
    if (descontoTipo === 'percentual') {
        desconto = (valorTotal * descontoValor) / 100;
    } else {
        desconto = descontoValor;
    }
    desconto = Math.min(desconto, valorTotal);
    const valorAReceber = Math.max(0, valorTotal - totalAdiantadoSubmit - desconto);
    if (tipoPagamento === 'agora') {
        const pagamentos = document.querySelectorAll('#pagamentos_container input[name*="[valor]"]');
        let totalPagamentos = 0;
        pagamentos.forEach(input => {
            totalPagamentos += parseBrNumber(input.value || 0);
        });
        const diferenca = Math.abs(totalPagamentos - valorAReceber);
        if (diferenca > 0.01) {
            if (typeof showToast === 'function') {
                showToast('A soma dos pagamentos deve ser exatamente igual ao valor a receber. Por favor, ajuste os valores.', 'error');
            } else {
                alert('A soma dos pagamentos deve ser exatamente igual ao valor a receber. Por favor, ajuste os valores.');
            }
            return;
        }
        const rowsPg = document.querySelectorAll('#pagamentos_container [data-pagamento-index]');
        for (const row of rowsPg) {
            const fs = row.querySelector('select[name*="[forma_pagamento_id]"]');
            const o = fs && fs.selectedOptions[0];
            const tipo = o && o.dataset.tipo ? String(o.dataset.tipo) : '';
            const parc = parseInt(row.querySelector('input[name*="[parcelas]"]')?.value, 10) || 1;
            const pixSel = row.querySelector('select[name*="[pix_chave_id]"]');
            if (tipo === 'pix' && parc > 1) {
                if (!pixSel || !pixSel.value) {
                    if (typeof showToast === 'function') showToast('Selecione a chave PIX em todo pagamento parcelado.', 'error');
                    else alert('Selecione a chave PIX em todo pagamento parcelado.');
                    return;
                }
                const datas = row.querySelectorAll('input[name*="[pix_parcela_vencimentos]"]');
                if (datas.length !== parc || [...datas].some(d => !d.value)) {
                    if (typeof showToast === 'function') showToast('Informe a data de vencimento de cada parcela do PIX.', 'error');
                    else alert('Informe a data de vencimento de cada parcela do PIX.');
                    return;
                }
            }
        }
    }
    const imprimirComprovante = !!(form.querySelector('input[name="imprimir_comprovante"]')?.checked);
    let printWindow = null;
    // Sem "noopener" aqui: com noopener o open() costuma devolver null e a aba fica em branco
    // (não dá para definir location depois do fetch). Mesma origem ao carregar o comprovante.
    if (imprimirComprovante) {
        printWindow = window.open('about:blank', '_blank');
    }
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Encerrando...';
    }
    try {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            credentials: 'same-origin',
            body: formData,
        });
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        if (!response.ok) {
            const msg = isJson ? ((await response.json().catch(() => ({}))).message) : `Erro ${response.status}.`;
            if (printWindow && !printWindow.closed) {
                try {
                    printWindow.close();
                } catch (eClose) { /* ignore */ }
            }
            if (typeof showToast === 'function') {
                showToast(msg || 'Não foi possível encerrar a OS.', 'error');
            } else {
                alert(msg || 'Não foi possível encerrar a OS.');
            }
            return;
        }
        if (isJson) {
            const data = await response.json().catch(() => ({}));
            if (typeof showToast === 'function') {
                showToast(data.message || 'OS encerrada com sucesso!', 'success');
            }
            if (data.redirect) {
                if (data.comprovante_url) {
                    if (printWindow && !printWindow.closed) {
                        try {
                            printWindow.location.replace(data.comprovante_url);
                        } catch (eNav) {
                            window.open(data.comprovante_url, '_blank');
                        }
                    } else {
                        window.open(data.comprovante_url, '_blank');
                    }
                } else if (printWindow && !printWindow.closed) {
                    try {
                        printWindow.close();
                    } catch (eClose2) { /* ignore */ }
                }
                window.location.href = data.redirect;
                return;
            }
        }
        if (printWindow && !printWindow.closed) {
            try {
                printWindow.close();
            } catch (eClose3) { /* ignore */ }
        }
        form.submit();
    } catch (err) {
        if (printWindow && !printWindow.closed) {
            try {
                printWindow.close();
            } catch (eClose4) { /* ignore */ }
        }
        if (typeof showToast === 'function') {
            showToast('Erro ao encerrar: ' + (err.message || 'tente novamente.'), 'error');
        } else {
            alert('Erro ao encerrar: ' + (err.message || 'tente novamente.'));
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar Encerramento';
        }
    }
});

    // Se a página foi carregada com ?abrir_encerrar=1 (após clicar em Encerrar OS), abrir o modal com dados já atualizados
    if (new URLSearchParams(window.location.search).get('abrir_encerrar') === '1') {
        abrirModalEncerrarComDadosAtualizados();
    }
</script>

<!-- Modal para Vincular Checklist -->
<div id="modalVincularChecklist" class="fixed inset-0 z-[2147483001] hidden" style="z-index:2147483647 !important;">
    <div class="absolute inset-0 bg-gray-900/50" onclick="fecharModalVincularChecklist()"></div>
    <div class="relative mx-auto mt-20 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Vincular Checklist</h3>
            <button type="button" onclick="fecharModalVincularChecklist()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✕</button>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Selecione um modelo de checklist</label>
            <select id="checklist_model_select" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione um modelo...</option>
                @foreach($checklistModels ?? [] as $model)
                    <option value="{{ $model->id }}" data-campos='@json($model->campos)'>{{ $model->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="fecharModalVincularChecklist()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
            <button type="button" onclick="vincularChecklist()" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Vincular</button>
        </div>
    </div>
</div>

<script>
function abrirModalVincularChecklist() {
    document.getElementById('modalVincularChecklist').classList.remove('hidden');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(true);
}

function fecharModalVincularChecklist() {
    document.getElementById('modalVincularChecklist').classList.add('hidden');
    if (typeof window.__osAdjustLayoutLayers === 'function') window.__osAdjustLayoutLayers(false);
    document.getElementById('checklist_model_select').value = '';
}

function vincularChecklist() {
    const select = document.getElementById('checklist_model_select');
    const modelId = select.value;
    if (!modelId) {
        if (typeof showToast === 'function') {
            showToast('Selecione um modelo de checklist.', 'error');
        } else {
            alert('Selecione um modelo de checklist.');
        }
        return;
    }
    
    const option = select.selectedOptions[0];
    const campos = JSON.parse(option.dataset.campos || '[]');
    const modelNome = option.textContent;
    
    // Criar card do checklist
    criarCardChecklist(modelId, modelNome, campos);
    
    fecharModalVincularChecklist();
}

function criarCardChecklist(modelId, modelNome, campos) {
    const container = document.getElementById('checklists_container');
    
    // Verificar se já existe um checklist deste modelo
    const existente = container.querySelector(`[data-checklist-model-id="${modelId}"]`);
    if (existente) {
        if (typeof showToast === 'function') {
            showToast('Este checklist já está vinculado a esta OS.', 'error');
        } else {
            alert('Este checklist já está vinculado a esta OS.');
        }
        return;
    }
    
    // Remover mensagem vazia se existir
    const emptyMsg = container.querySelector('.border-dashed');
    if (emptyMsg) {
        emptyMsg.remove();
    }
    
    let camposHTML = '';
    campos.forEach((campo, index) => {
        const obrigatorio = campo.obrigatorio ? '<span class="text-error-500">*</span>' : '';
        let inputHTML = '';
        
        if (campo.tipo === 'texto') {
            inputHTML = `<input type="text" name="respostas[${index}]" ${campo.obrigatorio ? 'required' : ''} class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">`;
        } else if (campo.tipo === 'numero') {
            inputHTML = `<input type="number" step="0.01" name="respostas[${index}]" ${campo.obrigatorio ? 'required' : ''} class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">`;
        } else if (campo.tipo === 'data') {
            inputHTML = `<input type="date" name="respostas[${index}]" ${campo.obrigatorio ? 'required' : ''} class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">`;
        } else if (campo.tipo === 'checkbox') {
            inputHTML = `<div class="flex items-center">
                <input type="checkbox" name="respostas[${index}]" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sim</label>
            </div>`;
        } else if (campo.tipo === 'selecao') {
            const opcoes = campo.opcoes || [];
            const opcoesHTML = opcoes.map(op => `<option value="${op}">${op}</option>`).join('');
            inputHTML = `<select name="respostas[${index}]" ${campo.obrigatorio ? 'required' : ''} class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Selecione...</option>
                ${opcoesHTML}
            </select>`;
        }
        
        camposHTML += `
            <div class="checklist-field" data-field-index="${index}">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    ${campo.label || 'Campo ' + (index + 1)} ${obrigatorio}
                </label>
                ${inputHTML}
            </div>
        `;
    });
    
    const cardHTML = `
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800" data-checklist-model-id="${modelId}">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">${modelNome}</h3>
                <button type="button" onclick="removerChecklistCard(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            <div class="checklist-form" data-checklist-model-id="${modelId}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="checklist_model_id" value="${modelId}">
                <div class="space-y-4">
                    ${camposHTML}
                </div>
                <div class="mt-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" class="btn-salvar-checklist rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Salvar Checklist
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', cardHTML);
    
    // Adicionar event listener ao botão recém-criado
    // Usar setTimeout para garantir que o DOM esteja totalmente atualizado
    setTimeout(() => {
        const card = container.querySelector(`[data-checklist-model-id="${modelId}"]`);
        if (card) {
            const newBtn = card.querySelector('.btn-salvar-checklist');
            if (newBtn) {
                // O event delegation já deve capturar, mas adicionar listener direto como backup
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    salvarChecklist(newBtn);
                });
            }
        }
    }, 10);
}

function removerChecklistCard(btn) {
    const card = btn.closest('[data-checklist-model-id]');
    if (!card) return;
    
    const form = card.querySelector('.checklist-form');
    const answerId = form?.dataset.checklistAnswerId;
    
    if (answerId) {
        // Se já foi salvo, confirmar remoção
        if (!confirm('Tem certeza que deseja remover este checklist?')) return;
        removerChecklist(answerId, card);
    } else {
        // Se ainda não foi salvo, apenas remover do DOM
        card.remove();
        
        // Mostrar mensagem vazia se não houver mais checklists
        const container = document.getElementById('checklists_container');
        if (container && container.children.length === 0) {
            container.innerHTML = `
                <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum checklist vinculado</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Clique em "Vincular Checklist" para adicionar um modelo de checklist</p>
                </div>
            `;
        }
    }
}

async function salvarChecklist(btn) {
    if (!btn) {
        if (typeof showToast === 'function') {
            showToast('Erro: Botão não encontrado.', 'error');
        } else {
            alert('Erro: Botão não encontrado.');
        }
        return;
    }
    
    // Encontrar o container do checklist - método mais confiável
    let form = null;
    
    // Método 1: Percorrer todos os elementos pais até encontrar .checklist-form
    // Começar do próprio botão e subir na hierarquia
    let current = btn;
    let depth = 0;
    while (current && current !== document.body && depth < 10) {
        // Verificar se o elemento atual tem a classe checklist-form
        if (current.classList && current.classList.contains('checklist-form')) {
            form = current;
            break;
        }
        current = current.parentElement;
        depth++;
    }
    
    // Método 2: Se não encontrou, buscar pelo card primeiro e depois o form dentro dele
    if (!form) {
        const card = btn.closest('[data-checklist-model-id]') || btn.closest('[data-checklist-id]');
        if (card) {
            form = card.querySelector('.checklist-form');
        }
    }
    
    // Método 3: Buscar diretamente no container de checklists
    if (!form) {
        const container = document.getElementById('checklists_container');
        if (container) {
            // Buscar todos os forms e verificar qual contém o botão
            const allForms = container.querySelectorAll('.checklist-form');
            for (let f of allForms) {
                if (f.contains(btn)) {
                    form = f;
                    break;
                }
            }
        }
    }
    
    // Método 4: Última tentativa - buscar pelo parentElement direto
    if (!form && btn.parentElement) {
        let parent = btn.parentElement;
        let attempts = 0;
        while (parent && parent !== document.body && attempts < 5) {
            if (parent.classList && parent.classList.contains('checklist-form')) {
                form = parent;
                break;
            }
            parent = parent.parentElement;
            attempts++;
        }
    }
    
    if (!form) {
        console.error('Debug: Botão encontrado, mas formulário não.');
        console.error('Botão:', btn);
        console.error('Botão classes:', btn.className);
        console.error('Parent:', btn.parentElement);
        console.error('Parent classes:', btn.parentElement?.className);
        console.error('Parent do parent:', btn.parentElement?.parentElement);
        console.error('Parent do parent classes:', btn.parentElement?.parentElement?.className);
        if (typeof showToast === 'function') {
            showToast('Erro: Formulário não encontrado. Por favor, recarregue a página e tente novamente.', 'error');
        } else {
            alert('Erro: Formulário não encontrado. Por favor, recarregue a página e tente novamente.');
        }
        return;
    }
    
    // Obter o modelId do formulário
    let modelId = form.dataset.checklistModelId;
    
    // Se não encontrou no dataset, tentar pelo atributo direto
    if (!modelId) {
        modelId = form.getAttribute('data-checklist-model-id');
    }
    
    // Se ainda não encontrou, tentar pelo input hidden
    if (!modelId) {
        const hiddenInput = form.querySelector('input[name="checklist_model_id"]');
        if (hiddenInput) {
            modelId = hiddenInput.value;
        }
    }
    
    if (!modelId) {
        if (typeof showToast === 'function') {
            showToast('Erro: ID do modelo de checklist não encontrado.', 'error');
        } else {
            alert('Erro: ID do modelo de checklist não encontrado.');
        }
        return;
    }
    
    // Validar campos obrigatórios
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    requiredFields.forEach(field => {
        if (!field.value || (field.type === 'checkbox' && !field.checked)) {
            isValid = false;
            field.classList.add('border-error-500');
            field.focus();
        } else {
            field.classList.remove('border-error-500');
        }
    });
    
    if (!isValid) {
        if (typeof showToast === 'function') {
            showToast('Por favor, preencha todos os campos obrigatórios.', 'error');
        } else {
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
        return;
    }
    
    // Desabilitar botão durante o salvamento
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    btn.classList.add('opacity-50', 'cursor-not-allowed');
    
    // Coletar respostas diretamente dos inputs pelo nome
    const respostas = {};
    const inputs = form.querySelectorAll('input[name^="respostas["], select[name^="respostas["]');
    
    inputs.forEach(input => {
        const nameMatch = input.name.match(/respostas\[(\d+)\]/);
        if (nameMatch) {
            const index = parseInt(nameMatch[1]);
            
            if (input.type === 'checkbox') {
                respostas[index] = input.checked ? '1' : '0';
            } else {
                respostas[index] = input.value || '';
            }
        }
    });
    
    const payload = {
        checklist_model_id: modelId,
        respostas: respostas,
        observacoes: form.querySelector('textarea[name="observacoes"]')?.value || '',
    };
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                         form.querySelector('input[name="_token"]')?.value ||
                         document.querySelector('input[name="_token"]')?.value;
        
        if (!csrfToken) {
            throw new Error('Token CSRF não encontrado');
        }
        
        const osId = {{ $ordem->id }};
        
        const response = await fetch(`/ordens-servico/${osId}/checklist`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erro ao salvar checklist.');
        }
        
        // Atualizar o card com o ID da resposta
        if (data.checklist_id) {
            form.dataset.checklistAnswerId = data.checklist_id;
            const card = form.closest('[data-checklist-model-id]');
            if (card) {
                card.setAttribute('data-checklist-id', data.checklist_id);
                // Atualizar o botão de remover para usar o ID correto
                const removeBtn = card.querySelector('button[onclick*="removerChecklist"]');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', `if(confirm('Tem certeza que deseja remover este checklist?')) removerChecklist(${data.checklist_id}, this.closest('[data-checklist-id]'))`);
                }
            }
        }
        
        // Mostrar mensagem de sucesso
        if (typeof showToast === 'function') {
            showToast('Checklist salvo com sucesso!', 'success');
        } else {
            alert('Checklist salvo com sucesso!');
        }
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('Erro ao salvar checklist: ' + error.message, 'error');
        } else {
            alert('Erro ao salvar checklist: ' + error.message);
        }
    } finally {
        // Reabilitar botão
        btn.disabled = false;
        btn.textContent = originalText;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

async function removerChecklist(checklistId, cardElement) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
        const osId = {{ $ordem->id }};
        
        const response = await fetch(`/ordens-servico/${osId}/checklist/${checklistId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Erro ao remover checklist.', 'error');
            } else {
                alert(data.message || 'Erro ao remover checklist.');
            }
            return;
        }
        
        // Remover card do DOM
        if (cardElement) {
            cardElement.remove();
        } else {
            const card = document.querySelector(`[data-checklist-id="${checklistId}"]`);
            if (card) card.remove();
        }
        
        // Mostrar mensagem vazia se não houver mais checklists
        const container = document.getElementById('checklists_container');
        if (container && container.children.length === 0) {
            container.innerHTML = `
                <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nenhum checklist vinculado</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Clique em "Vincular Checklist" para adicionar um modelo de checklist</p>
                </div>
            `;
        }
        
        if (typeof showToast === 'function') {
            showToast('Checklist removido com sucesso!', 'success');
        } else {
            alert('Checklist removido com sucesso!');
        }
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('Erro ao remover checklist: ' + error.message, 'error');
        } else {
            alert('Erro ao remover checklist: ' + error.message);
        }
    }
}

// Event delegation para botões de checklist criados dinamicamente
document.addEventListener('DOMContentLoaded', function() {
    // Delegar eventos de clique para botões de salvar checklist
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-salvar-checklist');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            salvarChecklist(btn);
        }
    });
    
    // Também adicionar listeners diretos aos botões existentes na página
    document.querySelectorAll('.btn-salvar-checklist').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            salvarChecklist(btn);
        });
    });

});
@include('ordens_servico._scripts_notificar_cliente_modal', ['ordem' => $ordem])
</script>
@endsection
