@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Ordem de Compra {{ $ordem->numero_ordem }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Status: <strong>{{ $ordem->status }}</strong></p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('plugin.compras.ordens.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Voltar</a>
        @if(in_array($ordem->status, ['rascunho', 'pendente_aprovacao'], true))
            <form action="{{ route('plugin.compras.ordens.aprovar', $ordem) }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Aprovar</button>
            </form>
        @endif
        @if(in_array($ordem->status, ['rascunho', 'pendente_aprovacao'], true))
            <form action="{{ route('plugin.compras.ordens.rejeitar', $ordem) }}" method="POST">
                @csrf
                <input type="text" name="motivo" placeholder="Motivo da rejeição" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Rejeitar</button>
            </form>
        @endif
        @if(in_array($ordem->status, ['aprovada'], true))
            <a href="{{ route('plugin.compras.ordens.receber', $ordem) }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Receber</a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-emerald-100 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-300">{{ session('error') }}</div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Dados da ordem</h2>
        <dl class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex justify-between"><dt>Fornecedor</dt><dd>{{ $ordem->fornecedor->nome ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt>Data emissão</dt><dd>{{ $ordem->data_emissao?->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt>Previsão</dt><dd>{{ $ordem->data_previsao?->format('d/m/Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt>Total</dt><dd>R$ {{ number_format((float) $ordem->valor_total, 2, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt>Observações</dt><dd>{{ $ordem->observacoes ?: '—' }}</dd></div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Itens</h2>
        <div class="space-y-3">
            @foreach($ordem->itens as $item)
                <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <div class="flex justify-between gap-3">
                        <strong>{{ $item->produto->nome ?? $item->descricao }}</strong>
                        <span class="text-xs text-gray-500">{{ $item->status_item }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <div>Qtd: {{ number_format((float) $item->quantidade, 4, ',', '.') }}</div>
                        <div>Preço unit.: R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</div>
                        <div>Recebido: {{ number_format((float) $item->quantidade_recebida, 4, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
