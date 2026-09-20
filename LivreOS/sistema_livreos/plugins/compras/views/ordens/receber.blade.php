@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Receber ordem {{ $ordem->numero_ordem }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Ao confirmar, a quantidade recebida é lançada no estoque seguindo o fluxo do ERP.</p>
    </div>
    <a href="{{ route('plugin.compras.ordens.show', $ordem) }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Voltar</a>
</div>

@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-300">{{ session('error') }}</div>
@endif

<form action="{{ route('plugin.compras.ordens.registrar-recebimento', $ordem) }}" method="POST" class="space-y-6">
    @csrf

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data do recebimento</label>
            <input type="date" name="data_recebimento" value="{{ old('data_recebimento', now()->toDateString()) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do documento</label>
            <input type="text" name="numero_documento" value="{{ old('numero_documento') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Depósito de entrada</label>
            <select name="deposito_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <option value="">Selecione o depósito</option>
                @foreach($depositos as $deposito)
                    <option value="{{ $deposito->id }}" @selected((string) old('deposito_id') === (string) $deposito->id)>{{ $deposito->nome }}{{ $deposito->localizacao ? ' - ' . $deposito->localizacao : '' }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
        <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('observacoes') }}</textarea>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Itens recebidos</h2>
        <div class="space-y-4">
            @foreach($ordem->itens as $item)
                @php $restante = (float) $item->quantidade - (float) $item->quantidade_recebida; @endphp
                <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <strong class="text-sm text-gray-800 dark:text-gray-200">{{ $item->produto->nome ?? $item->descricao }}</strong>
                        <span class="text-xs text-gray-500">Restante: {{ number_format($restante, 4, ',', '.') }}</span>
                    </div>
                    <div class="grid gap-3 md:grid-cols-5">
                        <input type="hidden" name="itens[{{ $item->id }}][ordem_item_id]" value="{{ $item->id }}">
                        <input type="number" name="itens[{{ $item->id }}][quantidade_recebida]" step="0.0001" min="0" max="{{ $restante }}" value="{{ max($restante, 0) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
                        <input type="number" name="itens[{{ $item->id }}][preco_unitario]" step="0.01" min="0" value="{{ number_format((float) $item->preco_unitario, 2, '.', '') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <input type="text" name="itens[{{ $item->id }}][lote]" placeholder="Lote" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <input type="date" name="itens[{{ $item->id }}][validade]" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <input type="text" name="itens[{{ $item->id }}][observacoes]" placeholder="Obs." class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Confirmar recebimento</button>
    </div>
</form>
@endsection
