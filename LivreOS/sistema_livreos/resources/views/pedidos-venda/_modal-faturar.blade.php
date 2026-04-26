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

{{-- Modal Faturar — pagamento em etapas no core (sem dependência de plugins): resumo, desconto R$/%, formas e confirmação --}}
<div x-show="showFaturar" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     style="display:none">
    <div @click.outside="showFaturar = false" class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-900">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pagamento — Faturar pedido</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pedido->numero_sequencial }}</p>
            </div>
            <button type="button" @click="showFaturar = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('pedidos-venda.faturar', $pedido) }}" class="flex min-h-0 flex-1 flex-col" @submit="validarSubmitFaturar($event)">
            @csrf
            <input type="hidden" name="tipo_pagamento" :value="tipoPagamento">
            <input type="hidden" name="desconto_valor" :value="descontoValorHidden">

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800/80">
                    <span class="text-gray-500">Cliente:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $pedido->cliente?->nome ?? '—' }}</span>
                </div>
                @if($pedido->cliente && $pedido->cliente->limite_credito !== null && $pedido->cliente->limite_credito !== '')
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    <strong>Limite de crédito</strong> (cadastro): R$ {{ number_format((float) $pedido->cliente->limite_credito, 2, ',', '.') }}
                    — vale para <strong>faturado/fiado</strong> e formas <strong>a prazo</strong> (boleto, transferência, cheque etc.); não se aplica a PIX, dinheiro ou cartão.
                </p>
                @endif

                {{-- Resumo cards --}}
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 dark:border-brand-500/30 dark:bg-brand-500/10">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">Total</p>
                        <p class="text-xs font-bold text-brand-800 dark:text-brand-200">R$ <span x-text="formatarValor(totalPedidoBase)"></span></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Desconto</p>
                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200">R$ <span x-text="formatarValor(descontoAplicadoReais)"></span></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Valor pago</p>
                        <p class="text-xs font-bold text-green-600 dark:text-green-400">R$ <span x-text="formatarValor(somaPagamentosFaturar)"></span></p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Saldo</p>
                        <p class="text-xs font-bold" :class="saldoRestanteFaturar <= 0.01 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">R$ <span x-text="formatarValor(saldoRestanteFaturar)"></span></p>
                    </div>
                </div>

                {{-- Desconto R$ / % --}}
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Desconto</label>
                        <div class="flex rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800">
                            <button type="button" class="rounded-md px-2.5 py-1 text-xs font-bold transition-colors"
                                    :class="descontoTipoPagamento === 'valor' ? 'bg-white shadow dark:bg-gray-700 text-brand-600' : 'text-gray-500'"
                                    @click="descontoTipoPagamento = 'valor'">R$</button>
                            <button type="button" class="rounded-md px-2.5 py-1 text-xs font-bold transition-colors"
                                    :class="descontoTipoPagamento === 'percentual' ? 'bg-white shadow dark:bg-gray-700 text-brand-600' : 'text-gray-500'"
                                    @click="descontoTipoPagamento = 'percentual'">%</button>
                        </div>
                    </div>
                    @if(!empty($usuarioSujeitoALimiteDesconto) && isset($limiteDesconto) && ($limiteDesconto->limite_valor || $limiteDesconto->limite_percentual))
                    <p class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        <strong>Limite para seu usuário:</strong>
                        @if($limiteDesconto->limite_valor) máx. R$ {{ number_format((float) $limiteDesconto->limite_valor, 2, ',', '.') }} @endif
                        @if($limiteDesconto->limite_valor && $limiteDesconto->limite_percentual)<span class="mx-1">|</span>@endif
                        @if($limiteDesconto->limite_percentual) máx. {{ number_format((float) $limiteDesconto->limite_percentual, 1, ',', '.') }}% @endif
                        <span class="text-amber-800/90 dark:text-amber-300/90"> (Configurações → Limites de desconto)</span>
                    </p>
                    @endif
                    <div class="flex gap-2">
                        <input type="text" x-model="descontoInputFaturar" placeholder="Valor do desconto"
                               class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                               @keydown.enter.prevent="aplicarDescontoFaturar()"/>
                        <button type="button" class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-xs font-bold text-white hover:bg-brand-700"
                                @click="aplicarDescontoFaturar()">APLICAR</button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Valor a receber após desconto: <strong class="text-brand-600">R$ <span x-text="formatarValor(valorAReceberFaturar)"></span></strong></p>
                </div>

                {{-- Tipo: pagar agora / fiado --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo de pagamento</label>
                    <select x-model="tipoPagamento"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        <option value="agora">Pagar agora</option>
                        <option value="faturado">Faturado / Fiado (conta a receber)</option>
                    </select>
                </div>

                {{-- Fluxo pagamento (seleção de forma → valor → confirmar → lista) --}}
                <template x-if="tipoPagamento === 'agora' && valorAReceberFaturar > 0.01">
                    <div class="space-y-4">
                        <div>
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Formas de pagamento</h3>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="f in formasPagamento" :key="f.id">
                                    <button type="button"
                                            class="flex min-w-[90px] flex-col items-center gap-1 rounded-xl border-2 px-3 py-2 text-center transition-colors"
                                            :class="pagamentoFormaSelecionada == f.id ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700'"
                                            @click="selecionarFormaFaturar(f)">
                                        <span class="text-lg" x-text="iconeFormaFaturar(f.tipo)"></span>
                                        <span class="text-[10px] font-medium leading-tight text-gray-700 dark:text-gray-300" x-text="f.nome"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="pagamentoFormaSelecionada && formaFaturarDetalhe" x-cloak class="space-y-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Valor a receber (<span x-text="formaFaturarDetalhe?.nome"></span>)</label>
                                        <div class="flex items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                                            <span class="text-lg font-bold text-gray-500">R$</span>
                                            <input type="text" x-model="pagamentoValorAtualFaturar" class="flex-1 border-0 bg-transparent text-right text-lg font-bold text-gray-900 focus:ring-0 dark:text-white"/>
                                        </div>
                                    </div>
                                    <div x-show="formaPagamentoEhCartao(formaFaturarDetalhe)" x-cloak class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-xs text-gray-600">Adquirente</label>
                                                <select x-model="pagamentoAdquirenteIdFaturar" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                    <option value="">Selecione</option>
                                                    @foreach($adquirentes as $adq)
                                                    <option value="{{ $adq->id }}">{{ $adq->nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs text-gray-600">Bandeira</label>
                                                <select x-model="pagamentoBandeiraFaturar" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                    <option value="">Selecione</option>
                                                    <template x-for="b in bandeirasPagamento" :key="b.value">
                                                        <option :value="b.value" x-text="b.label"></option>
                                                    </template>
                                                </select>
                                                <p x-show="formaPagamentoEhCartao(formaFaturarDetalhe) && bandeirasPagamento.length === 0" class="mt-1 text-xs text-amber-600">Cadastre bandeiras em Financeiro → Adquirentes.</p>
                                            </div>
                                    </div>
                                    <div x-show="(formaPagamentoEhCartao(formaFaturarDetalhe) || formaPagamentoEhPix(formaFaturarDetalhe)) && formaPermiteParcelasFaturar(formaFaturarDetalhe)" x-cloak>
                                            <label class="mb-1 block text-xs text-gray-600">Parcelas</label>
                                            <select x-model.number="pagamentoParcelasFaturar" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                <template x-for="n in getParcelasOptionsFaturar(formaFaturarDetalhe)" :key="n">
                                                    <option :value="n" x-text="n === 1 ? '1x à vista' : (n + 'x')"></option>
                                                </template>
                                            </select>
                                    </div>
                                    <div x-show="formaPagamentoEhPix(formaFaturarDetalhe)" x-cloak>
                                            <label class="mb-1 block text-xs text-gray-600">Chave PIX de recebimento</label>
                                            <select x-model="pagamentoPixChaveIdFaturar" class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                <option value="">Selecione...</option>
                                                <template x-for="k in getPixChavesFaturar(formaFaturarDetalhe)" :key="k.id">
                                                    <option :value="k.id" x-text="(k.nome || 'Chave PIX') + ' (' + (k.chave || '') + ')'"></option>
                                                </template>
                                            </select>
                                            <p x-show="getPixChavesFaturar(formaFaturarDetalhe).length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nenhuma chave cadastrada nesta forma — a taxa padrão da forma será usada.</p>
                                    </div>
                                    <div x-show="formaPagamentoEhPix(formaFaturarDetalhe) && formaPermiteParcelasFaturar(formaFaturarDetalhe) && pagamentoParcelasFaturar > 1" x-cloak class="rounded-lg border border-gray-100 bg-gray-50/80 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                                            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Datas de cada parcela PIX. Vencimento até hoje: baixa automática. Datas futuras: título em aberto até confirmar no financeiro.</p>
                                            <div class="space-y-2">
                                                <template x-for="(dv, vi) in pagamentoPixParcelaVencimentosFaturar" :key="'pv-'+vi">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <label class="text-xs text-gray-600 dark:text-gray-400 shrink-0">Parcela <span x-text="vi + 1"></span>/<span x-text="pagamentoParcelasFaturar"></span> — vencimento</label>
                                                        <input type="date" x-model="pagamentoPixParcelaVencimentosFaturar[vi]" required
                                                               class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"/>
                                                    </div>
                                                </template>
                                            </div>
                                    </div>
                                    <button type="button" class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 py-2.5 text-sm font-bold text-white hover:bg-gray-800 dark:bg-brand-600 dark:hover:bg-brand-500"
                                            @click="confirmarValorPagamentoFaturar()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Confirmar valor
                                    </button>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Pagamentos inseridos</h3>
                            <div class="space-y-2" x-show="pagamentosInseridos.length">
                                <template x-for="(pag, idx) in pagamentosInseridos" :key="'pi-'+idx">
                                    <div class="flex items-center justify-between border-b border-gray-100 py-2 dark:border-gray-800">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="pag.forma_nome || 'Pagamento'"></p>
                                            <p class="text-xs text-gray-500" x-show="pag.parcelas > 1" x-text="pag.parcelas + 'x parcelado'"></p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-gray-800 dark:text-gray-200">R$ <span x-text="pag.valor"></span></span>
                                            <button type="button" class="text-red-500 hover:text-red-700" @click="removerPagamentoFaturar(idx)">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p x-show="!pagamentosInseridos.length" class="text-sm text-gray-500">Nenhum pagamento. Selecione uma forma e confirme o valor.</p>
                        </div>
                    </div>
                </template>

                <template x-if="tipoPagamento === 'agora' && valorAReceberFaturar <= 0.01">
                    <p class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">Valor a receber zero (após desconto). Não é necessário lançar formas de pagamento.</p>
                </template>

                <template x-if="tipoPagamento === 'faturado'">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        Será gerada <strong>conta a receber</strong> para o valor após desconto (sem baixa no ato).
                    </div>
                </template>
            </div>

            {{-- Campos hidden para envio dos pagamentos --}}
            <template x-for="(pag, idx) in pagamentosInseridos" :key="'hid-'+idx">
                <div class="hidden">
                    <input type="hidden" :name="`pagamentos[${idx}][forma_pagamento_id]`" :value="pag.forma_pagamento_id">
                    <input type="hidden" :name="`pagamentos[${idx}][valor]`" :value="pag.valor">
                    <input type="hidden" :name="`pagamentos[${idx}][parcelas]`" :value="pag.parcelas || 1">
                    <input type="hidden" :name="`pagamentos[${idx}][forma_tipo]`" :value="pag.forma_tipo || ''">
                    <input type="hidden" :name="`pagamentos[${idx}][conta_bancaria_id]`" :value="pag.conta_bancaria_id || ''">
                    <input type="hidden" :name="`pagamentos[${idx}][adquirente_id]`" :value="pag.adquirente_id || ''">
                    <input type="hidden" :name="`pagamentos[${idx}][bandeira]`" :value="pag.bandeira || ''">
                    <input type="hidden" :name="`pagamentos[${idx}][pix_chave_id]`" :value="pag.pix_chave_id || ''">
                    <template x-if="pag.pix_parcela_vencimentos && pag.pix_parcela_vencimentos.length">
                        <template x-for="(venc, j) in pag.pix_parcela_vencimentos" :key="'pvv-'+idx+'-'+j">
                            <input type="hidden" :name="`pagamentos[${idx}][pix_parcela_vencimentos][]`" :value="venc">
                        </template>
                    </template>
                </div>
            </template>

            <div class="flex shrink-0 flex-col gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-800">
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400" x-show="tipoPagamento === 'agora'">
                    <span x-text="saldoRestanteFaturar <= 0.01 ? 'Pagamento concluído' : 'Aguardando valor total'"></span>
                    <span class="font-bold" :class="saldoRestanteFaturar <= 0.01 ? 'text-green-600' : 'text-red-600'" x-text="saldoRestanteFaturar <= 0.01 ? 'OK' : ('Faltam R$ ' + formatarValor(saldoRestanteFaturar))"></span>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showFaturar = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                    <button type="submit"
                            class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="tipoPagamento === 'agora' && valorAReceberFaturar > 0.01 && saldoRestanteFaturar > 0.01">
                        Faturar pedido
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
