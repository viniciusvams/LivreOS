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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ format_os_display($ordem) }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $ordem->cliente?->nome }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('ordens-servico.print', $ordem) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir OS
        </a>
        @if(in_array($ordem->status, ['Encerrado', 'Encerrada'], true))
        <a href="{{ route('ordens-servico.comprovante', $ordem) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true" fill="currentColor"><path d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/></svg>
            Imprimir comprovante
        </a>
        @endif
        <button type="button" onclick="abrirModalNotificarCliente({{ $ordem->id }})" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Notificar cliente
        </button>
        <a href="{{ route('ordens-servico.edit', $ordem) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('ordens-servico.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Resumo</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ format_status_os_display($ordem->status) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $ordem->tipo_os ?: '-' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Equipamento</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $ordem->equipamento_nome ?: '-' }}</dd>
            </div>
            @if($ordem->equipamento_unlock_type)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-unlock-pattern-view 
                        :unlockType="$ordem->equipamento_unlock_type" 
                        :unlockCode="$ordem->equipamento_unlock_code" 
                        :readonly="true" 
                    />
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Prioridade</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $ordem->prioridade ?: '-' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Abertura</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ optional($ordem->data_abertura)->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Prevista conclusão</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ optional($ordem->prevista_conclusao)->format('d/m/Y H:i') ?: '-' }}</dd>
            </div>
        </dl>
        @if(!in_array($ordem->status, ['Encerrado', 'Encerrada']))
        <form method="POST" action="{{ route('ordens-servico.status', $ordem) }}" class="mt-4 flex gap-2">
            @csrf
            @method('PATCH')
            <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @foreach(['Aberta','Em análise/orçamento','Aguardando aprovação','Aguardando peças','Em execução','Em teste','Concluída','Cancelada'] as $status)
                    <option value="{{ $status }}" {{ $ordem->status == $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        </form>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Totais</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Produtos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_produtos, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Serviços</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_servicos, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Descontos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_descontos, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Acréscimos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_acrescimos, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Impostos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_impostos, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between font-semibold">
                <dt class="text-gray-700 dark:text-gray-200">Total geral</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Produtos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Produto</th>
                        <th class="px-3 py-2">Qtd</th>
                        <th class="px-3 py-2">Valor</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($ordem->produtos as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->produto?->nome ?: $item->descricao }}</td>
                            <td class="px-3 py-2">{{ format_quantity($item->quantidade) }}</td>
                            <td class="px-3 py-2">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                            <td class="px-3 py-2">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">Sem produtos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Serviço</th>
                        <th class="px-3 py-2">Técnico</th>
                        <th class="px-3 py-2">Tempo</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($ordem->servicos as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->servico?->nome ?: $item->descricao }}</td>
                            <td class="px-3 py-2">{{ $item->tecnico?->name ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $item->tempo_real_min ?: $item->tempo_estimado_min ?: '-' }} min</td>
                            <td class="px-3 py-2">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">Sem serviços</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Anexos</h2>
        <div class="space-y-2">
            @forelse($ordem->anexos as $anexo)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $anexo->nome_arquivo }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $anexo->tipo }} {{ $anexo->tags ? '- ' . $anexo->tags : '' }}</p>
                    </div>
                    <a href="{{ route('ordens-servico.anexos.file', $anexo) }}" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir</a>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum anexo</p>
            @endforelse
        </div>
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('ordens_servico.show.extra', $ordem); ?>
@endif

@include('ordens_servico._modal_notificar_cliente', ['ordem' => $ordem])

@if($ordem->equipamento_unlock_type === 'pattern' && $ordem->equipamento_unlock_code)
@push('scripts')
<script>
@include('ordens_servico._unlock_pattern_script')
</script>
@endpush
@endif

@push('scripts')
@include('partials.jodit-assets')
<script>
@include('ordens_servico._scripts_notificar_cliente_modal', ['ordem' => $ordem])
</script>
@if($errors->has('email'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof abrirModalNotificarCliente === 'function') {
        abrirModalNotificarCliente({{ $ordem->id }});
    }
});
</script>
@endif
@endpush
@endsection
