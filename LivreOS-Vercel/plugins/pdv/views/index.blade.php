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

@extends('layouts.fullscreen-layout')

@section('content')
{{-- Design system Stitch: fontes e variáveis --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL@24,400,0&display=swap" rel="stylesheet"/>
{{-- Container para notificações toast do PDV --}}
<div id="pdv-toast-container" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-[99999] flex flex-col gap-2 pointer-events-none" style="max-width: 90vw;"></div>
<script>
(function() {
    function pdvNotify(message, type) {
        const container = document.getElementById('pdv-toast-container');
        if (!container) return;
        const el = document.createElement('div');
        const types = { success: 'bg-emerald-600 text-white', error: 'bg-red-600 text-white', warning: 'bg-amber-500 text-white' };
        el.className = 'rounded-xl px-4 py-3 text-sm font-medium shadow-lg ' + (types[type] || types.success);
        el.textContent = message;
        container.appendChild(el);
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s';
            setTimeout(function() { el.remove(); }, 300);
        }, 3500);
    }
    window.toast = window.toast || {};
    window.toast.success = function(msg) { pdvNotify(msg || 'Sucesso', 'success'); };
    window.toast.error = function(msg) { pdvNotify(msg || 'Erro', 'error'); };
    window.toast.warning = function(msg) { pdvNotify(msg || 'Atenção', 'warning'); };
})();
</script>
<div id="pdv-app" class="pdv-stitch" x-data="pdvApp()" x-init="init()" @keydown.window="atalhoTeclado($event)">
    {{-- Modal Ajuda — Como funciona o PDV (para quem não conhece o sistema) --}}
    <div x-show="mostrarAjuda" x-cloak class="pdv-modal pdv-modal-stitch" style="z-index: 55;" @keydown.escape.window="mostrarAjuda = false">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-lg mx-auto max-h-[90vh] flex flex-col">
            <header class="flex items-center p-4 border-b border-primary/10 shrink-0 bg-white dark:bg-slate-900">
                <button type="button" @click="mostrarAjuda = false" class="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex-1 ml-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">help</span>
                    Como usar o PDV
                </h2>
            </header>
            <div class="p-4 overflow-y-auto flex-1 min-h-0 space-y-6 text-sm text-slate-700 dark:text-slate-300">
                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">store</span>
                        O que é o PDV?
                    </h3>
                    <p class="mb-2">O <strong>PDV (Ponto de Venda)</strong> é o módulo onde você registra as vendas do dia. Aqui você adiciona produtos e serviços ao carrinho, identifica o cliente (se quiser), aplica descontos e recebe o pagamento. Tudo fica vinculado ao caixa aberto no momento.</p>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">lock_open</span>
                        Antes de começar: abrir o caixa
                    </h3>
                    <p class="mb-2">Para vender, é necessário que um <strong>caixa esteja aberto</strong>. Na tela inicial, informe o valor de abertura (quanto em dinheiro há no caixa) e clique em <strong>Abrir caixa</strong>. Se não houver caixa aberto, o sistema não permite registrar vendas. Ao final do turno, use <strong>Fechar caixa</strong> no menu do caixa.</p>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">add_shopping_cart</span>
                        Como fazer uma venda
                    </h3>
                    <ol class="list-decimal list-inside space-y-2 mb-2">
                        <li><strong>Adicionar itens</strong> — Use o campo de busca no topo para digitar o nome ou código do produto/serviço. Selecione na lista e informe a quantidade. Você também pode usar um leitor de código de barras (bipagem): posicione o cursor no campo e passe o produto no leitor.</li>
                        <li><strong>Identificar o cliente (opcional)</strong> — Toque em <strong>Identificar cliente</strong> para escolher “Cliente balcão” (venda sem CPF/CNPJ) ou buscar um cliente pelo nome ou documento. Clientes bloqueados para vendas exigem senha de um autorizador.</li>
                        <li><strong>Desconto (opcional)</strong> — Se precisar dar desconto, ajuste na área de totais. Descontos acima do permitido podem exigir senha do administrador ou autorizador.</li>
                        <li><strong>Finalizar</strong> — Toque em <strong>Finalizar venda</strong> (ou F3). Informe as formas de pagamento (dinheiro, PIX, cartão, boleto etc.) e os valores. A soma dos pagamentos deve bater com o total. Se o cliente tiver limite de crédito e você usar boleto acima do limite, será pedida autorização.</li>
                        <li><strong>Concluir</strong> — Após confirmar, a venda é registrada e você pode imprimir o comprovante ou iniciar uma nova venda.</li>
                    </ol>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">person_off</span>
                        Cliente bloqueado ou limite de crédito
                    </h3>
                    <p class="mb-2">Alguns clientes podem estar <strong>bloqueados para vendas</strong> ou com <strong>limite de crédito</strong> (para boleto). Nesses casos, o sistema pede a <strong>senha do administrador ou de um usuário autorizador</strong>. Quem tiver permissão pode digitar a senha e liberar a venda. Essas liberações ficam registradas no histórico.</p>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">account_balance_wallet</span>
                        Reforço e sangria
                    </h3>
                    <p class="mb-2">Pelo <strong>menu do caixa</strong> (ícone no canto superior):</p>
                    <ul class="list-disc list-inside space-y-1 mb-2">
                        <li><strong>Reforço (suprimento)</strong> — Entrada de dinheiro no caixa (ex.: troco trazido de fora).</li>
                        <li><strong>Sangria</strong> — Retirada de dinheiro do caixa. Pode exigir senha do autorizador, conforme configuração.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">description</span>
                        Orçamentos
                    </h3>
                    <p class="mb-2">Você pode <strong>salvar o carrinho como orçamento</strong> (sem finalizar venda) e depois abrir em <strong>Orç. salvos</strong> para converter em venda ou imprimir. Útil para orçar e fechar a venda em outro momento.</p>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">history</span>
                        Histórico e cancelamento
                    </h3>
                    <p class="mb-2">Em <strong>Histórico</strong> você vê as vendas do caixa atual. É possível ver detalhes e, quando permitido, <strong>cancelar uma venda</strong>. O cancelamento pode exigir senha do autorizador. Use apenas em casos de erro ou devolução.</p>
                </section>

                <section>
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">keyboard</span>
                        Atalhos de teclado
                    </h3>
                    <ul class="space-y-1">
                        <li><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-mono">F1</kbd> Abrir esta ajuda</li>
                        <li><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-mono">F2</kbd> Pesquisar produto / novo item</li>
                        <li><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-mono">F3</kbd> Finalizar venda</li>
                        <li><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-mono">F4</kbd> Cancelar último item do carrinho</li>
                        <li><kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-xs font-mono">Enter</kbd> no campo de código — adiciona item por leitor de código de barras</li>
                    </ul>
                </section>
            </div>
            <footer class="p-4 border-t border-primary/10 shrink-0 bg-white dark:bg-slate-900">
                <button type="button" @click="mostrarAjuda = false" class="w-full py-3 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition-colors">Fechar</button>
            </footer>
        </div>
    </div>

    {{-- Modal Identificar Cliente: tela seleção OU tela só cadastro (celular) --}}
    <div x-show="mostrarCliente" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="telaClienteModal === 'cadastro' ? (telaClienteModal = 'selecao') : (mostrarCliente = false)">
        <div class="pdv-stitch-modal-content bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl max-h-[90vh] min-h-[85vh] sm:min-h-0 flex flex-col w-full max-w-lg mx-auto">
            {{-- Header: título muda conforme tela --}}
            <header class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <template x-if="telaClienteModal === 'cadastro'">
                        <button type="button" @click="telaClienteModal = 'selecao'" class="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0" title="Voltar">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </button>
                    </template>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white truncate" x-text="telaClienteModal === 'cadastro' ? 'Novo Cliente' : 'Identificar Cliente'"></h2>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <template x-if="telaClienteModal === 'selecao'">
                        <button type="button" @click="telaClienteModal = 'cadastro'; mostrarNovoCliente = true" class="bg-primary/10 text-primary hover:bg-primary/20 px-3 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <span class="material-symbols-outlined text-sm">person_add</span>
                            Novo Cliente
                        </button>
                    </template>
                    <button type="button" @click="telaClienteModal = 'selecao'; mostrarCliente = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </header>
            <main class="flex-1 min-h-0 overflow-y-auto flex flex-col">
                {{-- Tela 1: Seleção (Cliente balcão + busca + resultados) --}}
                <div x-show="telaClienteModal === 'selecao'" class="p-4 flex flex-col gap-4 flex-1 min-h-0">
                    <button type="button" @click="escolherClienteBalcão(); mostrarCliente = false" class="w-full flex items-center gap-4 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-primary/50 transition-colors text-left">
                        <div class="size-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-slate-500">person_off</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-900 dark:text-white">Cliente balcão</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Venda sem identificar cliente</p>
                        </div>
                    </button>
                    <div class="flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus-within:ring-2 focus-within:ring-primary/20">
                        <span class="material-symbols-outlined pl-3 pr-1 text-slate-400 shrink-0 text-[22px] leading-none">search</span>
                        <input type="text" x-model="clienteBuscaModal" @input.debounce.300ms="buscarClientesModal()" placeholder="Buscar por nome ou CPF/CNPJ..." class="flex-1 min-w-0 py-3 pr-4 bg-transparent border-0 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-none">
                    </div>
                    <p x-show="carregandoClientes" class="text-slate-500 dark:text-slate-400 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined animate-pulse text-primary/60">progress_activity</span> Buscando...
                    </p>
                    <section class="flex-1 min-h-0 flex flex-col">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Resultados</h3>
                        <div class="space-y-2 flex-1 min-h-0 overflow-y-auto">
                            <template x-for="c in clientesResultados" :key="c.id">
                                <button type="button" @click="aoClicarCliente(c)" class="w-full flex items-center gap-4 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-primary hover:bg-primary/5 dark:hover:bg-white/5 transition-colors text-left">
                                    <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-primary">person</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900 dark:text-white truncate" x-text="c.nome"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate" x-text="c.documento ? 'Doc: ' + c.documento : ''"></p>
                                    </div>
                                    <span x-show="c.bloqueado_vendas" class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Bloqueado</span>
                                    <span class="material-symbols-outlined text-primary shrink-0">chevron_right</span>
                                </button>
                            </template>
                            <p x-show="!carregandoClientes && clientesResultados.length === 0 && clienteBuscaModal.length >= 2" class="text-slate-500 dark:text-slate-400 text-sm py-2">Nenhum cliente encontrado.</p>
                            <p x-show="!carregandoClientes && clientesResultados.length === 0 && clienteBuscaModal.length < 2" class="text-slate-500 dark:text-slate-400 text-sm py-2">Digite para buscar.</p>
                        </div>
                    </section>
                </div>
                {{-- Tela 2: Só cadastro (ocupa toda a área; bom para celular) --}}
                <div x-show="telaClienteModal === 'cadastro'" class="p-4 flex-1 min-h-0 overflow-y-auto flex flex-col">
                    <div class="space-y-3 pb-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Tipo</label>
                            <select x-model="novoClienteTipo" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                <option value="F">Pessoa Física</option>
                                <option value="J">Pessoa Jurídica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1" x-text="novoClienteTipo === 'F' ? 'Nome completo' : 'Razão social / Nome fantasia'"></label>
                            <input type="text" x-model="novoClienteNome" :placeholder="novoClienteTipo === 'F' ? 'Nome completo' : 'Razão social'" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                        </div>
                        <template x-if="novoClienteTipo === 'F'">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">CPF</label>
                                <input type="text" :value="novoClienteCpf" @input="novoClienteCpf = mascaraCpf($event.target.value); erroNovoClienteCpf = ''" @blur="if ((novoClienteCpf || '').trim()) erroNovoClienteCpf = validarCpf(novoClienteCpf)" placeholder="000.000.000-00" maxlength="14" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm" :class="{ 'border-red-500 dark:border-red-400': erroNovoClienteCpf }">
                                <p x-show="erroNovoClienteCpf" class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="erroNovoClienteCpf"></p>
                            </div>
                        </template>
                        <template x-if="novoClienteTipo === 'J'">
                            <div class="flex gap-2">
                                <div class="flex-1 min-w-0">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">CNPJ (numérico ou alfanumérico)</label>
                                    <input type="text" :value="novoClienteCnpj" @input="novoClienteCnpj = mascaraCnpj($event.target.value); erroNovoClienteCnpj = ''" @blur="if ((novoClienteCnpj || '').trim()) erroNovoClienteCnpj = validarCnpj(novoClienteCnpj)" placeholder="00.000.000/0001-00" maxlength="18" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm" :class="{ 'border-red-500 dark:border-red-400': erroNovoClienteCnpj }">
                                    <p x-show="erroNovoClienteCnpj" class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="erroNovoClienteCnpj"></p>
                                </div>
                                <button type="button" @click="buscarCnpjNovoCliente()" :disabled="carregandoCnpj || !novoClienteCnpj.trim()" class="self-end pb-2 px-3 py-2.5 rounded-lg bg-primary/10 text-primary font-medium text-sm hover:bg-primary/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1 shrink-0">
                                    <span class="material-symbols-outlined text-lg" x-text="carregandoCnpj ? 'progress_activity' : 'search'"></span>
                                    <span x-text="carregandoCnpj ? 'Buscando...' : 'Buscar'"></span>
                                </button>
                            </div>
                        </template>
                        {{-- Contato: nome (PJ), telefone, WhatsApp, email --}}
                        <div class="border-t border-slate-200 dark:border-slate-600 pt-3 mt-1">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2">Contato</p>
                            <template x-if="novoClienteTipo === 'J'">
                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nome do contato</label>
                                    <input type="text" x-model="novoClienteContatoNome" placeholder="Nome da pessoa de contato" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                            </template>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1 min-w-0">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Telefone</label>
                                    <input type="text" x-model="novoClienteTelefone" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                                <label class="flex items-center gap-2 pb-2.5 shrink-0 cursor-pointer">
                                    <input type="checkbox" x-model="novoClienteWhatsapp" class="rounded border-slate-300 text-primary focus:ring-primary/20">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">WhatsApp</span>
                                </label>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">E-mail</label>
                                <input type="email" x-model="novoClienteEmail" placeholder="email@exemplo.com" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                            </div>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-600 pt-3 mt-1">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2">Endereço</p>
                            <div class="flex gap-2">
                                <div class="w-28 shrink-0">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">CEP</label>
                                    <input type="text" x-model="novoClienteCep" @blur="buscarCepNovoCliente()" placeholder="00000-000" maxlength="9" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Logradouro</label>
                                    <input type="text" x-model="novoClienteLogradouro" placeholder="Rua, av." class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 mt-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nº</label>
                                    <input type="text" x-model="novoClienteNumero" placeholder="Nº" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Complemento</label>
                                    <input type="text" x-model="novoClienteComplemento" placeholder="Apto, sala" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Bairro</label>
                                    <input type="text" x-model="novoClienteBairro" placeholder="Bairro" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cidade</label>
                                    <input type="text" x-model="novoClienteCidade" placeholder="Cidade" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm">
                                </div>
                            </div>
                            <div class="mt-2 w-20">
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">UF</label>
                                <input type="text" x-model="novoClienteEstado" placeholder="UF" maxlength="2" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-slate-900 dark:text-white text-sm uppercase">
                            </div>
                        </div>
                        <p x-show="erroNovoCliente" class="text-red-600 text-xs" x-text="erroNovoCliente"></p>
                        <button type="button" @click="cadastrarNovoCliente()" class="w-full bg-primary text-white font-semibold py-3 rounded-lg text-sm hover:bg-primary/90 mt-2">Cadastrar e selecionar</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- Tela de abertura — idêntica ao Stitch (abertura_de_caixa/code.html) --}}
    <template x-if="!caixa && tela === 'abertura'">
        <div class="pdv-stitch-screen pdv-stitch-abertura bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen flex flex-col">
            <header class="flex items-center bg-background-light dark:bg-background-dark p-4 sticky top-0 z-10 border-b border-primary/10">
                <div class="size-10 shrink-0"></div>
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight flex-1 text-center pr-10">PDV Móvel</h2>
            </header>
            <main class="flex-1 flex flex-col max-w-md mx-auto w-full px-6 pt-8 pb-4">
                <template x-if="!podeAbrirCaixa">
                    <div class="flex-1 flex flex-col justify-center text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-amber-500 dark:text-amber-400 mb-4">lock</span>
                        <h1 class="text-slate-900 dark:text-white text-2xl font-bold tracking-tight mb-2">Você não está habilitado a abrir caixa</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-base mb-4">Peça a um administrador para liberar seu usuário em <strong>PDV → Habilitar caixa por usuário</strong>.</p>
                        <a :href="baseUrl.replace(/\/$/, '') + '/caixas'" class="text-primary font-semibold hover:underline">Ir para Gerenciar caixas</a>
                    </div>
                </template>
                <template x-if="podeAbrirCaixa">
                <div class="flex-1 flex flex-col">
                <div class="text-center mb-8">
                    <h1 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight mb-2">Abertura de Caixa</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-base">Informe o valor de fundo para iniciar as operações do dia</p>
                </div>
                <div class="flex-1 flex flex-col justify-center items-center mb-8">
                    <label class="text-primary font-semibold text-sm uppercase tracking-wider mb-2">Valor de Fundo</label>
                    <div class="relative w-full text-center">
                        <span class="text-slate-400 text-2xl font-medium mr-1">R$</span>
                        <span class="bg-transparent border-none text-slate-900 dark:text-white text-6xl font-bold text-center w-full p-0" x-text="valorFundoDisplay"></span>
                    </div>
                    <div class="h-1 w-24 bg-primary mx-auto mt-2 rounded-full opacity-30"></div>
                </div>
                <div class="mb-6">
                    <button type="button" @click="abrirCaixa()" :disabled="abrindo" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined">login</span>
                        <span>Abrir Caixa</span>
                    </button>
                </div>
                <p x-show="erroAbertura" class="text-red-600 text-sm text-center mb-2" x-text="erroAbertura"></p>
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <template x-for="n in [1,2,3,4,5,6,7,8,9]">
                        <button type="button" @click="keypadDigito(n)" class="h-16 flex items-center justify-center bg-white dark:bg-slate-800 rounded-xl text-2xl font-semibold shadow-sm border border-slate-200 dark:border-slate-700 active:scale-95 transition-transform text-slate-900 dark:text-slate-100" x-text="n"></button>
                    </template>
                    <button type="button" @click="keypadDigito(',')" class="h-16 flex items-center justify-center bg-slate-100 dark:bg-slate-900 rounded-xl text-2xl font-semibold active:scale-95 transition-transform text-slate-500">,</button>
                    <button type="button" @click="keypadDigito(0)" class="h-16 flex items-center justify-center bg-white dark:bg-slate-800 rounded-xl text-2xl font-semibold shadow-sm border border-slate-200 dark:border-slate-700 active:scale-95 transition-transform text-slate-900 dark:text-slate-100">0</button>
                    <button type="button" @click="keypadBackspace()" class="h-16 flex items-center justify-center bg-slate-100 dark:bg-slate-900 rounded-xl text-primary active:scale-95 transition-transform">
                        <span class="material-symbols-outlined text-3xl">backspace</span>
                    </button>
                </div>
                </div>
                </template>
            </main>
            <div class="fixed -bottom-24 -right-24 size-64 bg-primary/5 rounded-full blur-3xl -z-10"></div>
            <div class="fixed -top-24 -left-24 size-64 bg-primary/5 rounded-full blur-3xl -z-10"></div>
        </div>
    </template>

    {{-- PDV Venda Rápida — idêntico ao Stitch (pdv_-_venda_rápida_(atualizado)/code.html) --}}
    <template x-if="caixa && tela === 'venda'">
        <div class="pdv-stitch-screen pdv-stitch-venda bg-background-light dark:bg-background-dark min-h-screen font-display flex flex-col">
            <header class="sticky top-0 z-10 bg-white dark:bg-background-dark border-b border-primary/10 px-4 pt-4 pb-3 shadow-sm">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <div class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden">
                        <button type="button" @click="telaClienteModal = 'selecao'; mostrarCliente = true; clienteBuscaModal = ''; $nextTick(() => buscarClientesModal())" class="shrink-0 flex items-center gap-1.5 p-2 rounded-xl hover:bg-primary/5 text-slate-600 dark:text-slate-300 hover:text-primary transition-colors" :title="clienteId ? 'Alterar cliente' : 'Identificar cliente'">
                            <span class="material-symbols-outlined text-[22px]">person</span>
                            <span class="text-sm font-medium truncate max-w-[100px] sm:max-w-[120px]" x-text="clienteNome || 'Cliente balcão'"></span>
                        </button>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white truncate min-w-0 shrink">Venda Rápida</h1>
                    </div>
                    <div class="relative shrink-0 w-10 flex justify-end" x-data="{ menuCaixaAberto: false }" @click.outside="menuCaixaAberto = false">
                        <button type="button" @click="menuCaixaAberto = !menuCaixaAberto" class="text-slate-600 dark:text-slate-300 p-2 rounded-lg flex items-center justify-center hover:bg-primary/5" title="Menu caixa">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                        <div x-show="menuCaixaAberto" x-cloak
                             class="absolute right-0 top-full mt-1 z-50 min-w-[180px] py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg">
                            <button type="button" @click="abrirHistorico(); menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">history</span>
                                <span>Histórico</span>
                            </button>
                            <button type="button" @click="mostrarAjuda = true; menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">help</span>
                                <span>Ajuda</span>
                            </button>
                            <button type="button" @click="mostrarReforco = true; menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">add_circle</span>
                                <span>Reforço</span>
                            </button>
                            <button type="button" @click="mostrarSangria = true; sangriaRequerSenha = false; sangriaAutorizadorNome = ''; sangriaSenhaAutorizacao = ''; menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">remove_circle</span>
                                <span>Sangria</span>
                            </button>
                            <button type="button" @click="mostrarObservacaoModal = true; menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">note</span>
                                <span>Observação</span>
                            </button>
                            <button type="button" @click="imprimirOrcamento(); menuCaixaAberto = false" :disabled="!itens.length" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-primary text-xl">picture_as_pdf</span>
                                <span>Gerar PDF do orçamento</span>
                            </button>
                            <button type="button" @click="mostrarOrcamentoModal = true; menuCaixaAberto = false" :disabled="!itens.length" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="material-symbols-outlined text-primary text-xl">description</span>
                                <span>Salvar orçamento</span>
                            </button>
                            <button type="button" @click="abrirOrcamentosSalvos(); menuCaixaAberto = false" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">folder_open</span>
                                <span>Orçamentos salvos</span>
                            </button>
                            <button type="button" @click="abrirFechamentoModal()" class="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-700 dark:text-slate-200 hover:bg-primary/5 dark:hover:bg-white/5">
                                <span class="material-symbols-outlined text-primary text-xl">power_settings_new</span>
                                <span>Fechar caixa</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="relative" @click.outside="fecharAutocomplete()">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-primary/60">search</span>
                    </div>
                    <input type="text" x-ref="inputBipagem" x-model="bipagem"
                           @input.debounce.300ms="buscarAutocomplete()"
                           @keydown.enter.prevent="autocompleteResultados.length ? selecionarAutocomplete(autocompleteResultados[0]) : adicionarPorBipagem()"
                           class="block w-full pl-10 pr-12 py-3 border-none bg-primary/5 dark:bg-white/5 rounded-xl text-slate-900 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-primary/20 text-sm" placeholder="Buscar produto ou escanear..." tabindex="0">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" @click="abrirPesquisa()" class="text-primary hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">barcode_scanner</span>
                        </button>
                    </div>
                    {{-- Lista autocomplete: foto pequena, nome, preço --}}
                    <div x-show="(autocompleteResultados.length > 0 || carregandoAutocomplete) && bipagem.trim().length >= 2" x-cloak
                         class="absolute left-0 right-0 top-full mt-1 z-50 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg max-h-80 overflow-y-auto">
                        <p x-show="carregandoAutocomplete" class="px-3 py-3 text-slate-500 dark:text-slate-400 text-sm flex items-center gap-2">
                            <span class="material-symbols-outlined animate-pulse text-primary/60">progress_activity</span> Buscando...
                        </p>
                        <template x-for="p in autocompleteResultados" :key="p.id + '-' + (p.tipo || 'produto')">
                            <button type="button" @click="selecionarAutocomplete(p)" class="pdv-autocomplete-item w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-primary/5 dark:hover:bg-white/5 border-b border-slate-100 dark:border-slate-800 last:border-b-0 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-primary/5 flex-shrink-0 flex items-center justify-center overflow-hidden">
                                    <template x-if="p.imagens && p.imagens.length">
                                        <img :src="p.imagens[0]" :alt="p.nome || p.descricao" class="object-cover w-full h-full">
                                    </template>
                                    <span x-show="(!p.imagens || !p.imagens.length) && p.tipo === 'servico'" class="material-symbols-outlined text-lg text-primary/50">build</span>
                                    <span x-show="(!p.imagens || !p.imagens.length) && p.tipo !== 'servico'" class="material-symbols-outlined text-lg text-primary/50">inventory_2</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-900 dark:text-white text-sm truncate" x-text="p.nome || p.descricao"></div>
                                    <div class="text-primary font-bold text-sm" x-text="'R$ ' + formatarValor(p.preco)"></div>
                                    <template x-if="p.tipo === 'produto'">
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="p.estoque != null ? ('Est.: ' + p.estoque) : 'Est.: —'"></div>
                                    </template>
                                </div>
                                <span class="material-symbols-outlined text-primary/60 text-xl flex-shrink-0">add_circle</span>
                            </button>
                        </template>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><span x-text="numeroCaixa"></span> · <span x-text="'Venda #' + (caixa ? (numeroVendaInicial + (ultimaVendaId || 0)) : '—')"></span> · <span x-text="itens.length ? 'Carrinho (' + itens.length + ' itens)' : 'Venda em andamento'"></span></p>
            </header>

            <main class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                <div class="flex items-center justify-between" x-show="itens.length">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Carrinho (<span x-text="itens.length"></span> itens)</h2>
                    <button type="button" @click="itens = []; observacaoOrcamento = ''; observacaoInternaOrcamento = ''; orcamentoImportadoId = null" class="text-sm font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">delete_sweep</span> Limpar
                    </button>
                </div>

                <template x-for="(item, idx) in itens" :key="'item-' + indiceItem(item)">
                    <div>
                        <div x-show="idx === 0 || (idx > 0 && itens[idx-1].tipo !== item.tipo)" class="flex items-center gap-3 mb-2 pt-2 border-t-2 border-primary/20" :class="idx === 0 ? 'mt-0 pt-0 border-t-0' : 'mt-3'">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400" x-text="item.tipo === 'servico' ? 'Serviços' : 'Produtos'"></span>
                            <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-primary/5 overflow-hidden">
                            <div class="p-4 flex gap-4">
                                <div class="w-20 h-20 rounded-lg bg-primary/5 flex-shrink-0 flex items-center justify-center overflow-hidden cursor-pointer" @click="item.imagens && item.imagens.length && abrirCarousel(item.imagens, 0)">
                                    <template x-if="item.imagens && item.imagens.length">
                                        <img :src="item.imagens[0]" :alt="item.descricao" class="object-cover w-full h-full" loading="lazy">
                                    </template>
                                    <span x-show="(!item.imagens || !item.imagens.length) && item.tipo === 'servico'" class="material-symbols-outlined text-3xl text-primary/50">build</span>
                                    <span x-show="(!item.imagens || !item.imagens.length) && item.tipo !== 'servico'" class="material-symbols-outlined text-3xl text-primary/50">inventory_2</span>
                                </div>
                                <div class="flex-1 flex flex-col justify-between min-w-0">
                                    <div>
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="min-w-0">
                                                <h3 class="font-bold text-slate-900 dark:text-white text-base leading-tight" x-text="item.descricao"></h3>
                                                <p x-show="item.kit_componentes && item.kit_componentes.length" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-text="'(' + (item.kit_componentes || []).join(', ') + ')'"></p>
                                            </div>
                                            <div class="flex items-center gap-0.5 flex-shrink-0">
                                                <button x-show="podeEditarItem(item)" type="button" @click="abrirEditarItem(item)" class="p-1.5 rounded-lg text-primary hover:bg-primary/10 transition-colors" title="Editar variação ou serial">
                                                    <span class="material-symbols-outlined text-xl">edit</span>
                                                </button>
                                                <button x-show="item.tipo === 'servico'" type="button" @click="abrirObservacaoServico(item)" class="p-1.5 rounded-lg text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 transition-colors" title="Observação do serviço">
                                                    <span class="material-symbols-outlined text-xl">note</span>
                                                </button>
                                                <button type="button" @click="removerItem(indiceItem(item))" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 transition-colors">
                                                    <span class="material-symbols-outlined text-xl">close</span>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-primary font-bold mt-1" x-text="'R$ ' + formatarValor(item.total)"></p>
                                        <p x-show="item.serial" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">S/N: <span x-text="item.serial"></span></p>
                                        <p x-show="item.tipo === 'servico' && (item.observacao || '').trim()" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Obs: <span x-text="(item.observacao || '').trim()"></span></p>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center border border-primary/10 rounded-lg overflow-hidden">
                                            <button type="button" @click="alterarQuantidade(indiceItem(item), -1)" class="px-2 py-1 bg-primary/5 text-primary hover:bg-primary/10">
                                                <span class="material-symbols-outlined text-sm">remove</span>
                                            </button>
                                            <span class="px-3 text-sm font-bold text-slate-800 dark:text-white" x-text="item.quantidade"></span>
                                            <button type="button" @click="alterarQuantidade(indiceItem(item), 1)" class="px-2 py-1 bg-primary/5 text-primary hover:bg-primary/10">
                                                <span class="material-symbols-outlined text-sm">add</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <p x-show="!itens.length && !carregando" class="text-slate-500 dark:text-slate-400 text-center py-8">Carrinho vazio. Busque ou escaneie um produto.</p>
            </main>

            <footer class="bg-white dark:bg-slate-900 border-t border-primary/10 p-4 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] shrink-0" style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 0));">
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Subtotal</span>
                        <span class="text-slate-900 dark:text-white font-medium" x-text="'R$ ' + formatarValor(subtotal)"></span>
                    </div>
                </div>
                <button type="button" @click="abrirFinalizar()" :disabled="!itens.length" class="w-full bg-primary hover:bg-primary/90 text-white py-4 rounded-xl font-bold text-lg flex items-center justify-between px-6 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                    <div class="flex flex-col items-start">
                        <span class="text-xs font-normal opacity-80 uppercase tracking-wider">Total</span>
                        <span x-text="'R$ ' + formatarValor(totalFinal)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>Finalizar Venda</span>
                        <span class="material-symbols-outlined">chevron_right</span>
                    </div>
                </button>
                <div class="flex gap-2 mt-3">
                    <button type="button" @click="abrirPesquisa()" class="flex-1 py-2 rounded-lg border border-primary/20 text-primary text-sm font-semibold flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-lg">search</span> Pesquisar
                    </button>
                    <button type="button" @click="cancelarUltimoItem()" :disabled="!itens.length" class="flex-1 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm font-semibold flex items-center justify-center gap-1 disabled:opacity-50">
                        <span class="material-symbols-outlined text-lg">undo</span> Desfazer
                    </button>
                    <button type="button" @click="mostrarOrcamentoModal = true" :disabled="!itens.length" class="flex-1 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined text-lg">description</span> Orçamento
                    </button>
                </div>
            </footer>

            {{-- Barra inferior de navegação — idêntica ao Stitch --}}
            <nav class="bg-white dark:bg-background-dark border-t border-primary/5 px-4 pb-2 pt-2 flex justify-between items-center shrink-0" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0));">
                <a href="#" class="flex flex-1 flex-col items-center gap-0.5 p-2 text-primary">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <span class="text-[10px] font-bold">Venda</span>
                </a>
                <button type="button" @click="abrirOrcamentosSalvos()" class="flex flex-1 flex-col items-center gap-0.5 p-2 text-slate-500 dark:text-slate-400 hover:text-primary border-0 bg-transparent cursor-pointer">
                    <span class="material-symbols-outlined">folder_open</span>
                    <span class="text-[10px] font-medium">Orç. salvos</span>
                </button>
                <button type="button" @click="abrirHistorico()" class="flex flex-1 flex-col items-center gap-0.5 p-2 text-slate-500 dark:text-slate-400 hover:text-primary border-0 bg-transparent cursor-pointer">
                    <span class="material-symbols-outlined">history</span>
                    <span class="text-[10px] font-medium">Histórico</span>
                </button>
                <button type="button" @click="mostrarAjuda = true" class="flex flex-1 flex-col items-center gap-0.5 p-2 text-slate-500 dark:text-slate-400 hover:text-primary border-0 bg-transparent cursor-pointer">
                    <span class="material-symbols-outlined">help</span>
                    <span class="text-[10px] font-medium">Ajuda</span>
                </button>
            </nav>
        </div>
    </template>

    {{-- Modal Reforço — estilo Stitch (operações_de_caixa) --}}
    <div x-show="mostrarReforco" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="mostrarReforco = false">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark">
            <header class="flex items-center p-4 pb-2 justify-between bg-white dark:bg-slate-900 border-b border-primary/10">
                <button type="button" @click="mostrarReforco = false" class="text-slate-900 dark:text-slate-100 flex size-10 shrink-0 items-center justify-center cursor-pointer hover:bg-primary/10 rounded-full transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-tight flex-1 text-center pr-10">Reforço (Suprimento)</h2>
            </header>
            <div class="p-4">
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-primary text-white shadow-lg shadow-primary/20 mb-6">
                    <div class="flex items-center gap-2 opacity-90">
                        <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                        <p class="text-sm font-medium uppercase tracking-wider">Saldo Atual em Caixa</p>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-medium opacity-80">R$</span>
                        <p class="text-3xl font-bold leading-tight" x-text="formatarValor(saldo)"></p>
                    </div>
                </div>
                <form @submit.prevent="aplicarReforco()" class="space-y-5">
                    <div class="flex flex-col">
                        <label class="text-slate-700 dark:text-slate-300 text-sm font-semibold px-1 mb-5">Valor da Operação</label>
                        <div class="flex items-center rounded-xl bg-slate-50 dark:bg-slate-800/50 overflow-hidden focus-within:ring-2 focus-within:ring-primary/20 focus-within:ring-inset">
                            <span class="flex items-center justify-center w-12 h-14 shrink-0 text-slate-500 dark:text-slate-400 font-bold text-xl">R$</span>
                            <input type="text" x-model="reforcoValor" placeholder="0,00" class="flex-1 min-w-0 h-14 pl-2 pr-4 bg-transparent border-none text-2xl font-bold text-slate-900 dark:text-slate-100 focus:ring-0 outline-none placeholder:text-slate-400">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-slate-700 dark:text-slate-300 text-sm font-semibold px-1">Plano de contas (Aporte de Caixa)</label>
                        <select x-model="reforcoPlanoContaId" required class="w-full p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Selecione</option>
                            <template x-for="c in planoContasAporte" :key="c.id">
                                <option :value="c.id" x-text="c.codigo + ' - ' + c.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="mostrarReforco = false" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                        <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">check_circle</span> Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Sangria — estilo Stitch (operações_de_caixa) --}}
    <div x-show="mostrarSangria" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="mostrarSangria = false">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark">
            <header class="flex items-center p-4 pb-2 justify-between bg-white dark:bg-slate-900 border-b border-primary/10">
                <button type="button" @click="mostrarSangria = false" class="text-slate-900 dark:text-slate-100 flex size-10 shrink-0 items-center justify-center cursor-pointer hover:bg-primary/10 rounded-full transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-tight flex-1 text-center pr-10">Sangria</h2>
            </header>
            <div class="p-4">
                <div class="flex flex-col gap-2 rounded-xl p-6 bg-primary text-white shadow-lg shadow-primary/20 mb-6">
                    <div class="flex items-center gap-2 opacity-90">
                        <span class="material-symbols-outlined text-sm">account_balance_wallet</span>
                        <p class="text-sm font-medium uppercase tracking-wider">Saldo Atual em Caixa</p>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xl font-medium opacity-80">R$</span>
                        <p class="text-3xl font-bold leading-tight" x-text="formatarValor(saldo)"></p>
                    </div>
                </div>
                <form @submit.prevent="aplicarSangria()" class="space-y-5">
                    <div class="flex flex-col">
                        <label class="text-slate-700 dark:text-slate-300 text-sm font-semibold px-1 mb-5">Valor da Operação</label>
                        <div class="flex items-center rounded-xl bg-slate-50 dark:bg-slate-800/50 overflow-hidden focus-within:ring-2 focus-within:ring-primary/20 focus-within:ring-inset">
                            <span class="flex items-center justify-center w-12 h-14 shrink-0 text-slate-500 dark:text-slate-400 font-bold text-xl">R$</span>
                            <input type="text" x-model="sangriaValor" placeholder="0,00" class="flex-1 min-w-0 h-14 pl-2 pr-4 bg-transparent border-none text-2xl font-bold text-slate-900 dark:text-slate-100 focus:ring-0 outline-none placeholder:text-slate-400">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-slate-700 dark:text-slate-300 text-sm font-semibold px-1">Motivo / Justificativa</label>
                        <textarea x-model="sangriaJustificativa" required rows="3" placeholder="Ex: Pagamento de fornecedor..." class="w-full min-h-[120px] p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                    </div>
                    <div x-show="sangriaRequerSenha" class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">Você não tem permissão para sangria. Digite a senha do autorizador<span x-show="sangriaAutorizadorNome"> (<span x-text="sangriaAutorizadorNome"></span>)</span> para continuar:</p>
                        <input type="password" x-model="sangriaSenhaAutorizacao" placeholder="Senha do autorizador" class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"/>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="mostrarSangria = false" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                        <button type="submit" :disabled="sangriaRequerSenha && !sangriaSenhaAutorizacao.trim()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="material-symbols-outlined">check_circle</span> <span x-text="sangriaRequerSenha ? 'Autorizar e registrar' : 'Registrar sangria'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Fechamento — estilo Stitch (fechamento_de_caixa) --}}
    <div x-show="mostrarFechamento" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="mostrarFechamento = false">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark">
            <header class="flex items-center p-4 justify-between bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                <button type="button" @click="mostrarFechamento = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-700 dark:text-slate-300">arrow_back</span>
                </button>
                <h1 class="flex-1 text-center text-lg font-bold tracking-tight text-slate-900 dark:text-white mr-10">Fechamento de Caixa</h1>
            </header>
            <div class="p-4">
                <div class="flex flex-col gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4 dark:bg-primary/10 mb-6">
                    <div class="flex items-center gap-2 text-primary">
                        <span class="material-symbols-outlined text-xl">info</span>
                        <p class="text-sm font-bold uppercase tracking-wider">Atenção</p>
                    </div>
                    <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed">Informe os valores contados na gaveta (por forma de pagamento, se disponível). O sistema comparará com os registros após a confirmação.</p>
                </div>
                <form @submit.prevent="fecharCaixa()" class="space-y-4">
                    <template x-if="fechamentoFormas && fechamentoFormas.length > 0">
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Valores contados por forma de pagamento</p>
                            <template x-for="f in fechamentoFormas" :key="f.id">
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" x-text="f.nome"></label>
                                    <div class="flex items-center rounded-lg bg-slate-50 dark:bg-slate-800/50 overflow-hidden">
                                        <span class="w-10 shrink-0 text-center text-slate-500 font-bold">R$</span>
                                        <input type="text" :placeholder="'0,00'" x-model="fechamentoValoresPorForma[f.id]"
                                            class="flex-1 min-w-0 py-2.5 pl-2 pr-3 bg-transparent border-none text-slate-900 dark:text-white focus:ring-0 outline-none">
                                    </div>
                                    <p x-show="podeVerSaldoFechamento && f.total_calculado > 0" class="mt-1 text-xs text-slate-500" x-text="'Esperado: R$ ' + formatarValor(f.total_calculado)"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!fechamentoFormas || fechamentoFormas.length === 0">
                        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-5">
                                <label class="text-base font-semibold text-slate-900 dark:text-white shrink-0">Valor informado (R$)</label>
                                <span class="material-symbols-outlined text-primary text-xl">payments</span>
                            </div>
                            <div class="flex items-center rounded-lg bg-slate-50 dark:bg-slate-800/50 overflow-hidden focus-within:ring-2 focus-within:ring-primary/20 focus-within:ring-inset">
                                <span class="flex items-center justify-center w-12 h-14 shrink-0 text-slate-500 dark:text-slate-400 font-bold text-xl">R$</span>
                                <input type="text" x-model="fechamentoValor" placeholder="0,00" class="flex-1 min-w-0 h-14 pl-2 pr-4 bg-transparent border-none text-2xl font-extrabold text-slate-900 dark:text-white focus:ring-0 outline-none placeholder:text-slate-400">
                            </div>
                        </div>
                    </template>
                    {{-- Saldo em sistema e composição: apenas para administradores ou usuários autorizadores (fechamento cego para outros) --}}
                    <template x-if="podeVerSaldoFechamento">
                        <div class="space-y-1">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Saldo em sistema: R$ <span x-text="formatarValor(saldoFechamentoSistema)" class="font-bold"></span></p>
                            <template x-if="caixa && caixa.detalhe_saldo">
                                <div class="text-xs text-slate-500 dark:text-slate-400 space-y-0.5 mt-2 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                                    <p><span class="font-medium">Composição do saldo:</span></p>
                                    <p>Abertura: R$ <span x-text="formatarValor(caixa.detalhe_saldo.valor_abertura || 0)"></span></p>
                                    <p>Reforços: + R$ <span x-text="formatarValor(caixa.detalhe_saldo.total_reforcos || 0)"></span></p>
                                    <p>Sangrias: − R$ <span x-text="formatarValor(caixa.detalhe_saldo.total_sangrias || 0)"></span></p>
                                    <p>Dinheiro em vendas: + R$ <span x-text="formatarValor(caixa.detalhe_saldo.dinheiro_vendas || 0)"></span></p>
                                    <p class="font-semibold text-slate-700 dark:text-slate-300 pt-0.5 border-t border-slate-200 dark:border-slate-700 mt-0.5">Saldo = Abertura + Reforços − Sangrias + Dinheiro vendas</p>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="fechamentoRequerSenha" class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">Diferença acima do limite. Digite a senha do administrador ou autorizador<span x-show="fechamentoAutorizadorNome"> (<span x-text="fechamentoAutorizadorNome"></span>)</span> para concluir:</p>
                        <input type="password" x-model="fechamentoSenha" placeholder="Senha" class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"/>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="mostrarFechamento = false" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                        <button type="submit" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">lock</span> Fechar caixa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Resultado do Fechamento (quebra/sobra) — exibido após fechar o caixa --}}
    <div x-show="fechamentoResultado" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="fechamentoResultado = null">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-md">
            <header class="flex items-center p-4 justify-between bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
                <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Resultado do Fechamento</h1>
                <button type="button" @click="fechamentoResultado = null" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-slate-700 dark:text-slate-300">close</span>
                </button>
            </header>
            <template x-if="fechamentoResultado">
                <div class="p-4 space-y-4">
                    <p class="text-slate-700 dark:text-slate-300 font-medium" x-text="fechamentoResultado.relatorio"></p>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3">
                            <p class="text-slate-500 dark:text-slate-400 mb-0.5">Valor informado</p>
                            <p class="font-bold text-slate-900 dark:text-white" x-text="'R$ ' + formatarValor(fechamentoResultado.valor_informado)"></p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3">
                            <p class="text-slate-500 dark:text-slate-400 mb-0.5">Valor em sistema</p>
                            <p class="font-bold text-slate-900 dark:text-white" x-text="'R$ ' + formatarValor(fechamentoResultado.valor_sistema)"></p>
                        </div>
                    </div>
                    <div x-show="fechamentoResultado.quebra_caixa !== 0" class="rounded-xl p-4 text-center"
                         :class="fechamentoResultado.quebra_caixa > 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                        <p class="text-sm font-medium mb-1" :class="fechamentoResultado.quebra_caixa > 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'"
                           x-text="fechamentoResultado.quebra_caixa > 0 ? 'Sobra' : 'Quebra de caixa'"></p>
                        <p class="text-xl font-bold" :class="fechamentoResultado.quebra_caixa > 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                           x-text="'R$ ' + formatarValor(Math.abs(fechamentoResultado.quebra_caixa))"></p>
                    </div>
                    <template x-if="fechamentoResultado.valores_por_forma && fechamentoFormas && fechamentoFormas.length">
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 px-3 py-2 bg-slate-50 dark:bg-slate-800/50">Por forma de pagamento</p>
                            <div class="grid grid-cols-4 gap-2 px-3 py-2 text-xs font-medium text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <span>Forma</span>
                                <span>Informado</span>
                                <span>Sistema</span>
                                <span>Diferença</span>
                            </div>
                            <div class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                <template x-for="f in fechamentoFormas" :key="f.id">
                                    <div class="grid grid-cols-4 gap-2 px-3 py-2 items-center" x-show="fechamentoResultado.valores_por_forma.informados[f.id] !== undefined || fechamentoResultado.valores_por_forma.calculados[f.id] !== undefined">
                                        <span class="font-medium text-slate-700 dark:text-slate-300 truncate" x-text="f.nome"></span>
                                        <span x-text="'R$ ' + formatarValor(fechamentoResultado.valores_por_forma.informados[f.id] || 0)"></span>
                                        <span x-text="'R$ ' + formatarValor(fechamentoResultado.valores_por_forma.calculados[f.id] || 0)"></span>
                                        <span class="font-medium" :class="(fechamentoResultado.valores_por_forma.diferencas[f.id] || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                              x-text="'R$ ' + formatarValor(fechamentoResultado.valores_por_forma.diferencas[f.id] || 0)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="fechamentoResultado = null"
                            class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl shadow-lg shadow-primary/20">
                        Fechar
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Modal Configurar Item (variação + serial) — estilo Stitch detalhes_do_produto/kit --}}
    <div x-show="mostrarConfigurarItem" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="fecharConfigurarItem()">
        <div class="pdv-stitch-modal-content bg-white dark:bg-slate-900 max-w-md" x-show="itemParaConfigurar">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="fecharConfigurarItem()" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2">Configurar Item</h2>
            </header>
            <template x-if="itemParaConfigurar">
                <div class="p-4 space-y-4">
                    <div class="flex gap-4">
                        <div class="w-24 h-24 rounded-xl overflow-hidden bg-primary/5 flex-shrink-0 flex items-center justify-center">
                            <template x-if="itemParaConfigurar.imagens && itemParaConfigurar.imagens.length">
                                <img :src="itemParaConfigurar.imagens[0]" :alt="itemParaConfigurar.nome" class="object-cover w-full h-full">
                            </template>
                            <span x-show="!itemParaConfigurar.imagens || !itemParaConfigurar.imagens.length" class="material-symbols-outlined text-4xl text-primary/50">inventory_2</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 dark:text-white text-lg leading-tight" x-text="itemParaConfigurar.nome"></h3>
                            <p class="text-primary font-bold text-xl mt-1" x-text="'R$ ' + formatarValor(itemParaConfigurar.preco)"></p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Quantidade</label>
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                            <button type="button" @click="configurarQuantidade = Math.max(1, configurarQuantidade - 1)" class="w-10 h-10 flex items-center justify-center rounded-md hover:bg-white dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300">
                                <span class="material-symbols-outlined">remove</span>
                            </button>
                            <span class="w-12 text-center font-bold text-lg" x-text="configurarQuantidade"></span>
                            <button type="button" @click="configurarQuantidade++" class="w-10 h-10 flex items-center justify-center rounded-md bg-white dark:bg-slate-700 shadow-sm text-primary">
                                <span class="material-symbols-outlined">add</span>
                            </button>
                        </div>
                    </div>
                    <template x-if="itemParaConfigurar.variacoes && itemParaConfigurar.variacoes.length">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">Variação</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="v in itemParaConfigurar.variacoes" :key="v.id">
                                    <button type="button" @click="configurarVariacaoId = v.id" class="p-3 rounded-xl border-2 text-left transition-all text-sm font-semibold"
                                            :class="configurarVariacaoId === v.id ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-300'">
                                        <span x-text="v.referencia_sku || (Array.isArray(v.opcoes_valores) ? v.opcoes_valores.join(' / ') : v.opcoes_valores || 'Opção')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="itemParaConfigurar.exige_serial">
                        <div>
                            <label class="block text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Serial / IMEI</label>
                            <div class="flex gap-2">
                                <input type="text" x-ref="inputSerial" x-model="configurarSerial" placeholder="Digite ou escaneie o S/N" class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 outline-none text-sm">
                                <button type="button" class="p-2 text-primary/70 rounded-lg hover:bg-primary/5" title="Escanear">
                                    <span class="material-symbols-outlined">qr_code_scanner</span>
                                </button>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="confirmarConfigurarItem()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 flex items-center justify-center gap-2 mt-4">
                        <span class="material-symbols-outlined">shopping_cart</span> Adicionar ao Carrinho
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Modal Observação do serviço --}}
    <div x-show="mostrarObservacaoServico" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="fecharObservacaoServico()">
        <div class="pdv-stitch-modal-content bg-white dark:bg-slate-900 max-w-md">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="fecharObservacaoServico()" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2">Observação do serviço</h2>
            </header>
            <div class="p-4">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Texto curto que será impresso junto ao item (máx. 255 caracteres).</p>
                <input type="text" x-model="textoObservacaoServico" maxlength="255" placeholder="Ex: Trocar tela, cliente retira em 2 dias"
                    class="w-full px-3 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/20 outline-none text-sm">
                <p class="text-xs text-slate-400 mt-1" x-text="(textoObservacaoServico || '').length + '/255'"></p>
                <button type="button" @click="salvarObservacaoServico()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl mt-4 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">check</span> Salvar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Pesquisa produto/serviço --}}
    <div x-show="mostrarPesquisa" x-cloak class="pdv-modal pdv-modal-grande" @keydown.escape.window="fecharPesquisa()">
        <div class="pdv-modal-content">
            <h2>Pesquisar produto ou serviço</h2>
            <input type="text" x-model="pesquisaQ" @input.debounce.300ms="buscarProdutos()" placeholder="Nome, código ou EAN" class="pdv-input">
            <ul class="pdv-lista-resultados pdv-resultados-grid">
                <template x-for="p in resultadosProdutos" :key="p.id + '-' + p.tipo">
                    <li class="pdv-resultado-card" @click="precisaConfigurar(p) ? abrirConfigurarItem(p) : adicionarResultado(p)">
                        <div class="pdv-resultado-thumb" @click.stop="p.imagens && p.imagens.length && abrirCarousel(p.imagens, 0)">
                            <template x-if="p.tipo === 'produto' && p.imagens && p.imagens.length">
                                <img :src="p.imagens[0]" :alt="p.nome" loading="lazy">
                            </template>
                            <span class="pdv-resultado-placeholder" x-show="p.tipo === 'servico' || !p.imagens || !p.imagens.length" x-text="p.tipo === 'servico' ? '🔧' : '📦'"></span>
                        </div>
                        <div class="pdv-resultado-info">
                            <span class="pdv-resultado-nome" x-text="p.nome"></span>
                            <span class="pdv-resultado-preco" x-text="'R$ ' + formatarValor(p.preco)"></span>
                            <template x-if="p.tipo === 'produto'">
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 block" x-text="p.estoque != null ? ('Estoque: ' + p.estoque) : 'Estoque: —'"></span>
                            </template>
                        </div>
                    </li>
                </template>
            </ul>
            <button type="button" class="pdv-btn pdv-btn-secundario" @click="fecharPesquisa()">Fechar (Esc)</button>
        </div>
    </div>

    {{-- Modal Carrossel de imagens do produto --}}
    <div x-show="mostrarCarousel" x-cloak class="pdv-modal pdv-modal-carousel" @keydown.escape.window="fecharCarousel()" @keydown.arrow-left.window="carouselAnterior()" @keydown.arrow-right.window="carouselProximo()">
        <div class="pdv-carousel-content">
            <button type="button" class="pdv-carousel-fechar" @click="fecharCarousel()" aria-label="Fechar">×</button>
            <button type="button" class="pdv-carousel-nav pdv-carousel-prev" @click="carouselAnterior()" x-show="carouselImagens.length > 1" aria-label="Anterior">‹</button>
            <div class="pdv-carousel-slide">
                <img x-show="carouselImagens[carouselIndice]" :src="carouselImagens[carouselIndice]" alt="Imagem do produto" class="pdv-carousel-img">
            </div>
            <button type="button" class="pdv-carousel-nav pdv-carousel-next" @click="carouselProximo()" x-show="carouselImagens.length > 1" aria-label="Próximo">›</button>
            <p class="pdv-carousel-indice" x-show="carouselImagens.length > 1" x-text="(carouselIndice + 1) + ' / ' + carouselImagens.length"></p>
        </div>
    </div>

    {{-- Modal Histórico de Vendas — visual igual ao code.html (cards + busca + pills) --}}
    <div x-show="mostrarHistorico" x-cloak class="pdv-modal pdv-modal-stitch" style="max-width: 96%; width: 100%; max-height: 95vh;" @keydown.escape.window="mostrarHistorico = false">
        <div class="bg-background-light dark:bg-background-dark rounded-2xl border border-primary/10 shadow-xl flex flex-col max-h-[95vh] overflow-hidden">
            <header class="sticky top-0 z-10 bg-background-light dark:bg-background-dark border-b border-primary/10 px-4 py-4 shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Histórico de Vendas</h2>
                    </div>
                    <button type="button" @click="mostrarHistorico = false" class="flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary dark:text-primary/80" title="Fechar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="mt-4 w-full min-w-0">
                    <div class="flex items-center rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 focus-within:border-primary/60 overflow-hidden">
                        <div class="flex items-center justify-center w-10 shrink-0 text-slate-400">
                            <span class="material-symbols-outlined">search</span>
                        </div>
                        <input type="text" x-model="historicoBusca" @input.debounce.400ms="carregarHistorico()" placeholder="Buscar por cliente ou cupom #" class="min-w-0 flex-1 py-3 pl-2 pr-4 text-slate-900 dark:text-white placeholder:text-slate-400 text-sm bg-white dark:bg-slate-800 border-none outline-none ring-0 shadow-[none] focus:ring-0 focus:outline-none focus:border-none focus:shadow-[none] [box-shadow:none] [outline:none]"/>
                    </div>
                </div>
                <nav class="flex gap-4 mt-4 overflow-x-auto no-scrollbar pb-1">
                    <button type="button" @click="historicoFiltros.status = ''; carregarHistorico()" class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                        :class="historicoFiltros.status === '' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-primary/10'">Todas</button>
                    <button type="button" @click="historicoFiltros.status = 'finalizada'; carregarHistorico()" class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                        :class="historicoFiltros.status === 'finalizada' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-primary/10'">Concluídas</button>
                    <button type="button" @click="historicoFiltros.status = 'cancelada'; carregarHistorico()" class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                        :class="historicoFiltros.status === 'cancelada' ? 'bg-primary text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-primary/10'">Canceladas</button>
                </nav>
            </header>
            <main class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                <template x-if="carregandoHistorico">
                    <p class="py-6 text-center text-slate-500 dark:text-slate-400 text-sm flex items-center justify-center gap-2"><span class="material-symbols-outlined animate-spin">progress_activity</span> Carregando...</p>
                </template>

                <template x-if="!carregandoHistorico && historicoVendas.length === 0">
                    <p class="rounded-xl border border-primary/5 bg-white dark:bg-slate-800 p-6 text-center text-slate-600 dark:text-slate-400 shadow-sm">Nenhuma venda encontrada.</p>
                </template>

                <template x-if="!carregandoHistorico && historicoVendas.length > 0">
                    <template x-for="v in historicoVendas" :key="v.id">
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-primary/5">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex flex-col">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium" x-text="v.created_at_card || v.created_at"></span>
                                    <h3 class="font-bold text-lg text-slate-900 dark:text-slate-100" x-text="v.total_formatado"></h3>
                                </div>
                                <span x-show="v.status === 'finalizada'" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Concluída</span>
                                <span x-show="v.status === 'cancelada'" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-600 dark:text-red-400">Cancelada</span>
                                <span x-show="v.status && v.status !== 'finalizada' && v.status !== 'cancelada'" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400" x-text="statusLabelHistorico(v.status)"></span>
                            </div>
                            <div class="flex flex-col gap-1 mb-4">
                                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                    <span class="text-sm font-medium" x-text="v.cliente_nome"></span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                    <span class="material-symbols-outlined text-sm">confirmation_number</span>
                                    <span class="text-sm">Cupom: #<span x-text="v.numero_formatado"></span></span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="mostrarDetalheVendaHistorico(v.id)" class="flex-1 py-2.5 rounded-lg bg-primary text-white text-sm font-semibold flex items-center justify-center gap-2 shadow-sm">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                    Visualizar
                                </button>
                                <a :href="'{{ url('/plugin/pdv/api/vendas') }}/' + v.id + '/comprovante-pdf'" target="_blank" x-show="v.status === 'finalizada' || v.status === 'cancelada'" class="px-4 py-2.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-sm font-semibold flex items-center justify-center" title="Imprimir 2ª via">
                                    <span class="material-symbols-outlined text-lg">print</span>
                                </a>
                                <button x-show="v.status === 'finalizada' && (podeCancelarVendaTotal || podeCancelarVendaParcial)" type="button" @click="abrirModalCancelarHistorico(v.id)" class="px-4 py-2.5 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 text-sm font-semibold flex items-center justify-center" title="Excluir / Cancelar">
                                    <span class="material-symbols-outlined text-lg">cancel</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </template>

                <div x-show="!carregandoHistorico && historicoVendas.length > 0 && historicoPagination && historicoPagination.last_page > 1" class="flex items-center justify-between pt-2 pb-4">
                    <span class="text-sm text-slate-600 dark:text-slate-400" x-text="'Página ' + (historicoPagination?.current_page || 1) + ' de ' + (historicoPagination?.last_page || 1)"></span>
                    <div class="flex gap-1">
                        <button type="button" @click="irPaginaHistorico((historicoPagination?.current_page || 1) - 1)" :disabled="!historicoPagination || historicoPagination.current_page <= 1" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm disabled:opacity-50 text-slate-700 dark:text-slate-300">Anterior</button>
                        <button type="button" @click="irPaginaHistorico((historicoPagination?.current_page || 1) + 1)" :disabled="!historicoPagination || historicoPagination.current_page >= historicoPagination.last_page" class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm disabled:opacity-50 text-slate-700 dark:text-slate-300">Próxima</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- Modal Detalhe da venda (Visualizar) --}}
    <div x-show="historicoDetalheVenda !== null" x-cloak class="pdv-modal pdv-modal-stitch" style="max-width: 96%; width: 100%; max-width: 28rem;" @keydown.escape.window="fecharDetalheHistorico()">
        <div class="bg-background-light dark:bg-background-dark rounded-2xl border border-primary/10 shadow-xl flex flex-col max-h-[90vh] overflow-hidden" @click.outside="fecharDetalheHistorico()">
            <header class="flex items-center justify-between px-4 py-3 border-b border-primary/10 shrink-0">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Cupom #<span x-text="historicoDetalheVenda && String(historicoDetalheVenda.id).padStart(5, '0')"></span></h3>
                <button type="button" @click="fecharDetalheHistorico()" class="p-2 rounded-lg bg-primary/10 text-primary"><span class="material-symbols-outlined">close</span></button>
            </header>
            <main class="flex-1 overflow-y-auto p-4">
                <template x-if="historicoDetalheVenda">
                    <div class="space-y-4">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            <p><strong>Cliente:</strong> <span x-text="historicoDetalheVenda.cliente ? (historicoDetalheVenda.cliente.nome || historicoDetalheVenda.cliente.razao_social || 'Consumidor Final') : 'Consumidor Final'"></span></p>
                            <p class="mt-1"><strong>Data:</strong> <span x-text="historicoDetalheVenda.created_at ? new Date(historicoDetalheVenda.created_at).toLocaleString('pt-BR') : '—'"></span></p>
                        </div>
                        <div class="border-t border-primary/10 pt-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Itens</h4>
                            <ul class="space-y-2">
                                <template x-for="(item, i) in (historicoDetalheVenda.itens || [])" :key="i">
                                    <li class="flex justify-between text-sm">
                                        <span class="text-slate-700 dark:text-slate-300" x-text="(item.descricao || (item.produto && item.produto.nome) || (item.servico && item.servico.nome) || 'Item') + ' × ' + (Number(item.quantidade || 0) - Number(item.quantidade_cancelada || 0)) + (Number(item.quantidade_cancelada || 0) > 0 ? ' (canc. ' + item.quantidade_cancelada + ')' : '')"></span>
                                        <span class="font-medium text-slate-900 dark:text-white" x-text="'R$ ' + (item.total != null ? Number(item.total).toFixed(2).replace('.', ',') : '0,00')"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div class="border-t border-primary/10 pt-3 flex justify-between items-center font-bold text-slate-900 dark:text-white">
                            <span>Total</span>
                            <span x-text="'R$ ' + (historicoDetalheVenda.total_final != null ? Number(historicoDetalheVenda.total_final).toFixed(2).replace('.', ',') : '0,00')"></span>
                        </div>
                    </div>
                </template>
            </main>
        </div>
    </div>

    {{-- Modal Confirmar cancelamento — visual do code.html (confirmacao_o_de_cancelamento) — conteúdo rolável no celular --}}
    <div x-show="historicoCancelarId !== null" x-cloak class="fixed inset-0 z-[100000] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto overscroll-contain" style="background: rgba(0,0,0,0.5);" @keydown.escape.window="fecharModalCancelarHistorico()">
        <div class="bg-background-light dark:bg-background-dark rounded-t-2xl sm:rounded-2xl border border-primary/10 border-b-0 sm:border-b shadow-xl w-full max-w-md max-h-[95vh] flex flex-col overflow-hidden flex-1 sm:flex-initial">
            {{-- TopAppBar --}}
            <div class="flex items-center p-4 pb-2 justify-between border-b border-primary/10 shrink-0">
                <button type="button" @click="fecharModalCancelarHistorico()" class="text-primary flex size-10 shrink-0 items-center justify-center cursor-pointer rounded-full hover:bg-primary/10 transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-tight flex-1 ml-4">Cancelar Venda</h2>
            </div>
            {{-- Área rolável: alerta + detalhes + justificativa --}}
            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain">
                <div class="m-4 mt-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 rounded-xl flex items-center gap-3">
                    <span class="material-symbols-outlined text-red-600 dark:text-red-400 shrink-0">warning</span>
                    <p class="text-red-700 dark:text-red-300 text-sm font-medium">Esta ação não poderá ser desfeita após a confirmação.</p>
                </div>
                <h3 class="text-slate-900 dark:text-slate-100 text-base font-bold leading-tight px-4 pb-2 pt-0 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">receipt_long</span>
                    Detalhes da Venda
                </h3>
                <div class="mx-4 p-4 bg-white dark:bg-slate-800/50 border border-primary/10 rounded-xl shadow-sm">
                    <div class="flex justify-between gap-x-6 py-2.5 border-b border-primary/5">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Valor Total</p>
                        <p class="text-slate-900 dark:text-slate-100 text-sm font-bold text-right" x-text="historicoCancelarVenda ? historicoCancelarVenda.total_formatado : '—'"></p>
                    </div>
                    <div class="flex justify-between gap-x-6 py-2.5 border-b border-primary/5">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Número do Cupom</p>
                        <p class="text-slate-900 dark:text-slate-100 text-sm font-medium text-right" x-text="'#' + historicoCancelarNumero"></p>
                    </div>
                    <div class="flex justify-between gap-x-6 py-2.5 border-b border-primary/5">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Data e Hora</p>
                        <p class="text-slate-900 dark:text-slate-100 text-sm font-normal text-right" x-text="historicoCancelarVenda ? (historicoCancelarVenda.created_at || historicoCancelarVenda.created_at_card) : '—'"></p>
                    </div>
                    <div class="flex justify-between gap-x-6 py-2.5">
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Vendedor</p>
                        <p class="text-slate-900 dark:text-slate-100 text-sm font-normal text-right" x-text="historicoCancelarVenda && historicoCancelarVenda.operador_nome ? historicoCancelarVenda.operador_nome : '—'"></p>
                    </div>
                </div>
                <div x-show="!historicoCarregandoCancelarDetalhe && (podeCancelarVendaTotal || podeCancelarVendaParcial)" class="px-4 pt-4 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tipo de cancelamento</p>
                    <div class="flex flex-col gap-2" x-show="podeCancelarVendaTotal && podeCancelarVendaParcial">
                        <label class="flex items-center gap-3 rounded-xl border border-primary/15 bg-white dark:bg-slate-800/50 px-4 py-3 cursor-pointer">
                            <input type="radio" name="pdv-hist-tipo-can" value="total" x-model="historicoTipoCancelamento" class="text-primary">
                            <span class="text-sm text-slate-800 dark:text-slate-200">Cancelar a venda inteira (restante)</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-primary/15 bg-white dark:bg-slate-800/50 px-4 py-3 cursor-pointer">
                            <input type="radio" name="pdv-hist-tipo-can" value="parcial" x-model="historicoTipoCancelamento" class="text-primary">
                            <span class="text-sm text-slate-800 dark:text-slate-200">Cancelar só produtos/serviços (parcial)</span>
                        </label>
                    </div>
                    <p x-show="podeCancelarVendaParcial && !podeCancelarVendaTotal" class="text-xs text-slate-600 dark:text-slate-400">Você só pode cancelamento parcial. Informe as quantidades abaixo.</p>
                    <p x-show="podeCancelarVendaTotal && !podeCancelarVendaParcial" class="text-xs text-slate-600 dark:text-slate-400">Você só pode cancelamento total da venda.</p>
                    <div x-show="historicoTipoCancelamento === 'parcial' && podeCancelarVendaParcial" class="rounded-xl border border-primary/10 bg-white dark:bg-slate-800/40 p-3 space-y-3 max-h-48 overflow-y-auto">
                        <template x-for="it in historicoParcialItens" :key="it.id">
                            <div x-show="it.disponivel > 0" class="flex flex-col gap-1 pb-2 border-b border-primary/5 last:border-0">
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-200" x-text="it.descricao"></span>
                                <span class="text-xs text-slate-500" x-text="'Disponível: ' + it.disponivel + (it.tipo === 'servico' ? ' (serviço)' : '')"></span>
                                <input type="text" inputmode="decimal" x-model="it.inputCancelar" :placeholder="'Qtd a cancelar (máx. ' + it.disponivel + ')'" class="w-full rounded-lg border border-primary/20 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100"/>
                            </div>
                        </template>
                    </div>
                </div>
                <p x-show="historicoCarregandoCancelarDetalhe" class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400">Carregando itens da venda…</p>
                <h3 class="text-slate-900 dark:text-slate-100 text-base font-bold leading-tight px-4 pb-2 pt-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">comment</span>
                    Justificativa do Cancelamento
                </h3>
                <div class="flex flex-col gap-4 px-4 py-3 pb-6">
                    <label class="flex flex-col w-full">
                        <textarea x-model="historicoMotivo" placeholder="Descreva obrigatoriamente o motivo do cancelamento..." rows="4" class="w-full min-h-36 resize-none rounded-xl border border-primary/20 bg-white dark:bg-slate-800/50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 p-4 text-base font-normal leading-normal"></textarea>
                        <p x-show="historicoErroMotivo" class="mt-1 text-xs text-red-600 dark:text-red-400">Informe o motivo do cancelamento.</p>
                    </label>
                    <div x-show="historicoCancelarRequerSenha" class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">Você não tem permissão para cancelar. Digite a senha do autorizador<span x-show="historicoCancelarAutorizadorNome"> (<span x-text="historicoCancelarAutorizadorNome"></span>)</span> para continuar:</p>
                        <input type="password" x-model="historicoSenhaAutorizacao" placeholder="Senha do autorizador" class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"/>
                    </div>
                </div>
            </div>
            {{-- Actions Footer — sempre visível no rodapé --}}
            <div class="p-4 flex flex-col gap-3 border-t border-primary/10 shrink-0 bg-background-light dark:bg-background-dark" style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 0));">
                <button type="button" @click="confirmarCancelamentoHistorico()" :disabled="historicoCancelando || (historicoCancelarRequerSenha && !historicoSenhaAutorizacao.trim())" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-md flex items-center justify-center gap-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined" x-show="!historicoCancelando">delete_forever</span>
                    <span class="material-symbols-outlined animate-spin" x-show="historicoCancelando">progress_activity</span>
                    <span x-text="historicoCancelando ? 'Cancelando...' : (historicoCancelarRequerSenha ? 'Autorizar e cancelar' : 'Confirmar Cancelamento')"></span>
                </button>
                <button type="button" @click="fecharModalCancelarHistorico()" class="w-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold py-4 rounded-xl transition-colors text-center hover:bg-slate-300 dark:hover:bg-slate-600">
                    Voltar / Manter Venda
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Observação (independente — abas Observação e Observação interna) --}}
    <div x-show="mostrarObservacaoModal" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="mostrarObservacaoModal = false">
        <div class="pdv-stitch-modal-content bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-lg w-full mx-auto">
            <header class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Observação</h2>
                <button type="button" @click="mostrarObservacaoModal = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <div class="p-4">
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <div class="flex border-b border-slate-200 dark:border-slate-700">
                        <button type="button" @click="abaObservacaoOrcamento = 'normal'" class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                                :class="abaObservacaoOrcamento === 'normal' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'">
                            Observação
                        </button>
                        <button type="button" @click="abaObservacaoOrcamento = 'interna'" class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                                :class="abaObservacaoOrcamento === 'interna' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'">
                            Observação interna
                        </button>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-900">
                        <div x-show="abaObservacaoOrcamento === 'normal'" class="min-h-[100px]">
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Observação (visível ao cliente)</label>
                            <textarea x-model="observacaoOrcamento" rows="4" placeholder="Texto que pode aparecer no orçamento para o cliente" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary/20 outline-none resize-none"></textarea>
                        </div>
                        <div x-show="abaObservacaoOrcamento === 'interna'" class="min-h-[100px]">
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Observação interna (não aparece para o cliente)</label>
                            <textarea x-model="observacaoInternaOrcamento" rows="4" placeholder="Uso interno da equipe" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary/20 outline-none resize-none"></textarea>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" @click="mostrarObservacaoModal = false" class="flex-1 py-2.5 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-semibold">Fechar</button>
                    <button type="button" @click="mostrarObservacaoModal = false" class="flex-1 py-2.5 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary/90">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Salvar orçamento (confirmação; observação e observação interna são enviadas junto se preenchidas) --}}
    <div x-show="mostrarOrcamentoModal" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="mostrarOrcamentoModal = false">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-lg w-full mx-auto flex flex-col max-h-[90vh] min-h-0">
            <header class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 shrink-0">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Salvar orçamento</h2>
                <button type="button" @click="mostrarOrcamentoModal = false" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <div class="p-4 flex-1 min-h-0 overflow-y-auto">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Itens no carrinho: <span x-text="itens.length"></span>. Total: R$ <span x-text="formatarValor(totalFinal)"></span></p>
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">Cliente: <strong x-text="clienteNome || 'Cliente balcão'"></strong></p>
                <p x-show="observacaoOrcamento.trim() || observacaoInternaOrcamento.trim()" class="text-xs text-slate-500 dark:text-slate-400 mb-4">As observações (normal e interna) preenchidas serão salvas junto com o orçamento.</p>
            </div>
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 shrink-0 flex gap-2 bg-white dark:bg-slate-900 rounded-b-2xl">
                <button type="button" @click="mostrarOrcamentoModal = false" class="flex-1 py-3 rounded-lg border-2 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-semibold">Cancelar</button>
                <button type="button" @click="confirmarSalvarOrcamento()" class="flex-1 py-3 rounded-lg text-sm font-semibold border-2 border-amber-600 hover:opacity-90" style="background-color:#d97706;color:#fff;">Salvar orçamento</button>
            </div>
        </div>
    </div>

    {{-- Modal Orçamentos salvos: busca, lista, ver detalhes, importar --}}
    <div x-show="mostrarOrcamentosSalvosModal" x-cloak class="pdv-modal pdv-modal-stitch" @keydown.escape.window="orcamentoDetalheModal ? fecharDetalheOrcamento() : mostrarOrcamentosSalvosModal = false">
        <div class="pdv-modal-content max-w-lg w-full max-h-[90vh] flex flex-col">
            <header class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 shrink-0">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Orçamentos salvos</h2>
                <button type="button" @click="mostrarOrcamentosSalvosModal = false; orcamentoDetalheModal = null" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <template x-if="!orcamentoDetalheModal">
                <div class="flex flex-col flex-1 min-h-0">
                    <div class="p-4 shrink-0">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                            <input type="text" x-model="orcamentosSalvosBusca" @input.debounce.300ms="carregarOrcamentosSalvos()" placeholder="Buscar por cliente ou ID do orçamento" class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/20 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="px-4 pb-2 flex items-center justify-between shrink-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Orçamentos</span>
                        <span class="text-xs font-medium bg-slate-200 dark:bg-slate-700 px-2 py-1 rounded text-slate-600 dark:text-slate-300" x-text="(orcamentosSalvosLista.length || 0) + ' encontrados'"></span>
                    </div>
                    <div class="flex-1 overflow-y-auto px-4 pb-4 space-y-3">
                        <template x-if="orcamentosSalvosCarregando">
                            <p class="py-4 text-slate-500 dark:text-slate-400 text-sm flex items-center gap-2"><span class="material-symbols-outlined animate-pulse text-primary/60">progress_activity</span> Carregando...</p>
                        </template>
                        <template x-if="!orcamentosSalvosCarregando && orcamentosSalvosLista.length === 0">
                            <p class="py-6 text-slate-500 dark:text-slate-400 text-sm text-center">Nenhum orçamento encontrado.</p>
                        </template>
                        <template x-for="o in orcamentosSalvosLista" :key="o.id">
                            <div class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-primary/30 transition-all shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-primary mb-1" x-text="'#' + o.id"></span>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-base truncate" x-text="o.nome_cliente || 'Sem cliente'"></h4>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0">
                                        <span class="text-lg font-bold text-primary" x-text="'R$ ' + formatarValor(o.total || 0)"></span>
                                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Total</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-700 mt-2">
                                    <span class="text-xs text-slate-500 dark:text-slate-400" x-text="formatarDataOrcamento(o.created_at)"></span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="verDetalheOrcamento(o)" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                                            <span class="material-symbols-outlined text-base">visibility</span> Ver detalhes
                                        </button>
                                        <button type="button" @click="importarOrcamento(o.id)" class="text-xs font-semibold bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary/90 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">download</span> Importar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="orcamentoDetalheModal">
                <div class="flex flex-col flex-1 min-h-0">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <h3 class="font-bold text-slate-900 dark:text-white">Detalhes #<span x-text="orcamentoDetalheModal.id"></span></h3>
                        <button type="button" @click="fecharDetalheOrcamento()" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </button>
                    </div>
                    <div class="p-4 flex-1 overflow-y-auto">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2"><span class="font-medium">Cliente:</span> <span x-text="orcamentoDetalheModal.nome_cliente || 'Sem cliente'"></span></p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><span class="font-medium">Total:</span> R$ <span x-text="formatarValor(orcamentoDetalheModal.total || 0)"></span></p>
                        <ul class="space-y-2 mb-4">
                            <template x-for="i in (orcamentoDetalheModal.itens || [])" :key="i.id || (i.descricao + i.quantidade)">
                                <li class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700 text-sm">
                                    <span class="text-slate-900 dark:text-white" x-text="i.descricao"></span>
                                    <span class="text-primary font-semibold" x-text="(i.quantidade || 0) + ' x R$ ' + formatarValor(i.preco_unitario || 0)"></span>
                                </li>
                            </template>
                        </ul>
                        <template x-if="(orcamentoDetalheModal.observacao || orcamentoDetalheModal.observacao_interna)">
                            <div class="space-y-2 border-t border-slate-200 dark:border-slate-700 pt-3">
                                <div x-show="orcamentoDetalheModal.observacao" class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <button type="button" @click="detalheObsAberto = !detalheObsAberto" class="w-full flex items-center justify-between px-3 py-2.5 bg-slate-50 dark:bg-slate-800/50 text-left text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <span>Observação</span>
                                        <span class="material-symbols-outlined text-lg transition-transform" :class="detalheObsAberto ? 'rotate-180' : ''">expand_more</span>
                                    </button>
                                    <div x-show="detalheObsAberto" class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap border-t border-slate-100 dark:border-slate-700" x-text="orcamentoDetalheModal.observacao"></div>
                                </div>
                                <div x-show="orcamentoDetalheModal.observacao_interna" class="rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                    <button type="button" @click="detalheObsInternaAberto = !detalheObsInternaAberto" class="w-full flex items-center justify-between px-3 py-2.5 bg-slate-50 dark:bg-slate-800/50 text-left text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <span>Observação interna</span>
                                        <span class="material-symbols-outlined text-lg transition-transform" :class="detalheObsInternaAberto ? 'rotate-180' : ''">expand_more</span>
                                    </button>
                                    <div x-show="detalheObsInternaAberto" class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap border-t border-slate-100 dark:border-slate-700" x-text="orcamentoDetalheModal.observacao_interna"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800 shrink-0 flex flex-wrap gap-2">
                        <button type="button" @click="fecharDetalheOrcamento()" class="flex-1 min-w-[100px] py-2.5 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-semibold">Voltar</button>
                        <button type="button" @click="imprimirOrcamentoFromOrcamentoSalvo(orcamentoDetalheModal)" class="flex-1 min-w-[100px] py-2.5 rounded-lg border border-primary text-primary text-sm font-semibold hover:bg-primary/10">Gerar PDF</button>
                        <button type="button" @click="importarOrcamento(orcamentoDetalheModal.id)" class="flex-1 min-w-[100px] py-2.5 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary/90">Importar para venda</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Modal Finalizar venda (layout pagamento detalhado - code.html) --}}
    <div x-show="mostrarFinalizar" x-cloak class="pdv-modal pdv-modal-grande pdv-modal-pagamento" @keydown.escape.window="mostrarFinalizar = false">
        <div class="pdv-modal-content pdv-pagamento-content">
            <header class="pdv-pagamento-header">
                <button type="button" class="pdv-pagamento-voltar" @click="mostrarFinalizar = false">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <h2 class="pdv-pagamento-titulo">Pagamento</h2>
            </header>

            <div class="pdv-pagamento-cliente">
                <span class="text-sm text-slate-600 dark:text-slate-400">Cliente:</span>
                <span class="font-medium" x-text="clienteNome || 'Cliente balcão'"></span>
                <button type="button" @click="mostrarFinalizar = false; telaClienteModal = 'selecao'; mostrarCliente = true; clienteBuscaModal = ''; $nextTick(() => buscarClientesModal())" class="text-primary text-sm font-medium hover:underline">Alterar</button>
            </div>

            <div class="pdv-pagamento-body">
            <div class="pdv-pagamento-resumo">
                <div class="pdv-pagamento-card pdv-pagamento-card-total">
                    <p class="pdv-pagamento-label">Total da Venda</p>
                    <p class="pdv-pagamento-valor-primario">R$ <span x-text="formatarValor(totalFinal)"></span></p>
                </div>
                <div class="pdv-pagamento-desconto-linha">
                    <p class="text-xs font-medium text-slate-500">Desconto Aplicado:</p>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">R$ <span x-text="formatarValor(desconto)"></span></p>
                </div>
                <div class="pdv-pagamento-card">
                    <p class="pdv-pagamento-label">Valor Pago</p>
                    <p class="pdv-pagamento-valor-verde">R$ <span x-text="formatarValor(somaPagamentos)"></span></p>
                </div>
                <div class="pdv-pagamento-card">
                    <p class="pdv-pagamento-label">Saldo Restante</p>
                    <p class="pdv-pagamento-valor-vermelho">R$ <span x-text="formatarValor(saldoRestanteFinalizar)"></span></p>
                </div>
            </div>

            {{-- Desconto --}}
            <section class="pdv-pagamento-secao">
                <div class="flex items-center justify-between mb-2">
                    <label class="pdv-pagamento-secao-titulo">Desconto</label>
                    <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                        <button type="button" class="pdv-pagamento-toggle px-3 py-1 text-xs font-bold rounded-md transition-colors" :class="descontoTipoPagamento === 'valor' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:text-primary'" @click="descontoTipoPagamento = 'valor'">R$</button>
                        <button type="button" class="pdv-pagamento-toggle px-3 py-1 text-xs font-bold rounded-md transition-colors" :class="descontoTipoPagamento === 'percentual' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:text-primary'" @click="descontoTipoPagamento = 'percentual'">%</button>
                    </div>
                </div>
                <div class="relative">
                    <input type="text" x-model="descontoInputValor" placeholder="Digite o valor do desconto" class="pdv-pagamento-input w-full pr-24"
                           @keydown.enter.prevent="aplicaDesconto()"/>
                    <button type="button" class="pdv-pagamento-btn-aplicar" @click.stop.prevent="aplicaDesconto()">APLICAR</button>
                </div>
            </section>

            {{-- Adicionar pagamento (botões por forma) --}}
            <section class="pdv-pagamento-secao">
                <h3 class="pdv-pagamento-secao-titulo">Adicionar Pagamento</h3>
                <div class="pdv-pagamento-formas">
                    <template x-for="f in formasPagamento" :key="f.id">
                        <button type="button" class="pdv-pagamento-forma-btn"
                                :class="{ 'pdv-pagamento-forma-btn-ativo': pagamentoFormaSelecionada == f.id }"
                                @click="selecionarFormaPagamento(pagamentoFormaSelecionada == f.id ? null : f)">
                            <span class="material-symbols-outlined pdv-pagamento-forma-icone" x-text="getFormaIcon(f.tipo)"></span>
                            <span class="text-xs font-medium" x-text="f.nome"></span>
                        </button>
                    </template>
                </div>
            </section>

            {{-- Card valor + adquirente/bandeira/parcelas + Confirmar --}}
            <section x-show="pagamentoFormaSelecionada" x-cloak class="pdv-pagamento-card-form">
                <template x-if="getFormaById(pagamentoFormaSelecionada)">
                    <div class="space-y-4">
                        <div>
                            <label class="pdv-pagamento-input-label">Valor a Receber (<span x-text="getFormaById(pagamentoFormaSelecionada)?.nome"></span>)</label>
                            <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-lg border border-transparent focus-within:ring-2 focus-within:ring-primary/50">
                                <span class="flex items-center justify-center w-12 h-12 shrink-0 text-slate-500 dark:text-slate-400 font-bold text-lg">R$</span>
                                <input type="text" x-model="pagamentoValorAtual" class="pdv-pagamento-input-valor flex-1 min-w-0 py-3 pr-4 bg-transparent border-none text-lg font-bold focus:ring-0 focus:outline-none rounded-lg"/>
                            </div>
                        </div>
                        <template x-if="formaPermiteCartao(getFormaById(pagamentoFormaSelecionada))">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="pdv-pagamento-input-label">Adquirente</label>
                                    <select x-model="pagamentoAdquirenteId" class="w-full p-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                        <option value="">Selecione</option>
                                        <template x-for="a in adquirentes" :key="a.id">
                                            <option :value="a.id" x-text="a.nome"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="pdv-pagamento-input-label">Bandeira</label>
                                    <select x-model="pagamentoBandeira" class="w-full p-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                        <option value="">Selecione</option>
                                        <template x-for="b in bandeiras" :key="b.value">
                                            <option :value="b.value" x-text="b.label"></option>
                                        </template>
                                    </select>
                                    <p x-show="formaPermiteCartao(getFormaById(pagamentoFormaSelecionada)) && bandeiras.length === 0" class="text-xs text-amber-600 dark:text-amber-400">Cadastre bandeiras em Financeiro &gt; Adquirentes &gt; Taxas.</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="formaEhPix(getFormaById(pagamentoFormaSelecionada))">
                            <div class="flex flex-col gap-1">
                                <label class="pdv-pagamento-input-label">Chave PIX</label>
                                <select x-model="pagamentoPixChaveId" class="w-full p-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                    <option value="">Selecione a chave</option>
                                    <template x-for="k in getPixChavesAtivas(getFormaById(pagamentoFormaSelecionada))" :key="k.id">
                                        <option :value="k.id" x-text="(k.nome || 'Chave PIX') + ' - ' + (k.chave || '')"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                        <template x-if="formaPermiteParcelas(getFormaById(pagamentoFormaSelecionada))">
                            <div class="flex flex-col gap-1">
                                <label class="pdv-pagamento-input-label">Parcelas</label>
                                <select x-model="pagamentoParcelas" class="w-full p-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                    <template x-for="n in getParcelasOptions(getFormaById(pagamentoFormaSelecionada))" :key="n">
                                        <option :value="n" x-text="n === 1 ? '1x à vista' : (n + 'x')"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                        <template x-if="formaEhPix(getFormaById(pagamentoFormaSelecionada)) && (pagamentoParcelas || 1) > 1">
                            <div class="flex flex-col gap-1">
                                <label class="pdv-pagamento-input-label">1º vencimento</label>
                                <input type="date" x-model="pagamentoPixPrimeiroVencimento" class="w-full p-2 bg-slate-50 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50"/>
                                <p class="text-xs text-slate-500 dark:text-slate-400">As demais parcelas serao geradas mensalmente a partir desta data.</p>
                            </div>
                        </template>
                        <button type="button" class="pdv-pagamento-btn-confirmar w-full py-3 rounded-lg font-bold flex items-center justify-center gap-2" @click="confirmarValorPagamento()">
                            <span class="material-symbols-outlined">add_circle</span>
                            Confirmar Valor
                        </button>
                    </div>
                </template>
            </section>

            {{-- Lista pagamentos inseridos --}}
            <section class="pdv-pagamento-secao">
                <h3 class="pdv-pagamento-secao-titulo">Pagamentos Inseridos</h3>
                <div class="space-y-2" x-show="pagamentosModal.length">
                    <template x-for="(pag, i) in pagamentosModal" :key="'pm-'+i">
                        <div class="pdv-pagamento-item">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg" :class="(pag.forma_tipo || '').indexOf('credito') >= 0 || (pag.forma_tipo || '').indexOf('debito') >= 0 ? 'bg-primary/10 text-primary' : 'bg-green-50 dark:bg-green-900/20 text-green-600'">
                                    <span class="material-symbols-outlined text-xl block" x-text="getFormaIcon(pag.forma_tipo)"></span>
                                </div>
                                <div>
                                    <p class="font-bold" x-text="pag.forma_nome || 'Pagamento'"></p>
                                    <p class="text-xs text-slate-500 uppercase" x-text="(pag.parcelas && pag.parcelas > 1) ? (pag.parcelas + 'x Parcelado') : ''"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="font-bold text-lg text-slate-700 dark:text-slate-300">R$ <span x-text="formatarValor(pag.valor)"></span></p>
                                <button type="button" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-1 rounded-md transition-colors" @click="removerPagamento(i)">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <p x-show="!pagamentosModal.length" class="text-sm text-slate-500 py-2">Nenhum pagamento adicionado. Selecione uma forma acima e confirme o valor.</p>
            </section>

            </div>
            {{-- /pdv-pagamento-body --}}

            {{-- Rodapé fixo --}}
            <div class="pdv-pagamento-rodape">
                <div class="flex justify-between items-center text-sm px-2">
                    <span class="text-slate-500" x-text="saldoRestanteFinalizar <= 0 ? 'Pagamento concluído' : 'Aguardando pagamento total'"></span>
                    <span class="font-bold" :class="saldoRestanteFinalizar <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="saldoRestanteFinalizar <= 0 ? 'Pago' : ('Faltam R$ ' + formatarValor(saldoRestanteFinalizar))"></span>
                </div>
                <button type="button" class="pdv-pagamento-btn-finalizar w-full py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2"
                        :disabled="saldoRestanteFinalizar > 0.01"
                        :class="saldoRestanteFinalizar <= 0.01 ? '' : 'opacity-50 cursor-not-allowed'"
                        @click="confirmarVenda()">
                    <span class="material-symbols-outlined">print</span>
                    Finalizar e Imprimir
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: senha do autorizador para desconto acima do máximo --}}
    <div x-show="mostrarDescontoAutorizacaoModal" x-cloak class="pdv-modal pdv-modal-stitch" style="z-index: 60;" @keydown.escape.window="mostrarDescontoAutorizacaoModal = false; descontoPendenteValor = null; descontoSenhaAutorizacao = ''">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-md">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="mostrarDescontoAutorizacaoModal = false; descontoPendenteValor = null; descontoSenhaAutorizacao = ''" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2">Desconto acima do permitido</h2>
            </header>
            <div class="p-4 space-y-4">
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">
                        O desconto de <span x-text="descontoPendentePercentualAplicado != null ? descontoPendentePercentualAplicado.toFixed(1).replace('.', ',') : ''"></span>% está acima do permitido (<span x-text="descontoMaximoPercentual != null ? descontoMaximoPercentual.toString().replace('.', ',') : ''"></span>%). Digite a senha do autorizador<span x-show="descontoAutorizadorNome"> (<span x-text="descontoAutorizadorNome"></span>)</span> para aplicar:
                    </p>
                    <input type="password" x-model="descontoSenhaAutorizacao" placeholder="Senha do autorizador"
                           class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"
                           @keydown.enter.prevent="enviarSenhaDescontoAutorizacao()"/>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="mostrarDescontoAutorizacaoModal = false; descontoPendenteValor = null; descontoSenhaAutorizacao = ''" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                    <button type="button" @click="enviarSenhaDescontoAutorizacao()" :disabled="!descontoSenhaAutorizacao.trim()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">Autorizar e aplicar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: cliente bloqueado - autorização --}}
    <div x-show="clienteBloqueadoModal" x-cloak class="pdv-modal pdv-modal-stitch" style="z-index: 62;" @keydown.escape.window="fecharModalClienteBloqueado()">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-md">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="fecharModalClienteBloqueado()" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2">Cliente bloqueado para vendas</h2>
            </header>
            <div class="p-4 space-y-4">
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">
                        O cliente <strong x-text="clienteBloqueadoSelecionado ? clienteBloqueadoSelecionado.nome : ''"></strong> está bloqueado para vendas. Digite a senha do administrador ou de um usuário autorizador<span x-show="clienteBloqueadoAutorizadorNome"> (<span x-text="clienteBloqueadoAutorizadorNome"></span>)</span> para continuar:
                    </p>
                    <input type="password" x-model="clienteBloqueadoSenha" placeholder="Senha do autorizador"
                           class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"
                           @keydown.enter.prevent="confirmarClienteBloqueadoSenha()"/>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="fecharModalClienteBloqueado()" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                    <button type="button" @click="confirmarClienteBloqueadoSenha()" :disabled="!clienteBloqueadoSenha.trim()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">Autorizar e selecionar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: limite de crédito excedido (boleto) - autorização --}}
    <div x-show="limiteCreditoExcedidoModal" x-cloak class="pdv-modal pdv-modal-stitch" style="z-index: 62;" @keydown.escape.window="fecharModalLimiteCredito()">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-md">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="fecharModalLimiteCredito()" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2">Limite de crédito excedido</h2>
            </header>
            <div class="p-4 space-y-4">
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2" x-text="limiteCreditoExcedidoMensagem || 'O valor em boleto ultrapassa o limite de crédito do cliente. Digite a senha do administrador ou de um usuário autorizador para continuar.'"></p>
                    <input type="password" x-model="limiteCreditoSenha" placeholder="Senha do autorizador"
                           class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"
                           @keydown.enter.prevent="confirmarLimiteCreditoSenha()"/>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="fecharModalLimiteCredito()" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                    <button type="button" @click="confirmarLimiteCreditoSenha()" :disabled="!limiteCreditoSenha.trim()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">Autorizar e finalizar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: estoque insuficiente / zerado — autorização para adicionar ou finalizar --}}
    <div x-show="estoqueInsuficienteModal" x-cloak class="pdv-modal pdv-modal-stitch" style="z-index: 62;" @keydown.escape.window="fecharModalEstoqueInsuficiente()">
        <div class="pdv-stitch-modal-content bg-background-light dark:bg-background-dark max-w-lg mx-auto rounded-2xl border border-primary/10 shadow-xl">
            <header class="flex items-center p-4 border-b border-primary/10">
                <button type="button" @click="fecharModalEstoqueInsuficiente()" class="p-2 -ml-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
                <h2 class="text-lg font-bold flex-1 ml-2" x-text="itemPendenteEstoque ? 'Não é possível adicionar: estoque insuficiente' : 'Estoque insuficiente ou zerado'"></h2>
            </header>
            <div class="p-4 space-y-4">
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">
                        <span x-text="itemPendenteEstoque ? 'O produto não possui estoque suficiente. Digite a senha do administrador ou de um usuário com permissão para autorizar a inclusão no carrinho' : 'Há produto(s) com estoque zerado ou insuficiente. Digite a senha do administrador ou de um usuário com permissão para vender com estoque zerado'"></span><span x-show="estoqueInsuficienteAutorizadorNome"> (<span x-text="estoqueInsuficienteAutorizadorNome"></span>)</span>:
                    </p>
                    <input type="password" x-model="estoqueInsuficienteSenha" placeholder="Senha do autorizador"
                           class="w-full rounded-xl border border-primary/20 bg-white dark:bg-slate-800 px-4 py-3 text-slate-900 dark:text-slate-100 placeholder:text-slate-400"
                           @keydown.enter.prevent="confirmarEstoqueInsuficienteSenha()"/>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="fecharModalEstoqueInsuficiente()" class="flex-1 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold">Cancelar</button>
                    <button type="button" @click="confirmarEstoqueInsuficienteSenha()" :disabled="!estoqueInsuficienteSenha.trim()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed" x-text="itemPendenteEstoque ? 'Autorizar e adicionar' : 'Autorizar e finalizar'"></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tela Comprovante de Venda (após finalizar) --}}
    <div x-show="comprovanteVenda" x-cloak class="fixed inset-0 z-50 flex flex-col bg-background-light dark:bg-background-dark" style="display: none;">
        <header class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 shadow-sm shrink-0">
            <button type="button" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors" @click="comprovanteVenda = null">
                <span class="material-symbols-outlined align-middle">arrow_back</span>
            </button>
            <h1 class="text-lg font-bold">Comprovante de Venda</h1>
            <div class="w-10"></div>
        </header>
        <main class="flex-1 overflow-y-auto p-4 pb-32 max-w-md mx-auto w-full">
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg overflow-hidden border border-slate-200 dark:border-slate-800">
                <div class="p-6 flex flex-col items-center text-center gap-3">
                    <div class="size-16 bg-primary/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-4xl">store</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-primary" x-text="comprovanteVenda && comprovanteVenda.empresa_nome ? comprovanteVenda.empresa_nome : 'Loja'"></h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400" x-text="comprovanteVenda && comprovanteVenda.empresa_cnpj ? ('CNPJ: ' + comprovanteVenda.empresa_cnpj) : ''"></p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center border-b border-dashed border-slate-200 dark:border-slate-700">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Número do Cupom</span>
                        <span class="font-mono font-medium" x-text="comprovanteVenda ? ('#' + String(comprovanteVenda.id).padStart(6, '0')) : ''"></span>
                    </div>
                    <div class="flex flex-col text-right">
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Data e Hora</span>
                        <span class="text-sm font-medium" x-text="comprovanteVenda && comprovanteVenda.created_at ? new Date(comprovanteVenda.created_at).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''"></span>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-2">Itens Vendidos</h3>
                    <template x-for="(item, idx) in (comprovanteVenda && comprovanteVenda.itens ? comprovanteVenda.itens : [])" :key="idx">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <p class="font-semibold text-sm" x-text="item.descricao || '-'"></p>
                                <p x-show="item.serial" class="flex items-center gap-1 mt-0.5 text-xs text-slate-500 dark:text-slate-400 font-mono">S/N: <span x-text="item.serial"></span></p>
                                <p x-show="item.observacao" class="text-xs text-slate-500 dark:text-slate-400 italic" x-text="item.observacao"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-sm">R$ <span x-text="formatarValor(item.total)"></span></p>
                                <p class="text-[10px] text-slate-400"><span x-text="formatarQuantidade(item.quantidade)"></span> un x R$ <span x-text="formatarValor(item.preco_unitario)"></span></p>
                            </div>
                        </div>
                    </template>
                    <div class="border-t border-slate-100 dark:border-slate-800 my-4"></div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                            <span>Subtotal</span>
                            <span>R$ <span x-text="comprovanteVenda ? formatarValor(comprovanteVenda.total) : '0,00'"></span></span>
                        </div>
                        <div x-show="comprovanteVenda && parseFloat(comprovanteVenda.desconto) > 0" class="flex justify-between text-sm text-emerald-600 font-medium">
                            <span>Desconto aplicado</span>
                            <span>- R$ <span x-text="comprovanteVenda ? formatarValor(comprovanteVenda.desconto) : '0,00'"></span></span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="text-lg font-bold">Total</span>
                            <span class="text-2xl font-black text-primary">R$ <span x-text="comprovanteVenda ? formatarValor(comprovanteVenda.total_final) : '0,00'"></span></span>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-3">Forma de Pagamento</h3>
                    <template x-for="(p, idx) in (comprovanteVenda && comprovanteVenda.pagamentos ? comprovanteVenda.pagamentos : [])" :key="idx">
                        <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-700 mb-2">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">credit_card</span>
                                </div>
                                <span class="text-sm font-semibold" x-text="(p.forma_pagamento && p.forma_pagamento.nome ? p.forma_pagamento.nome : 'Pagamento') + (p.parcelas > 1 ? ' (' + p.parcelas + 'x)' : '')"></span>
                            </div>
                            <span class="text-sm font-bold">R$ <span x-text="formatarValor(p.valor)"></span></span>
                        </div>
                    </template>
                </div>
                <div x-show="comprovanteVenda && (comprovanteVenda?.observacoes || '').trim()" class="p-6 border-t border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-2">Observação</h3>
                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap" x-text="(comprovanteVenda?.observacoes || '').trim()"></p>
                </div>
                <div class="p-6 text-center border-t border-dashed border-slate-200 dark:border-slate-800">
                    <div class="bg-primary/5 dark:bg-primary/10 p-4 rounded-xl border border-primary/10">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <span class="material-symbols-outlined text-primary text-lg">verified</span>
                            <h4 class="text-sm font-bold text-primary uppercase tracking-wide">Garantia</h4>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            Guarde este comprovante para sua referência.
                        </p>
                    </div>
                    <p class="mt-6 text-[10px] text-slate-400 uppercase tracking-widest">Obrigado pela preferência!</p>
                </div>
            </div>
        </main>
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 flex flex-col gap-3 max-w-md mx-auto">
            <div class="flex gap-3">
                <button type="button" @click="compartilharComprovante()" class="flex-1 flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-primary/10 text-primary font-bold hover:bg-primary/20 transition-all border border-primary/20">
                    <span class="material-symbols-outlined">share</span>
                    Compartilhar
                </button>
                <button type="button" class="flex-[1.5] flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-primary text-white font-bold hover:opacity-90 transition-all shadow-lg shadow-primary/30" @click="comprovanteVenda = null">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nova Venda
                </button>
            </div>
        </div>
    </div>

    {{-- Notificação estilizada (toast) --}}
    <div x-show="notificacao.mostrar" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] max-w-[90vw] sm:max-w-md px-4 py-3 rounded-xl shadow-lg border flex items-center gap-3"
         :class="notificacao.tipo === 'erro' ? 'bg-red-50 dark:bg-red-950/80 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200' : 'bg-primary text-white border-primary/20 shadow-primary/20'">
        <span class="material-symbols-outlined shrink-0" :class="notificacao.tipo === 'erro' ? 'text-red-600 dark:text-red-400' : ''" x-text="notificacao.tipo === 'erro' ? 'error' : 'check_circle'"></span>
        <p class="text-sm font-medium" x-text="notificacao.texto"></p>
    </div>
</div>

<style>
/* Design system Stitch (cores e tipografia) — idêntico aos HTMLs da pasta stitch/ */
.pdv-stitch {
    --pdv-primary: #1a227f;
    --pdv-primary-light: #2d38a8;
    --pdv-bg-light: #f6f6f8;
    --pdv-bg-dark: #121320;
    font-family: 'Inter', sans-serif;
}
.pdv-stitch .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
/* Utilitários Stitch (quando Tailwind não estende primary/background) */
.pdv-stitch .bg-background-light { background-color: #f6f6f8; }
.pdv-stitch .dark .bg-background-dark,
.pdv-stitch .dark.bg-background-dark { background-color: #121320; }
.pdv-stitch .border-primary\/10 { border-color: rgba(26, 34, 127, 0.1); }
.pdv-stitch .text-primary { color: #1a227f; }
.pdv-stitch .bg-primary { background-color: #1a227f; }
.pdv-stitch .bg-primary\/5 { background-color: rgba(26, 34, 127, 0.05); }
.pdv-stitch .bg-primary\/10 { background-color: rgba(26, 34, 127, 0.1); }
.pdv-stitch .hover\:bg-primary\/90:hover { background-color: rgba(26, 34, 127, 0.9); }
.pdv-stitch .shadow-primary\/20 { box-shadow: 0 10px 15px -3px rgba(26, 34, 127, 0.2); }
.pdv-stitch .focus\:ring-primary\/20:focus { box-shadow: 0 0 0 2px rgba(26, 34, 127, 0.2); }
.pdv-stitch .font-display { font-family: 'Inter', sans-serif; }
/* Autocomplete no campo busca (lupa) */
.pdv-autocomplete-item { cursor: pointer; outline: none; }
.pdv-autocomplete-item:focus { background: rgba(26, 34, 127, 0.08); }
/* Modais estilo Stitch */
.pdv-modal.pdv-modal-stitch { align-items: flex-start; padding: 1rem; padding-top: 2rem; overflow-y: auto; }
.pdv-stitch-modal-content { width: 100%; max-width: 28rem; margin: 0 auto 2rem; border-radius: 1rem; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
.pdv-stitch .dark .bg-background-dark,
.dark .pdv-stitch .bg-background-dark { background-color: #121320; }

/* Tela Abertura de Caixa (Stitch) */
.pdv-stitch-screen.pdv-stitch-abertura {
    min-height: 100vh; min-height: 100dvh;
    background: var(--pdv-bg-light);
    color: #0f172a;
    display: flex; flex-direction: column;
    position: relative;
}
.dark .pdv-stitch-screen.pdv-stitch-abertura { background: var(--pdv-bg-dark); color: #f1f5f9; }
.pdv-stitch-abertura .pdv-stitch-header {
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
    border-bottom: 1px solid rgba(26, 34, 127, 0.1);
    background: var(--pdv-bg-light);
}
.dark .pdv-stitch-abertura .pdv-stitch-header { background: var(--pdv-bg-dark); border-color: rgba(255,255,255,0.08); }
.pdv-stitch-title { font-size: 1.125rem; font-weight: 700; margin: 0; }
.pdv-stitch-main { flex: 1; display: flex; flex-direction: column; max-width: 28rem; margin: 0 auto; width: 100%; padding: 1.5rem 1.5rem 2rem; }
.pdv-stitch-hero { text-align: center; margin-bottom: 2rem; }
.pdv-stitch-hero-title { font-size: 1.875rem; font-weight: 700; margin: 0 0 0.5rem; color: #0f172a; }
.dark .pdv-stitch-hero-title { color: #f8fafc; }
.pdv-stitch-hero-sub { font-size: 1rem; color: #64748b; margin: 0; }
.dark .pdv-stitch-hero-sub { color: #94a3b8; }
.pdv-stitch-amount-block { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; margin-bottom: 2rem; }
.pdv-stitch-label { color: var(--pdv-primary); font-weight: 600; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
.pdv-stitch-amount-display { width: 100%; text-align: center; }
.pdv-stitch-currency { color: #94a3b8; font-size: 1.5rem; font-weight: 500; margin-right: 0.25rem; }
.pdv-stitch-amount-value { color: #0f172a; font-size: 3.75rem; font-weight: 700; }
.dark .pdv-stitch-amount-value { color: #f8fafc; }
.pdv-stitch-amount-underline { height: 4px; width: 6rem; background: var(--pdv-primary); margin: 0.5rem auto 0; border-radius: 9999px; opacity: 0.3; }
.pdv-stitch-btn-primary { background: var(--pdv-primary); color: #fff; border: none; cursor: pointer; transition: all 0.2s; }
.pdv-stitch-btn-primary:hover:not(:disabled) { background: var(--pdv-primary-light); filter: brightness(1.05); }
.pdv-stitch-btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.pdv-stitch-erro { color: #dc2626; font-size: 0.875rem; }
.pdv-stitch-keypad-btn { background: #fff; border: 1px solid #e2e8f0; color: #334155; cursor: pointer; transition: transform 0.1s; }
.pdv-stitch-keypad-btn:active { transform: scale(0.95); }
.dark .pdv-stitch-keypad-btn { background: #1e293b; border-color: #334155; color: #e2e8f0; }
.pdv-stitch-select { outline: none; }
.pdv-stitch-bg-deco { position: fixed; width: 16rem; height: 16rem; border-radius: 50%; background: var(--pdv-primary); opacity: 0.05; filter: blur(60px); pointer-events: none; z-index: -1; }
.pdv-stitch-bg-1 { bottom: -6rem; right: -6rem; }
.pdv-stitch-bg-2 { top: -6rem; left: -6rem; }

/* Tela Venda (Stitch) - primary usado em bordas e focos */
.pdv-stitch-venda { min-height: 100vh; min-height: 100dvh; background: var(--pdv-bg-light); color: #0f172a; }
.dark .pdv-stitch-venda { background: var(--pdv-bg-dark); }
.pdv-stitch-venda .border-primary\\/10 { border-color: rgba(26, 34, 127, 0.1); }
.pdv-stitch-venda .bg-primary { background: var(--pdv-primary); }
.pdv-stitch-venda .bg-primary\\/5 { background: rgba(26, 34, 127, 0.05); }
.pdv-stitch-venda .text-primary { color: var(--pdv-primary); }
.pdv-stitch-venda-footer { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
.safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }

/* Base e container tipo app (fallback) */
.pdv-container { min-height: 100vh; min-height: 100dvh; }
.pdv-tela { max-width: 960px; margin: 0 auto; min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; }
.pdv-tela.pdv-app { padding: 0; padding-bottom: env(safe-area-inset-bottom, 0); }
.pdv-conteudo { flex: 1; padding: 0.75rem 1rem; padding-bottom: 100px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
@media (min-width: 640px) { .pdv-conteudo { padding: 1rem 1.25rem; padding-bottom: 1rem; } }
.pdv-header { padding: 0.6rem 1rem; padding-top: calc(0.6rem + env(safe-area-inset-top, 0)); background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); color: #fff; border-radius: 0 0 1rem 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.pdv-header-top { display: flex; justify-content: space-between; align-items: center; }
.pdv-saldo { font-weight: 700; font-size: 1rem; }
.pdv-header-acoes { display: flex; gap: 0.35rem; }
.pdv-header .pdv-btn-ico { min-width: 40px; min-height: 40px; padding: 0.5rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); color: #fff; font-size: 1rem; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
.pdv-header .pdv-btn-ico:hover { background: rgba(255,255,255,0.35); }
/* Barra inferior tipo app */
.pdv-bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; max-width: 960px; margin: 0 auto; background: #fff; border-top: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0)); box-shadow: 0 -4px 12px rgba(0,0,0,0.08); z-index: 40; }
.pdv-total-bar { font-size: 0.9rem; margin-bottom: 0.5rem; text-align: center; }
.pdv-total-bar strong { font-size: 1.15rem; color: #1e40af; }
.pdv-botoes-bar { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.pdv-bar-btn { min-height: 48px; padding: 0.6rem 0.5rem; border-radius: 0.75rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.25rem; }
.pdv-bar-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pdv-bar-btn:first-of-type { background: #e0e7ff; color: #3730a3; }
.pdv-bar-btn:nth-of-type(2) { background: #fef3c7; color: #92400e; }
.pdv-bar-btn.pdv-bar-btn-orc { background: #f59e0b; color: #fff; }
.pdv-bar-btn.pdv-bar-btn-ok { background: #16a34a; color: #fff; grid-column: span 2; min-height: 52px; font-size: 1rem; }
@media (min-width: 640px) {
  .pdv-bottom-bar { display: none; }
  .pdv-conteudo { padding-bottom: 1rem; }
}
.pdv-botoes-desktop { display: none; }
@media (min-width: 640px) {
  .pdv-botoes-desktop { display: flex; flex-wrap: wrap; gap: 0.5rem; }
}
/* Bipagem */
.pdv-bipagem { margin-bottom: 1rem; }
.pdv-input-bipagem { font-size: 1rem; padding: 0.85rem 1rem; border-radius: 0.75rem; border: 2px solid #e5e7eb; }
.pdv-input-bipagem:focus { border-color: #2563eb; outline: none; }
/* Blocos produtos/serviços */
.pdv-bloco-itens { margin-bottom: 1.25rem; border-radius: 1rem; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.pdv-bloco-produtos { border-color: #93c5fd; background: linear-gradient(to bottom, #eff6ff 0%, #fff 100%); }
.pdv-bloco-servicos { border-color: #a7f3d0; background: linear-gradient(to bottom, #ecfdf5 0%, #fff 100%); }
.pdv-bloco-titulo { margin: 0; padding: 0.55rem 1rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.pdv-bloco-produtos .pdv-bloco-titulo { background: #3b82f6; color: #fff; }
.pdv-bloco-servicos .pdv-bloco-titulo { background: #10b981; color: #fff; }
.pdv-itens { list-style: none; padding: 0; margin: 0; max-height: 240px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.pdv-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.75rem; border-bottom: 1px solid rgba(0,0,0,0.06); }
.pdv-item:last-child { border-bottom: none; }
.pdv-item-thumb { width: 48px; height: 48px; flex-shrink: 0; border-radius: 0.5rem; overflow: hidden; background: #f3f4f6; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.pdv-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
.pdv-item-thumb-placeholder { font-size: 1.5rem; }
.pdv-item-thumb-servico { cursor: default; }
.pdv-item-desc { flex: 1; font-size: 0.9rem; min-width: 0; }
.pdv-item-qtd-control { display: flex; align-items: center; gap: 0.2rem; flex-shrink: 0; }
.pdv-qtd-btn { width: 32px; height: 32px; min-width: 32px; min-height: 32px; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; font-size: 1.1rem; font-weight: 600; cursor: pointer; color: #374151; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
.pdv-qtd-btn:hover { background: #f3f4f6; }
.pdv-item-qtd-num { min-width: 1.75rem; text-align: center; font-weight: 600; font-size: 0.9rem; }
.pdv-item-unit { font-size: 0.75rem; color: #6b7280; min-width: 3.5rem; text-align: right; }
.pdv-item-preco { font-weight: 700; min-width: 4rem; text-align: right; font-size: 0.9rem; color: #111827; }
.pdv-btn-remover { color: #dc2626; font-size: 1.35rem; line-height: 1; padding: 0.4rem !important; min-width: 40px; min-height: 40px; border-radius: 0.5rem; }
.pdv-btn-remover:hover { background: #fef2f2; }
.pdv-vazio { color: #6b7280; padding: 1.5rem 1rem; text-align: center; font-size: 0.95rem; }
.pdv-totais { border-top: 2px solid #e5e7eb; padding-top: 1rem; margin-bottom: 1rem; }
.pdv-total-linha { display: flex; justify-content: space-between; margin-bottom: 0.5rem; align-items: center; }
.pdv-total-final { font-size: 1.25rem; font-weight: 700; }
.pdv-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; }
.pdv-input-valor { font-size: 1.25rem; text-align: right; }
.pdv-btn { padding: 0.75rem 1.25rem; border-radius: 0.75rem; font-weight: 600; cursor: pointer; border: none; min-height: 44px; }
.pdv-btn-primario { background: #2563eb; color: white; }
.pdv-btn-secundario { background: #e5e7eb; color: #374151; }
.pdv-btn-orcamento { background: #f59e0b; color: white; }
.pdv-btn-ico { padding: 0.5rem; min-width: 40px; min-height: 40px; }
.pdv-erro { color: #dc2626; margin-top: 0.5rem; font-size: 0.875rem; }
.pdv-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.pdv-form .pdv-btn { margin-top: 1rem; width: 100%; }
/* Abertura */
.pdv-abertura { display: flex; align-items: center; justify-content: center; padding: 1rem; }
.pdv-card { background: #fff; border-radius: 1.25rem; padding: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
.pdv-titulo { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
.pdv-subtitulo { color: #6b7280; margin-bottom: 1.5rem; }
.pdv-form label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
/* Pesquisa - grid de cards com imagem */
.pdv-resultados-grid { list-style: none; padding: 0; margin: 0.5rem 0; max-height: 60vh; overflow-y: auto; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
.pdv-resultado-card { display: flex; flex-direction: column; border-radius: 0.75rem; border: 1px solid #e5e7eb; overflow: hidden; cursor: pointer; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition: transform 0.15s, box-shadow 0.15s; }
.pdv-resultado-card:hover, .pdv-resultado-card:active { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.pdv-resultado-thumb { height: 100px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; }
.pdv-resultado-thumb img { width: 100%; height: 100%; object-fit: cover; }
.pdv-resultado-placeholder { font-size: 2rem; }
.pdv-resultado-info { padding: 0.5rem 0.6rem; display: flex; flex-direction: column; gap: 0.2rem; }
.pdv-resultado-nome { font-size: 0.8rem; font-weight: 500; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pdv-resultado-preco { font-size: 0.95rem; font-weight: 700; color: #1e40af; }
@media (min-width: 480px) { .pdv-resultados-grid { grid-template-columns: repeat(3, 1fr); } }
/* Modal carrossel */
.pdv-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 60; padding: 1rem; }
.pdv-modal-content { background: white; border-radius: 1.25rem; padding: 1.5rem; max-width: 420px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
.pdv-modal-grande { max-width: 96%; width: 100%; }
.pdv-modal-grande .pdv-modal-content { max-width: 520px; }

/* Modal Pagamento (layout code.html) */
.pdv-modal-pagamento .pdv-modal-content.pdv-pagamento-content { max-width: 28rem; padding: 0; overflow: visible; display: flex; flex-direction: column; max-height: 90vh; }
.pdv-pagamento-header { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
.dark .pdv-pagamento-header { background: #0f172a; border-color: #334155; }
.pdv-pagamento-voltar { padding: 0.5rem; border-radius: 9999px; transition: background 0.2s; color: #64748b; }
.pdv-pagamento-voltar:hover { background: #f1f5f9; color: #1a227f; }
.dark .pdv-pagamento-voltar:hover { background: #1e293b; color: #818cf8; }
.pdv-pagamento-titulo { font-size: 1.25rem; font-weight: 700; margin: 0; }
.pdv-pagamento-cliente { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding: 0.75rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.dark .pdv-pagamento-cliente { border-color: #1e293b; }
.pdv-pagamento-resumo { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; padding: 1rem 1.25rem; }
.pdv-pagamento-card { background: #fff; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; }
.dark .pdv-pagamento-card { background: #1e293b; border-color: #334155; }
.pdv-pagamento-card-total { grid-column: 1 / -1; }
.pdv-pagamento-label { font-size: 0.875rem; font-weight: 500; color: #64748b; margin: 0 0 0.25rem; }
.pdv-pagamento-valor-primario { font-size: 1.875rem; font-weight: 700; color: #1a227f; margin: 0; }
.dark .pdv-pagamento-valor-primario { color: #818cf8; }
.pdv-pagamento-valor-verde { font-size: 1.25rem; font-weight: 700; color: #16a34a; margin: 0; }
.pdv-pagamento-valor-vermelho { font-size: 1.25rem; font-weight: 700; color: #dc2626; margin: 0; }
.dark .pdv-pagamento-valor-vermelho { color: #f87171; }
.pdv-pagamento-desconto-linha { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; border-top: 1px solid #f1f5f9; }
.dark .pdv-pagamento-desconto-linha { border-color: #334155; }
.pdv-pagamento-secao { padding: 0 1.25rem 1rem; }
.pdv-pagamento-secao-titulo { font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
.pdv-pagamento-input { padding: 0.75rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.875rem; }
.dark .pdv-pagamento-input { background: #1e293b; border-color: #334155; }
.pdv-pagamento-btn-aplicar { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: #1a227f; font-weight: 700; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; }
.pdv-pagamento-btn-aplicar:hover { background: rgba(26, 34, 127, 0.08); }
.pdv-pagamento-formas { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem; }
.pdv-pagamento-forma-btn { flex: 0 0 auto; min-width: 84px; aspect-ratio: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; transition: background 0.2s; color: #64748b; }
.pdv-pagamento-forma-btn:hover { background: rgba(26, 34, 127, 0.05); color: #1a227f; }
.dark .pdv-pagamento-forma-btn { background: #1e293b; border-color: #334155; }
.dark .pdv-pagamento-forma-btn:hover { background: rgba(129, 140, 248, 0.1); color: #818cf8; }
.pdv-pagamento-forma-btn-ativo { background: #1a227f; color: #fff; border-color: #1a227f; }
.dark .pdv-pagamento-forma-btn-ativo { background: #4338ca; border-color: #4338ca; color: #fff; }
.pdv-pagamento-forma-icone { font-size: 1.5rem; margin-bottom: 0.25rem; }
.pdv-pagamento-card-form { margin: 0 1.25rem 1rem; padding: 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; }
.dark .pdv-pagamento-card-form { background: #1e293b; border-color: #334155; }
.pdv-pagamento-input-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.25rem; display: block; }
.pdv-pagamento-btn-confirmar { background: rgba(26, 34, 127, 0.1); color: #1a227f; border: none; cursor: pointer; transition: background 0.2s; }
.pdv-pagamento-btn-confirmar:hover { background: rgba(26, 34, 127, 0.2); }
.dark .pdv-pagamento-btn-confirmar { background: rgba(129, 140, 248, 0.2); color: #818cf8; }
.dark .pdv-pagamento-btn-confirmar:hover { background: rgba(129, 140, 248, 0.3); }
.pdv-pagamento-item { display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; border-left-width: 4px; border-left-color: #16a34a; margin-bottom: 0.5rem; }
.pdv-pagamento-item:last-child { margin-bottom: 0; }
.dark .pdv-pagamento-item { background: #1e293b; border-color: #334155; }
.pdv-pagamento-rodape { padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0; background: #fff; }
.dark .pdv-pagamento-rodape { background: #0f172a; border-color: #334155; }
.pdv-pagamento-btn-finalizar { background: #1a227f; color: #fff; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transition: opacity 0.2s; }
.pdv-pagamento-btn-finalizar:hover:not(:disabled) { filter: brightness(1.05); }
.pdv-pagamento-btn-finalizar:disabled { cursor: not-allowed; }
.dark .pdv-pagamento-btn-finalizar { background: #4338ca; }
.pdv-modal-pagamento .pdv-pagamento-content { overflow-y: auto; padding-bottom: 0; }
.pdv-modal-pagamento .pdv-pagamento-content .pdv-pagamento-body { flex: 1; overflow-y: auto; min-height: 0; }
.pdv-modal-pagamento .pdv-pagamento-rodape { flex-shrink: 0; }
.pdv-modal-pagamento .pdv-pagamento-content > section:last-of-type { padding-bottom: 1rem; }
.pdv-modal-carousel { padding: 0; align-items: stretch; }
.pdv-carousel-content { position: relative; display: flex; align-items: center; justify-content: center; width: 100%; min-height: 60vh; padding: 3rem 0.5rem 2rem; }
.pdv-carousel-fechar { position: absolute; top: 0.5rem; right: 0.5rem; width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,0.2); color: #fff; border: none; font-size: 1.5rem; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; }
.pdv-carousel-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.25); color: #fff; border: none; font-size: 2rem; cursor: pointer; z-index: 2; display: flex; align-items: center; justify-content: center; line-height: 1; }
.pdv-carousel-prev { left: 0.5rem; }
.pdv-carousel-next { right: 0.5rem; }
.pdv-carousel-slide { flex: 1; display: flex; align-items: center; justify-content: center; }
.pdv-carousel-img { max-width: 100%; max-height: 70vh; width: auto; height: auto; object-fit: contain; border-radius: 0.5rem; }
.pdv-carousel-indice { position: absolute; bottom: 0.5rem; left: 50%; transform: translateX(-50%); color: rgba(255,255,255,0.9); font-size: 0.9rem; }
.pdv-modal h2 { margin-bottom: 1rem; font-size: 1.25rem; }
.pdv-modal label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
.pdv-modal input, .pdv-modal select, .pdv-modal textarea { width: 100%; margin-bottom: 1rem; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
.pdv-modal-botoes { display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem; }
.pdv-info { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
.pdv-pagamento-linha { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
.pdv-pagamento-linha select { flex: 1; }
.pdv-pagamento-linha input { flex: 0 0 100px; }
.pdv-lista-atalhos { list-style: none; padding: 0; margin: 1rem 0; }
.pdv-lista-atalhos li { margin-bottom: 0.5rem; }
.pdv-lista-atalhos kbd { background: #e5e7eb; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.875rem; }
.pdv-btn-sm { padding: 0.4rem 0.75rem; font-size: 0.875rem; }
.pdv-historico-item { cursor: default; }
/* Dark */
.dark .pdv-bloco-produtos { border-color: #1d4ed8; background: linear-gradient(to bottom, rgba(59,130,246,0.15) 0%, transparent 100%); }
.dark .pdv-bloco-servicos { border-color: #047857; background: linear-gradient(to bottom, rgba(16,185,129,0.12) 0%, transparent 100%); }
.dark .pdv-item { border-bottom-color: rgba(255,255,255,0.06); }
.dark .pdv-qtd-btn { background: #374151; border-color: #4b5563; color: #e5e7eb; }
.dark .pdv-qtd-btn:hover { background: #4b5563; }
.dark .pdv-item-unit { color: #9ca3af; }
.dark .pdv-item-preco { color: #f3f4f6; }
.dark .pdv-bottom-bar { background: #1f2937; border-top-color: #374151; }
.dark .pdv-total-bar { color: #e5e7eb; }
.dark .pdv-total-bar strong { color: #93c5fd; }
[x-cloak] { display: none !important; }
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pdvApp', () => ({
        caixa: null,
        tela: 'abertura',
        numeroCaixa: 'Caixa 1',
        ultimaVendaId: null,
        numeroVendaInicial: 1,
        mostrarOrcamentoModal: false,
        mostrarObservacaoModal: false,
        observacaoOrcamento: '',
        observacaoInternaOrcamento: '',
        abaObservacaoOrcamento: 'normal',
        mostrarOrcamentosSalvosModal: false,
        orcamentosSalvosLista: [],
        orcamentosSalvosBusca: '',
        orcamentosSalvosCarregando: false,
        orcamentoDetalheModal: null,
        detalheObsAberto: false,
        detalheObsInternaAberto: false,
        imprimirOrcamentoNumero: null,
        orcamentoImportadoId: null,
        notificacao: { texto: '', tipo: 'sucesso', mostrar: false },
        terminais: ['Caixa 1', 'Caixa 2', 'Caixa 3'],
        podeAbrirCaixa: true,
        valorFundo: '0,00',
        valorFundoCents: 0,
        abrindo: false,
        erroAbertura: '',
        saldo: 0,
        bipagem: '',
        itens: [],
        desconto: '0',
        mostrarAjuda: false,
        mostrarReforco: false,
        reforcoValor: '',
        reforcoPlanoContaId: '',
        planoContasAporte: [],
        mostrarSangria: false,
        sangriaValor: '',
        sangriaJustificativa: '',
        sangriaRequerSenha: false,
        sangriaAutorizadorNome: '',
        sangriaSenhaAutorizacao: '',
        mostrarFechamento: false,
        fechamentoValor: '',
        fechamentoFormas: [],
        fechamentoValoresPorForma: {},
        fechamentoRequerSenha: false,
        fechamentoSenha: '',
        fechamentoAutorizadorNome: '',
        fechamentoResultado: null,
        podeVerSaldoFechamento: false,
        mostrarPesquisa: false,
        pesquisaQ: '',
        resultadosProdutos: [],
        autocompleteResultados: [],
        carregandoAutocomplete: false,
        mostrarFinalizar: false,
        comprovanteVenda: null,
        mostrarHistorico: false,
        historicoVendas: [],
        historicoClientes: [],
        historicoPagination: null,
        historicoFiltros: { data_de: '', data_ate: '', status: '', cliente_id: '', numero_venda: '' },
        historicoBusca: '',
        carregandoHistorico: false,
        historicoCancelarId: null,
        historicoCancelarNumero: '',
        historicoCancelarVenda: null,
        historicoDetalheVenda: null,
        historicoMotivo: '',
        historicoErroMotivo: false,
        historicoCancelando: false,
        historicoCancelarRequerSenha: false,
        historicoCancelarAutorizadorNome: '',
        historicoSenhaAutorizacao: '',
        podeCancelarVendaTotal: true,
        podeCancelarVendaParcial: true,
        historicoTipoCancelamento: 'total',
        historicoParcialItens: [],
        historicoCarregandoCancelarDetalhe: false,
        descontoMaximoPercentual: null,
        descontoAutorizadorNome: '',
        mostrarDescontoAutorizacaoModal: false,
        descontoSenhaAutorizacao: '',
        descontoPendenteValor: null,
        descontoPendentePercentualAplicado: null,
        descontoAutorizadoPorUserId: null,
        clienteBloqueadoModal: false,
        clienteBloqueadoSelecionado: null,
        clienteBloqueadoSenha: '',
        clienteBloqueadoAutorizadoPorUserId: null,
        clienteBloqueadoAutorizadorNome: '',
        limiteCreditoExcedidoModal: false,
        limiteCreditoExcedidoMensagem: '',
        limiteCreditoSenha: '',
        limiteCreditoAutorizadoPorUserId: null,
        limiteCreditoExcedidoPayload: null,
        estoqueInsuficienteModal: false,
        estoqueInsuficientePayload: null,
        estoqueInsuficienteSenha: '',
        estoqueInsuficienteAutorizadorNome: '',
        itemPendenteEstoque: null,
        controleEstoque: false,
        podeVenderEstoqueZerado: false,
        estoqueZeradoAutorizadoPorUserId: null,
        pagamentos: [{ forma_pagamento_id: '', valor: '0' }],
        pagamentosModal: [],
        formasPagamento: [],
        adquirentes: [],
        descontoTipoPagamento: 'valor',
        descontoInputValor: '',
        pagamentoFormaSelecionada: null,
        pagamentoValorAtual: '',
        pagamentoAdquirenteId: '',
        pagamentoBandeira: '',
        pagamentoAntecipado: false,
        pagamentoParcelas: 1,
        pagamentoPixChaveId: '',
        pagamentoPixPrimeiroVencimento: '',
        bandeiras: [],
        clienteId: '',
        clienteNome: '',
        clienteBusca: '',
        mostrarCliente: false,
        clientesResultados: [],
        clienteBuscaModal: '',
        carregandoClientes: false,
        telaClienteModal: 'selecao',
        mostrarNovoCliente: false,
        novoClienteNome: '',
        novoClienteTipo: 'F',
        novoClienteCpf: '',
        novoClienteCnpj: '',
        novoClienteTelefone: '',
        novoClienteContatoNome: '',
        novoClienteEmail: '',
        novoClienteWhatsapp: false,
        novoClienteCep: '',
        novoClienteLogradouro: '',
        novoClienteNumero: '',
        novoClienteComplemento: '',
        novoClienteBairro: '',
        novoClienteCidade: '',
        novoClienteEstado: '',
        carregandoCep: false,
        carregandoCnpj: false,
        erroNovoCliente: '',
        erroNovoClienteCpf: '',
        erroNovoClienteCnpj: '',
        quickStoreUrl: '{{ route("clientes.quick-store") }}',
        carregando: false,
        baseUrl: '{{ route("plugin.pdv.index") }}',
        formasPagamentoUrl: '{{ route("plugin.pdv.api.formas-pagamento") }}',
        adquirentesUrl: '{{ route("plugin.pdv.api.adquirentes") }}',
        planoContasAporteUrl: '{{ route("plugin.pdv.api.plano-contas-aporte") }}',
        mostrarCarousel: false,
        carouselImagens: [],
        carouselIndice: 0,
        mostrarConfigurarItem: false,
        itemParaConfigurar: null,
        configurarVariacaoId: null,
        configurarSerial: '',
        configurarQuantidade: 1,
        editandoItemIndex: null,
        mostrarObservacaoServico: false,
        itemObservacaoIndex: null,
        textoObservacaoServico: '',

        abrirCarousel(imagens, indice) {
            if (!imagens || !imagens.length) return;
            this.carouselImagens = imagens;
            this.carouselIndice = indice || 0;
            this.mostrarCarousel = true;
        },
        fecharCarousel() { this.mostrarCarousel = false; },
        carouselAnterior() { this.carouselIndice = (this.carouselIndice - 1 + this.carouselImagens.length) % this.carouselImagens.length; },
        carouselProximo() { this.carouselIndice = (this.carouselIndice + 1) % this.carouselImagens.length; },

        atalhoTeclado(e) {
            const isFunctionKey = ['F1', 'F2', 'F3', 'F4'].includes(e.key);
            const emCampoDigitação = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);
            if (emCampoDigitação && !isFunctionKey) return;
            if (e.key === 'F1') { e.preventDefault(); this.mostrarAjuda = !this.mostrarAjuda; }
            if (e.key === 'F2') { e.preventDefault(); this.abrirPesquisa(); }
            if (e.key === 'F3') { e.preventDefault(); if (this.itens.length) this.abrirFinalizar(); }
            if (e.key === 'F4') { e.preventDefault(); this.cancelarUltimoItem(); }
        },

        async init() {
            const params = new URLSearchParams(window.location.search);
            const caixaFromUrl = params.get('numero_caixa');
            if (caixaFromUrl) {
                this.numeroCaixa = caixaFromUrl;
                localStorage.setItem('pdv_numero_caixa', caixaFromUrl);
                await this.carregarStatus();
            } else {
                await this.carregarStatus(true);
                if (this.caixa && this.caixa.numero_caixa) {
                    this.numeroCaixa = this.caixa.numero_caixa;
                    localStorage.setItem('pdv_numero_caixa', this.caixa.numero_caixa);
                }
            }
            if (this.caixa) {
                this.tela = 'venda';
                if (this.caixa.numero_caixa) {
                    this.numeroCaixa = this.caixa.numero_caixa;
                    localStorage.setItem('pdv_numero_caixa', this.caixa.numero_caixa);
                }
            }
            await this.carregarFormasPagamento();
            await this.carregarAdquirentes();
            await this.carregarPlanoContasAporte();
            setInterval(() => {
                if (this.tela !== 'venda' || this.mostrarPesquisa || this.mostrarFinalizar || this.mostrarAjuda || this.mostrarHistorico || this.historicoCancelarId || this.historicoDetalheVenda || this.mostrarConfigurarItem || this.mostrarObservacaoServico || this.mostrarCliente) return;
                const el = document.activeElement;
                if (el && ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName)) return;
                if (this.$refs && this.$refs.inputBipagem && document.activeElement !== this.$refs.inputBipagem) this.$refs.inputBipagem.focus();
            }, 1000);
        },

        async carregarStatus(meu) {
            let url = this.baseUrl + '/api/caixa/status';
            if (meu) url += '?meu=1'; else url += '?numero_caixa=' + encodeURIComponent(this.numeroCaixa);
            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const d = await r.json();
            if (d.caixa) {
                this.caixa = d.caixa;
                this.saldo = d.caixa.saldo_atual ?? d.caixa.valor_abertura;
                if (d.caixa.numero_caixa) this.numeroCaixa = d.caixa.numero_caixa;
                this.ultimaVendaId = d.caixa.ultima_venda_id ?? null;
                if (d.pode_ver_saldo_fechamento != null) this.podeVerSaldoFechamento = !!d.pode_ver_saldo_fechamento;
            } else {
                this.caixa = null;
                this.ultimaVendaId = null;
            }
            if (d.pdv_numero_venda_inicial != null && d.pdv_numero_venda_inicial >= 1) this.numeroVendaInicial = d.pdv_numero_venda_inicial;
            if (typeof d.pode_abrir_caixa !== 'undefined') this.podeAbrirCaixa = !!d.pode_abrir_caixa;
            if (d.terminais && d.terminais.length) this.terminais = d.terminais;
            if (d.desconto_maximo_percentual != null) this.descontoMaximoPercentual = d.desconto_maximo_percentual;
            if (d.desconto_autorizador_nome != null) this.descontoAutorizadorNome = d.desconto_autorizador_nome || '';
            if (d.estoque_zerado_autorizador_nome != null) this.estoqueInsuficienteAutorizadorNome = d.estoque_zerado_autorizador_nome || '';
            if (typeof d.controle_estoque !== 'undefined') this.controleEstoque = !!d.controle_estoque;
            if (typeof d.pode_vender_estoque_zerado !== 'undefined') this.podeVenderEstoqueZerado = !!d.pode_vender_estoque_zerado;
            if (typeof d.pode_cancelar_venda_total !== 'undefined') this.podeCancelarVendaTotal = !!d.pode_cancelar_venda_total;
            if (typeof d.pode_cancelar_venda_parcial !== 'undefined') this.podeCancelarVendaParcial = !!d.pode_cancelar_venda_parcial;
        },

        async carregarFormasPagamento() {
            const url = this.formasPagamentoUrl || (this.baseUrl.replace(/\/$/, '') + '/api/formas-pagamento');
            const maxAttempts = 3;
            const fetchOpts = {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                },
            };
            const deveTentarDeNovo = (status, err) => {
                if (status === 401 || status === 403 || status === 404) return false;
                if (status === 429 || (status >= 500 && status < 600)) return true;
                if (err && err.name === 'TypeError') return true;
                return false;
            };
            let ultimoErro = null;
            for (let tentativa = 1; tentativa <= maxAttempts; tentativa++) {
                try {
                    const r = await fetch(url, fetchOpts);
                    const ct = (r.headers.get('content-type') || '').toLowerCase();
                    if (!r.ok) {
                        ultimoErro = new Error('http_' + r.status);
                        if (tentativa < maxAttempts && deveTentarDeNovo(r.status, null)) {
                            await new Promise((res) => setTimeout(res, 250 * tentativa));
                            continue;
                        }
                        throw ultimoErro;
                    }
                    if (!ct.includes('application/json')) {
                        ultimoErro = new Error('not_json');
                        if (tentativa < maxAttempts) {
                            await new Promise((res) => setTimeout(res, 250 * tentativa));
                            continue;
                        }
                        throw ultimoErro;
                    }
                    const d = await r.json();
                    this.formasPagamento = d.formas || [];
                    this.bandeiras = d.bandeiras || [];
                    return;
                } catch (e) {
                    ultimoErro = e;
                    const falhaTransitória = e && (e.name === 'TypeError' || e.name === 'SyntaxError');
                    if (tentativa < maxAttempts && (falhaTransitória || (e && e.message === 'not_json'))) {
                        await new Promise((res) => setTimeout(res, 250 * tentativa));
                        continue;
                    }
                    break;
                }
            }
            this.formasPagamento = [];
            this.bandeiras = [];
            const msg = ultimoErro && String(ultimoErro.message || '').includes('http_401')
                ? 'Sessão expirada. Atualize a página ou faça login novamente.'
                : 'Não foi possível carregar as formas de pagamento.';
            window.toast?.error?.(msg);
        },

        async carregarAdquirentes() {
            const r = await fetch(this.adquirentesUrl || (this.baseUrl.replace(/\/$/, '') + '/api/adquirentes'), { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            this.adquirentes = d.adquirentes || [];
        },

        async carregarPlanoContasAporte() {
            const r = await fetch(this.planoContasAporteUrl || (this.baseUrl.replace(/\/$/, '') + '/api/plano-contas-aporte'), { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            this.planoContasAporte = d.contas || [];
        },

        formatarValor(v) {
            const n = typeof v === 'string' ? parseFloat(v.replace(/\D/g, '')) / 100 : Number(v);
            return isNaN(n) ? '0,00' : n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatarQuantidade(q) {
            const raw = String(q || '').trim().replace(',', '.');
            let n = parseFloat(raw);
            if (isNaN(n)) return '0';
            if (n >= 10000 && n === Math.floor(n) && n % 10000 === 0) n = n / 10000;
            return n % 1 === 0 ? String(n) : n.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        },

        parseValor(s) {
            if (typeof s !== 'string') return Number(s) || 0;
            const t = s.trim();
            if (!t) return 0;
            // Formato BR: 1.000,50 → remove pontos (milhar), troca vírgula por ponto
            if (t.indexOf(',') !== -1) {
                return parseFloat(t.replace(/\./g, '').replace(',', '.')) || 0;
            }
            // Sem vírgula: tratar como número normal (1.00 = 1, 10.5 = 10.5)
            return parseFloat(t) || 0;
        },

        async abrirCaixa() {
            this.erroAbertura = '';
            const valor = this.valorFundoCents / 100;
            this.abrindo = true;
            try {
                const r = await fetch(this.baseUrl + '/api/caixa/abrir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                    body: JSON.stringify({ valor_fundo: valor })
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || 'Erro ao abrir caixa');
                this.caixa = d.caixa;
                this.saldo = d.caixa.saldo_atual;
                this.tela = 'venda';
                this.valorFundoCents = 0;
                this.valorFundo = '0,00';
                localStorage.setItem('pdv_numero_caixa', this.numeroCaixa);
            } catch (e) {
                this.erroAbertura = e.message;
            }
            this.abrindo = false;
        },

        async aplicarReforco() {
            const valor = this.parseValor(this.reforcoValor);
            if (!this.reforcoPlanoContaId || valor <= 0) return;
            const r = await fetch(this.baseUrl + '/api/caixa/reforco', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                body: JSON.stringify({ valor, plano_conta_id: this.reforcoPlanoContaId, numero_caixa: this.numeroCaixa })
            });
            const d = await r.json();
            if (r.ok) { this.saldo = d.saldo_atual; if (d.detalhe_saldo) this.caixa.detalhe_saldo = d.detalhe_saldo; this.mostrarReforco = false; this.reforcoValor = ''; window.toast.success('Reforço registrado.'); }
            else window.toast.error(d.message || 'Erro');
        },

        async aplicarSangria() {
            const valor = this.parseValor(this.sangriaValor);
            if (!this.sangriaJustificativa.trim() || valor <= 0) return;
            const body = { valor, justificativa: this.sangriaJustificativa, numero_caixa: this.numeroCaixa };
            if (this.sangriaSenhaAutorizacao && this.sangriaSenhaAutorizacao.trim()) body.senha_autorizacao = this.sangriaSenhaAutorizacao.trim();
            const r = await fetch(this.baseUrl + '/api/caixa/sangria', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                body: JSON.stringify(body),
            });
            const d = await r.json();
            if (r.ok) {
                this.saldo = d.saldo_atual;
                if (d.detalhe_saldo) this.caixa.detalhe_saldo = d.detalhe_saldo;
                this.mostrarSangria = false;
                this.sangriaValor = '';
                this.sangriaJustificativa = '';
                this.sangriaRequerSenha = false;
                this.sangriaAutorizadorNome = '';
                this.sangriaSenhaAutorizacao = '';
                window.toast.success('Sangria registrada.');
            } else if (r.status === 403 && d.requires_authorization) {
                this.sangriaRequerSenha = true;
                this.sangriaAutorizadorNome = d.autorizador_nome || '';
                window.toast.warning(d.message || 'Sem permissão. Digite a senha do autorizador.');
            } else {
                window.toast.error(d.message || 'Erro');
            }
        },

        async fecharCaixa() {
            let body = { numero_caixa: this.numeroCaixa };
            if (this.fechamentoFormas && this.fechamentoFormas.length > 0) {
                const valores = this.fechamentoFormas.map(f => ({
                    forma_pagamento_id: f.id,
                    valor: this.parseValor(this.fechamentoValoresPorForma[f.id] || '0') || 0
                }));
                body.valores_informados = valores;
            } else {
                body.valor_informado = this.parseValor(this.fechamentoValor) || 0;
            }
            if (this.fechamentoRequerSenha && this.fechamentoSenha && this.fechamentoSenha.trim())
                body.senha_autorizacao = this.fechamentoSenha.trim();
            const r = await fetch(this.baseUrl + '/api/caixa/fechar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                body: JSON.stringify(body)
            });
            const d = await r.json();
            if (r.ok) {
                this.fechamentoResultado = d;
                window.toast.success(d.relatorio || 'Caixa fechado.');
                this.caixa = null;
                this.tela = 'abertura';
                this.mostrarFechamento = false;
                this.fechamentoRequerSenha = false;
                this.fechamentoSenha = '';
            } else if (r.status === 403 && d.requires_authorization) {
                this.fechamentoRequerSenha = true;
                this.fechamentoAutorizadorNome = d.autorizador_nome || '';
                window.toast.warning(d.message || 'Aprovação necessária. Digite a senha do autorizador.');
            } else {
                window.toast.error(d.message || 'Erro');
            }
        },

        async abrirFechamentoModal() {
            this.menuCaixaAberto = false;
            this.mostrarFechamento = true;
            this.fechamentoResultado = null;
            this.fechamentoRequerSenha = false;
            this.fechamentoSenha = '';
            this.fechamentoValoresPorForma = {};
            this.fechamentoValor = '';
            try {
                const r = await fetch(this.baseUrl + '/api/caixa/fechamento-formas', { headers: { 'Accept': 'application/json', 'X-PDV-Caixa': this.numeroCaixa } });
                const d = await r.json();
                if (r.ok) {
                    if (d.pode_ver_saldo_fechamento != null) this.podeVerSaldoFechamento = !!d.pode_ver_saldo_fechamento;
                    if (d.formas && d.formas.length) {
                        this.fechamentoFormas = d.formas;
                        const obj = {};
                        d.formas.forEach(f => { obj[f.id] = ''; });
                        this.fechamentoValoresPorForma = obj;
                    } else {
                        this.fechamentoFormas = [];
                    }
                } else {
                    this.fechamentoFormas = [];
                }
            } catch (e) {
                this.fechamentoFormas = [];
            }
        },

        get subtotal() {
            return this.itens.reduce((s, i) => s + (Number(i.total) || 0), 0);
        },
        get totalFinal() {
            return Math.max(0, this.subtotal - this.parseValor(this.desconto));
        },
        get somaPagamentos() {
            return this.pagamentosModal.reduce((s, p) => s + this.parseValor(p.valor), 0);
        },
        get saldoRestanteFinalizar() {
            return Math.max(0, this.totalFinal - this.somaPagamentos);
        },
        get itensProdutos() {
            return this.itens.filter(i => i.tipo === 'produto');
        },
        get itensServicos() {
            return this.itens.filter(i => i.tipo === 'servico');
        },
        get valorFundoDisplay() {
            const v = (this.valorFundoCents / 100).toFixed(2);
            return v.replace('.', ',');
        },
        /** No fechamento por formas: saldo total = soma dos totais por forma. Caso contrário: saldo (dinheiro). */
        get saldoFechamentoSistema() {
            if (this.fechamentoFormas && this.fechamentoFormas.length && this.podeVerSaldoFechamento) {
                return this.fechamentoFormas.reduce((s, f) => s + (Number(f.total_calculado) || 0), 0);
            }
            return this.saldo;
        },

        keypadDigito(d) {
            if (d === ',') return;
            const n = typeof d === 'number' ? d : parseInt(d, 10);
            if (isNaN(n)) return;
            this.valorFundoCents = Math.min(99999999, this.valorFundoCents * 10 + n);
            this.valorFundo = this.valorFundoDisplay;
        },
        keypadBackspace() {
            this.valorFundoCents = Math.floor(this.valorFundoCents / 10);
            this.valorFundo = this.valorFundoDisplay;
        },

        indiceItem(item) {
            return this.itens.indexOf(item);
        },

        alterarQuantidade(i, delta) {
            if (i < 0 || i >= this.itens.length) return;
            const it = this.itens[i];
            let q = (Number(it.quantidade) || 1) + delta;
            q = Math.round(q * 1000) / 1000;
            if (q < 0.001) { this.removerItem(i); return; }
            it.quantidade = q;
            it.total = q * (Number(it.preco_unitario) || 0);
        },

        _adicionarItemSemCheck(item) {
            const qtd = Number(item.quantidade) || 1;
            const descricao = item.nome || item.descricao;
            const existente = this.itens.find(i => (i.produto_id && i.produto_id === item.id && i.tipo === 'produto' && i.descricao === descricao && (i.serial || '') === (item.serial || '')) || (i.servico_id && i.servico_id === item.id && i.tipo === 'servico'));
            if (existente) {
                existente.quantidade += qtd;
                existente.total = existente.quantidade * existente.preco_unitario;
                if (item.imagens && item.imagens.length && (!existente.imagens || !existente.imagens.length)) existente.imagens = item.imagens;
            } else {
                this.itens.push({
                    tipo: item.tipo || 'produto',
                    produto_id: item.tipo === 'produto' ? item.id : null,
                    servico_id: item.tipo === 'servico' ? item.id : null,
                    descricao: descricao,
                    quantidade: qtd,
                    preco_unitario: item.preco,
                    total: (item.preco || 0) * qtd,
                    serial: item.serial || null,
                    imagens: item.imagens || [],
                    produto_variacao_id: item.produto_variacao_id || null,
                    produto_edit_data: item.produto_edit_data || null,
                    kit_componentes: item.kit_componentes || [],
                    observacao: item.observacao || '',
                });
            }
        },
        async adicionarItem(item) {
            const qtd = Number(item.quantidade) || 1;
            const isProduto = (item.tipo || 'produto') === 'produto' && item.id;
            if (!isProduto || !this.controleEstoque || this.podeVenderEstoqueZerado) {
                this._adicionarItemSemCheck(item);
                return;
            }
            const wouldBeItens = [
                ...this.itens.map(i => ({ tipo: i.tipo, produto_id: i.produto_id || null, servico_id: i.servico_id || null, quantidade: Number(i.quantidade) || 0 })),
                { tipo: 'produto', produto_id: item.id, quantidade: qtd }
            ];
            try {
                const r = await fetch(this.baseUrl + '/api/check-estoque-carrinho', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ itens: wouldBeItens })
                });
                const d = await r.json();
                if (d.allowed) {
                    this._adicionarItemSemCheck(item);
                    return;
                }
                this.itemPendenteEstoque = item;
                this.estoqueInsuficientePayload = null;
                this.estoqueInsuficienteAutorizadorNome = d.estoque_zerado_autorizador_nome || this.estoqueInsuficienteAutorizadorNome || '';
                this.estoqueInsuficienteSenha = '';
                this.estoqueInsuficienteModal = true;
                window.toast.warning(d.message || 'Estoque insuficiente. Autorize para incluir o produto.');
            } catch (e) {
                this._adicionarItemSemCheck(item);
            }
        },

        removerItem(i) {
            this.itens.splice(i, 1);
        },

        cancelarUltimoItem() {
            if (this.itens.length) this.itens.pop();
        },

        async abrirHistorico() {
            this.mostrarHistorico = true;
            await this.carregarHistorico();
        },

        async carregarHistorico() {
            this.carregandoHistorico = true;
            const q = new URLSearchParams();
            if (this.historicoFiltros.data_de) q.set('data_de', this.historicoFiltros.data_de);
            if (this.historicoFiltros.data_ate) q.set('data_ate', this.historicoFiltros.data_ate);
            if (this.historicoFiltros.status) q.set('status', this.historicoFiltros.status);
            if (this.historicoFiltros.cliente_id) q.set('cliente_id', this.historicoFiltros.cliente_id);
            if (this.historicoFiltros.numero_venda) q.set('numero_venda', this.historicoFiltros.numero_venda);
            if (this.historicoBusca && this.historicoBusca.trim()) q.set('q', this.historicoBusca.trim());
            q.set('page', this.historicoPagination?.current_page || 1);
            try {
                const r = await fetch(this.baseUrl.replace(/\/$/, '') + '/api/historico-vendas?' + q.toString(), { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.historicoVendas = d.vendas || [];
                if (d.clientes) this.historicoClientes = d.clientes;
                if (d.pagination) this.historicoPagination = d.pagination;
            } catch (e) {
                this.historicoVendas = [];
            }
            this.carregandoHistorico = false;
        },

        limparFiltrosHistorico() {
            this.historicoFiltros = { data_de: '', data_ate: '', status: '', cliente_id: '', numero_venda: '' };
            this.historicoBusca = '';
            this.historicoPagination = null;
            this.carregarHistorico();
        },

        irPaginaHistorico(page) {
            if (!this.historicoPagination) return;
            if (page < 1 || page > this.historicoPagination.last_page) return;
            this.historicoPagination.current_page = page;
            this.carregarHistorico();
        },

        async abrirModalCancelarHistorico(id) {
            const v = this.historicoVendas.find(x => x.id === id);
            this.historicoCancelarVenda = v || null;
            this.historicoCancelarNumero = v ? v.numero_formatado : String(id).padStart(5, '0');
            this.historicoCancelarId = id;
            this.historicoMotivo = '';
            this.historicoErroMotivo = false;
            this.historicoCancelarRequerSenha = false;
            this.historicoCancelarAutorizadorNome = '';
            this.historicoSenhaAutorizacao = '';
            if (this.podeCancelarVendaTotal && !this.podeCancelarVendaParcial) this.historicoTipoCancelamento = 'total';
            else if (!this.podeCancelarVendaTotal && this.podeCancelarVendaParcial) this.historicoTipoCancelamento = 'parcial';
            else this.historicoTipoCancelamento = 'total';
            this.historicoParcialItens = [];
            this.historicoCarregandoCancelarDetalhe = true;
            try {
                const r = await fetch(this.baseUrl.replace(/\/$/, '') + '/api/vendas/' + id, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (d.venda && Array.isArray(d.venda.itens)) {
                    this.historicoParcialItens = d.venda.itens.map(it => {
                        const q = Number(it.quantidade) || 0;
                        const qc = Number(it.quantidade_cancelada) || 0;
                        const disp = Math.max(0, q - qc);
                        return { id: it.id, descricao: it.descricao || 'Item', tipo: it.tipo || 'produto', quantidade: q, quantidade_cancelada: qc, disponivel: disp, inputCancelar: '' };
                    });
                }
            } catch (e) {
                this.historicoParcialItens = [];
            }
            this.historicoCarregandoCancelarDetalhe = false;
        },

        fecharModalCancelarHistorico() {
            if (this.historicoCancelando) return;
            this.historicoCancelarId = null;
            this.historicoCancelarNumero = '';
            this.historicoCancelarVenda = null;
            this.historicoMotivo = '';
            this.historicoErroMotivo = false;
            this.historicoCancelarRequerSenha = false;
            this.historicoCancelarAutorizadorNome = '';
            this.historicoSenhaAutorizacao = '';
            this.historicoParcialItens = [];
            this.historicoCarregandoCancelarDetalhe = false;
            this.historicoTipoCancelamento = 'total';
        },

        async mostrarDetalheVendaHistorico(id) {
            try {
                const r = await fetch(this.baseUrl.replace(/\/$/, '') + '/api/vendas/' + id, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (d.venda) {
                    this.historicoDetalheVenda = d.venda;
                } else {
                    window.toast.error(d.message || 'Venda não encontrada.');
                }
            } catch (e) {
                window.toast.error(e.message || 'Erro ao carregar venda.');
            }
        },
        fecharDetalheHistorico() {
            this.historicoDetalheVenda = null;
        },

        async confirmarCancelamentoHistorico() {
            if (!this.historicoCancelarId || this.historicoCancelando) return;
            const motivo = (this.historicoMotivo || '').trim();
            if (!motivo) { this.historicoErroMotivo = true; return; }
            this.historicoErroMotivo = false;
            const body = { motivo_cancelamento: motivo };
            if (this.historicoTipoCancelamento === 'parcial' && this.podeCancelarVendaParcial) {
                const itensPayload = [];
                for (const it of this.historicoParcialItens) {
                    const raw = String(it.inputCancelar || '').trim().replace(',', '.');
                    const q = parseFloat(raw) || 0;
                    if (q <= 0) continue;
                    if (q > it.disponivel + 0.0001) {
                        window.toast.error('Quantidade a cancelar maior que o disponível em "' + (it.descricao || 'item') + '".');
                        return;
                    }
                    itensPayload.push({ item_id: it.id, quantidade_cancelar: q });
                }
                if (itensPayload.length === 0) {
                    window.toast.error('Informe a quantidade a cancelar em ao menos um item.');
                    return;
                }
                body.tipo = 'parcial';
                body.itens = itensPayload;
            } else {
                body.tipo = 'total';
            }
            this.historicoCancelando = true;
            if (this.historicoSenhaAutorizacao && this.historicoSenhaAutorizacao.trim()) body.senha_autorizacao = this.historicoSenhaAutorizacao.trim();
            try {
                const r = await fetch(this.baseUrl.replace(/\/$/, '') + '/api/vendas/' + this.historicoCancelarId + '/cancelar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(body),
                });
                const data = await r.json();
                this.historicoCancelando = false;
                if (r.ok) {
                    this.historicoCancelarRequerSenha = false;
                    this.historicoSenhaAutorizacao = '';
                    this.fecharModalCancelarHistorico();
                    if (data.message) window.toast.success(data.message);
                    await this.carregarHistorico();
                } else if (r.status === 403 && data.requires_authorization) {
                    this.historicoCancelarRequerSenha = true;
                    this.historicoCancelarAutorizadorNome = data.autorizador_nome || '';
                    if (data.message) window.toast.warning(data.message);
                } else {
                    const msg = data.message || 'Erro ao cancelar venda.';
                    window.toast.error(msg);
                }
            } catch (e) {
                this.historicoCancelando = false;
                const msg = e.message || 'Erro ao cancelar venda.';
                window.toast.error(msg);
            }
        },

        statusLabelHistorico(status) {
            if (status === 'finalizada') return 'Concluída';
            if (status === 'cancelada') return 'Cancelada';
            return status || '—';
        },

        async adicionarPorBipagem() {
            const q = this.bipagem.trim();
            if (!q) return;
            const r = await fetch(this.baseUrl + '/api/busca/produtos?ean=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
            let d = { produtos: [] };
            try { d = await r.json(); } catch (_) {}
            if (d.produtos && d.produtos.length > 0) {
                const p = d.produtos[0];
                this.adicionarItem({ tipo: 'produto', id: p.id, nome: p.nome, preco: p.preco, imagens: p.imagens || [] });
                this.bipagem = '';
            } else {
                const r2 = await fetch(this.baseUrl + '/api/busca/produtos?q=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
                let d2 = { produtos: [] };
                try { d2 = await r2.json(); } catch (_) {}
                if (d2.produtos && d2.produtos.length > 0) {
                    const p2 = d2.produtos[0];
                    this.adicionarItem({ tipo: 'produto', id: p2.id, nome: p2.nome, preco: p2.preco, imagens: p2.imagens || [] });
                    this.bipagem = '';
                }
            }
        },

        abrirPesquisa() {
            this.mostrarPesquisa = true;
            this.pesquisaQ = '';
            this.resultadosProdutos = [];
            setTimeout(() => this.$refs?.inputBipagem?.focus(), 100);
        },

        fecharPesquisa() {
            this.mostrarPesquisa = false;
        },

        async buscarProdutos() {
            const q = this.pesquisaQ.trim();
            if (q.length < 2) { this.resultadosProdutos = []; return; }
            const r = await fetch(this.baseUrl + '/api/busca/produtos?q=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
            let d = { produtos: [] };
            try { d = await r.json(); } catch (_) {}
            this.resultadosProdutos = (d.produtos || []).map(p => ({ ...p, tipo: 'produto' }));
            const r2 = await fetch(this.baseUrl + '/api/busca/servicos?q=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
            let d2 = { servicos: [] };
            try { d2 = await r2.json(); } catch (_) {}
            const servicos = (d2.servicos || []).map(s => ({ ...s, tipo: 'servico' }));
            this.resultadosProdutos = [...this.resultadosProdutos, ...servicos];
        },

        adicionarResultado(p) {
            this.adicionarItem(p);
            this.fecharPesquisa();
        },

        async buscarAutocomplete() {
            const q = this.bipagem.trim();
            if (q.length < 2) { this.autocompleteResultados = []; return; }
            this.carregandoAutocomplete = true;
            this.autocompleteResultados = [];
            try {
                const r = await fetch(this.baseUrl + '/api/busca/produtos?q=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
                let d = { produtos: [] };
                try { d = await r.json(); } catch (_) {}
                const prods = (d.produtos || []).map(p => ({ ...p, tipo: 'produto' }));
                const r2 = await fetch(this.baseUrl + '/api/busca/servicos?q=' + encodeURIComponent(q) + '&numero_caixa=' + encodeURIComponent(this.numeroCaixa), { headers: { 'Accept': 'application/json' } });
                let d2 = { servicos: [] };
                try { d2 = await r2.json(); } catch (_) {}
                const servicos = (d2.servicos || []).map(s => ({ ...s, tipo: 'servico' }));
                this.autocompleteResultados = [...prods, ...servicos];
            } finally {
                this.carregandoAutocomplete = false;
            }
        },

        precisaConfigurar(p) {
            if (p.tipo !== 'produto') return false;
            return (p.variacoes && p.variacoes.length > 0) || p.exige_serial;
        },
        abrirConfigurarItem(p) {
            this.itemParaConfigurar = p;
            this.configurarVariacaoId = (p.variacoes && p.variacoes[0]) ? p.variacoes[0].id : null;
            this.configurarSerial = '';
            this.configurarQuantidade = 1;
            this.mostrarPesquisa = false;
            this.mostrarConfigurarItem = true;
            this.$nextTick(() => {
                setTimeout(() => {
                    if (p.exige_serial && this.$refs.inputSerial) this.$refs.inputSerial.focus();
                }, 200);
            });
        },
        fecharConfigurarItem() {
            this.mostrarConfigurarItem = false;
            this.itemParaConfigurar = null;
            this.configurarVariacaoId = null;
            this.configurarSerial = '';
            this.editandoItemIndex = null;
        },
        abrirObservacaoServico(item) {
            const idx = this.indiceItem(item);
            if (idx < 0) return;
            this.itemObservacaoIndex = idx;
            this.textoObservacaoServico = (this.itens[idx].observacao || '').trim();
            this.mostrarObservacaoServico = true;
        },
        fecharObservacaoServico() {
            this.mostrarObservacaoServico = false;
            this.itemObservacaoIndex = null;
            this.textoObservacaoServico = '';
        },
        salvarObservacaoServico() {
            if (this.itemObservacaoIndex !== null && this.itens[this.itemObservacaoIndex]) {
                const t = (this.textoObservacaoServico || '').trim().slice(0, 255);
                this.itens[this.itemObservacaoIndex].observacao = t;
            }
            this.fecharObservacaoServico();
        },
        podeEditarItem(item) {
            return item.tipo === 'produto' && (item.produto_edit_data || item.serial != null);
        },
        abrirEditarItem(item) {
            const idx = this.indiceItem(item);
            if (idx < 0) return;
            const p = item.produto_edit_data || { id: item.produto_id, nome: item.descricao, preco: item.preco_unitario, imagens: item.imagens || [], variacoes: [], exige_serial: !!item.serial };
            this.itemParaConfigurar = p;
            this.configurarVariacaoId = item.produto_variacao_id || (p.variacoes && p.variacoes[0] ? p.variacoes[0].id : null);
            this.configurarSerial = item.serial || '';
            this.configurarQuantidade = item.quantidade;
            this.editandoItemIndex = idx;
            this.mostrarConfigurarItem = true;
            this.$nextTick(() => {
                setTimeout(() => {
                    if (p.exige_serial && this.$refs.inputSerial) this.$refs.inputSerial.focus();
                }, 200);
            });
        },
        confirmarConfigurarItem() {
            const p = this.itemParaConfigurar;
            if (!p) return;
            if (p.exige_serial && !this.configurarSerial.trim()) { window.toast.warning('Informe o número de série/IMEI.'); return; }
            if (p.variacoes && p.variacoes.length && !this.configurarVariacaoId) { window.toast.warning('Selecione uma variação.'); return; }
            const variacao = (p.variacoes || []).find(v => v.id === this.configurarVariacaoId);
            const variacaoLabel = variacao ? (variacao.referencia_sku || (Array.isArray(variacao.opcoes_valores) ? variacao.opcoes_valores.join(', ') : variacao.opcoes_valores || '')) : '';
            const descricao = variacaoLabel ? (p.nome + ' - ' + variacaoLabel) : (p.nome || p.descricao);
            const payload = {
                ...p,
                nome: descricao,
                descricao: descricao,
                serial: this.configurarSerial.trim() || null,
                quantidade: this.configurarQuantidade,
                produto_variacao_id: this.configurarVariacaoId,
                produto_edit_data: (p.variacoes && p.variacoes.length) || p.exige_serial ? p : null,
                kit_componentes: p.kit_componentes || [],
            };
            if (this.editandoItemIndex !== null) {
                this.itens[this.editandoItemIndex] = {
                    tipo: 'produto',
                    produto_id: p.id,
                    servico_id: null,
                    descricao: payload.descricao,
                    quantidade: payload.quantidade,
                    preco_unitario: p.preco,
                    total: (p.preco || 0) * payload.quantidade,
                    serial: payload.serial,
                    imagens: p.imagens || [],
                    produto_variacao_id: payload.produto_variacao_id,
                    produto_edit_data: payload.produto_edit_data,
                    kit_componentes: payload.kit_componentes,
                };
            } else {
                this.adicionarItem(payload);
            }
            this.configurarQuantidade = 1;
            this.fecharConfigurarItem();
            this.bipagem = '';
            this.autocompleteResultados = [];
            this.$nextTick(() => this.$refs.inputBipagem?.focus());
        },
        selecionarAutocomplete(p) {
            if (this.precisaConfigurar(p)) {
                this.abrirConfigurarItem(p);
                this.bipagem = '';
                this.autocompleteResultados = [];
                return;
            }
            this.adicionarItem(p);
            this.bipagem = '';
            this.autocompleteResultados = [];
            this.$nextTick(() => this.$refs.inputBipagem?.focus());
        },

        fecharAutocomplete() {
            this.autocompleteResultados = [];
        },

        escolherCliente(c) {
            this.clienteBloqueadoAutorizadoPorUserId = null;
            this.clienteId = c.id;
            this.clienteNome = c.nome || '';
            this.clienteBusca = this.clienteNome;
        },
        aoClicarCliente(c) {
            if (c.bloqueado_vendas) {
                this.abrirModalClienteBloqueado(c);
            } else {
                this.escolherCliente(c);
                this.mostrarCliente = false;
            }
        },
        abrirModalClienteBloqueado(c) {
            this.clienteBloqueadoSelecionado = c;
            this.clienteBloqueadoSenha = '';
            this.clienteBloqueadoModal = true;
            this.clienteBloqueadoAutorizadorNome = '';
            fetch(this.baseUrl + '/api/caixa/status', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => { if (d.desconto_autorizador_nome != null) this.clienteBloqueadoAutorizadorNome = d.desconto_autorizador_nome || ''; })
                .catch(() => {});
        },
        fecharModalClienteBloqueado() {
            this.clienteBloqueadoModal = false;
            this.clienteBloqueadoSelecionado = null;
            this.clienteBloqueadoSenha = '';
        },
        async confirmarClienteBloqueadoSenha() {
            if (!this.clienteBloqueadoSenha || !this.clienteBloqueadoSenha.trim()) return;
            try {
                const r = await fetch(this.baseUrl + '/api/validar-senha-autorizador', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ senha_autorizacao: this.clienteBloqueadoSenha })
                });
                const d = await r.json();
                if (d.ok && d.autorizador_user_id != null) {
                    this.clienteBloqueadoAutorizadoPorUserId = d.autorizador_user_id;
                    if (this.clienteBloqueadoSelecionado) {
                        this.clienteId = this.clienteBloqueadoSelecionado.id;
                        this.clienteNome = this.clienteBloqueadoSelecionado.nome || '';
                        this.clienteBusca = this.clienteNome;
                    }
                    this.fecharModalClienteBloqueado();
                    this.mostrarCliente = false;
                    window.toast.success('Cliente selecionado com autorização.');
                } else {
                    window.toast.error(d.message || 'Senha incorreta.');
                }
            } catch (e) {
                window.toast.error('Erro ao validar senha.');
            }
        },
        fecharModalLimiteCredito() {
            this.limiteCreditoExcedidoModal = false;
            this.limiteCreditoExcedidoPayload = null;
            this.limiteCreditoExcedidoMensagem = '';
            this.limiteCreditoSenha = '';
        },
        fecharModalEstoqueInsuficiente() {
            this.estoqueInsuficienteModal = false;
            this.estoqueInsuficientePayload = null;
            this.itemPendenteEstoque = null;
            this.estoqueInsuficienteSenha = '';
        },
        async confirmarEstoqueInsuficienteSenha() {
            if (!this.estoqueInsuficienteSenha || !this.estoqueInsuficienteSenha.trim()) return;
            const isPendenteAdd = !!this.itemPendenteEstoque;
            if (!isPendenteAdd && !this.estoqueInsuficientePayload) return;
            try {
                const r = await fetch(this.baseUrl + '/api/validar-senha-estoque-zero', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ senha_autorizacao: this.estoqueInsuficienteSenha })
                });
                const d = await r.json();
                if (d.ok && d.autorizador_user_id != null) {
                    this.estoqueZeradoAutorizadoPorUserId = d.autorizador_user_id;
                    this.estoqueInsuficienteModal = false;
                    this.estoqueInsuficienteSenha = '';
                    if (isPendenteAdd) {
                        this._adicionarItemSemCheck(this.itemPendenteEstoque);
                        this.itemPendenteEstoque = null;
                        window.toast.success('Produto adicionado com autorização.');
                    } else {
                        const payload = { ...this.estoqueInsuficientePayload, estoque_zerado_autorizado_por_user_id: d.autorizador_user_id };
                        this.estoqueInsuficientePayload = null;
                        await this.enviarVendaComPayload(payload);
                        this.estoqueZeradoAutorizadoPorUserId = null;
                    }
                } else {
                    window.toast.error(d.message || 'Senha incorreta.');
                }
            } catch (e) {
                window.toast.error('Erro ao validar senha.');
            }
        },
        async confirmarLimiteCreditoSenha() {
            if (!this.limiteCreditoSenha || !this.limiteCreditoSenha.trim() || !this.limiteCreditoExcedidoPayload) return;
            try {
                const r = await fetch(this.baseUrl + '/api/validar-senha-autorizador', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ senha_autorizacao: this.limiteCreditoSenha })
                });
                const d = await r.json();
                if (d.ok && d.autorizador_user_id != null) {
                    this.limiteCreditoAutorizadoPorUserId = d.autorizador_user_id;
                    this.limiteCreditoExcedidoModal = false;
                    this.limiteCreditoExcedidoMensagem = '';
                    this.limiteCreditoSenha = '';
                    const payload = { ...this.limiteCreditoExcedidoPayload, limite_credito_autorizado_por_user_id: d.autorizador_user_id };
                    this.limiteCreditoExcedidoPayload = null;
                    await this.enviarVendaComPayload(payload);
                    this.limiteCreditoAutorizadoPorUserId = null;
                } else {
                    window.toast.error(d.message || 'Senha incorreta.');
                }
            } catch (e) {
                window.toast.error('Erro ao validar senha.');
            }
        },
        escolherClienteBalcão() {
            this.clienteId = '';
            this.clienteNome = '';
            this.clienteBusca = '';
            this.clienteBloqueadoAutorizadoPorUserId = null;
        },
        async buscarClientesModal() {
            this.carregandoClientes = true;
            const q = (this.clienteBuscaModal || '').trim();
            const url = this.baseUrl + '/api/busca/clientes' + (q ? '?q=' + encodeURIComponent(q) : '');
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.clientesResultados = d.clientes || [];
            } catch (e) {
                this.clientesResultados = [];
            }
            this.carregandoClientes = false;
        },

        normalizarCep(cep) {
            return (cep || '').replace(/\D/g, '').slice(0, 8);
        },
        mascaraCpf(val) {
            const d = (val || '').replace(/\D/g, '').slice(0, 11);
            if (d.length <= 3) return d;
            if (d.length <= 6) return d.slice(0, 3) + '.' + d.slice(3);
            return d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6, 9) + '-' + d.slice(9);
        },
        mascaraCnpj(val) {
            const raw = (val || '').replace(/[^0-9A-Za-z]/g, '');
            if (/[A-Za-z]/.test(raw)) {
                const s = raw.length > 14 ? raw.slice(-14) : raw.slice(0, 14);
                return s.toUpperCase();
            }
            const d = raw.replace(/\D/g, '').slice(0, 14);
            if (d.length <= 2) return d;
            if (d.length <= 5) return d.slice(0, 2) + '.' + d.slice(2);
            if (d.length <= 8) return d.slice(0, 2) + '.' + d.slice(2, 5) + '.' + d.slice(5);
            if (d.length <= 12) return d.slice(0, 2) + '.' + d.slice(2, 5) + '.' + d.slice(5, 8) + '/' + d.slice(8);
            return d.slice(0, 2) + '.' + d.slice(2, 5) + '.' + d.slice(5, 8) + '/' + d.slice(8, 12) + '-' + d.slice(12);
        },
        validarCpf(cpf) {
            const d = (cpf || '').replace(/\D/g, '');
            if (d.length !== 11) return 'CPF deve ter 11 dígitos.';
            if (/^(\d)\1{10}$/.test(d)) return 'CPF inválido.';
            let s = 0; for (let i = 0; i < 9; i++) s += parseInt(d[i], 10) * (10 - i);
            let r = (s * 10) % 11; if (r === 10) r = 0; if (r !== parseInt(d[9], 10)) return 'CPF inválido.';
            s = 0; for (let i = 0; i < 10; i++) s += parseInt(d[i], 10) * (11 - i);
            r = (s * 10) % 11; if (r === 10) r = 0; if (r !== parseInt(d[10], 10)) return 'CPF inválido.';
            return '';
        },
        validarCnpj(cnpj) {
            const raw = (cnpj || '').replace(/[^0-9A-Za-z]/g, '');
            if (raw.length !== 14) return 'CNPJ deve ter 14 caracteres.';
            if (/^([0-9A-Z])\1{13}$/.test(raw)) return 'CNPJ inválido.';
            if (/^\d+$/.test(raw)) {
                const pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]; const pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                let s = 0; for (let i = 0; i < 12; i++) s += parseInt(raw[i], 10) * pesos1[i];
                let r = s % 11; const d1 = r < 2 ? 0 : 11 - r; if (d1 !== parseInt(raw[12], 10)) return 'CNPJ inválido.';
                s = 0; for (let i = 0; i < 13; i++) s += parseInt(raw[i], 10) * pesos2[i];
                r = s % 11; const d2 = r < 2 ? 0 : 11 - r; if (d2 !== parseInt(raw[13], 10)) return 'CNPJ inválido.';
            }
            return '';
        },
        async buscarCepNovoCliente() {
            const cep = this.normalizarCep(this.novoClienteCep);
            if (cep.length !== 8) return;
            this.carregandoCep = true;
            try {
                const r = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
                const data = await r.json();
                if (data && !data.erro) {
                    this.novoClienteCep = cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                    this.novoClienteLogradouro = data.logradouro || '';
                    this.novoClienteBairro = data.bairro || '';
                    this.novoClienteCidade = data.localidade || '';
                    this.novoClienteEstado = (data.uf || '').toUpperCase();
                }
            } catch (e) {}
            this.carregandoCep = false;
        },
        normalizarCnpjResponse(data) {
            const est = data.estabelecimento || data.endereco || data.address || data;
            const cidade = est?.cidade?.nome || est?.municipio?.nome || est?.cidade || est?.municipio || data.municipio || est?.localidade || '';
            const uf = est?.estado?.sigla || est?.estado?.uf || est?.estado || est?.uf || data.uf || '';
            return {
                razao_social: data.razao_social || data.razaoSocial || data.nome || data.company?.name || '',
                nome_fantasia: est?.nome_fantasia || est?.nomeFantasia || data.nome_fantasia || data.nomeFantasia || data.fantasia || data.alias || '',
                cep: est?.cep || data.cep || '',
                logradouro: est?.logradouro || data.logradouro || est?.endereco || est?.street || '',
                numero: est?.numero || data.numero || est?.number || '',
                bairro: est?.bairro || data.bairro || est?.district || '',
                cidade: cidade,
                uf: uf,
                telefone: (data.estabelecimento?.telefone1 || data.telefone1 || data.telefone || '').toString().replace(/\D/g, '').slice(0, 11),
            };
        },
        async buscarCnpjNovoCliente() {
            this.erroNovoClienteCnpj = '';
            const cnpjStr = (this.novoClienteCnpj || '').trim();
            if (!cnpjStr) { this.erroNovoClienteCnpj = 'Informe o CNPJ.'; return; }
            const errCnpj = this.validarCnpj(cnpjStr);
            if (errCnpj) { this.erroNovoClienteCnpj = errCnpj; return; }
            const raw = cnpjStr.replace(/[^0-9A-Za-z]/g, '');
            const cnpjNumerico = raw.replace(/\D/g, '');
            if (cnpjNumerico.length !== 14) { this.erroNovoClienteCnpj = 'Para buscar dados na Receita, o CNPJ deve ser apenas números (14 dígitos).'; return; }
            this.carregandoCnpj = true;
            try {
                const endpoints = ['https://publica.cnpj.ws/cnpj/' + cnpjNumerico, 'https://api.opencnpj.org/' + cnpjNumerico];
                let data = null;
                for (const url of endpoints) {
                    try {
                        const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!r.ok) continue;
                        const json = await r.json();
                        if (json && Object.keys(json).length) { data = this.normalizarCnpjResponse(json); break; }
                    } catch (e) {}
                }
                if (data) {
                    if (data.nome_fantasia) this.novoClienteNome = data.nome_fantasia; else if (data.razao_social) this.novoClienteNome = data.razao_social;
                    this.novoClienteCep = (data.cep || '').toString().replace(/\D/g, '').length === 8 ? (data.cep || '').replace(/(\d{5})(\d{3})/, '$1-$2') : (data.cep || '');
                    this.novoClienteLogradouro = data.logradouro || '';
                    this.novoClienteNumero = data.numero || '';
                    this.novoClienteBairro = data.bairro || '';
                    this.novoClienteCidade = data.cidade || '';
                    this.novoClienteEstado = (data.uf || '').toUpperCase();
                    if (data.telefone && data.telefone.length >= 10) this.novoClienteTelefone = data.telefone.replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3');
                } else {
                    this.erroNovoClienteCnpj = 'Não foi possível buscar dados do CNPJ. Preencha manualmente.';
                }
            } catch (e) {
                this.erroNovoClienteCnpj = 'Erro ao buscar CNPJ. Tente novamente.';
            }
            this.carregandoCnpj = false;
        },

        async cadastrarNovoCliente() {
            this.erroNovoCliente = ''; this.erroNovoClienteCpf = ''; this.erroNovoClienteCnpj = '';
            const nome = (this.novoClienteNome || '').trim();
            if (!nome) { this.erroNovoCliente = 'Informe o nome.'; return; }
            const tipo = this.novoClienteTipo || 'F';
            const cpfStr = (this.novoClienteCpf || '').trim();
            const cnpjStr = (this.novoClienteCnpj || '').trim();
            if (tipo === 'F' && cpfStr) {
                const errCpf = this.validarCpf(cpfStr);
                if (errCpf) { this.erroNovoClienteCpf = errCpf; return; }
            }
            if (tipo === 'J' && cnpjStr) {
                const errCnpj = this.validarCnpj(cnpjStr);
                if (errCnpj) { this.erroNovoClienteCnpj = errCnpj; return; }
            }
            const payload = {
                nome,
                tipo_pessoa: tipo,
                cpf: tipo === 'F' ? (this.novoClienteCpf || '').trim() || null : null,
                cnpj: tipo === 'J' ? (this.novoClienteCnpj || '').trim() || null : null,
                contato_nome: tipo === 'J' ? (this.novoClienteContatoNome || '').trim() || null : null,
                contato_telefone: (this.novoClienteTelefone || '').trim() || null,
                contato_email: (this.novoClienteEmail || '').trim() || null,
                contato_whatsapp: !!this.novoClienteWhatsapp,
                cep: (this.novoClienteCep || '').trim() || null,
                logradouro: (this.novoClienteLogradouro || '').trim() || null,
                numero: (this.novoClienteNumero || '').trim() || null,
                complemento: (this.novoClienteComplemento || '').trim() || null,
                bairro: (this.novoClienteBairro || '').trim() || null,
                cidade: (this.novoClienteCidade || '').trim() || null,
                estado: (this.novoClienteEstado || '').trim() || null,
            };
            try {
                const r = await fetch(this.quickStoreUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(payload)
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.message || d.errors ? Object.values(d.errors).flat().join(' ') : 'Erro ao cadastrar');
                this.escolherCliente({ id: d.id, nome: d.nome });
                this.mostrarCliente = false;
                this.mostrarNovoCliente = false;
                this.novoClienteNome = ''; this.novoClienteCpf = ''; this.novoClienteCnpj = ''; this.novoClienteTelefone = '';
                this.novoClienteContatoNome = ''; this.novoClienteEmail = ''; this.novoClienteWhatsapp = false;
                this.novoClienteCep = ''; this.novoClienteLogradouro = ''; this.novoClienteNumero = ''; this.novoClienteComplemento = '';
                this.novoClienteBairro = ''; this.novoClienteCidade = ''; this.novoClienteEstado = '';
                this.erroNovoClienteCpf = ''; this.erroNovoClienteCnpj = '';
                this.telaClienteModal = 'selecao';
            } catch (e) {
                this.erroNovoCliente = e.message || 'Erro ao cadastrar cliente';
            }
        },

        async confirmarSalvarOrcamento() {
            await this.salvarOrcamento();
            if (this.itens.length === 0) {
                this.mostrarOrcamentoModal = false;
            }
        },
        async salvarOrcamento() {
            if (!this.itens.length) return;
            const payload = {
                cliente_id: this.clienteId ? (isNaN(Number(this.clienteId)) ? null : Number(this.clienteId)) : null,
                nome_cliente: (this.clienteNome || '').trim() || null,
                observacao: (this.observacaoOrcamento || '').trim() || null,
                observacao_interna: (this.observacaoInternaOrcamento || '').trim() || null,
                itens: this.itens.map(i => ({
                    tipo: i.tipo,
                    produto_id: i.produto_id,
                    servico_id: i.servico_id,
                    descricao: i.descricao,
                    quantidade: i.quantidade,
                    preco_unitario: i.preco_unitario,
                    serial: i.serial,
                    kit_componentes: i.kit_componentes && Array.isArray(i.kit_componentes) ? i.kit_componentes : [],
                    observacao: (i.observacao || '').trim() || null
                }))
            };
            const r = await fetch(this.baseUrl + '/api/orcamentos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                body: JSON.stringify({ ...payload, numero_caixa: this.numeroCaixa })
            });
            if (r.ok) {
                this.itens = [];
                this.orcamentoImportadoId = null;
                this.mostrarOrcamentoModal = false;
                this.mostrarNotificacao('Orçamento salvo com sucesso.', 'sucesso');
            } else {
                const d = await r.json();
                this.mostrarNotificacao(d.message || 'Erro ao salvar orçamento.', 'erro');
            }
        },
        mostrarNotificacao(texto, tipo = 'sucesso') {
            this.notificacao = { texto, tipo, mostrar: true };
            const t = this;
            setTimeout(() => { t.notificacao.mostrar = false; }, 3500);
        },

        async abrirOrcamentosSalvos() {
            this.mostrarOrcamentosSalvosModal = true;
            this.orcamentosSalvosLista = [];
            this.orcamentoDetalheModal = null;
            await this.carregarOrcamentosSalvos();
        },
        async carregarOrcamentosSalvos() {
            this.orcamentosSalvosCarregando = true;
            try {
                const q = (this.orcamentosSalvosBusca || '').trim();
                const url = this.baseUrl + '/api/orcamentos' + (q ? '?q=' + encodeURIComponent(q) : '');
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.orcamentosSalvosLista = d.orcamentos || [];
            } catch (e) {
                this.orcamentosSalvosLista = [];
            }
            this.orcamentosSalvosCarregando = false;
        },
        verDetalheOrcamento(o) {
            this.orcamentoDetalheModal = o;
            this.detalheObsAberto = false;
            this.detalheObsInternaAberto = false;
        },
        fecharDetalheOrcamento() {
            this.orcamentoDetalheModal = null;
        },
        async imprimirOrcamento() {
            if (!this.itens.length) return;
            const payload = {
                itens: this.itens.map(i => ({
                    tipo: i.tipo || 'produto',
                    descricao: i.descricao,
                    quantidade: Number(i.quantidade) || 0,
                    preco_unitario: Number(i.preco_unitario) || 0,
                    total: Number(i.total) || 0,
                    kit_componentes: i.kit_componentes && Array.isArray(i.kit_componentes) ? i.kit_componentes : [],
                    observacao: (i.observacao || '').trim() || null
                })),
                cliente_id: this.clienteId ? (isNaN(Number(this.clienteId)) ? null : Number(this.clienteId)) : null,
                clienteNome: this.clienteNome || 'Cliente balcão',
                totalGeral: this.totalFinal,
                desconto: this.parseValor(this.desconto) || 0,
                observacao: (this.observacaoOrcamento || '').trim()
            };
            if (this.orcamentoImportadoId != null) payload.numeroOrcamento = String(this.orcamentoImportadoId);
            else if (this.imprimirOrcamentoNumero != null && this.imprimirOrcamentoNumero !== '') payload.numeroOrcamento = String(this.imprimirOrcamentoNumero);
            try {
                const r = await fetch(this.baseUrl + '/api/orcamento/pdf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/pdf', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(payload)
                });
                if (!r.ok) { this.mostrarNotificacao('Erro ao gerar PDF do orçamento.', 'erro'); return; }
                const blob = await r.blob();
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            } catch (e) {
                this.mostrarNotificacao('Erro ao gerar PDF do orçamento.', 'erro');
            }
        },
        async imprimirOrcamentoFromOrcamentoSalvo(o) {
            if (!o || !o.itens || !o.itens.length) return;
            const itens = o.itens.map(i => ({
                tipo: i.tipo || 'produto',
                descricao: i.descricao,
                quantidade: Number(i.quantidade) || 0,
                preco_unitario: Number(i.preco_unitario) || 0,
                total: Number(i.total) || (Number(i.quantidade) || 0) * (Number(i.preco_unitario) || 0),
                kit_componentes: i.kit_componentes && Array.isArray(i.kit_componentes) ? i.kit_componentes : [],
                observacao: (i.observacao || '').trim() || null
            }));
            const payload = {
                itens,
                cliente_id: o.cliente_id ? (isNaN(Number(o.cliente_id)) ? null : Number(o.cliente_id)) : null,
                clienteNome: o.nome_cliente || 'Cliente balcão',
                totalGeral: Number(o.total) || 0,
                desconto: Number(o.desconto) || 0,
                observacao: (o.observacao || '').trim(),
                numeroOrcamento: o.id != null ? String(o.id) : null
            };
            try {
                const r = await fetch(this.baseUrl + '/api/orcamento/pdf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/pdf', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(payload)
                });
                if (!r.ok) { this.mostrarNotificacao('Erro ao gerar PDF do orçamento.', 'erro'); return; }
                const blob = await r.blob();
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            } catch (e) {
                this.mostrarNotificacao('Erro ao gerar PDF do orçamento.', 'erro');
            }
        },
        formatarDataOrcamento(d) {
            if (!d) return '';
            const dt = new Date(d);
            return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },
        async importarOrcamento(id) {
            try {
                const r = await fetch(this.baseUrl + '/api/orcamentos/' + id + '/importar-venda', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa }
                });
                const d = await r.json();
                if (!r.ok) { window.toast.error(d.message || 'Erro ao importar.'); return; }
                const itensFormatados = (d.itens || []).map(i => ({
                    tipo: i.tipo || 'produto',
                    produto_id: i.produto_id,
                    servico_id: i.servico_id,
                    descricao: i.descricao,
                    quantidade: Number(i.quantidade) || 1,
                    preco_unitario: Number(i.preco_unitario) || 0,
                    total: (Number(i.quantidade) || 1) * (Number(i.preco_unitario) || 0),
                    serial: i.serial || null,
                    imagens: Array.isArray(i.imagens) ? i.imagens : [],
                    kit_componentes: Array.isArray(i.kit_componentes) ? i.kit_componentes : [],
                    observacao: (i.observacao || '').trim()
                }));
                this.itens = itensFormatados;
                this.clienteId = d.cliente_id || '';
                this.clienteNome = d.nome_cliente || '';
                this.observacaoOrcamento = d.observacao || '';
                this.observacaoInternaOrcamento = d.observacao_interna || '';
                this.orcamentoImportadoId = id;
                this.mostrarOrcamentosSalvosModal = false;
                this.orcamentoDetalheModal = null;
            } catch (e) {
                window.toast.error('Erro ao importar orçamento.');
            }
        },

        abrirFinalizar() {
            const estavaAberto = this.mostrarFinalizar;
            this.mostrarFinalizar = true;
            if (!this.formasPagamento || this.formasPagamento.length === 0) {
                this.carregarFormasPagamento();
            }
            if (!estavaAberto) {
                this.pagamentosModal = [];
                this.pagamentoFormaSelecionada = null;
                this.pagamentoValorAtual = this.formatarValor(this.totalFinal);
                this.pagamentoAdquirenteId = '';
                this.pagamentoBandeira = '';
                this.pagamentoParcelas = 1;
                this.descontoInputValor = this.descontoTipoPagamento === 'percentual' ? (this.subtotal > 0 ? (this.parseValor(this.desconto) / this.subtotal * 100).toFixed(1) : '0') : this.desconto;
            }
        },

        aplicaDesconto() {
            const v = this.parseValor(this.descontoInputValor);
            let novoDescontoStr;
            if (this.descontoTipoPagamento === 'percentual') {
                novoDescontoStr = this.subtotal > 0 ? String((this.subtotal * v / 100).toFixed(2)) : '0';
            } else {
                novoDescontoStr = String(Math.min(v, this.subtotal).toFixed(2));
            }
            const novoDescontoNum = this.parseValor(novoDescontoStr);
            const subtotal = this.subtotal;
            const percentualAplicado = subtotal > 0 ? (novoDescontoNum / subtotal) * 100 : 0;

            if (this.descontoMaximoPercentual != null && percentualAplicado > this.descontoMaximoPercentual) {
                if (!this.descontoAutorizadorNome || !this.descontoAutorizadorNome.trim()) {
                    window.toast.error('Desconto acima do permitido (' + this.descontoMaximoPercentual + '%). Configure um autorizador em PDV > Habilitar caixa por usuário.');
                    return;
                }
                this.descontoPendenteValor = novoDescontoStr;
                this.descontoPendentePercentualAplicado = percentualAplicado;
                this.descontoSenhaAutorizacao = '';
                this.mostrarDescontoAutorizacaoModal = true;
                return;
            }
            this.desconto = novoDescontoStr;
        },

        async enviarSenhaDescontoAutorizacao() {
            if (this.descontoPendenteValor == null) return;
            const senha = (this.descontoSenhaAutorizacao || '').trim();
            if (!senha) {
                window.toast.error('Digite a senha do autorizador.');
                return;
            }
            const subtotal = this.subtotal;
            const descontoValor = this.parseValor(this.descontoPendenteValor);
            try {
                const r = await fetch(this.baseUrl + '/api/validar-senha-desconto', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ senha_autorizacao: senha, desconto_valor: descontoValor, subtotal: subtotal })
                });
                const d = await r.json();
                if (d.ok) {
                    this.desconto = this.descontoPendenteValor;
                    this.descontoAutorizadoPorUserId = d.autorizador_user_id != null ? d.autorizador_user_id : null;
                    this.mostrarDescontoAutorizacaoModal = false;
                    this.descontoPendenteValor = null;
                    this.descontoPendentePercentualAplicado = null;
                    this.descontoSenhaAutorizacao = '';
                    window.toast.success('Desconto aplicado.');
                } else {
                    window.toast.error(d.message || 'Senha incorreta.');
                    if (d.autorizador_nome) this.descontoAutorizadorNome = d.autorizador_nome;
                }
            } catch (e) {
                window.toast.error('Erro ao validar senha.');
            }
        },

        getFormaById(id) {
            return this.formasPagamento.find(f => f.id == id) || null;
        },

        getFormaIcon(tipo) {
            const t = (tipo || '').toLowerCase();
            if (t === 'dinheiro') return 'payments';
            if (t === 'pix') return 'qr_code_2';
            if (t === 'cartao_credito' || t === 'cartao_debito') return 'credit_card';
            return 'account_balance_wallet';
        },

        formaPermiteCartao(forma) {
            if (!forma || !forma.tipo) return false;
            const t = (forma.tipo || '').toLowerCase();
            return t === 'cartao_credito' || t === 'cartao_debito';
        },

        formaEhPix(forma) {
            if (!forma || !forma.tipo) return false;
            return (forma.tipo || '').toLowerCase() === 'pix';
        },

        getPixChavesAtivas(forma) {
            if (!forma || !Array.isArray(forma.pix_chaves)) return [];
            return forma.pix_chaves.filter(k => k && (k.ativo === undefined || !!k.ativo) && String(k.chave || '').trim() !== '');
        },

        getPixChaveSelecionada(forma) {
            const id = String(this.pagamentoPixChaveId || '').trim();
            if (!id) return null;
            return this.getPixChavesAtivas(forma).find(k => String(k.id || '') === id) || null;
        },

        gerarVencimentosPix(parcelas, primeiroVencimento) {
            const total = Math.max(1, parseInt(parcelas, 10) || 1);
            let baseStr = String(primeiroVencimento || '').trim();
            if (!baseStr) baseStr = new Date().toISOString().slice(0, 10);
            const m = baseStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return [];
            const y = parseInt(m[1], 10);
            const mon = parseInt(m[2], 10);
            const day = parseInt(m[3], 10);
            if (!y || !mon || !day) return [];
            const base = new Date(Date.UTC(y, mon - 1, day));
            if (isNaN(base.getTime())) return [];
            const out = [];
            for (let i = 0; i < total; i++) {
                const d = new Date(base.getTime());
                d.setUTCMonth(d.getUTCMonth() + i);
                out.push(d.toISOString().slice(0, 10));
            }
            return out;
        },

        formaPermiteParcelas(forma) {
            if (!forma || !forma.permite_parcela) return false;
            const max = parseInt(forma.max_parcelas, 10);
            // Quando max_parcelas não vier definido no cadastro, mantemos parcelamento habilitado com padrão.
            return isNaN(max) ? true : max > 1;
        },

        getParcelasOptions(forma) {
            const maxRaw = forma && forma.max_parcelas ? parseInt(forma.max_parcelas, 10) : NaN;
            const max = (!isNaN(maxRaw) && maxRaw > 1) ? maxRaw : 12;
            const arr = [];
            for (let i = 1; i <= max; i++) arr.push(i);
            return arr;
        },

        selecionarFormaPagamento(forma) {
            this.pagamentoFormaSelecionada = forma ? forma.id : null;
            this.pagamentoValorAtual = this.formatarValor(this.saldoRestanteFinalizar);
            this.pagamentoAdquirenteId = '';
            this.pagamentoBandeira = '';
            this.pagamentoAntecipado = false;
            this.pagamentoParcelas = 1;
            this.pagamentoPixChaveId = '';
            this.pagamentoPixPrimeiroVencimento = '';
        },

        confirmarValorPagamento() {
            const forma = this.getFormaById(this.pagamentoFormaSelecionada);
            const valor = this.parseValor(this.pagamentoValorAtual);
            if (!forma || valor <= 0) return;
            if (this.formaPermiteCartao(forma)) {
                if (!this.pagamentoAdquirenteId || !this.pagamentoBandeira) {
                    window.toast.error('Selecione adquirente e bandeira para cartão.');
                    return;
                }
            }
            const pixChave = this.formaEhPix(forma) ? this.getPixChaveSelecionada(forma) : null;
            if (this.formaEhPix(forma) && this.getPixChavesAtivas(forma).length > 0 && !pixChave) {
                window.toast.error('Selecione a chave PIX.');
                return;
            }
            const parcelas = this.formaPermiteParcelas(forma) ? (this.pagamentoParcelas || 1) : 1;
            const parcelasNum = Math.max(1, parseInt(parcelas, 10) || 1);
            const primeiroVencimentoPix = this.pagamentoPixPrimeiroVencimento || new Date().toISOString().slice(0, 10);
            const pixVencimentos = (this.formaEhPix(forma) && parcelasNum > 1)
                ? this.gerarVencimentosPix(parcelasNum, primeiroVencimentoPix)
                : [];
            if (this.formaEhPix(forma) && parcelasNum > 1 && pixVencimentos.length !== parcelasNum) {
                window.toast.error('Nao foi possivel validar as datas do PIX parcelado.');
                return;
            }
            this.pagamentosModal.push({
                forma_pagamento_id: forma.id,
                forma_nome: forma.nome,
                forma_tipo: forma.tipo,
                valor: this.formatarValor(valor),
                parcelas: parcelasNum,
                adquirente_id: this.formaPermiteCartao(forma) ? this.pagamentoAdquirenteId : null,
                bandeira: this.formaPermiteCartao(forma) ? this.pagamentoBandeira : null,
                antecipado: this.formaPermiteCartao(forma) ? (this.pagamentoAntecipado || null) : null,
                pix_chave_id: pixChave ? (pixChave.id || null) : null,
                pix_parcela_vencimentos: pixVencimentos,
                conta_bancaria_id: pixChave && pixChave.conta_bancaria_id ? pixChave.conta_bancaria_id : (forma.conta_bancaria_id || null),
                observacoes: pixChave ? ('PIX: ' + (pixChave.nome || 'Chave') + (pixChave.chave ? ' - ' + pixChave.chave : '')) : null
            });
            this.pagamentoFormaSelecionada = null;
            this.pagamentoValorAtual = this.formatarValor(this.saldoRestanteFinalizar);
            this.pagamentoAdquirenteId = '';
            this.pagamentoBandeira = '';
            this.pagamentoAntecipado = false;
            this.pagamentoParcelas = 1;
            this.pagamentoPixChaveId = '';
            this.pagamentoPixPrimeiroVencimento = '';
        },

        get saldoRestanteFinalizar() {
            return Math.max(0, this.totalFinal - this.somaPagamentos);
        },

        adicionarPagamento() {
            this.pagamentosModal.push({ forma_pagamento_id: '', valor: '0', forma_nome: '', forma_tipo: '', parcelas: 1, adquirente_id: null, bandeira: null, antecipado: null });
        },

        removerPagamento(i) {
            this.pagamentosModal.splice(i, 1);
        },

        atualizarFormaPagamento(i) {},

        async confirmarVenda() {
            const payload = {
                cliente_id: this.clienteId || null,
                desconto: this.parseValor(this.desconto),
                desconto_autorizado_por_user_id: this.descontoAutorizadoPorUserId || null,
                cliente_bloqueado_autorizado_por_user_id: this.clienteBloqueadoAutorizadoPorUserId || null,
                limite_credito_autorizado_por_user_id: this.limiteCreditoAutorizadoPorUserId || null,
                estoque_zerado_autorizado_por_user_id: this.estoqueZeradoAutorizadoPorUserId || null,
                observacoes: (this.observacaoOrcamento || '').trim() || null,
                observacao_interna: (this.observacaoInternaOrcamento || '').trim() || null,
                itens: this.itens.map(i => ({
                    tipo: i.tipo,
                    produto_id: i.produto_id,
                    servico_id: i.servico_id,
                    descricao: i.descricao,
                    quantidade: i.quantidade,
                    preco_unitario: i.preco_unitario,
                    serial: i.serial || null,
                    observacao: (i.observacao || '').trim() || null
                })),
                pagamentos: this.pagamentosModal.filter(p => p.forma_pagamento_id && this.parseValor(p.valor) > 0).map(p => ({
                    forma_pagamento_id: p.forma_pagamento_id,
                    valor: this.parseValor(p.valor),
                    parcelas: p.parcelas || 1,
                    conta_bancaria_id: p.conta_bancaria_id || null,
                    adquirente_id: p.adquirente_id || null,
                    bandeira: p.bandeira || null,
                    antecipado: p.antecipado != null ? p.antecipado : null,
                    pix_chave_id: p.pix_chave_id || null,
                    pix_parcela_vencimentos: Array.isArray(p.pix_parcela_vencimentos) ? p.pix_parcela_vencimentos : [],
                    observacoes: p.observacoes || null
                }))
            };
            await this.enviarVendaComPayload(payload);
        },

        async enviarVendaComPayload(payload) {
            const body = { ...payload, numero_caixa: this.numeroCaixa };
            const r = await fetch(this.baseUrl + '/api/vendas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-PDV-Caixa': this.numeroCaixa },
                body: JSON.stringify(body)
            });
            const d = await r.json();
            if (r.ok) {
                const numVenda = (d.venda && (d.venda.numero_exibicao != null)) ? d.venda.numero_exibicao : (d.venda && d.venda.id ? (this.numeroVendaInicial + (d.venda.id - 1)) : (this.numeroVendaInicial + (this.ultimaVendaId || 0)));
                if (d.venda && d.venda.id) this.ultimaVendaId = d.venda.id;
                window.toast.success('Venda #' + numVenda + ' registrada.');
                this.comprovanteVenda = d.venda;
                this.descontoAutorizadoPorUserId = null;
                this.clienteBloqueadoAutorizadoPorUserId = null;
                this.limiteCreditoAutorizadoPorUserId = null;
                this.estoqueZeradoAutorizadoPorUserId = null;
                this.itens = []; this.desconto = '0'; this.orcamentoImportadoId = null; this.mostrarFinalizar = false;
                await this.carregarStatus(true);
            } else if (d.code === 'limite_credito_excedido') {
                this.limiteCreditoExcedidoPayload = payload;
                this.limiteCreditoExcedidoMensagem = d.message || 'Limite de crédito do cliente excedido. Autorização necessária.';
                this.limiteCreditoExcedidoModal = true;
                this.limiteCreditoSenha = '';
            } else if (d.code === 'estoque_insuficiente') {
                this.estoqueInsuficientePayload = payload;
                this.estoqueInsuficienteModal = true;
                this.estoqueInsuficienteSenha = '';
                this.estoqueInsuficienteAutorizadorNome = d.estoque_zerado_autorizador_nome || this.estoqueInsuficienteAutorizadorNome || '';
                window.toast.warning(d.message || 'Estoque insuficiente. Autorização necessária.');
            } else {
                window.toast.error(d.message || 'Erro ao registrar venda');
            }
        },

        async compartilharComprovante() {
            if (!this.comprovanteVenda || !this.comprovanteVenda.id) return;
            try {
                const r = await fetch(this.baseUrl + '/api/vendas/' + this.comprovanteVenda.id + '/comprovante-pdf', {
                    method: 'GET',
                    headers: { 'Accept': 'application/pdf', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!r.ok) {
                    window.toast.error('Erro ao gerar PDF do comprovante.');
                    return;
                }
                const blob = await r.blob();
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            } catch (e) {
                window.toast.error('Erro ao gerar PDF do comprovante.');
            }
        }
    }));
});
</script>
@endsection
