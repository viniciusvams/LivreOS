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
    $statusColors = [
        'rascunho' => 'gray', 'enviada' => 'sky', 'aprovada' => 'green',
        'expirada' => 'amber', 'convertida' => 'violet', 'cancelada' => 'red',
    ];
    $cor = $statusColors[$proposta->status] ?? 'gray';
@endphp
<div class="mb-6 flex flex-wrap items-start justify-between gap-3 overflow-visible">
    <div class="min-w-0 flex-1 flex items-center gap-3">
        <a href="{{ route('propostas-comerciais.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Proposta — {{ $proposta->referenciaGrupo() }}</h1>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                    @if($cor==='gray') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                    @elseif($cor==='sky') bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300
                    @elseif($cor==='green') bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300
                    @elseif($cor==='amber') bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300
                    @elseif($cor==='violet') bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300
                    @else bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300
                    @endif">{{ $proposta->status_label }}</span>
                @unless($proposta->numero_sequencial)
                <span class="text-sm text-gray-500 dark:text-gray-400">ID interno #{{ $proposta->id }}</span>
                @endunless
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $proposta->cliente?->nome ?? '—' }} · Emissão {{ $proposta->data_emissao?->format('d/m/Y') }}</p>
        </div>
    </div>

    @if(auth()->user()->is_admin || auth()->user()->hasPermission('view-propostas-comerciais'))
    {{-- ml-auto: em flex-wrap no mobile o bloco do menu fica à direita; painel right-0 alinha ao botão (antes o menu ia para a esquerda da viewport). --}}
    <details class="group relative z-30 ml-auto shrink-0">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 shadow-theme-xs hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 dark:hover:bg-gray-800 [&::-webkit-details-marker]:hidden">
            <span>Menu</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="absolute right-0 z-50 mt-1 min-w-[220px] max-w-[min(100vw-1.5rem,22rem)] rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
            @if(auth()->user()->is_admin || auth()->user()->hasPermission('edit-propostas-comerciais'))
                @if($proposta->podeEditar())
                <a href="{{ route('propostas-comerciais.edit', $proposta) }}" class="block px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50 dark:text-white/90 dark:hover:bg-gray-800">Editar</a>
                @endif

                @if($proposta->podeNovaVersao() && (auth()->user()->is_admin || auth()->user()->hasPermission('create-propostas-comerciais')))
                <form method="POST" action="{{ route('propostas-comerciais.nova-versao', $proposta) }}" class="block" onsubmit="return confirm('Criar nova versão a partir desta? A versão atual deixa de ser editável como rascunho anterior.')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 text-left text-sm text-gray-800 hover:bg-gray-50 dark:text-white/90 dark:hover:bg-gray-800">Nova versão</button>
                </form>
                @endif

                @if($proposta->podeConverterParaPedido() && (auth()->user()->is_admin || auth()->user()->hasPermission('create-pedidos-venda')))
                <form method="POST" action="{{ route('propostas-comerciais.converter-pedido', $proposta) }}" class="block" onsubmit="return confirm('Gerar pedido de venda a partir desta proposta?')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 text-left text-sm text-gray-800 hover:bg-gray-50 dark:text-white/90 dark:hover:bg-gray-800">Converter em pedido</button>
                </form>
                @endif
            @endif

            <a href="{{ route('propostas-comerciais.impressao', $proposta) }}" target="_blank" rel="noopener noreferrer" class="block px-4 py-2.5 text-sm text-gray-800 hover:bg-gray-50 dark:text-white/90 dark:hover:bg-gray-800">Gerar PDF <span class="text-xs text-gray-500">(abre HTML para imprimir)</span></a>

            @if($proposta->pedidoVenda && (auth()->user()->is_admin || auth()->user()->hasPermission('view-pedidos-venda')))
            <a href="{{ route('pedidos-venda.show', $proposta->pedidoVenda) }}" class="block px-4 py-2.5 text-sm text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/40">Ver pedido</a>
            @endif

            @if(auth()->user()->is_admin || auth()->user()->hasPermission('delete-propostas-comerciais'))
                @if($proposta->podeExcluir())
                <form method="POST" action="{{ route('propostas-comerciais.destroy', $proposta) }}" class="block border-t border-gray-100 dark:border-gray-800" onsubmit="return confirm('Excluir esta proposta?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">Excluir</button>
                </form>
                @endif
            @endif
        </div>
    </details>
    @endif
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">{{ session('error') }}</div>
@endif

@php
    $modelosDocumento = $modelosDocumento ?? $modelosDocx ?? collect();
    $podeGerirModelosDocx = auth()->user()->is_admin || auth()->user()->hasPermission('manage-proposta-documento-modelos');
@endphp
@if(auth()->user()->is_admin || auth()->user()->hasPermission('view-propostas-comerciais'))
<div class="mb-6 space-y-4">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Gerar documentos</h2>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        O <strong>PDF padrão do sistema</strong> usa sempre o mesmo layout (Blade). Os <strong>modelos</strong> cadastrados são Word (.docx) ou HTML (.html): o Word gera ficheiro para descarregar; o HTML abre numa <strong>página no browser</strong> com os dados — use <kbd class="rounded bg-gray-200 px-1 dark:bg-gray-700">Ctrl+P</kbd> para imprimir ou guardar como PDF no próprio navegador.
    </p>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border-2 border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-600 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">PDF padrão do sistema</h3>
            <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                Layout fixo do ERP (não usa modelos Word/HTML). Arquivo ou impressão rápida.
            </p>
            <a href="{{ route('propostas-comerciais.pdf', $proposta) }}"
               download="proposta-{{ $proposta->id }}-v{{ $proposta->versao }}.pdf"
               class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-md ring-1 ring-brand-600/20 transition hover:bg-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 sm:w-auto dark:bg-brand-500 dark:hover:bg-brand-600 dark:ring-brand-400/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 opacity-95" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF (padrão)
            </a>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Se o navegador abrir o PDF em vez de guardar, use “Guardar como” no visualizador.</p>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-500/25 dark:bg-emerald-950/25">
            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Modelo personalizado (Word ou HTML)</h3>
            <p class="mt-1 text-xs text-emerald-900/80 dark:text-emerald-200/90">
                Escolha o modelo. <strong>Word</strong>: descarrega .docx. <strong>HTML</strong>: abre nova página com o documento preenchido (imprimir/PDF pelo browser).
            </p>
            @if($modelosDocumento->isNotEmpty())
            <form id="form-modelo-proposta" method="get" action="{{ route('propostas-comerciais.docx', $proposta) }}" class="mt-3 flex flex-col gap-3">
                <div class="min-w-0 flex-1">
                    <label for="modelo_documento_id" class="mb-1 block text-[10px] font-medium uppercase text-emerald-800 dark:text-emerald-300">Modelo</label>
                    <select id="modelo_documento_id" name="modelo_id" required class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2.5 text-sm dark:border-emerald-600/50 dark:bg-gray-800 dark:text-white/90">
                        <option value="">— Escolher modelo —</option>
                        @foreach($modelosDocumento as $m)
                            @php $fmt = $m->formato ?? \App\Models\PropostaDocumentoModelo::FORMATO_DOCX; @endphp
                            <option value="{{ $m->id }}" data-formato="{{ $fmt }}">{{ $m->nome }} @if($fmt === \App\Models\PropostaDocumentoModelo::FORMATO_HTML)(HTML)@else(Word)@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <button type="submit" id="btn-modelo-docx" formaction="{{ route('propostas-comerciais.docx', $proposta) }}" disabled class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                        Descarregar DOCX
                    </button>
                    <button type="submit" id="btn-modelo-html" formaction="{{ route('propostas-comerciais.modelo-html', $proposta) }}" formtarget="_blank" disabled class="inline-flex shrink-0 items-center justify-center rounded-lg border border-emerald-700 bg-white px-4 py-2.5 text-sm font-medium text-emerald-800 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-100 dark:hover:bg-emerald-900/50">
                        Abrir página HTML
                    </button>
                </div>
            </form>
            <script>
            (function () {
                var sel = document.getElementById('modelo_documento_id');
                var bDocx = document.getElementById('btn-modelo-docx');
                var bHtml = document.getElementById('btn-modelo-html');
                if (!sel || !bDocx || !bHtml) return;
                function sync() {
                    var opt = sel.options[sel.selectedIndex];
                    var fmt = opt && opt.dataset ? opt.dataset.formato : '';
                    var ok = sel.value !== '';
                    bDocx.disabled = !ok || fmt !== 'docx';
                    bHtml.disabled = !ok || fmt !== 'html';
                }
                sel.addEventListener('change', sync);
                sync();
            })();
            </script>
            @else
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                <p class="font-medium">Nenhum modelo ativo</p>
                <p class="mt-1 text-xs opacity-90">Cadastre modelos em <strong>Vendas → Modelos de proposta</strong> (.docx ou .html).</p>
                @if($podeGerirModelosDocx)
                <p class="mt-2 text-xs">
                    <a href="{{ route('propostas-comerciais.modelos-documento.index') }}" class="font-semibold text-amber-800 underline hover:no-underline dark:text-amber-200">Abrir modelos</a>
                    · <code class="rounded bg-white/80 px-1 dark:bg-black/30">php artisan proposta:instalar-modelo-docx-padrao</code>
                </p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Resumo</h2>
        <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
            <div><dt class="text-gray-500 dark:text-gray-400">Título</dt><dd class="font-medium text-gray-800 dark:text-gray-200">{{ $proposta->titulo ?: '—' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Vendedor</dt><dd class="font-medium text-gray-800 dark:text-gray-200">{{ $proposta->vendedor?->name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Tabela de preços</dt><dd class="font-medium text-gray-800 dark:text-gray-200">{{ $proposta->tabelaPreco?->nome ?? 'Padrão' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Validade</dt><dd class="font-medium text-gray-800 dark:text-gray-200">
                @if($proposta->validade_em)
                    {{ $proposta->validade_em->format('d/m/Y') }}
                    @if($proposta->validade_dias) <span class="text-gray-500">({{ $proposta->validade_dias }} dia(s))</span> @endif
                @else
                    —
                @endif
            </dd></div>
            <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">Total geral</dt><dd class="text-xl font-bold text-brand-600 dark:text-brand-400">R$ {{ number_format((float) $proposta->total_geral, 2, ',', '.') }}</dd></div>
        </dl>
        @if(filled($proposta->descricao))
        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
            <p class="text-xs font-semibold uppercase text-gray-500">Descrição geral</p>
            <div class="proposta-html mt-2 text-sm text-gray-700 dark:text-gray-300 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">
                {!! $proposta->descricao !!}
            </div>
        </div>
        @endif
        @if(filled($proposta->observacoes))
        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
            <p class="text-xs font-semibold uppercase text-gray-500">Observações (cliente)</p>
            <div class="proposta-html mt-2 text-sm text-gray-700 dark:text-gray-300 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">
                {!! $proposta->observacoes !!}
            </div>
        </div>
        @endif
        @if(filled($proposta->observacoes_internas))
        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
            <p class="text-xs font-semibold uppercase text-gray-500">Observações internas</p>
            <div class="proposta-html mt-2 text-sm text-gray-600 dark:text-gray-400 [&_a]:text-brand-600 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1">
                {!! $proposta->observacoes_internas !!}
            </div>
        </div>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Versões do grupo</h2>
        <ul class="space-y-2 text-sm">
            @foreach($versoes as $v)
            <li class="flex items-center justify-between rounded-lg border border-gray-100 px-2 py-1.5 dark:border-gray-800">
                <span>v{{ $v->versao }}</span>
                @if($v->id === $proposta->id)
                    <span class="text-xs font-medium text-brand-600">atual</span>
                @else
                    <a href="{{ route('propostas-comerciais.show', $v->id) }}" class="text-xs text-brand-600 hover:underline">Abrir</a>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- Dados de contato do cliente --}}
<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    {{-- Contatos --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Contatos</h2>
        @if($proposta->cliente?->contatos?->isNotEmpty())
        <div class="space-y-3">
            @foreach($proposta->cliente->contatos as $contato)
            <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-800 dark:text-gray-200">
                        {{ $contato->nome ?: 'Contato' }}
                        @if($contato->cargo) <span class="text-xs text-gray-500 dark:text-gray-400">— {{ $contato->cargo }}</span> @endif
                        @if($contato->principal) <span class="ml-1 rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">principal</span> @endif
                    </span>
                </div>
                <div class="mt-2 space-y-1.5 text-sm">
                    @if($contato->telefone)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $contato->telefone) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $contato->telefone }}</a>
                    </div>
                    @endif
                    @if($contato->telefone2)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $contato->telefone2) }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $contato->telefone2 }} <span class="text-xs text-gray-500">(secundário)</span></a>
                    </div>
                    @endif
                    @if($contato->telefone_whatsapp && $contato->telefone)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contato->telefone) }}" target="_blank" rel="noopener noreferrer" class="text-green-600 hover:underline dark:text-green-400">{{ $contato->telefone }} <span class="text-xs text-gray-500">(WhatsApp)</span></a>
                    </div>
                    @endif
                    @if($contato->email)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:{{ $contato->email }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $contato->email }}</a>
                    </div>
                    @endif
                    @if($contato->email2)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:{{ $contato->email2 }}" class="text-brand-600 hover:underline dark:text-brand-400">{{ $contato->email2 }} <span class="text-xs text-gray-500">(secundário)</span></a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum contato cadastrado.</p>
        @endif
    </div>

    {{-- Endereços --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Endereços</h2>
        @if($proposta->cliente?->enderecos?->isNotEmpty())
        <div class="space-y-3">
            @foreach($proposta->cliente->enderecos as $endereco)
            <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-800 dark:text-gray-200">
                        {{ $endereco->logradouro }}, {{ $endereco->numero }}
                        @if($endereco->complemento) <span class="text-gray-500 dark:text-gray-400">— {{ $endereco->complemento }}</span> @endif
                    </span>
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $endereco->bairro }} — {{ $endereco->cidade }}/{{ $endereco->estado }}<br>
                    CEP: {{ $endereco->cep }}
                    @if($endereco->pais && $endereco->pais !== 'Brasil') <span class="text-gray-500">— {{ $endereco->pais }}</span> @endif
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($endereco->principal) <span class="rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">principal</span> @endif
                    @if($endereco->cobranca) <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">cobrança</span> @endif
                    @if($endereco->entrega) <span class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700 dark:bg-green-500/20 dark:text-green-300">entrega</span> @endif
                    @php
                        $enderecoMapa = urlencode(trim("{$endereco->logradouro}, {$endereco->numero}, {$endereco->bairro}, {$endereco->cidade}-{$endereco->estado}, {$endereco->cep}"));
                    @endphp
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $enderecoMapa }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline dark:text-brand-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9a3 3 0 100 6 3 3 0 000-6z"/></svg>
                        Ver no Google Maps
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum endereço cadastrado.</p>
        @endif
    </div>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Itens</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Tipo</th>
                    <th class="px-4 py-2 text-left">Descrição</th>
                    <th class="px-4 py-2 text-right">Qtd</th>
                    <th class="px-4 py-2 text-right">Unit.</th>
                    <th class="px-4 py-2 text-right">Desc.</th>
                    <th class="px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($proposta->itens as $i => $item)
                <tr>
                    <td class="px-4 py-2">{{ $i + 1 }}</td>
                    <td class="px-4 py-2">{{ $item->tipo === 'servico' ? 'Serviço' : 'Produto' }}</td>
                    <td class="px-4 py-2">{{ $item->descricao }}</td>
                    <td class="px-4 py-2 text-right">{{ format_quantidade_br($item->quantidade) }}</td>
                    <td class="px-4 py-2 text-right">R$ {{ format_br_decimal($item->preco_unitario) }}</td>
                    <td class="px-4 py-2 text-right">{{ $item->desconto > 0 ? '- R$ '.format_br_decimal($item->desconto) : '—' }}</td>
                    <td class="px-4 py-2 text-right font-medium">R$ {{ format_br_decimal($item->total) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nenhum item.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-end border-t border-gray-200 px-4 py-3 text-sm dark:border-gray-800">
        <div class="w-64 space-y-1 text-right">
            <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Produtos</span><span>R$ {{ number_format((float) $proposta->total_produtos, 2, ',', '.') }}</span></div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Serviços</span><span>R$ {{ number_format((float) $proposta->total_servicos, 2, ',', '.') }}</span></div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Descontos</span><span class="text-red-500">- R$ {{ number_format((float) $proposta->total_descontos, 2, ',', '.') }}</span></div>
            <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold dark:border-gray-700"><span>Total</span><span class="text-brand-600">R$ {{ number_format((float) $proposta->total_geral, 2, ',', '.') }}</span></div>
        </div>
    </div>
</div>
@endsection
