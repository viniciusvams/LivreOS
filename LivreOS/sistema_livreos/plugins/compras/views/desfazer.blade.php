@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        <p class="text-gray-600 dark:text-gray-400">Remove títulos em contas a pagar gerados por esta NF (apenas em aberto ou vencido, sem pagamento), reverte estoque/custo conforme opções e apaga o registro da importação.</p>
    </div>
    <a href="{{ route('plugin.compras.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">Voltar</a>
</div>

@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
@endif

<div class="max-w-2xl rounded-lg border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
    <strong>NF</strong> {{ $imp->numero }}/{{ $imp->serie }}
    @if($imp->chave_acesso)<span class="mt-1 block font-mono text-xs break-all">{{ $imp->chave_acesso }}</span>@endif
    <p class="mt-3">Importações antigas (antes da gravação do snapshot) podem não reverter o estoque com precisão — desmarque “Reverter estoque” e ajuste manualmente se necessário.</p>
</div>

<form action="{{ route('plugin.compras.desfazer', $imp) }}" method="post" class="mt-8 max-w-2xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800">
    @csrf
    <div class="space-y-4">
        <label class="flex items-start gap-3 text-sm text-gray-800 dark:text-gray-200">
            <input type="hidden" name="reverter_estoque" value="0">
            <input type="checkbox" name="reverter_estoque" value="1" checked class="mt-1 rounded border-gray-300 dark:border-gray-600">
            <span><strong>Reverter estoque e custo médio</strong> para os valores anteriores à confirmação (itens com “atualizar estoque” na NF).</span>
        </label>
        <label class="flex items-start gap-3 text-sm text-gray-800 dark:text-gray-200">
            <input type="hidden" name="excluir_produtos_plugin" value="0">
            <input type="checkbox" name="excluir_produtos_plugin" value="1" checked class="mt-1 rounded border-gray-300 dark:border-gray-600">
            <span><strong>Excluir produtos criados pelo plugin</strong> nesta importação (SKU <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-900">NF-IMP{{ $imp->id }}-…</code>). Produtos que já existiam no cadastro não são removidos.</span>
        </label>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Digite <strong>DESFAZER</strong> para confirmar</label>
        <input type="text" name="confirmar_desfazer" required autocomplete="off"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
            placeholder="DESFAZER">
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">As parcelas em contas a pagar vinculadas a esta importação serão <strong>sempre</strong> excluídas (somente se estiverem em aberto ou vencidas e sem valor pago).</p>
    <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">Desfazer importação</button>
</form>
@endsection
