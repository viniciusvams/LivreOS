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
@php $colorClass = \AreaCliente\AreaClienteHelper::statusColor($ordem->status); @endphp
<div class="mx-auto max-w-2xl pb-6 lg:max-w-5xl">
    {{-- Cabeçalho estilo app --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-gray-800 to-gray-900 p-5 text-white shadow-lg dark:from-gray-900 dark:to-black sm:mb-8 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-medium opacity-80">Ordem de Serviço</p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ format_os_display($ordem) }}</h1>
                <p class="mt-2 truncate text-sm opacity-90">{{ $ordem->equipamento_nome ?: 'Equipamento não informado' }}</p>
            </div>
            <span class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold {{ $colorClass }}">{{ format_status_os_display($ordem->status) }}</span>
        </div>
    </div>

    @if(session('error'))
    <div class="mb-6 rounded-2xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-800 dark:border-error-800 dark:bg-error-500/10 dark:text-error-300">
        {{ session('error') }}
    </div>
    @endif

    @if($ordem->status === 'Aguardando aprovação')
    <div class="mb-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-5 shadow-sm dark:border-amber-600 dark:bg-amber-500/10">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-amber-900 dark:text-amber-200">Aprovação necessária</h2>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90">Esta ordem aguarda a <strong>sua assinatura digital</strong> para aprovar o orçamento/serviço. Usamos o mesmo sistema de assinatura do escritório: você será redirecionado para uma página segura, lerá os termos e assinará na tela. Depois o status passará para <strong>Aprovado pelo cliente</strong>.</p>
            </div>
            <form method="POST" action="{{ route('plugin.area-cliente.os.assinatura-aprovacao', $ordem) }}" class="shrink-0">
                @csrf
                <button type="submit" class="flex w-full min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-amber-700 active:scale-[0.98] dark:bg-amber-500 dark:hover:bg-amber-600 sm:w-auto">
                    <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Assinar e aprovar
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Botões de ação grandes --}}
    <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:gap-4">
        <a href="{{ route('plugin.area-cliente.os.pdf', $ordem) }}" class="flex min-h-[56px] flex-1 items-center justify-center gap-3 rounded-2xl bg-brand-500 px-5 py-4 text-base font-semibold text-white shadow-lg shadow-brand-500/25 transition active:scale-[0.98] hover:bg-brand-600 dark:bg-brand-600 dark:shadow-brand-600/20">
            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5M17 14v2m0 2h2m-2 0v-2m0-2h-2"/></svg>
            Baixar PDF
        </a>
        <a href="{{ route('plugin.area-cliente.index') }}" class="flex min-h-[56px] flex-1 items-center justify-center gap-3 rounded-2xl border-2 border-gray-200 bg-white px-5 py-4 text-base font-semibold text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Voltar às ordens
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-500/20">
                <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Resumo</h2>
        </div>
        <dl class="space-y-2 text-sm">
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $colorClass }}">{{ format_status_os_display($ordem->status) }}</span>
                </dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">{{ $ordem->tipo_os ?: '-' }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Equipamento</dt>
                <dd class="text-gray-800 dark:text-white/90 break-words sm:text-right">{{ $ordem->equipamento_nome ?: '-' }}</dd>
            </div>
            @if($ordem->equipamento_marca || $ordem->equipamento_modelo)
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Marca/Modelo</dt>
                <dd class="text-gray-800 dark:text-white/90 break-words sm:text-right">{{ trim(($ordem->equipamento_marca ?? '') . ' / ' . ($ordem->equipamento_modelo ?? ''), ' /') ?: '-' }}</dd>
            </div>
            @endif
            @if($ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio)
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Série/Patrimônio</dt>
                <dd class="text-gray-800 dark:text-white/90 break-words sm:text-right">{{ trim(($ordem->equipamento_numero_serie ?? '') . ' / ' . ($ordem->equipamento_numero_patrimonio ?? ''), ' /') ?: '-' }}</dd>
            </div>
            @endif
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Data de abertura</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">{{ optional($ordem->data_abertura)->format('d/m/Y H:i') ?: '-' }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Previsão de conclusão</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">{{ optional($ordem->prevista_conclusao)->format('d/m/Y H:i') ?: '-' }}</dd>
            </div>
            @if(in_array($ordem->status, ['Encerrado', 'Encerrada']) && $ordem->real_conclusao)
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Data de conclusão</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">{{ optional($ordem->real_conclusao)->format('d/m/Y H:i') }}</dd>
            </div>
            @endif
            @if($ordem->garantia_dias || $ordem->garantia_tipo)
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Garantia</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">
                    @if($ordem->garantia_dias)
                        {{ $ordem->garantia_tipo ? $ordem->garantia_tipo . ' ' : '' }}{{ $ordem->garantia_dias }} dias
                    @else
                        {{ $ordem->garantia_tipo ?? '-' }}
                    @endif
                </dd>
            </div>
            @endif
            @if($ordem->relato_cliente)
            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Relato</dt>
                <dd class="mt-1 text-gray-800 dark:text-white/90">{!! \AreaCliente\AreaClienteHelper::sanitizeHtml($ordem->relato_cliente) !!}</dd>
            </div>
            @endif
            @if($ordem->diagnostico_tecnico)
            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Diagnóstico técnico</dt>
                <dd class="mt-1 text-gray-800 dark:text-white/90">{!! \AreaCliente\AreaClienteHelper::sanitizeHtml($ordem->diagnostico_tecnico) !!}</dd>
            </div>
            @endif
            @if($ordem->servicos_realizados)
            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Serviços realizados</dt>
                <dd class="mt-1 text-gray-800 dark:text-white/90">{!! \AreaCliente\AreaClienteHelper::sanitizeHtml($ordem->servicos_realizados) !!}</dd>
            </div>
            @endif
            @if($ordem->observacoes_cliente)
            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <dt class="text-gray-500 dark:text-gray-400">Observações</dt>
                <dd class="mt-1 text-gray-800 dark:text-white/90">{!! \AreaCliente\AreaClienteHelper::sanitizeHtml($ordem->observacoes_cliente) !!}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-success-100 dark:bg-success-500/20">
                <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Totais</h2>
        </div>
        <dl class="space-y-2 text-sm">
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Produtos</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">R$ {{ number_format($ordem->total_produtos ?? 0, 2, ',', '.') }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Serviços</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">R$ {{ number_format($ordem->total_servicos ?? 0, 2, ',', '.') }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Descontos</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">R$ {{ number_format($ordem->total_descontos ?? 0, 2, ',', '.') }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Acréscimos</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">R$ {{ number_format($ordem->total_acrescimos ?? 0, 2, ',', '.') }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 border-t border-gray-200 pt-4 font-semibold dark:border-gray-700 sm:flex-row sm:justify-between">
                <dt class="text-gray-700 dark:text-gray-200">Total geral</dt>
                <dd class="text-gray-800 dark:text-white/90 sm:text-right">R$ {{ number_format($ordem->total_geral ?? 0, 2, ',', '.') }}</dd>
            </div>
        </dl>
    </div>

    @if(($ordem->anexos ?? collect())->isNotEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6 lg:col-span-2">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-500/20">
                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Documentos e Anexos</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($ordem->anexos as $anexo)
            <a href="{{ route('plugin.area-cliente.os.anexo', [$ordem, $anexo]) }}" target="_blank" class="flex min-h-[52px] min-w-0 flex-1 items-center gap-3 rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-base font-medium text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200 sm:flex-initial">
                <svg class="h-5 w-5 shrink-0 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5M17 14v2m0 2h2m-2 0v-2m0-2h-2"/></svg>
                <span class="truncate">{{ $anexo->nome_arquivo ?? 'Documento' }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-500/20">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Produtos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2 sm:px-4">Produto</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Qtd</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Valor</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($ordem->produtos ?? [] as $item)
                    <tr>
                        <td class="min-w-0 px-3 py-2 sm:px-4"><span class="block truncate max-w-[180px] sm:max-w-none">{{ $item->produto?->nome ?: $item->descricao ?? '-' }}</span></td>
                        <td class="shrink-0 px-2 py-2 sm:px-4">{{ format_quantity($item->quantidade ?? 0) }}</td>
                        <td class="shrink-0 px-2 py-2 text-xs sm:px-4 sm:text-sm">R$ {{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="shrink-0 px-2 py-2 text-xs sm:px-4 sm:text-sm">R$ {{ number_format(($item->quantidade ?? 0) * ($item->valor_unitario ?? 0), 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Nenhum produto</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-500/20">
                <svg class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-white/90">Serviços</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2 sm:px-4">Serviço</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Qtd</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Valor</th>
                        <th class="shrink-0 px-2 py-2 sm:px-4">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($ordem->servicos ?? [] as $item)
                    <tr>
                        <td class="min-w-0 px-3 py-2 sm:px-4"><span class="block truncate max-w-[180px] sm:max-w-none">{{ $item->servico?->nome ?: $item->descricao ?? '-' }}</span></td>
                        <td class="shrink-0 px-2 py-2 sm:px-4">{{ format_quantity($item->quantidade ?? 0) }}</td>
                        <td class="shrink-0 px-2 py-2 text-xs sm:px-4 sm:text-sm">R$ {{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="shrink-0 px-2 py-2 text-xs sm:px-4 sm:text-sm">R$ {{ number_format(($item->quantidade ?? 0) * ($item->valor_unitario ?? 0), 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Nenhum serviço</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(function_exists('do_action'))
    <div class="lg:col-span-2">
        <?php do_action('area_cliente.os.show.extra', $ordem); ?>
    </div>
    @endif
</div>
</div>
@endsection
