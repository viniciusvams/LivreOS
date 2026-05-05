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
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Fila de E-mails e Notificações</h1>
    <p class="text-gray-600 dark:text-gray-400">Visualize os envios de e-mail e WhatsApp registrados, com preview e opção de exclusão.</p>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label>
            <select name="tipo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="email" {{ request('tipo') === 'email' ? 'selected' : '' }}>E-mail</option>
                <option value="whatsapp" {{ request('tipo') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendente</option>
                <option value="enviado" {{ request('status') === 'enviado' ? 'selected' : '' }}>Enviado</option>
                <option value="falha" {{ request('status') === 'falha' ? 'selected' : '' }}>Falha</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('configuracoes-sistema.fila-notificacoes.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">OS</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Destinatário</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Preview</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($envios as $envio)
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $envio->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-xs font-medium {{ $envio->tipo === 'email' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' }}">
                            {{ $envio->tipo === 'email' ? 'E-mail' : 'WhatsApp' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('ordens-servico.edit', $envio->ordemServico) }}" class="text-brand-500 hover:underline">
                            {{ format_os_display($envio->ordemServico) }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $envio->destinatario }}</td>
                    <td class="px-4 py-3">
                        @if($envio->status === 'pending')
                            <span class="rounded px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Pendente</span>
                        @elseif($envio->status === 'enviado')
                            <span class="rounded px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">Enviado</span>
                            @if($envio->enviado_em)
                                <span class="text-xs text-gray-500">({{ $envio->enviado_em->format('d/m H:i') }})</span>
                            @endif
                        @else
                            <span class="rounded px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Falha</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <button type="button" onclick="abrirPreview({{ $envio->id }})" class="text-brand-500 hover:underline text-xs">
                            Ver preview
                        </button>
                        <div id="preview-{{ $envio->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onclick="fecharPreview({{ $envio->id }})">
                            <div class="max-h-[80vh] w-full max-w-2xl overflow-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
                                <div class="mb-4 flex justify-between">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Preview - {{ $envio->tipo === 'email' ? 'E-mail' : 'WhatsApp' }}</h3>
                                    <button type="button" onclick="fecharPreview({{ $envio->id }})" class="text-gray-500 hover:text-gray-700">✕</button>
                                </div>
                                @if($envio->tipo === 'email')
                                    <p class="mb-2 text-sm text-gray-600 dark:text-gray-400"><strong>Assunto:</strong> {{ $envio->assunto ?? '-' }}</p>
                                    <p class="mb-2 text-sm text-gray-600 dark:text-gray-400"><strong>Para:</strong> {{ $envio->destinatario }}</p>
                                @endif
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 whitespace-pre-wrap">{{ $envio->conteudo ?? '-' }}</div>
                                @if($envio->erro)
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400"><strong>Erro:</strong> {{ $envio->erro }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('configuracoes-sistema.fila-notificacoes.destroy', $envio) }}" class="inline" onsubmit="return confirm('Excluir este registro?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:underline text-xs">Excluir</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum envio registrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $envios->links() }}
    </div>
</div>

<script>
function abrirPreview(id) {
    document.getElementById('preview-' + id).classList.remove('hidden');
    document.getElementById('preview-' + id).classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function fecharPreview(id) {
    document.getElementById('preview-' + id).classList.add('hidden');
    document.getElementById('preview-' + id).classList.remove('flex');
    document.body.style.overflow = '';
}
</script>
@endsection
