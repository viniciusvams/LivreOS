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

{{-- Formulário compartilhado: create e edit --}}
@php
    $pedido = $pedido ?? null;
    $tabelaPrecoInicial = old('tabela_preco_id', isset($pedido) && $pedido->tabela_preco_id !== null && $pedido->tabela_preco_id !== '' ? (int) $pedido->tabela_preco_id : null);
    if ($tabelaPrecoInicial === null || $tabelaPrecoInicial === '') {
        $tabelaPrecoInicialJs = '';
    } else {
        $tabelaPrecoInicialJs = (int) $tabelaPrecoInicial;
    }
    $clientesMeta = isset($clientes) ? $clientes->keyBy('id')->map(fn ($c) => [
        'bloqueado_vendas' => (bool) ($c->bloqueado_vendas ?? false),
        'limite_credito' => $c->limite_credito !== null && $c->limite_credito !== '' ? (float) $c->limite_credito : null,
    ])->toArray() : [];
    $dataPedidoInicial = old('data_pedido', isset($pedido) ? $pedido->data_pedido?->format('Y-m-d') : now()->format('Y-m-d'));
    $validadeOrcDiasCfg = (int) ($validade_orcamento_dias_config ?? 0);
    $validadePedidoOldRaw = old('validade_orcamento_dias', isset($pedido) ? $pedido->validade_orcamento_dias : null);
    $validadePedidoInicialStr = ($validadePedidoOldRaw !== null && $validadePedidoOldRaw !== '' && (int) $validadePedidoOldRaw > 0)
        ? (string) (int) $validadePedidoOldRaw
        : '';
@endphp
<div x-data="pedidoVendaForm()" x-init="init()">

{{-- Dados principais --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dados do Pedido</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        {{-- Cliente --}}
        <div class="lg:col-span-2">
            @include('partials.cliente-autocomplete-field', [
                'prefix' => 'pedido_venda',
                'clientes' => $clientes,
                'selectedId' => old('cliente_id', $pedido?->cliente_id ?? ''),
                'showBloqueadoVendasHint' => true,
                'labelClass' => 'mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400',
                'inputClass' => 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 focus:border-brand-500 focus:ring-brand-500',
                'extraSelectAttributes' => 'x-on:change="aoMudarCliente($event)"',
                'dispatchChangeOnSync' => true,
            ])
        </div>

        {{-- Unidade/Contato/Endereço (matriz/filial, igual Propostas) --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unidade (filial)</label>
            <select name="cliente_unidade_id" id="cliente_unidade_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Matriz —</option>
                @foreach(($unidades ?? collect()) as $unidade)
                    <option value="{{ $unidade->id }}"
                            data-grupo="{{ $unidade->grupo_economico_id }}"
                            data-matriz="{{ $unidade->matriz_id }}"
                            {{ old('cliente_unidade_id', $pedido?->cliente_unidade_id ?? '') == $unidade->id ? 'selected' : '' }}>
                        {{ $unidade->nome }}
                    </option>
                @endforeach
            </select>
            @error('cliente_unidade_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Contato principal</label>
            <select name="contato_id" id="contato_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">Selecione...</option>
                @foreach(($contatos ?? collect()) as $contato)
                    @php
                        $contatoWhatsapp = $contato->telefone_whatsapp ? ($contato->telefone ?? $contato->telefone2 ?? '') : ($contato->telefone ?? $contato->telefone2 ?? '');
                    @endphp
                    <option value="{{ $contato->id }}"
                            data-cliente="{{ $contato->cliente_id }}"
                            data-principal="{{ $contato->principal ? '1' : '0' }}"
                            data-email="{{ e($contato->email ?? '') }}"
                            data-telefone="{{ e($contato->telefone ?? '') }}"
                            data-whatsapp="{{ e($contatoWhatsapp ?? '') }}"
                            {{ old('contato_id', $pedido?->contato_id ?? '') == $contato->id ? 'selected' : '' }}>
                        {{ $contato->nome }}
                    </option>
                @endforeach
            </select>
            @error('contato_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Endereço</label>
            <select name="endereco_id" id="endereco_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">Selecione...</option>
                @foreach(($enderecos ?? collect()) as $endereco)
                    @php
                        $enderecoLabel = trim(($endereco->logradouro ?? '') . ' ' . ($endereco->numero ?? '') . ' ' . ($endereco->complemento ? '- ' . $endereco->complemento : '') . ', ' . ($endereco->bairro ?? '') . ' - ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
                    @endphp
                    <option value="{{ $endereco->id }}"
                            data-cliente="{{ $endereco->cliente_id }}"
                            data-principal="{{ $endereco->principal ? '1' : '0' }}"
                            {{ old('endereco_id', $pedido?->endereco_id ?? '') == $endereco->id ? 'selected' : '' }}>
                        {{ $enderecoLabel }}
                    </option>
                @endforeach
            </select>
            @error('endereco_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Canal de Venda --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Canal de Venda</label>
            <select name="canal_venda"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Selecione —</option>
                @foreach($canaisVenda as $canal)
                    <option value="{{ $canal }}" {{ old('canal_venda', $pedido?->canal_venda ?? '') === $canal ? 'selected' : '' }}>{{ ucfirst($canal) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Vendedor --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Vendedor</label>
            <select name="vendedor_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Selecione —</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}" {{ old('vendedor_id', $pedido?->vendedor_id ?? '') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tabela de Preços --}}
        @if($tabelasPrecos->isNotEmpty())
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tabela de Preços</label>
            <select name="tabela_preco_id" x-ref="tabelaPrecoSelect" x-model="tabelaPrecoId"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Padrão (preço de venda) —</option>
                @foreach($tabelasPrecos as $tp)
                    <option value="{{ $tp->id }}" {{ old('tabela_preco_id', $pedido?->tabela_preco_id ?? '') == $tp->id ? 'selected' : '' }}>
                        {{ $tp->nome }}{{ $tp->percentual_ajuste != 0 ? ' ('.($tp->percentual_ajuste > 0 ? '+' : '').number_format($tp->percentual_ajuste, 2, ',', '.').'%)' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">Afeta apenas o <strong>preço sugerido</strong> ao buscar e escolher produtos a partir daqui. Itens já lançados no pedido <strong>não</strong> são alterados ao trocar a tabela.</p>
        </div>
        @endif

        {{-- Data do Pedido --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Data do Pedido</label>
            <input type="date" name="data_pedido" x-model="dataPedidoStr"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
        </div>

        {{-- Entrega Prevista --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Entrega Prevista</label>
            <input type="date" name="data_prevista_entrega" value="{{ old('data_prevista_entrega', isset($pedido) ? $pedido->data_prevista_entrega?->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
        </div>

        {{-- Validade desta proposta (opcional; sobrescreve o padrão da empresa) --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Validade desta proposta (dias corridos)</label>
            <input type="number" name="validade_orcamento_dias" min="1" max="3650" step="1"
                   x-model="validadeOrcamentoPedidoStr"
                   placeholder="{{ $validadeOrcDiasCfg > 0 ? 'Padrão: '.$validadeOrcDiasCfg.' dias' : 'Opcional' }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            @if($validadeOrcDiasCfg > 0)
                <p class="mt-1 text-xs text-gray-400">Em branco = usar o <strong>padrão da empresa</strong> ({{ $validadeOrcDiasCfg }} dia(s)).</p>
            @else
                <p class="mt-1 text-xs text-gray-400">Opcional. Não há padrão global — defina aqui ou em <strong>Configurações → Pedidos de venda</strong>.</p>
            @endif
            @error('validade_orcamento_dias')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2 lg:col-span-3 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2.5 dark:border-sky-500/25 dark:bg-sky-500/10"
             x-show="validadeOrcamentoDiasEfetiva() > 0" x-cloak>
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">Validade do orçamento / proposta</p>
            <p class="mt-1 text-xs text-sky-900 dark:text-sky-100/95" x-text="textoLinhaValidadeOrcamento()"></p>
        </div>
    </div>

    {{-- Observações --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Observações para o cliente</label>
            <textarea name="observacoes" rows="2"
                      class="js-jodit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">{!! old('observacoes', $pedido?->observacoes ?? '') !!}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Observações internas</label>
            <textarea name="observacoes_internas" rows="2"
                      class="js-jodit w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">{!! old('observacoes_internas', $pedido?->observacoes_internas ?? '') !!}</textarea>
        </div>
    </div>
</div>

{{-- Itens --}}
<div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3 dark:border-gray-800">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Itens do Pedido</h2>
        <div class="flex flex-wrap gap-2">
            @if(!empty($podeImportarOrcamento))
            <button type="button" @click="abrirModalImportarOrcamento()"
                    class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar de orçamento
            </button>
            @endif
            <button type="button" @click="adicionarItem('produto')"
                    class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300">
                + Produto
            </button>
            <button type="button" @click="adicionarItem('servico')"
                    class="inline-flex items-center gap-1 rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-100 dark:bg-purple-500/10 dark:text-purple-300">
                + Serviço
            </button>
        </div>
    </div>

    @if(empty($podeAplicarDescontoPedidoVenda))
    <div class="mx-5 mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
        <strong>Desconto:</strong> você não tem permissão para aplicar ou aumentar descontos. Os campos ficam somente leitura; na edição é possível manter ou reduzir descontos já existentes, mas não aumentá-los.
    </div>
    @endif

    @if(!empty($podeImportarOrcamento))
    <div x-show="modalImportarOrcamento" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="modalImportarOrcamento = false">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
             @click.outside="modalImportarOrcamento = false">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Importar itens de outro orçamento</h3>
                <button type="button" @click="modalImportarOrcamento = false" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">&times;</button>
            </div>
            <div class="p-4">
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Pedidos em <strong>rascunho</strong> ou <strong>confirmado</strong> com itens. Os itens serão <strong>acrescentados</strong> à lista atual.</p>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Buscar por número ou cliente</label>
                <input type="text" x-model="buscaOrcamento" @input.debounce.400ms="carregarListaOrcamentos()"
                       placeholder="Ex.: PV0001 ou nome do cliente"
                       class="mb-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <div class="max-h-[min(60vh,28rem)] overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <p x-show="carregandoOrcamentos" class="p-4 text-center text-sm text-gray-400">Carregando…</p>
                    <p x-show="!carregandoOrcamentos && listaOrcamentos.length === 0" class="p-4 text-center text-sm text-gray-400">Nenhum orçamento encontrado.</p>
                    <ul x-show="!carregandoOrcamentos && listaOrcamentos.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="row in listaOrcamentos" :key="row.id">
                            <li class="px-3 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-gray-800 dark:text-gray-200" x-text="row.numero_sequencial"></div>
                                        <div class="truncate text-xs text-gray-500" x-text="row.cliente_nome + ' · ' + row.data_pedido + ' · R$ ' + row.total_geral"></div>
                                    </div>
                                    <button type="button" @click="importarItensDoOrcamento(row.id)"
                                            class="shrink-0 rounded-lg bg-brand-500 px-2.5 py-1 text-xs font-medium text-white hover:bg-brand-600">
                                        Importar
                                    </button>
                                </div>
                                <div class="mt-2 rounded-md border border-gray-100 bg-gray-50/80 px-2 py-1.5 dark:border-gray-700 dark:bg-gray-800/50">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Itens <span x-text="'(' + (row.itens_count ?? (row.itens || []).length) + ')'"></span></p>
                                    <ul class="space-y-0.5 text-xs text-gray-700 dark:text-gray-300">
                                        <template x-for="(it, iix) in (row.itens || [])" :key="iix">
                                            <li class="flex gap-2 border-b border-gray-100/80 pb-0.5 last:border-0 dark:border-gray-700/80">
                                                <span class="shrink-0 font-mono text-[10px] uppercase text-gray-400" x-text="it.tipo === 'servico' ? 'Srv' : 'Prd'"></span>
                                                <span class="min-w-0 flex-1" x-text="it.descricao"></span>
                                                <span class="shrink-0 whitespace-nowrap text-gray-500" x-text="it.quantidade + ' × R$ ' + it.preco_unitario"></span>
                                            </li>
                                        </template>
                                    </ul>
                                    <p x-show="row.itens_mais > 0" class="mt-1 text-[10px] italic text-gray-400" x-text="'… e mais ' + row.itens_mais + ' item(ns)'"></p>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2 text-left w-6">#</th>
                    <th class="px-3 py-2 text-left min-w-[200px]">Descrição</th>
                    <th class="px-3 py-2 text-left w-24">Qtd</th>
                    <th class="px-3 py-2 text-left w-28">Preço Unit.</th>
                    <th class="px-3 py-2 text-left w-24">Desconto</th>
                    <th class="px-3 py-2 text-right w-28">Total</th>
                    <th class="px-3 py-2 text-center w-28">Gerar OS</th>
                    <th class="px-3 py-2 w-10"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, idx) in itens" :key="item._key">
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 text-gray-400 text-xs" x-text="idx+1"></td>
                        <td class="px-3 py-2">
                            <input type="hidden" :name="`itens[${idx}][id]`" :value="item.id">
                            <input type="hidden" :name="`itens[${idx}][tipo]`" :value="item.tipo">
                            <input type="hidden" :name="`itens[${idx}][produto_id]`" :value="item.produto_id">
                            <input type="hidden" :name="`itens[${idx}][produto_variacao_id]`" :value="item.produto_variacao_id || ''">
                            <input type="hidden" :name="`itens[${idx}][servico_id]`" :value="item.servico_id">

                            <div class="relative" x-show="!item.produto_id && !item.servico_id">
                                <input type="text" :placeholder="item.tipo === 'servico' ? 'Buscar serviço...' : 'Buscar produto...'"
                                       x-model="item.busca" @input.debounce.300ms="buscar(item)"
                                       class="w-full min-w-[14rem] rounded border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 sm:min-w-[18rem]">
                                <div x-show="item.resultados && item.resultados.length > 0"
                                     class="absolute left-0 z-40 mt-1 max-h-80 w-[min(90vw,36rem)] max-w-none overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 sm:w-[min(70vw,40rem)] md:w-full md:max-w-none">
                                    <template x-for="r in item.resultados" :key="r.id">
                                        <div @click="selecionarItem(item, r)"
                                             class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-medium text-gray-800 dark:text-gray-200" x-text="r.descricao"></span>
                                                <div class="flex shrink-0 items-center gap-1">
                                                    <template x-if="r.preco_tabela">
                                                        <span class="rounded bg-amber-100 px-1 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">TABELA</span>
                                                    </template>
                                                    <span class="font-semibold text-brand-600 dark:text-brand-400">R$ <span x-text="formatBr(r.preco)"></span></span>
                                                </div>
                                            </div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-400">
                                                <template x-if="r.formato === 'composicao'">
                                                    <span class="rounded bg-sky-100 px-1 py-0.5 text-[10px] font-semibold text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">Kit</span>
                                                </template>
                                                <template x-if="r.formato === 'variacao' && (r.variacoes || []).length">
                                                    <span class="rounded bg-violet-100 px-1 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-500/20 dark:text-violet-300">Variações</span>
                                                </template>
                                                <template x-if="r.estoque_quantidade !== undefined">
                                                    <span>
                                                        Estoque:
                                                        <span :class="parseFloat(r.estoque_quantidade) <= 0 ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-300'"
                                                              x-text="parseFloat(r.estoque_quantidade) % 1 === 0 ? parseInt(r.estoque_quantidade) : parseFloat(r.estoque_quantidade)"></span>
                                                    </span>
                                                </template>
                                                <template x-if="r.estoque_localizacao">
                                                    <span class="flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <span x-text="r.estoque_localizacao"></span>
                                                    </span>
                                                </template>
                                            </div>
                                            <p x-show="(r.kit_componentes || []).length" class="mt-1 border-l-2 border-sky-200 pl-2 text-xs leading-relaxed text-gray-500 dark:border-sky-500/40 dark:text-gray-400"
                                               x-text="'Composição: ' + (r.kit_componentes || []).join(', ')"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="item.produto_id || item.servico_id" class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="item.tipo === 'servico' ? 'text-purple-600' : 'text-brand-600'" class="shrink-0 text-xs font-medium" x-text="item.tipo === 'servico' ? 'SERV' : 'PROD'"></span>
                                    <input type="text" :name="`itens[${idx}][descricao]`" x-model="item.descricao"
                                           class="min-w-[8rem] flex-1 rounded border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                                    <button type="button" x-show="item.tipo === 'produto' && item.produto_id && (item.variacoes_disponiveis || []).length"
                                            @click="abrirModalVariacao(item)"
                                            class="shrink-0 rounded border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-violet-800 hover:bg-violet-100 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-200">
                                        Variação
                                    </button>
                                    <button type="button" @click="limparItem(item)" class="shrink-0 text-gray-400 hover:text-red-500" title="Limpar item">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <p x-show="item.tipo === 'produto' && (item.kit_componentes || []).length" class="mt-1 border-l-2 border-sky-200 pl-2 text-xs leading-relaxed text-gray-500 dark:border-sky-500/40 dark:text-gray-400"
                                   x-text="'Composição: ' + (item.kit_componentes || []).join(', ')"></p>
                            </div>
                            <div x-show="!item.produto_id && !item.servico_id">
                                <input type="text" :name="`itens[${idx}][descricao]`" x-model="item.descricao" placeholder="ou digite manualmente"
                                       class="mt-1 w-full rounded border border-gray-200 px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" :name="`itens[${idx}][quantidade]`" x-model="item.quantidade"
                                   step="0.0001" min="0.0001" @change="calcularTotais(); aoAlterarDescontoItem(item)"
                                   class="w-20 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" :name="`itens[${idx}][preco_unitario]`" x-model="item.preco_unitario"
                                   @change="calcularTotais(); aoAlterarDescontoItem(item)"
                                   class="w-28 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" :name="`itens[${idx}][desconto]`" x-model="item.desconto"
                                   @change="aoAlterarDescontoItem(item)"
                                   :readonly="!podeAplicarDesconto"
                                   :class="!podeAplicarDesconto ? 'w-24 cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-2 py-1.5 text-sm text-right dark:border-gray-600 dark:bg-gray-800/80' : 'w-24 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90'"
                                   title="{{ empty($podeAplicarDescontoPedidoVenda) ? 'Sem permissão para alterar desconto' : '' }}">
                        </td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                            R$ <span x-text="formatBr(calcularItemTotal(item))"></span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <template x-if="item.tipo === 'servico'">
                                <label class="flex cursor-pointer items-center justify-center gap-1">
                                    <input type="checkbox" :name="`itens[${idx}][gerar_os]`" value="1" x-model="item.gerar_os" class="rounded">
                                    <span class="text-xs text-gray-500">OS</span>
                                </label>
                            </template>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" @click="removerItem(idx)"
                                    class="text-gray-400 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="itens.length === 0">
                    <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-400">Nenhum item adicionado. Clique em "+ Produto" ou "+ Serviço".</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Totais --}}
    <div class="flex justify-end border-t border-gray-200 px-5 py-4 dark:border-gray-800">
        <div class="w-72 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Total produtos</span>
                <span>R$ <span x-text="formatBr(totalProdutos)"></span></span>
            </div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Total serviços</span>
                <span>R$ <span x-text="formatBr(totalServicos)"></span></span>
            </div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Descontos itens</span>
                <span class="text-red-500">- R$ <span x-text="formatBr(totalDescontos)"></span></span>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-600 dark:text-gray-400">Desconto global</span>
                <div class="flex items-center gap-1">
                    <span class="text-red-500">-</span>
                    <input type="text" name="desconto_global" x-model="descontoGlobal" @change="aoAlterarDescontoGlobal()"
                           :readonly="!podeAplicarDesconto"
                           :class="!podeAplicarDesconto ? 'w-24 cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-2 py-1 text-right text-sm dark:border-gray-600 dark:bg-gray-800/80' : 'w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90'"
                           title="{{ empty($podeAplicarDescontoPedidoVenda) ? 'Sem permissão para alterar desconto global' : '' }}">
                </div>
            </div>
            @if(!empty($podeAplicarDescontoPedidoVenda) && !empty($usuarioSujeitoALimiteDesconto) && isset($limiteDesconto) && (($limiteDesconto->limite_valor ?? null) || ($limiteDesconto->limite_percentual ?? null)))
            <p class="text-right text-[10px] text-amber-600 dark:text-amber-400">
                Limite de desconto: @if($limiteDesconto->limite_valor) máx. R$ {{ number_format((float) $limiteDesconto->limite_valor, 2, ',', '.') }} @endif
                @if($limiteDesconto->limite_valor && $limiteDesconto->limite_percentual)<span class="mx-0.5">|</span>@endif
                @if($limiteDesconto->limite_percentual) máx. {{ number_format((float) $limiteDesconto->limite_percentual, 1, ',', '.') }}% @endif
            </p>
            @endif
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-600 dark:text-gray-400">Acréscimos</span>
                <input type="text" name="acrescimos_global" x-model="acrescimosGlobal" @change="calcularTotais(); aoAlterarDescontoGlobal()"
                       class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            </div>
            <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                <span class="font-semibold text-gray-800 dark:text-white">Total Geral</span>
                <span class="text-lg font-bold text-brand-600 dark:text-brand-400">R$ <span x-text="formatBr(totalGeral)"></span></span>
            </div>
        </div>
    </div>
</div>

    {{-- Modal escolha de variação (alinhado ao fluxo do PDV) --}}
    <div x-show="modalVariacaoAberto" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10"
         @keydown.escape.window="fecharModalVariacao()">
        <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
             @click.outside="fecharModalVariacao()">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Escolher variação</h3>
                <button type="button" @click="fecharModalVariacao()" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">&times;</button>
            </div>
            <div class="p-4" x-show="produtoVariacaoPayload">
                <p class="mb-1 text-sm font-medium text-gray-800 dark:text-gray-200" x-text="produtoVariacaoPayload ? produtoVariacaoPayload.descricao : ''"></p>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    Preço unit. sugerido: R$ <span x-text="produtoVariacaoPayload ? formatBr(produtoVariacaoPayload.preco) : '0,00'"></span>
                </p>
                <p class="mb-2 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Variação</p>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="v in (produtoVariacaoPayload && produtoVariacaoPayload.variacoes) ? produtoVariacaoPayload.variacoes : []" :key="v.id">
                        <button type="button" @click="variacaoModalSelecionadaId = v.id"
                                class="rounded-lg border-2 p-3 text-left text-xs font-semibold transition-all"
                                :class="variacaoModalSelecionadaId === v.id ? 'border-brand-500 bg-brand-50 text-brand-800 dark:border-brand-400 dark:bg-brand-500/15 dark:text-brand-200' : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:text-gray-300 dark:hover:border-gray-500'">
                            <span x-text="v.referencia_sku || (Array.isArray(v.opcoes_valores) ? v.opcoes_valores.join(' / ') : (v.opcoes_valores || 'Opção'))"></span>
                        </button>
                    </template>
                </div>
                <button type="button" @click="confirmarModalVariacao()"
                        class="mt-5 w-full rounded-lg bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600">
                    Aplicar
                </button>
            </div>
        </div>
    </div>

</div>

@php
    $itensIniciais = old('itens', isset($pedido) ? $pedido->itens->map(function($i) {
        $variacoesDisponiveis = [];
        $produtoNomeBase = '';
        $produtoFormato = '';
        if ($i->tipo === 'produto' && $i->produto) {
            $prod = $i->produto;
            $produtoNomeBase = $prod->nome ?? '';
            $produtoFormato = $prod->formato ?? '';
            if (($prod->formato ?? '') === 'variacao' && $prod->relationLoaded('variacoes')) {
                foreach ($prod->variacoes as $v) {
                    $variacoesDisponiveis[] = [
                        'id' => $v->id,
                        'referencia_sku' => $v->referencia_sku ?? '',
                        'opcoes_valores' => $v->opcoes_valores ?? [],
                        'ean_variacao' => $v->ean_variacao ?? null,
                        'quantidade' => $v->quantidade !== null ? (float) $v->quantidade : null,
                    ];
                }
            }
        }
        $kitComponentes = [];
        if ($i->tipo === 'produto' && $i->produto && ($i->produto->formato ?? '') === 'composicao' && $i->produto->relationLoaded('composicoes')) {
            foreach ($i->produto->composicoes as $comp) {
                $qtd = (float) ($comp->quantidade ?? 1);
                $nomeComp = $comp->componente?->nome ?? 'Item';
                $kitComponentes[] = abs($qtd - 1) > 0.00001 ? "{$nomeComp} ({$qtd}x)" : $nomeComp;
            }
        }
        return [
            'id'             => $i->id,
            '_key'           => $i->id,
            'tipo'           => $i->tipo,
            'produto_id'     => $i->produto_id,
            'produto_variacao_id' => $i->produto_variacao_id,
            'servico_id'     => $i->servico_id,
            'descricao'      => $i->descricao,
            'produto_nome_base' => $produtoNomeBase,
            'produto_formato' => $produtoFormato,
            'variacoes_disponiveis' => $variacoesDisponiveis,
            'kit_componentes' => $kitComponentes,
            // decimal:4 no model pode vir como "10.0000"; input type="number" usa ponto decimal → format_quantity
            'quantidade'     => format_quantity($i->quantidade ?? 1) ?: '1',
            'preco_unitario' => number_format($i->preco_unitario, 2, ',', '.'),
            'desconto'       => number_format($i->desconto, 2, ',', '.'),
            'gerar_os'       => (bool)$i->gerar_os,
            'busca'          => '',
            'resultados'     => [],
        ];
    })->values()->toArray() : []);
    $descontoGlobalVal = old('desconto_global', number_format(isset($pedido) ? (float) $pedido->desconto_global : 0, 2, ',', '.'));
    $acrescimosGlobalVal = old('acrescimos_global', number_format(isset($pedido) ? (float) $pedido->acrescimos_global : 0, 2, ',', '.'));
@endphp

@push('scripts')
<script>
function pedidoVendaForm() {
    return {
        itens: @json($itensIniciais),
        clientesMeta: @json($clientesMeta),
        dataPedidoStr: @json($dataPedidoInicial),
        validadeOrcamentoDiasConfig: @json($validadeOrcDiasCfg),
        validadeOrcamentoPedidoStr: @json($validadePedidoInicialStr),
        descontoGlobal: '{{ $descontoGlobalVal }}',
        acrescimosGlobal: '{{ $acrescimosGlobalVal }}',
        tabelaPrecoId: @json($tabelaPrecoInicialJs),
        totalProdutos: 0,
        totalServicos: 0,
        totalDescontos: 0,
        totalGeral: 0,
        _keyCounter: 1000,
        podeAplicarDesconto: @json(!empty($podeAplicarDescontoPedidoVenda)),
        usuarioSujeitoALimiteDesconto: @json(!empty($usuarioSujeitoALimiteDesconto)),
        limiteDescontoValor: @json(isset($limiteDesconto) && $limiteDesconto->limite_valor !== null && $limiteDesconto->limite_valor !== '' ? (float) $limiteDesconto->limite_valor : null),
        limiteDescontoPercentual: @json(isset($limiteDesconto) && $limiteDesconto->limite_percentual !== null && $limiteDesconto->limite_percentual !== '' ? (float) $limiteDesconto->limite_percentual : null),
        @if(!empty($podeImportarOrcamento))
        modalImportarOrcamento: false,
        carregandoOrcamentos: false,
        listaOrcamentos: [],
        buscaOrcamento: '',
        pedidoAtualId: @json(isset($pedido) ? $pedido->id : null),
        @endif
        modalVariacaoAberto: false,
        itemVariacaoModal: null,
        produtoVariacaoPayload: null,
        variacaoModalSelecionadaId: null,

        init() {
            this.$nextTick(() => {
                const sel = this.$refs.tabelaPrecoSelect;
                if (sel && sel.value !== undefined && sel.value !== null && sel.value !== '') {
                    this.tabelaPrecoId = sel.value;
                }
            });
            this.itens = this.itens.map((i) => ({
                ...i,
                _key: i._key || (this._keyCounter++),
                resultados: i.resultados || [],
                produto_variacao_id: (i.produto_variacao_id != null && i.produto_variacao_id !== '') ? i.produto_variacao_id : null,
                variacoes_disponiveis: i.variacoes_disponiveis || [],
                kit_componentes: i.kit_componentes || [],
                produto_nome_base: i.produto_nome_base || '',
                produto_formato: i.produto_formato || '',
            }));
            this.calcularTotais();
            this.itens.forEach((item) => {
                if (item.tipo === 'produto' && item.produto_id) {
                    this.sincronizarDetalhesProdutoPorId(item);
                }
            });
        },

        /** Valor atual da tabela no &lt;select&gt; (evita dessincronia do x-model com o DOM). */
        tabelaPrecoSelecionada() {
            const sel = this.$refs.tabelaPrecoSelect;
            if (sel && sel.value !== undefined && sel.value !== null && String(sel.value).trim() !== '') {
                return String(sel.value);
            }
            if (this.tabelaPrecoId !== undefined && this.tabelaPrecoId !== null && String(this.tabelaPrecoId).trim() !== '') {
                return String(this.tabelaPrecoId);
            }
            return '';
        },

        aoMudarCliente(e) {
            const id = parseInt(e.target.value, 10);
            if (!id || !this.clientesMeta || !this.clientesMeta[id]) return;
            const m = this.clientesMeta[id];
            if (m.bloqueado_vendas) {
                alert('Este cliente está bloqueado para vendas. Não será possível salvar o pedido até alterar o cadastro do cliente.');
            }
        },

        validadeOrcamentoDiasEfetiva() {
            const raw = String(this.validadeOrcamentoPedidoStr || '').trim();
            const p = parseInt(raw, 10);
            if (!isNaN(p) && p > 0) return p;
            const c = parseInt(this.validadeOrcamentoDiasConfig, 10) || 0;
            return c > 0 ? c : 0;
        },

        usaValidadeEspecificaProposta() {
            const raw = String(this.validadeOrcamentoPedidoStr || '').trim();
            const p = parseInt(raw, 10);
            return !isNaN(p) && p > 0;
        },

        dataLimiteOrcamentoFormatada() {
            const d = this.validadeOrcamentoDiasEfetiva();
            const s = String(this.dataPedidoStr || '').trim();
            if (!d || !s) return null;
            const parts = s.split('-');
            if (parts.length !== 3) return null;
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            if (!y || m < 0 || !day) return null;
            const dt = new Date(y, m, day);
            if (isNaN(dt.getTime())) return null;
            dt.setDate(dt.getDate() + d);
            const dd = String(dt.getDate()).padStart(2, '0');
            const mm = String(dt.getMonth() + 1).padStart(2, '0');
            const yyyy = dt.getFullYear();
            return dd + '/' + mm + '/' + yyyy;
        },

        textoLinhaValidadeOrcamento() {
            const d = this.validadeOrcamentoDiasEfetiva();
            if (!d) return '';
            const lim = this.dataLimiteOrcamentoFormatada();
            const espec = this.usaValidadeEspecificaProposta();
            const origem = espec ? 'Validade específica desta proposta' : 'Prazo padrão da empresa';
            if (!lim) {
                return origem + ': ' + d + ' dia(s) corrido(s) a partir da data do pedido.';
            }
            return origem + ': ' + d + ' dia(s) corrido(s) a partir da data do pedido. Referência: válido até ' + lim + '.';
        },

        /**
         * Preço do produto na tabela atual (ou preço de venda), via mesma API da busca.
         */
        async buscarProdutoPorIdNaTabela(item) {
            if (item.tipo !== 'produto' || !item.produto_id) return null;
            const tid = this.tabelaPrecoSelecionada();
            let url = `{{ route('pedidos-venda.buscar-produto') }}?produto_id=${encodeURIComponent(item.produto_id)}&tipo=produto`;
            if (tid) url += `&tabela_preco_id=${encodeURIComponent(tid)}`;
            try {
                const resp = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                const arr = await resp.json();
                return (Array.isArray(arr) && arr.length) ? arr[0] : null;
            } catch (e) {
                return null;
            }
        },

        adicionarItem(tipo) {
            this.itens.push({
                id: null, _key: this._keyCounter++, tipo,
                produto_id: null, produto_variacao_id: null, servico_id: null,
                produto_nome_base: '', produto_formato: '', variacoes_disponiveis: [], kit_componentes: [],
                descricao: '', quantidade: 1,
                preco_unitario: '0,00', desconto: '0,00',
                gerar_os: false, busca: '', resultados: [],
            });
        },

        removerItem(idx) {
            this.itens.splice(idx, 1);
            this.calcularTotais();
        },

        async buscar(item) {
            if (!item.busca || item.busca.length < 2) { item.resultados = []; return; }
            const tid = this.tabelaPrecoSelecionada();
            let url = `{{ route('pedidos-venda.buscar-produto') }}?q=${encodeURIComponent(item.busca)}&tipo=${item.tipo}`;
            if (tid) url += `&tabela_preco_id=${encodeURIComponent(tid)}`;
            const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            item.resultados = await resp.json();
        },

        selecionarItem(item, resultado) {
            item.resultados = [];
            item.busca = '';
            if (item.tipo === 'servico') {
                this.aplicarProdutoOuServicoAoItem(item, resultado, null);
                return;
            }
            if ((resultado.formato === 'variacao') && (resultado.variacoes || []).length > 0) {
                this.itemVariacaoModal = item;
                this.produtoVariacaoPayload = JSON.parse(JSON.stringify(resultado));
                this.variacaoModalSelecionadaId = resultado.variacoes[0].id;
                this.modalVariacaoAberto = true;
                return;
            }
            this.aplicarProdutoOuServicoAoItem(item, resultado, null);
        },

        fecharModalVariacao() {
            this.modalVariacaoAberto = false;
            this.itemVariacaoModal = null;
            this.produtoVariacaoPayload = null;
            this.variacaoModalSelecionadaId = null;
        },

        confirmarModalVariacao() {
            if (!this.itemVariacaoModal || !this.produtoVariacaoPayload) {
                this.fecharModalVariacao();
                return;
            }
            const p = this.produtoVariacaoPayload;
            if ((p.variacoes || []).length && !this.variacaoModalSelecionadaId) {
                alert('Selecione uma variação.');
                return;
            }
            this.aplicarProdutoOuServicoAoItem(this.itemVariacaoModal, p, this.variacaoModalSelecionadaId);
            this.fecharModalVariacao();
        },

        async abrirModalVariacao(item) {
            await this.sincronizarDetalhesProdutoPorId(item);
            const vars = item.variacoes_disponiveis || [];
            if (!vars.length) {
                alert('Não foi possível carregar as variações deste produto.');
                return;
            }
            this.itemVariacaoModal = item;
            this.produtoVariacaoPayload = {
                id: item.produto_id,
                descricao: item.produto_nome_base || item.descricao,
                formato: 'variacao',
                variacoes: vars,
                preco: this.parseBr(item.preco_unitario),
            };
            this.variacaoModalSelecionadaId = item.produto_variacao_id || vars[0].id;
            this.modalVariacaoAberto = true;
        },

        /**
         * Busca produto por ID (autocomplete) para preencher variações e/ou composição do kit (como no PDV).
         */
        async sincronizarDetalhesProdutoPorId(item) {
            if (item.tipo !== 'produto' || !item.produto_id) return;
            const precisaVar = (item.produto_formato === 'variacao' || item.produto_variacao_id)
                && (!item.variacoes_disponiveis || !item.variacoes_disponiveis.length);
            const precisaKit = item.produto_formato === 'composicao'
                && (!item.kit_componentes || !item.kit_componentes.length);
            if (!precisaVar && !precisaKit) return;
            const p = await this.buscarProdutoPorIdNaTabela(item);
            if (p) {
                item.variacoes_disponiveis = p.variacoes || item.variacoes_disponiveis || [];
                item.produto_formato = p.formato || item.produto_formato;
                if (!item.produto_nome_base) item.produto_nome_base = p.descricao || '';
                if (p.kit_componentes && p.kit_componentes.length) {
                    item.kit_componentes = [...p.kit_componentes];
                } else if (p.kit_itens && p.kit_itens.length) {
                    item.kit_componentes = this.kitComponentesDeKitItens(p.kit_itens);
                }
                // Não alterar preço aqui: preserva valores na edição/importação; só "Tabela de preços" refaz unitário.
            }
        },

        kitComponentesDeKitItens(kitItens) {
            if (!Array.isArray(kitItens) || !kitItens.length) return [];
            return kitItens.map((k) => {
                const nome = k.nome || 'Item';
                const q = this.parseQuantidadeKit(k.quantidade);
                return (Math.abs(q - 1) > 0.0001) ? `${nome} (${q}x)` : nome;
            });
        },

        parseQuantidadeKit(v) {
            if (v === undefined || v === null || v === '') return 1;
            const s = String(v).trim().replace(/\./g, '').replace(',', '.');
            const n = parseFloat(s);
            return (Number.isFinite(n) && n > 0) ? n : 1;
        },

        aplicarProdutoOuServicoAoItem(item, resultado, produtoVariacaoId) {
            if (item.tipo === 'servico') {
                item.servico_id = resultado.id;
                item.produto_id = null;
                item.produto_variacao_id = null;
                item.produto_nome_base = '';
                item.produto_formato = '';
                item.variacoes_disponiveis = [];
                item.kit_componentes = [];
                item.descricao = resultado.descricao;
                const preco = parseFloat(resultado.preco || 0);
                item.preco_unitario = preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                this.calcularTotais();
                return;
            }
            item.produto_id = resultado.id;
            item.servico_id = null;
            item.produto_formato = resultado.formato || '';
            item.variacoes_disponiveis = Array.isArray(resultado.variacoes) ? [...resultado.variacoes] : [];
            item.produto_nome_base = resultado.descricao || '';

            const vars = resultado.variacoes || [];
            const temVariacoes = (resultado.formato === 'variacao') && vars.length > 0;
            if (temVariacoes) {
                item.produto_variacao_id = produtoVariacaoId;
                const v = vars.find((x) => x.id === produtoVariacaoId);
                const variacaoLabel = v
                    ? (v.referencia_sku || (Array.isArray(v.opcoes_valores) ? v.opcoes_valores.join(' / ') : (v.opcoes_valores || '')))
                    : '';
                item.descricao = variacaoLabel ? (item.produto_nome_base + ' - ' + variacaoLabel) : item.produto_nome_base;
            } else {
                item.produto_variacao_id = null;
                item.descricao = resultado.descricao;
            }

            if (Array.isArray(resultado.kit_componentes) && resultado.kit_componentes.length) {
                item.kit_componentes = [...resultado.kit_componentes];
            } else {
                item.kit_componentes = this.kitComponentesDeKitItens(resultado.kit_itens);
            }

            const preco = parseFloat(resultado.preco || 0);
            item.preco_unitario = preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            this.calcularTotais();
        },

        limparItem(item) {
            item.produto_id = null; item.servico_id = null; item.produto_variacao_id = null;
            item.produto_nome_base = ''; item.produto_formato = ''; item.variacoes_disponiveis = []; item.kit_componentes = [];
            item.descricao = ''; item.busca = ''; item.resultados = [];
        },

        parseBr(v) {
            const s = String(v || '0').replace(/\./g, '').replace(',', '.');
            return parseFloat(s) || 0;
        },

        formatBr(v) {
            return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        calcularItemTotal(item) {
            const sub = this.parseBr(item.preco_unitario) * parseFloat(item.quantidade || 0);
            const desc = this.parseBr(item.desconto);
            return Math.max(sub - desc, 0);
        },

        calcularTotais() {
            let tp = 0, ts = 0, td = 0;
            this.itens.forEach(item => {
                const sub = this.parseBr(item.preco_unitario) * parseFloat(item.quantidade || 0);
                const desc = this.parseBr(item.desconto);
                if (item.tipo === 'servico') ts += sub; else tp += sub;
                td += desc;
            });
            const desGlobal = this.parseBr(this.descontoGlobal);
            const acrGlobal = this.parseBr(this.acrescimosGlobal);
            td += desGlobal;
            this.totalProdutos = tp;
            this.totalServicos = ts;
            this.totalDescontos = td;
            this.totalGeral = Math.max(tp + ts - td + acrGlobal, 0);
        },

        formatMoedaBr(n) {
            return (parseFloat(n) || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        subtotalBrutoItens() {
            let tp = 0, ts = 0;
            this.itens.forEach(item => {
                const sub = this.parseBr(item.preco_unitario) * parseFloat(item.quantidade || 0);
                if (item.tipo === 'servico') ts += sub; else tp += sub;
            });
            return tp + ts;
        },

        somaDescontosSomenteItens() {
            let s = 0;
            this.itens.forEach(item => { s += this.parseBr(item.desconto); });
            return s;
        },

        baseParaDescontoGlobal() {
            const acr = this.parseBr(this.acrescimosGlobal);
            return Math.max(0, this.subtotalBrutoItens() - this.somaDescontosSomenteItens() + acr);
        },

        aoAlterarDescontoItem(item) {
            if (this.podeAplicarDesconto && this.usuarioSujeitoALimiteDesconto) {
                const sub = this.parseBr(item.preco_unitario) * parseFloat(item.quantidade || 0);
                let desc = this.parseBr(item.desconto);
                if (this.limiteDescontoValor != null && desc > this.limiteDescontoValor + 0.009) {
                    alert('Desconto do item acima do limite em reais permitido (R$ ' + this.formatMoedaBr(this.limiteDescontoValor) + ').');
                    desc = this.limiteDescontoValor;
                    item.desconto = this.formatMoedaBr(desc);
                }
                if (this.limiteDescontoPercentual != null && sub > 0.009) {
                    const pct = (desc / sub) * 100;
                    if (pct > this.limiteDescontoPercentual + 0.05) {
                        const maxDesc = sub * (this.limiteDescontoPercentual / 100);
                        alert('Desconto do item acima do limite percentual permitido (' + this.limiteDescontoPercentual.toLocaleString('pt-BR') + '%).');
                        item.desconto = this.formatMoedaBr(maxDesc);
                    }
                }
            }
            this.calcularTotais();
        },

        aoAlterarDescontoGlobal() {
            if (this.podeAplicarDesconto && this.usuarioSujeitoALimiteDesconto) {
                const base = this.baseParaDescontoGlobal();
                let dg = this.parseBr(this.descontoGlobal);
                if (base > 0.009) {
                    if (this.limiteDescontoValor != null && dg > this.limiteDescontoValor + 0.009) {
                        alert('Desconto global acima do limite em reais permitido (R$ ' + this.formatMoedaBr(this.limiteDescontoValor) + ').');
                        dg = this.limiteDescontoValor;
                        this.descontoGlobal = this.formatMoedaBr(dg);
                    }
                    if (this.limiteDescontoPercentual != null) {
                        const pct = (dg / base) * 100;
                        if (pct > this.limiteDescontoPercentual + 0.05) {
                            const maxD = base * (this.limiteDescontoPercentual / 100);
                            alert('Desconto global acima do limite percentual permitido (' + this.limiteDescontoPercentual.toLocaleString('pt-BR') + '%).');
                            this.descontoGlobal = this.formatMoedaBr(maxD);
                        }
                    }
                }
            }
            this.calcularTotais();
        },

        @if(!empty($podeImportarOrcamento))
        abrirModalImportarOrcamento() {
            this.modalImportarOrcamento = true;
            this.buscaOrcamento = '';
            this.carregarListaOrcamentos();
        },

        async carregarListaOrcamentos() {
            this.carregandoOrcamentos = true;
            try {
                const p = new URLSearchParams();
                if (this.buscaOrcamento) p.set('q', this.buscaOrcamento);
                if (this.pedidoAtualId) p.set('exceto', this.pedidoAtualId);
                const r = await fetch(`{{ route('pedidos-venda.listar-orcamentos') }}?${p}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.listaOrcamentos = await r.json();
            } catch (e) {
                this.listaOrcamentos = [];
            } finally {
                this.carregandoOrcamentos = false;
            }
        },

        itensImportacaoUrl(id) {
            let url = `{{ url('/pedidos-venda') }}/${id}/itens-importacao`;
            if (this.pedidoAtualId) url += `?exceto=${this.pedidoAtualId}`;
            return url;
        },

        async importarItensDoOrcamento(id) {
            const sel = document.querySelector('select[name="cliente_id"]');
            const clienteAtual = sel ? String(sel.value || '') : '';
            try {
                const r = await fetch(this.itensImportacaoUrl(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await r.json();
                if (!r.ok) {
                    alert(data.message || 'Não foi possível importar os itens.');
                    return;
                }
                if (data.cliente_id && clienteAtual && String(data.cliente_id) !== clienteAtual) {
                    const ok = confirm(`Este orçamento é do cliente "${data.cliente_nome || ''}". O cliente selecionado no pedido atual é outro. Deseja importar os itens mesmo assim?`);
                    if (!ok) return;
                }
                const novos = (data.itens || []).map((i) => ({
                    ...i,
                    _key: this._keyCounter++,
                    busca: '',
                    resultados: [],
                    produto_variacao_id: (i.produto_variacao_id != null && i.produto_variacao_id !== '') ? i.produto_variacao_id : null,
                    variacoes_disponiveis: i.variacoes_disponiveis || [],
                    produto_nome_base: i.produto_nome_base || '',
                    produto_formato: i.produto_formato || '',
                    kit_componentes: i.kit_componentes || [],
                }));
                if (!this.podeAplicarDesconto) {
                    novos.forEach((i) => { i.desconto = '0,00'; });
                }
                if (!novos.length) {
                    alert('Nenhum item retornado.');
                    return;
                }
                this.itens.push(...novos);
                novos.forEach((i) => {
                    if (i.tipo === 'produto' && i.produto_id) {
                        this.sincronizarDetalhesProdutoPorId(i);
                    }
                });
                this.calcularTotais();
                this.modalImportarOrcamento = false;
            } catch (e) {
                alert('Erro ao importar itens.');
            }
        },
        @endif
    };
}
</script>
@include('partials.jodit-assets')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Jodit === 'undefined') return;

    const optsSimples = {
        theme: 'default',
        toolbarAdaptive: true,
        buttons: [
            'bold', 'italic', 'underline', '|',
            'ul', 'ol', '|',
            'link', '|',
            'hr', 'source'
        ],
        height: 360
    };

    document.querySelectorAll('#form-pedido-venda textarea.js-jodit').forEach(function (textarea) {
        if (textarea.jodit) return;
        const editor = Jodit.make(textarea, optsSimples);
        editor.events.on('change', function () {
            textarea.value = editor.value;
        });
    });

    const form = document.getElementById('form-pedido-venda');
    if (form) {
        // Capture: roda antes de outros handlers; disabled não é enviado no POST (sem isso, observações podem ficar presas ao texto da duplicata).
        form.addEventListener('submit', function () {
            document.querySelectorAll('#form-pedido-venda textarea.js-jodit').forEach(function (textarea) {
                textarea.disabled = false;
                textarea.removeAttribute('readonly');
                const instance = textarea.jodit;
                if (instance) {
                    textarea.value = instance.value;
                }
            });
        }, true);
    }
});
</script>

<script>
// Cliente + Matriz/Filial (contatos/endereços em cascata) — mesmo comportamento do modal em Propostas
document.addEventListener('DOMContentLoaded', () => {
    const clienteSelect = document.getElementById('pedido_venda_cliente_id');
    const unidadeSelect = document.getElementById('cliente_unidade_id');
    const contatoSelect = document.getElementById('contato_id');
    const enderecoSelect = document.getElementById('endereco_id');

    const updateSelectVisibility = (select, shouldShow, tryPrincipal = false, autoPick = true) => {
        if (!select) return;
        const options = select.options;
        let selectedHidden = false;
        let selectedHasValue = !!select.value;
        let firstVisibleValue = '';
        let principalVisibleValue = '';

        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            if (!option.value) continue;
            const visible = !!shouldShow(option);
            const nextHidden = !visible;
            if (option.hidden !== nextHidden) option.hidden = nextHidden;
            if (!nextHidden) {
                if (!firstVisibleValue) firstVisibleValue = option.value;
                if (!principalVisibleValue && option.dataset.principal === '1') principalVisibleValue = option.value;
            }
            if (selectedHasValue && option.value === select.value && nextHidden) {
                selectedHidden = true;
            }
        }

        if (selectedHidden) {
            select.value = '';
            selectedHasValue = false;
        }

        if (!selectedHasValue && autoPick) {
            const fallback = tryPrincipal ? (principalVisibleValue || firstVisibleValue) : firstVisibleValue;
            if (fallback) select.value = fallback;
        }
    };

    const filtrarPorCliente = () => {
        if (!clienteSelect) return;
        const clienteId = clienteSelect.value;
        const selectedClienteOption = clienteSelect.options[clienteSelect.selectedIndex] || null;
        const grupo = selectedClienteOption?.dataset?.grupo || '';

        // Unidade: mostrar somente do grupo; manter em branco (matriz) por padrão
        updateSelectVisibility(
            unidadeSelect,
            (option) => !!grupo && option.dataset.grupo === grupo,
            false,
            false
        );

        const baseClienteId = unidadeSelect?.value || clienteId;

        updateSelectVisibility(
            contatoSelect,
            (option) => option.dataset.cliente === baseClienteId,
            true,
            true
        );

        updateSelectVisibility(
            enderecoSelect,
            (option) => option.dataset.cliente === baseClienteId,
            true,
            true
        );
    };

    if (clienteSelect) {
        clienteSelect.addEventListener('change', filtrarPorCliente);
        requestAnimationFrame(() => filtrarPorCliente());
    }
    if (unidadeSelect) {
        unidadeSelect.addEventListener('change', filtrarPorCliente);
    }
});
</script>
@endpush
