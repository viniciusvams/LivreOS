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

@php
    $embed = $embed ?? false;
    $urlBase = route('plugin.pdv.historico-vendas', $embed ? ['embed' => 1] : []);
    $pdvUid = auth()->id();
    $pdvPodeCancelarTotal = $pdvUid ? \Pdv\PdvPermissoes::podeCancelarVendaTotal((int) $pdvUid) : false;
    $pdvPodeCancelarParcial = $pdvUid ? \Pdv\PdvPermissoes::podeCancelarVendaParcial((int) $pdvUid) : false;
    $pdvPodeAlgumCancelamento = $pdvPodeCancelarTotal || $pdvPodeCancelarParcial;
@endphp
<div class="{{ $embed ? 'p-4' : '' }}">
    @if(!$embed)
    <div class="mb-6">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Histórico de Vendas</h1>
        <p class="text-gray-600 dark:text-gray-400">Consulte e gerencie o histórico de vendas do PDV.</p>
    </div>
    @endif

    <div x-data="historicoVendas()" x-init="init()">
        <form method="get" action="{{ $urlBase }}" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            @if($embed)<input type="hidden" name="embed" value="1">@endif
            <div class="flex flex-wrap items-end gap-4">
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Período (de)</label>
                        <input type="date" name="data_de" value="{{ $filtros['data_de'] ?? '' }}" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Período (até)</label>
                        <input type="date" name="data_ate" value="{{ $filtros['data_ate'] ?? '' }}" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                    <select name="status" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        <option value="finalizada" {{ ($filtros['status'] ?? '') === 'finalizada' ? 'selected' : '' }}>Concluída</option>
                        <option value="cancelada" {{ ($filtros['status'] ?? '') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        <option value="aberta" {{ ($filtros['status'] ?? '') === 'aberta' ? 'selected' : '' }}>Aberta</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cliente</label>
                    <select name="cliente_id" class="min-w-[180px] rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        @foreach($clientesParaFiltro as $cl)
                        <option value="{{ $cl->id }}" {{ ($filtros['cliente_id'] ?? '') == $cl->id ? 'selected' : '' }}>{{ $cl->nome ?? $cl->razao_social ?? '#' . $cl->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nº da venda</label>
                    <input type="text" name="numero_venda" value="{{ $filtros['numero_venda'] ?? '' }}" placeholder="Ex: 1" class="w-28 rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $urlBase }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Limpar filtros</a>
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">Pesquisar</button>
                    @if(!$vendas->isEmpty())
                    <a href="{{ route('plugin.pdv.historico-vendas.export-csv', request()->only(['data_de', 'data_ate', 'status', 'cliente_id', 'cliente_nome', 'numero_venda'])) }}" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Exportar CSV
                    </a>
                    <a href="{{ route('plugin.pdv.historico-vendas.export-pdf', request()->only(['data_de', 'data_ate', 'status', 'cliente_id', 'cliente_nome', 'numero_venda'])) }}" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Exportar PDF
                    </a>
                    @endif
                </div>
            </div>
        </form>

        @if($vendas->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nenhuma venda encontrada.</p>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Nº da venda</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Data/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Valor total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($vendas as $v)
                        <tr class="bg-white dark:bg-gray-800">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? '—') : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">R$ {{ number_format($v->total_final, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if($v->status === 'finalizada')
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">Concluída</span>
                                @elseif($v->status === 'cancelada')
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">Cancelada</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $v->status }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button"
                                        @click="abrirModalItens({{ $v->id }})"
                                        class="inline-flex items-center rounded p-1.5 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                        title="Ver itens vendidos">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    @if($v->status === 'finalizada' && $pdvPodeAlgumCancelamento)
                                        <button type="button"
                                            @click="abrirModalCancelar({{ $v->id }})"
                                            class="inline-flex items-center rounded p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="Excluir / Cancelar venda">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 w-9">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-700/30">
                    {{ $vendas->withQueryString()->links() }}
                </div>
            </div>
        @endif

        {{-- Modal: Ver itens vendidos --}}
        <div x-show="modalItensAberto"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50" @click="fecharModalItens()"></div>
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Itens da venda <span x-text="vendaItensNumero ? '#' + vendaItensNumero : ''"></span>
                    </h3>
                    <button type="button" @click="fecharModalItens()" class="rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto p-4">
                    <template x-if="carregandoItens">
                        <p class="text-center text-gray-500 dark:text-gray-400">Carregando...</p>
                    </template>
                    <template x-if="!carregandoItens && vendaItens && vendaItens.length">
                        <ul class="space-y-2">
                            <template x-for="(item, i) in vendaItens" :key="i">
                                <li class="flex justify-between rounded border border-gray-100 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-700/50">
                                    <span class="text-sm text-gray-800 dark:text-gray-200" x-text="(item.descricao || 'Item') + ' × ' + (item.quantidade || 0)"></span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="'R$ ' + (item.total != null ? Number(item.total).toFixed(2).replace('.', ',') : '0,00')"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                    <template x-if="!carregandoItens && vendaItens && vendaItens.length">
                        <div class="mt-3 space-y-1 border-t border-gray-200 pt-3 dark:border-gray-700">
                            <template x-if="vendaItensDesconto != null && vendaItensDesconto > 0">
                                <div class="flex justify-between text-sm text-orange-600 dark:text-orange-400">
                                    <span>Desconto</span>
                                    <span x-text="'- R$ ' + Number(vendaItensDesconto).toFixed(2).replace('.', ',')"></span>
                                </div>
                            </template>
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span x-text="'R$ ' + (vendaItensTotal != null ? Number(vendaItensTotal).toFixed(2).replace('.', ',') : '0,00')"></span>
                            </div>
                            <template x-if="vendaItensObservacoes">
                                <div class="mt-2 rounded border border-gray-100 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                    <span class="font-medium">Observação:</span>
                                    <p class="mt-0.5 whitespace-pre-wrap" x-text="vendaItensObservacoes"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!carregandoItens && (!vendaItens || !vendaItens.length) && erroItens === null">
                        <div class="space-y-2">
                            <p class="text-center text-gray-500 dark:text-gray-400">Nenhum item encontrado.</p>
                            <template x-if="vendaItensTotal != null">
                                <div class="flex justify-between border-t border-gray-200 pt-3 font-semibold dark:border-gray-700">
                                    <span>Total</span>
                                    <span x-text="'R$ ' + Number(vendaItensTotal).toFixed(2).replace('.', ',')"></span>
                                </div>
                            </template>
                            <template x-if="vendaItensDesconto != null && vendaItensDesconto > 0">
                                <div class="flex justify-between text-sm text-orange-600 dark:text-orange-400">
                                    <span>Desconto</span>
                                    <span x-text="'- R$ ' + Number(vendaItensDesconto).toFixed(2).replace('.', ',')"></span>
                                </div>
                            </template>
                            <template x-if="vendaItensObservacoes">
                                <div class="rounded border border-gray-100 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                    <span class="font-medium">Observação:</span>
                                    <p class="mt-0.5 whitespace-pre-wrap" x-text="vendaItensObservacoes"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="erroItens">
                        <p class="text-center text-red-600 dark:text-red-400" x-text="erroItens"></p>
                    </template>
                </div>
            </div>
        </div>

        {{-- Modal: Confirmar cancelamento (com motivo) — visual igual à tela de confirmação --}}
        <div x-show="modalAberto"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-black/50" @click="fecharModal()"></div>
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Confirmar cancelamento</h3>
                <p class="mb-3 text-gray-600 dark:text-gray-400" x-show="vendaNumero">
                    Venda <span x-text="'#' + vendaNumero"></span>. Escolha o tipo de cancelamento e informe o motivo.
                </p>
                <div class="mb-4 space-y-2" x-show="podeCancelarVendaTotal || podeCancelarVendaParcial">
                    <div class="flex flex-col gap-2" x-show="podeCancelarVendaTotal && podeCancelarVendaParcial">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" value="total" x-model="tipoCancelamento" class="rounded border-gray-300">
                            Cancelar venda inteira (restante)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="radio" value="parcial" x-model="tipoCancelamento" class="rounded border-gray-300">
                            Cancelar só alguns itens
                        </label>
                    </div>
                    <p x-show="podeCancelarVendaParcial && !podeCancelarVendaTotal" class="text-xs text-gray-500 dark:text-gray-400">Apenas cancelamento parcial permitido para seu usuário.</p>
                    <p x-show="podeCancelarVendaTotal && !podeCancelarVendaParcial" class="text-xs text-gray-500 dark:text-gray-400">Apenas cancelamento total permitido para seu usuário.</p>
                    <div x-show="tipoCancelamento === 'parcial' && podeCancelarVendaParcial" class="max-h-40 overflow-y-auto rounded border border-gray-200 p-2 dark:border-gray-600">
                        <template x-for="it in parcialItens" :key="it.id">
                            <div x-show="it.disponivel > 0" class="mb-2 border-b border-gray-100 pb-2 text-xs dark:border-gray-600">
                                <div class="font-medium text-gray-800 dark:text-gray-200" x-text="it.descricao"></div>
                                <div class="text-gray-500" x-text="'Disponível: ' + it.disponivel"></div>
                                <input type="text" x-model="it.inputCancelar" class="mt-1 w-full rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Qtd">
                            </div>
                        </template>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do cancelamento <span class="text-red-500">*</span></label>
                    <textarea x-model="motivoCancelamento"
                        placeholder="Informe o motivo do cancelamento..."
                        rows="3"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"></textarea>
                    <p x-show="erroMotivo" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400">Informe o motivo do cancelamento.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                        @click="fecharModal()"
                        class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button"
                        @click="confirmarCancelamento()"
                        :disabled="cancelando"
                        class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 dark:bg-red-500 dark:hover:bg-red-600">
                        <span x-text="cancelando ? 'Cancelando...' : 'Confirmar cancelamento'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function historicoVendas() {
        return {
            modalAberto: false,
            vendaId: null,
            vendaNumero: '',
            motivoCancelamento: '',
            erroMotivo: false,
            cancelando: false,
            podeCancelarVendaTotal: @json($pdvPodeCancelarTotal),
            podeCancelarVendaParcial: @json($pdvPodeCancelarParcial),
            tipoCancelamento: 'total',
            parcialItens: [],
            carregandoParcial: false,

            modalItensAberto: false,
            vendaItens: null,
            vendaItensNumero: '',
            vendaItensTotal: null,
            vendaItensDesconto: null,
            vendaItensObservacoes: null,
            carregandoItens: false,
            erroItens: null,

            init() {
                if (this.podeCancelarVendaTotal && !this.podeCancelarVendaParcial) this.tipoCancelamento = 'total';
                else if (!this.podeCancelarVendaTotal && this.podeCancelarVendaParcial) this.tipoCancelamento = 'parcial';
                else this.tipoCancelamento = 'total';
            },
            async abrirModalItens(id) {
                this.modalItensAberto = true;
                this.vendaItens = null;
                this.vendaItensNumero = String(id).padStart(5, '0');
                this.vendaItensTotal = null;
                this.carregandoItens = true;
                this.erroItens = null;
                try {
                    const r = await fetch('{{ url("/plugin/pdv/api/vendas") }}/' + id, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (d.venda && d.venda.itens) {
                        this.vendaItens = d.venda.itens;
                        this.vendaItensTotal = d.venda.total_final;
                        this.vendaItensDesconto = d.venda.desconto != null ? parseFloat(d.venda.desconto) : null;
                        this.vendaItensObservacoes = (d.venda.observacoes || '').trim() || null;
                    } else {
                        this.erroItens = d.message || 'Venda não encontrada.';
                    }
                } catch (e) {
                    this.erroItens = e.message || 'Erro ao carregar itens.';
                }
                this.carregandoItens = false;
            },
            fecharModalItens() {
                this.modalItensAberto = false;
                this.vendaItens = null;
                this.vendaItensNumero = '';
                this.vendaItensTotal = null;
                this.vendaItensDesconto = null;
                this.vendaItensObservacoes = null;
                this.erroItens = null;
            },
            async abrirModalCancelar(id) {
                this.vendaId = id;
                this.vendaNumero = String(id).padStart(5, '0');
                this.motivoCancelamento = '';
                this.erroMotivo = false;
                if (this.podeCancelarVendaTotal && !this.podeCancelarVendaParcial) this.tipoCancelamento = 'total';
                else if (!this.podeCancelarVendaTotal && this.podeCancelarVendaParcial) this.tipoCancelamento = 'parcial';
                else this.tipoCancelamento = 'total';
                this.parcialItens = [];
                this.carregandoParcial = true;
                this.modalAberto = true;
                try {
                    const r = await fetch('{{ url("/plugin/pdv/api/vendas") }}/' + id, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (d.venda && Array.isArray(d.venda.itens)) {
                        this.parcialItens = d.venda.itens.map(it => {
                            const q = Number(it.quantidade) || 0;
                            const qc = Number(it.quantidade_cancelada) || 0;
                            const disp = Math.max(0, q - qc);
                            return { id: it.id, descricao: it.descricao || 'Item', disponivel: disp, inputCancelar: '' };
                        });
                    }
                } catch (e) { this.parcialItens = []; }
                this.carregandoParcial = false;
            },
            fecharModal() {
                if (this.cancelando) return;
                this.modalAberto = false;
                this.vendaId = null;
                this.vendaNumero = '';
                this.motivoCancelamento = '';
                this.erroMotivo = false;
            },
            confirmarCancelamento() {
                if (!this.vendaId || this.cancelando) return;
                const motivo = typeof this.motivoCancelamento === 'string' ? this.motivoCancelamento.trim() : '';
                if (motivo === '') {
                    this.erroMotivo = true;
                    return;
                }
                this.erroMotivo = false;
                const payload = { motivo_cancelamento: motivo };
                if (this.tipoCancelamento === 'parcial' && this.podeCancelarVendaParcial) {
                    const itens = [];
                    for (const it of this.parcialItens) {
                        const raw = String(it.inputCancelar || '').trim().replace(',', '.');
                        const q = parseFloat(raw) || 0;
                        if (q <= 0) continue;
                        if (q > it.disponivel + 0.0001) {
                            if (typeof window.toast !== 'undefined') window.toast.error('Quantidade inválida em "' + (it.descricao || '') + '".');
                            else alert('Quantidade inválida.');
                            return;
                        }
                        itens.push({ item_id: it.id, quantidade_cancelar: q });
                    }
                    if (itens.length === 0) {
                        if (typeof window.toast !== 'undefined') window.toast.error('Informe quantidade em ao menos um item.');
                        else alert('Informe quantidade em ao menos um item.');
                        return;
                    }
                    payload.tipo = 'parcial';
                    payload.itens = itens;
                } else {
                    payload.tipo = 'total';
                }
                const url = '{{ url("/plugin/pdv/api/vendas") }}/' + this.vendaId + '/cancelar';
                this.cancelando = true;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    this.cancelando = false;
                    this.fecharModal();
                    if (ok && data.message) {
                        if (typeof window.toast !== 'undefined') window.toast.success(data.message);
                        else alert(data.message);
                    } else if (!ok && data.message) {
                        if (typeof window.toast !== 'undefined') window.toast.error(data.message);
                        else alert(data.message);
                        return;
                    }
                    window.location.reload();
                })
                .catch(err => {
                    this.cancelando = false;
                    const msg = err.message || 'Erro ao cancelar venda.';
                    if (typeof window.toast !== 'undefined') window.toast.error(msg);
                    else alert(msg);
                });
            },
        };
    }
    </script>

    <style>
    [x-cloak] { display: none !important; }
    </style>
</div>
