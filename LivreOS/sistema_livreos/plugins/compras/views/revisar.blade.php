@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
        <p class="text-gray-600 dark:text-gray-400">Conferência fiscal e financeira antes de gerar títulos.</p>
    </div>
    <a href="{{ route('plugin.compras.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">Voltar</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
@endif
@if(session('warning'))
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ session('warning') }}</div>
@endif

@if($imp->tipo_fonte === 'pdf')
<div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100">
    <strong>Modo PDF.</strong> A confirmação automática (contas a pagar e estoque) exige XML da NF-e. Use esta tela apenas como rascunho ou complemente no financeiro.
</div>
@endif

@if(!empty($imp->parse_warnings))
<div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-100">
    <strong>Avisos de parse:</strong>
    <ul class="mt-2 list-disc pl-5">
        @foreach(array_filter($imp->parse_warnings) as $w)
        <li>{{ $w }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($imp->sefaz_resultado)
<div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900">
    <strong>SEFAZ / chave:</strong> {{ $imp->sefaz_resultado['mensagem'] ?? '—' }}
</div>
@endif

<div class="mb-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Documento</h3>
        <dl class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Número / Série:</dt> <dd class="inline">{{ $imp->numero }} / {{ $imp->serie }}</dd></div>
            <div><dt class="inline font-medium">Emissão:</dt> <dd class="inline">{{ $imp->data_emissao?->format('d/m/Y') ?? '—' }}</dd></div>
            <div><dt class="inline font-medium">Chave:</dt> <dd class="inline font-mono text-xs break-all">{{ $imp->chave_acesso ?? '—' }}</dd></div>
            <div><dt class="inline font-medium">Total NF (vNF):</dt> <dd class="inline">R$ {{ number_format((float)($imp->totais['vNF'] ?? 0), 2, ',', '.') }}</dd></div>
        </dl>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Emitente (fornecedor)</h3>
        <dl class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">CNPJ:</dt> <dd class="inline">{{ $imp->fornecedor_cnpj }}</dd></div>
            <div><dt class="inline font-medium">Razão:</dt> <dd class="inline">{{ $imp->fornecedor_nome }}</dd></div>
            <div><dt class="inline font-medium">Cadastro ERP:</dt> <dd class="inline">{{ $imp->fornecedor ? ($imp->fornecedor->nome ?? 'ID '.$imp->fornecedor_id) : 'Não vinculado' }}</dd></div>
        </dl>
    </div>
</div>

@if(count($duplicatas))
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Parcelas (duplicatas)</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead><tr class="border-b border-gray-200 text-left dark:border-gray-700">
                <th class="py-2 pr-4">Nº</th>
                <th class="py-2 pr-4">Vencimento</th>
                <th class="py-2">Valor</th>
            </tr></thead>
            <tbody>
                @foreach($duplicatas as $d)
                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                    <td class="py-2 pr-4">{{ $d['nDup'] ?? '—' }}</td>
                    <td class="py-2 pr-4">{{ $d['dVenc'] ?? '—' }}</td>
                    <td class="py-2">R$ {{ number_format((float)($d['vDup'] ?? 0), 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($imp->itens->isEmpty() && $imp->tipo_fonte === 'xml')
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
    <strong>Nenhum item de produto foi lido do XML.</strong> Confira se o arquivo é NF-e autorizada (<code class="rounded bg-amber-100 px-1 dark:bg-amber-950">nfeProc</code> ou <code class="rounded bg-amber-100 px-1 dark:bg-amber-950">NFe</code> com grupo <code class="rounded bg-amber-100 px-1 dark:bg-amber-950">det</code>). XML resumido ou de outro documento não gera linhas de importação.
</div>
@endif

@if($imp->itens->isNotEmpty())
<form action="{{ route('plugin.compras.mapear', $imp) }}" method="post" class="mb-6">
    @csrf
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Itens e de-para (produto estoque)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Cód. forn.</th>
                        <th class="py-2 pr-2">Descrição</th>
                        <th class="py-2 pr-2">NCM/CFOP</th>
                        <th class="py-2 pr-2">Qtd</th>
                        <th class="py-2 pr-2">V. unit.</th>
                        <th class="py-2 pr-2">Produto ERP</th>
                        <th class="py-2">Atualizar custo/qtd</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($imp->itens as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <td class="py-2 pr-2">{{ $item->indice }}</td>
                        <td class="py-2 pr-2 font-mono text-xs">{{ $item->c_prod }}</td>
                        <td class="py-2 pr-2 max-w-xs truncate" title="{{ $item->x_prod }}">{{ $item->x_prod }}</td>
                        <td class="py-2 pr-2 text-xs">{{ $item->ncm }} / {{ $item->cfop }}</td>
                        <td class="py-2 pr-2">{{ number_format((float)$item->q_com, 4, ',', '.') }}</td>
                        <td class="py-2 pr-2">R$ {{ number_format((float)$item->v_un_com, 4, ',', '.') }}</td>
                        <td class="py-2 pr-2">
                            <select name="item[{{ $item->id }}][produto_id]" class="w-full max-w-xs rounded-lg border border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-900">
                                <option value="">— Selecionar —</option>
                                @foreach($produtos as $p)
                                <option value="{{ $p->id }}" @selected($item->produto_id == $p->id)>{{ $p->nome }} @if($p->codigo_sku)({{ $p->codigo_sku }})@endif</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-2">
                            <label class="inline-flex items-center gap-1 text-xs">
                                <input type="hidden" name="item[{{ $item->id }}][atualizar_estoque]" value="0">
                                <input type="checkbox" name="item[{{ $item->id }}][atualizar_estoque]" value="1" @checked($item->atualizar_estoque)>
                                Sim
                            </label>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($imp->status !== 'confirmada')
        <div class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-700">
            <label class="flex max-w-xl items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="criar_produtos_novos" value="0">
                <input type="checkbox" name="criar_produtos_novos" value="1" checked class="mt-0.5 rounded border-gray-300 dark:border-gray-600">
                <span><strong>Criar produtos no ERP</strong> automaticamente para linhas em “Selecionar” (nome, NCM, EAN e preço da NF; SKU interno <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-900">NF-IMP…</code>). Se o EAN já existir, vincula ao cadastro existente.</span>
            </label>
            <div>
                <button type="submit" class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-600">Salvar de-para</button>
            </div>
        </div>
        @endif
    </div>
</form>
@endif

@if($imp->status !== 'confirmada' && $imp->tipo_fonte === 'xml')
<form action="{{ route('plugin.compras.confirmar', $imp) }}" method="post" class="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-900 dark:bg-green-900/20">
    @csrf
    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Confirmar entrada e gerar contas a pagar</h3>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-medium">Forma de pagamento *</label>
            <select name="forma_pagamento_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                @foreach($formas as $f)
                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Conta bancária (saída)</label>
            <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                <option value="">—</option>
                @foreach($contasBancarias as $cb)
                <option value="{{ $cb->id }}">{{ $cb->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2 lg:col-span-1">
            <label class="mb-1 block text-sm font-medium">Plano de contas (despesa) *</label>
            <select name="plano_conta_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                @foreach($planos as $pl)
                <option value="{{ $pl->id }}">{{ $pl->codigo ? $pl->codigo.' — ' : '' }}{{ $pl->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @if(!$imp->fornecedor_id)
    <div class="mt-4">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="criar_fornecedor" value="0">
            <input type="checkbox" name="criar_fornecedor" value="1" checked>
            Cadastrar fornecedor (PJ) automaticamente se não existir
        </label>
    </div>
    @endif
    <p class="mt-4 text-xs text-gray-600 dark:text-gray-400">Serão criados {{ count($duplicatas) }} título(s) em Contas a Pagar; produtos com de-para terão quantidade e custo médio atualizados.@if(($imp->tipo_fonte ?? '') === 'xml') Importação XML: se o fornecedor já estiver vinculado pelo CNPJ, o sistema completa endereço, contato e dados fiscais ainda vazios com a NF-e e com a consulta pública de CNPJ (sem sobrescrever o que já estiver preenchido).@endif</p>
    <button type="submit" class="mt-4 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">Confirmar entrada</button>
</form>
@elseif($imp->status === 'confirmada')
<div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
    Importação confirmada em {{ $imp->confirmed_at?->format('d/m/Y H:i') }}. Consulte o financeiro — Contas a Pagar.
</div>
@endif
@endsection
