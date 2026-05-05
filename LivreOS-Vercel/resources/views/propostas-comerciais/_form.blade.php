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

{{-- Formulário: create e edit (sem coluna Gerar OS / sem desconto global) --}}
@php
    $proposta = $proposta ?? null;
    $tabelaPrecoInicial = old('tabela_preco_id', isset($proposta) && $proposta->tabela_preco_id !== null && $proposta->tabela_preco_id !== '' ? (int) $proposta->tabela_preco_id : null);
    $tabelaPrecoInicialJs = ($tabelaPrecoInicial === null || $tabelaPrecoInicial === '') ? '' : (int) $tabelaPrecoInicial;
    $validadeOrcDiasCfg = (int) ($validade_orcamento_dias_config ?? 0);
    $validadeOldRaw = old('validade_dias', isset($proposta) ? $proposta->validade_dias : null);
    $validadeInicialStr = ($validadeOldRaw !== null && $validadeOldRaw !== '' && (int) $validadeOldRaw > 0)
        ? (string) (int) $validadeOldRaw
        : '';
    $dataEmissaoInicial = old('data_emissao', isset($proposta) ? $proposta->data_emissao?->format('Y-m-d') : now()->format('Y-m-d'));
@endphp
<div x-data="propostaComercialForm()" x-init="init()">

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dados da proposta</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
    <div class="lg:col-span-2">

        @if(isset($proposta) && $proposta->exists)
            @php
                $clienteAtual = $proposta->cliente;
                $baseCliente = $proposta->unidade ?? $clienteAtual;
                $contatosCliente = $baseCliente?->contatos ?? collect();
                $contatoPreferencial = $proposta->contato
                    ?? $contatosCliente->firstWhere('principal', true)
                    ?? $contatosCliente->sortBy('id')->first();
                $enderecoAtual = $proposta->endereco
                    ?? $baseCliente?->enderecos?->firstWhere('principal', true);
                $contatoWhatsappPreferencial = $baseCliente
                    ? $baseCliente->contatos
                        ->where('telefone_whatsapp', true)
                        ->sortByDesc(fn($c) => (int) $c->principal)
                        ->first()
                    : null;
                $enderecoTexto = $enderecoAtual
                    ? trim($enderecoAtual->logradouro . ' ' . $enderecoAtual->numero . ' ' . ($enderecoAtual->complemento ? '- ' . $enderecoAtual->complemento : '') . ', ' . $enderecoAtual->bairro . ' - ' . $enderecoAtual->cidade . '/' . $enderecoAtual->estado)
                    : '-';
                $contatosResumo = $contatosCliente
                    ->where('principal', false)
                    ->sortBy('id')
                    ->take(3);
            @endphp
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="mb-2 flex items-center justify-between">
                    <span class="font-medium">Dados do cliente</span>
                    <button type="button" class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400" data-modal-open="propostaClienteModal">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill="currentColor" d="M14.7 2.3a1 1 0 0 1 1.4 0l1.6 1.6a1 1 0 0 1 0 1.4l-8.6 8.6-3.2.5.5-3.2 8.6-8.6zM3 14.5V17h2.5l8.2-8.2-2.5-2.5L3 14.5z"/>
                        </svg>
                        Editar
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-1 md:grid-cols-2">
                    <div><span class="font-medium">Cliente:</span> <span id="cliente_nome">{{ $clienteAtual->nome ?? '-' }}</span></div>
                    <div>
                        <span class="font-medium">Endereço:</span>
                        <a id="cliente_endereco_link"
                           href="{{ $enderecoTexto && $enderecoTexto !== '-' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoTexto) : '#' }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                            <span id="cliente_endereco">{{ $enderecoTexto }}</span>
                        </a>
                    </div>
                    <div>
                        <span class="font-medium">Telefone:</span>
                        <a id="cliente_contato_telefone_link"
                           href="{{ !empty($contatoPreferencial?->telefone) ? 'tel:' . preg_replace('/\D+/', '', $contatoPreferencial->telefone) : '#' }}"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                            <span id="cliente_contato_telefone">{{ $contatoPreferencial->telefone ?? '-' }}</span>
                        </a>
                    </div>
                    <div><span class="font-medium">Contato:</span> <span id="cliente_contato_nome">{{ $contatoPreferencial->nome ?? '-' }}</span></div>
                    <div><span class="font-medium">E-mail:</span> <span id="cliente_contato_email">{{ $contatoPreferencial->email ?? '-' }}</span></div>
                    <div>
                        <span class="font-medium">WhatsApp:</span>
                        <a id="cliente_whatsapp_link"
                           href="{{ !empty($contatoWhatsappPreferencial?->telefone) ? 'https://wa.me/' . preg_replace('/\D+/', '', $contatoWhatsappPreferencial->telefone) : '#' }}"
                           target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline dark:text-brand-400">
                        <span id="cliente_whatsapp">
                            @if($contatoWhatsappPreferencial?->telefone)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="currentColor" d="M12.04 2C6.52 2 2 6.48 2 12c0 1.94.54 3.83 1.56 5.47L2 22l4.74-1.54A9.96 9.96 0 0 0 12.04 22C17.56 22 22 17.52 22 12S17.56 2 12.04 2zm0 18.2c-1.74 0-3.44-.5-4.9-1.45l-.35-.22-2.8.9.92-2.72-.23-.35A8.18 8.18 0 0 1 3.8 12c0-4.53 3.7-8.2 8.24-8.2 4.54 0 8.16 3.67 8.16 8.2 0 4.53-3.62 8.2-8.16 8.2zm4.6-6.14c-.25-.12-1.47-.72-1.7-.8-.23-.08-.39-.12-.56.12-.17.25-.64.8-.78.96-.14.17-.29.19-.53.07-.25-.12-1.05-.38-2-1.2-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.1-.5.1-.1.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42l-.48-.01c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.9 2.4 1.02 2.56.12.17 1.77 2.7 4.3 3.79.6.26 1.07.42 1.44.54.61.19 1.17.16 1.61.1.49-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.47-.28z"/>
                                    </svg>
                                    {{ $contatoWhatsappPreferencial->telefone }}
                                </span>
                            @else
                                -
                            @endif
                        </span>
                        </a>
                    </div>
                    <div class="md:col-span-2" id="cliente_contatos_resumo">
                        <span class="font-medium">Contatos cadastrados:</span>
                        <div class="mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            @forelse($contatosResumo as $contato)
                                <div>{{ $contato->nome ?? 'Contato' }} — {{ $contato->telefone ?? '-' }}@if(!empty($contato->email)) • {{ $contato->email }}@endif</div>
                            @empty
                                <div>-</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Create: seletor de cliente + filial (mesmo comportamento do modal) --}}
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="mb-3">
                    <span class="font-medium">Cliente</span>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Se a empresa tiver filiais, deixe <strong>Unidade</strong> em branco para usar a matriz.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-error-500">*</span></label>
                        <input type="text" id="cliente_busca" list="clientes_lista"
                               value="{{ old('cliente_busca', optional($clientes->firstWhere('id', old('cliente_id')))->nome ?? '') }}"
                               placeholder="Digite para buscar..."
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <datalist id="clientes_lista">
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->nome }}" data-id="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}"></option>
                            @endforeach
                        </datalist>

                        <select name="cliente_id" id="cliente_id" required class="hidden">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}"
                                        data-grupo="{{ $cliente->grupo_economico_id }}"
                                        {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->nome }}
                                </option>
                            @endforeach
                        </select>
                        @error('cliente_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade (filial)</label>
                        <select name="cliente_unidade_id" id="cliente_unidade_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">— Matriz —</option>
                            @foreach($unidades as $unidade)
                                <option value="{{ $unidade->id }}"
                                        data-grupo="{{ $unidade->grupo_economico_id }}"
                                        data-matriz="{{ $unidade->matriz_id }}"
                                        {{ old('cliente_unidade_id') == $unidade->id ? 'selected' : '' }}>
                                    {{ $unidade->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato principal</label>
                        <select name="contato_id" id="contato_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($contatos as $contato)
                                @php
                                    $contatoWhatsapp = $contato->telefone_whatsapp ? ($contato->telefone ?? $contato->telefone2 ?? '') : ($contato->telefone ?? $contato->telefone2 ?? '');
                                @endphp
                                <option value="{{ $contato->id }}"
                                        data-cliente="{{ $contato->cliente_id }}"
                                        data-principal="{{ $contato->principal ? '1' : '0' }}"
                                        data-email="{{ e($contato->email ?? '') }}"
                                        data-telefone="{{ e($contato->telefone ?? '') }}"
                                        data-whatsapp="{{ e($contatoWhatsapp ?? '') }}"
                                        {{ old('contato_id') == $contato->id ? 'selected' : '' }}>
                                    {{ $contato->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</label>
                        <select name="endereco_id" id="endereco_id"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach($enderecos as $endereco)
                                @php
                                    $enderecoLabel = trim(($endereco->logradouro ?? '') . ' ' . ($endereco->numero ?? '') . ' ' . ($endereco->complemento ? '- ' . $endereco->complemento : '') . ', ' . ($endereco->bairro ?? '') . ' - ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
                                @endphp
                                <option value="{{ $endereco->id }}"
                                        data-cliente="{{ $endereco->cliente_id }}"
                                        data-principal="{{ $endereco->principal ? '1' : '0' }}"
                                        {{ old('endereco_id') == $endereco->id ? 'selected' : '' }}>
                                    {{ $enderecoLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
    </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Título (opcional)</label>
            <input type="text" name="titulo" value="{{ old('titulo', $proposta?->titulo ?? '') }}" maxlength="255"
                   placeholder="Ex.: Orçamento manutenção 2026"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Vendedor</label>
            <select name="vendedor_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Selecione —</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}" {{ old('vendedor_id', $proposta?->vendedor_id ?? '') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>

        @if($tabelasPrecos->isNotEmpty())
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tabela de preços</label>
            <select name="tabela_preco_id" x-ref="tabelaPrecoSelect" x-model="tabelaPrecoId"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="">— Padrão (preço de venda) —</option>
                @foreach($tabelasPrecos as $tp)
                    <option value="{{ $tp->id }}" {{ old('tabela_preco_id', $proposta?->tabela_preco_id ?? '') == $tp->id ? 'selected' : '' }}>
                        {{ $tp->nome }}{{ $tp->percentual_ajuste != 0 ? ' ('.($tp->percentual_ajuste > 0 ? '+' : '').number_format($tp->percentual_ajuste, 2, ',', '.').'%)' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">Afeta o <strong>preço sugerido</strong> na busca de produtos. Itens já lançados não são alterados ao trocar a tabela.</p>
        </div>
        @endif

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Data de emissão <span class="text-red-500">*</span></label>
            <input type="date" name="data_emissao" required x-model="dataEmissaoStr"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            @error('data_emissao')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Validade (dias corridos)</label>
            <input type="number" name="validade_dias" min="1" max="3650" step="1"
                   x-model="validadeDiasStr"
                   placeholder="{{ $validadeOrcDiasCfg > 0 ? 'Padrão: '.$validadeOrcDiasCfg.' dias' : 'Opcional' }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            @if($validadeOrcDiasCfg > 0)
                <p class="mt-1 text-xs text-gray-400">Em branco = usar o <strong>padrão da empresa</strong> ({{ $validadeOrcDiasCfg }} dia(s)), conforme Configurações → Pedidos de venda.</p>
            @else
                <p class="mt-1 text-xs text-gray-400">Opcional. Defina aqui ou configure o padrão em <strong>Pedidos de venda</strong>.</p>
            @endif
            @error('validade_dias')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2 lg:col-span-3 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2.5 dark:border-sky-500/25 dark:bg-sky-500/10"
             x-show="validadeDiasEfetiva() > 0" x-cloak>
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">Validade da proposta</p>
            <p class="mt-1 text-xs text-sky-900 dark:text-sky-100/95" x-text="textoLinhaValidade()"></p>
        </div>
    </div>

    <div class="mt-4">
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição geral (opcional)</label>
        <p class="mb-1.5 text-xs text-gray-400">Texto introdutório da proposta; aparece na visualização e no PDF/impressão antes dos itens.</p>
        <textarea name="descricao" rows="4"
                  class="js-jodit js-jodit-descricao-completa w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('descricao', $proposta?->descricao ?? '') !!}</textarea>
        @error('descricao')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Observações para o cliente</label>
            <textarea name="observacoes" rows="4"
                      class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('observacoes', $proposta?->observacoes ?? '') !!}</textarea>
            @error('observacoes')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Observações internas</label>
            <textarea name="observacoes_internas" rows="4"
                      class="js-jodit w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('observacoes_internas', $proposta?->observacoes_internas ?? '') !!}</textarea>
            @error('observacoes_internas')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3 dark:border-gray-800">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Itens</h2>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="adicionarItem('produto')"
                    class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300">+ Produto</button>
            <button type="button" @click="adicionarItem('servico')"
                    class="inline-flex items-center gap-1 rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-100 dark:bg-purple-500/10 dark:text-purple-300">+ Serviço</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2 text-left w-6">#</th>
                    <th class="px-3 py-2 text-left min-w-[200px]">Descrição</th>
                    <th class="px-3 py-2 text-left w-24">Qtd</th>
                    <th class="px-3 py-2 text-left w-28">Preço unit.</th>
                    <th class="px-3 py-2 text-left w-24">Desconto</th>
                    <th class="px-3 py-2 text-right w-28">Total</th>
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
                                       class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                                <div x-show="item.resultados && item.resultados.length > 0"
                                     class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
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
                                            class="shrink-0 rounded border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-violet-800 hover:bg-violet-100 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-200">Variação</button>
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
                                   step="0.0001" min="0.0001" @change="calcularTotais()"
                                   class="w-20 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" :name="`itens[${idx}][preco_unitario]`" x-model="item.preco_unitario"
                                   @change="calcularTotais()"
                                   class="w-28 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" :name="`itens[${idx}][desconto]`" x-model="item.desconto"
                                   @change="calcularTotais()"
                                   class="w-24 rounded border border-gray-300 px-2 py-1.5 text-sm text-right dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        </td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                            R$ <span x-text="formatBr(calcularItemTotal(item))"></span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" @click="removerItem(idx)" class="text-gray-400 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="itens.length === 0">
                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">Nenhum item. Use + Produto ou + Serviço.</td>
                </tr>
            </tbody>
        </table>
    </div>

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
                <span>Descontos (itens)</span>
                <span class="text-red-500">- R$ <span x-text="formatBr(totalDescontos)"></span></span>
            </div>
            <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                <span class="font-semibold text-gray-800 dark:text-white">Total geral</span>
                <span class="text-lg font-bold text-brand-600 dark:text-brand-400">R$ <span x-text="formatBr(totalGeral)"></span></span>
            </div>
        </div>
    </div>
</div>

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
                    class="mt-5 w-full rounded-lg bg-brand-500 py-3 text-sm font-semibold text-white hover:bg-brand-600">Aplicar</button>
        </div>
    </div>
</div>

</div>

@if(isset($proposta) && $proposta->exists)
{{-- Modal Editar dados do cliente --}}
<div id="propostaClienteModal" class="fixed inset-0 z-[2147483001] hidden" style="z-index:2147483647 !important;">
    <div class="absolute inset-0 bg-gray-900/50" data-modal-close="propostaClienteModal"></div>
    <div class="relative mx-auto mt-20 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Editar dados do cliente</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" data-modal-close="propostaClienteModal">✕</button>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-error-500">*</span></label>
                <input type="text" id="cliente_busca" list="clientes_lista" value="{{ $proposta->cliente?->nome ?? '' }}" placeholder="Digite para buscar..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <datalist id="clientes_lista">
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->nome }}" data-id="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}"></option>
                    @endforeach
                </datalist>
                <select name="cliente_id" id="cliente_id" required class="hidden">
                    <option value="">Selecione...</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}" {{ old('cliente_id', $proposta->cliente_id) == $cliente->id ? 'selected' : '' }}>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Unidade (filial)</label>
                <select name="cliente_unidade_id" id="cliente_unidade_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($unidades as $unidade)
                        <option value="{{ $unidade->id }}" data-grupo="{{ $unidade->grupo_economico_id }}" data-matriz="{{ $unidade->matriz_id }}" {{ old('cliente_unidade_id', $proposta->cliente_unidade_id) == $unidade->id ? 'selected' : '' }}>{{ $unidade->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato principal</label>
                <select name="contato_id" id="contato_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contatos as $contato)
                        @php
                            $contatoWhatsapp = $contato->telefone_whatsapp ? ($contato->telefone ?? $contato->telefone2 ?? '') : ($contato->telefone ?? $contato->telefone2 ?? '');
                        @endphp
                        <option value="{{ $contato->id }}" data-cliente="{{ $contato->cliente_id }}" data-principal="{{ $contato->principal ? '1' : '0' }}" data-email="{{ e($contato->email ?? '') }}" data-telefone="{{ e($contato->telefone ?? '') }}" data-whatsapp="{{ e($contatoWhatsapp ?? '') }}" {{ old('contato_id', $proposta->contato_id) == $contato->id ? 'selected' : '' }}>{{ $contato->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</label>
                <select name="endereco_id" id="endereco_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($enderecos as $endereco)
                        @php
                            $enderecoLabel = trim(($endereco->logradouro ?? '') . ' ' . ($endereco->numero ?? '') . ' ' . ($endereco->complemento ? '- ' . $endereco->complemento : '') . ', ' . ($endereco->bairro ?? '') . ' - ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
                        @endphp
                        <option value="{{ $endereco->id }}" data-cliente="{{ $endereco->cliente_id }}" data-principal="{{ $endereco->principal ? '1' : '0' }}" {{ old('endereco_id', $proposta->endereco_id) == $endereco->id ? 'selected' : '' }}>{{ $enderecoLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" data-modal-close="propostaClienteModal">Fechar</button>
            <button type="button" id="propostaClienteSalvar" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Alterar</button>
        </div>
    </div>
</div>
@endif

@php
    $itensIniciais = old('itens', isset($proposta) ? $proposta->itens->map(function ($i) {
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
            'id' => $i->id,
            '_key' => $i->id,
            'tipo' => $i->tipo,
            'produto_id' => $i->produto_id,
            'produto_variacao_id' => $i->produto_variacao_id,
            'servico_id' => $i->servico_id,
            'descricao' => $i->descricao,
            'produto_nome_base' => $produtoNomeBase,
            'produto_formato' => $produtoFormato,
            'variacoes_disponiveis' => $variacoesDisponiveis,
            'kit_componentes' => $kitComponentes,
            'quantidade' => format_quantity($i->quantidade ?? 1) ?: '1',
            'preco_unitario' => number_format($i->preco_unitario, 2, ',', '.'),
            'desconto' => number_format($i->desconto, 2, ',', '.'),
            'busca' => '',
            'resultados' => [],
        ];
    })->values()->toArray() : []);
@endphp

@push('scripts')
{{-- Mesmo padrão do editor em ordens_servico/_form.blade.php (Jodit) --}}
@include('partials.jodit-assets')
<script>
function initRichTextEditors() {
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

    // Descrição geral: toolbar alinhada ao editor de Termos de garantia + extras (pt-BR, imagem base64, tabela, etc.)
    const optsDescricaoCompleta = {
        theme: 'default',
        language: 'pt_br',
        toolbar: true,
        toolbarAdaptive: true,
        height: 440,
        showCharsCounter: true,
        showWordsCounter: true,
        buttons: [
            'undo', 'redo',
            '|',
            'bold', 'italic', 'underline', 'strikethrough',
            '|',
            'superscript', 'subscript',
            '|',
            'ul', 'ol',
            '|',
            'outdent', 'indent',
            '|',
            'font', 'fontsize', 'brush', 'paragraph',
            '|',
            'image', 'link', 'table', 'video',
            '|',
            'align',
            '|',
            'hr', 'eraser', 'copyformat',
            '|',
            'symbol',
            '|',
            'fullsize', 'selectall',
            '|',
            'source', 'print'
        ],
        uploader: {
            insertImageAsBase64URI: true
        }
    };

    document.querySelectorAll('#form-proposta-comercial textarea.js-jodit-descricao-completa').forEach(textarea => {
        if (textarea.jodit) return;
        const editor = Jodit.make(textarea, optsDescricaoCompleta);
        editor.events.on('change', () => {
            textarea.value = editor.value;
        });
    });

    document.querySelectorAll('#form-proposta-comercial textarea.js-jodit:not(.js-jodit-descricao-completa)').forEach(textarea => {
        if (textarea.jodit) return;
        const editor = Jodit.make(textarea, optsSimples);
        editor.events.on('change', () => {
            textarea.value = editor.value;
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initRichTextEditors();
    const form = document.getElementById('form-proposta-comercial');
    if (form) {
        form.addEventListener('submit', function () {
            document.querySelectorAll('#form-proposta-comercial textarea.js-jodit').forEach(textarea => {
                const instance = textarea.jodit;
                if (instance) {
                    textarea.value = instance.value;
                }
            });
        });
    }

});
document.addEventListener('DOMContentLoaded', () => {
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            alert(message);
            return;
        }
        const toast = document.createElement('div');
        const base = 'rounded-lg px-4 py-2 text-sm shadow-lg';
        const colors = type === 'error' ? 'bg-error-500 text-white' : 'bg-emerald-600 text-white';
        toast.className = `${base} ${colors}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // Guard de camadas para modais (mesmo padrão de ordens_servico/_form)
    let pcModalLayerOpenCount = 0;
    const pcLayerTargets = () => ([
        document.querySelector('header.sticky'),
        document.getElementById('sidebar'),
        document.getElementById('sidebar-backdrop'),
    ].filter(Boolean));
    const pcAdjustLayoutLayers = (open) => {
        const targets = pcLayerTargets();
        if (open) {
            pcModalLayerOpenCount += 1;
            if (pcModalLayerOpenCount > 1) return;
            targets.forEach((el) => {
                if (el.dataset.pcModalPrevZ === undefined) {
                    el.dataset.pcModalPrevZ = el.style.zIndex || '';
                }
                el.style.zIndex = '1';
            });
            return;
        }
        pcModalLayerOpenCount = Math.max(0, pcModalLayerOpenCount - 1);
        if (pcModalLayerOpenCount > 0) return;
        targets.forEach((el) => {
            if (el.dataset.pcModalPrevZ !== undefined) {
                el.style.zIndex = el.dataset.pcModalPrevZ;
                delete el.dataset.pcModalPrevZ;
            } else {
                el.style.zIndex = '';
            }
        });
    };
    window.__pcAdjustLayoutLayers = pcAdjustLayoutLayers;

    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    openButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.modalOpen);
            if (target) {
                target.classList.remove('hidden');
                pcAdjustLayoutLayers(true);
                if (btn.dataset.modalOpen === 'propostaClienteModal') {
                    filtrarPorCliente();
                }
            }
        });
    });
    closeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(btn.dataset.modalClose);
            if (target) {
                target.classList.add('hidden');
                pcAdjustLayoutLayers(false);
            }
        });
    });

    const clienteSelect = document.getElementById('cliente_id');
    const unidadeSelect = document.getElementById('cliente_unidade_id');
    const contatoSelect = document.getElementById('contato_id');
    const enderecoSelect = document.getElementById('endereco_id');
    const clienteBusca = document.getElementById('cliente_busca');

    function filtrarPorCliente() {
        if (!clienteSelect) return;
        const clienteId = clienteSelect.value;
        const selectedClienteOption = clienteSelect.options[clienteSelect.selectedIndex] || null;
        const grupo = selectedClienteOption?.dataset?.grupo || '';

        // autoPick:
        // - true (default): se nada selecionado, seleciona automaticamente o primeiro visível
        // - false: mantém em branco (útil para "Unidade", para ficar na matriz por padrão)
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
                if (option.hidden !== nextHidden) {
                    option.hidden = nextHidden;
                }
                if (!nextHidden) {
                    if (!firstVisibleValue) firstVisibleValue = option.value;
                    if (!principalVisibleValue && option.dataset.principal === '1') {
                        principalVisibleValue = option.value;
                    }
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

        updateSelectVisibility(
            unidadeSelect,
            (option) => !!grupo && option.dataset.grupo === grupo,
            false,
            false // não auto-selecionar unidade -> mantém matriz por padrão
        );

        const baseClienteId = unidadeSelect?.value || clienteId;

        updateSelectVisibility(
            contatoSelect,
            (option) => option.dataset.cliente === baseClienteId,
            true
        );

        updateSelectVisibility(
            enderecoSelect,
            (option) => option.dataset.cliente === baseClienteId,
            true
        );
    }

    if (clienteSelect) {
        clienteSelect.addEventListener('change', filtrarPorCliente);
        requestAnimationFrame(() => filtrarPorCliente());
    }
    if (unidadeSelect) {
        unidadeSelect.addEventListener('change', filtrarPorCliente);
    }
    if (clienteBusca && clienteSelect) {
        const syncByValue = (value) => {
            const raw = String(value || '').trim();
            if (!raw) {
                clienteSelect.value = '';
                filtrarPorCliente();
                return;
            }

            // Preferir o datalist (value -> data-id) para evitar problemas de whitespace no <option>
            const dl = document.getElementById('clientes_lista');
            const dlOpt = dl
                ? Array.from(dl.options).find(o => String(o.value || '').trim() === raw)
                : null;
            const idFromDatalist = dlOpt?.dataset?.id || '';

            if (idFromDatalist) {
                clienteSelect.value = idFromDatalist;
            } else {
                const match = Array.from(clienteSelect.options).find(opt => String(opt.textContent || '').trim() === raw);
                clienteSelect.value = match ? match.value : '';
            }
            filtrarPorCliente();
        };
        clienteBusca.addEventListener('change', (e) => syncByValue(e.target.value));
        clienteBusca.addEventListener('blur', (e) => syncByValue(e.target.value));
        // Adicionar suporte a input para match imediato se desejado, mas OS usa change/blur
    }

    // Salvar
    const btnSalvar = document.getElementById('propostaClienteSalvar');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', async () => {
            const clienteId = clienteSelect?.value;
            if (!clienteId) {
                showToast('Selecione um cliente.', 'error');
                return;
            }
            const form = document.getElementById('form-proposta-comercial');
            const url = form?.dataset?.clienteUpdateUrl;
            if (!url) {
                showToast('URL de atualização do cliente não encontrada.', 'error');
                return;
            }
            
            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        cliente_id: clienteId,
                        cliente_unidade_id: unidadeSelect?.value || null,
                        contato_id: contatoSelect?.value || null,
                        endereco_id: enderecoSelect?.value || null,
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Erro ao salvar.');
                }

                const data = await response.json();
                const elNome = document.getElementById('cliente_nome');
                const elEndereco = document.getElementById('cliente_endereco');
                const elEnderecoLink = document.getElementById('cliente_endereco_link');
                const elContatoTelefone = document.getElementById('cliente_contato_telefone');
                const elContatoTelefoneLink = document.getElementById('cliente_contato_telefone_link');
                const elContatoNome = document.getElementById('cliente_contato_nome');
                const elContatoEmail = document.getElementById('cliente_contato_email');
                const elWhatsapp = document.getElementById('cliente_whatsapp');
                const elWhatsappLink = document.getElementById('cliente_whatsapp_link');
                const elResumo = document.getElementById('cliente_contatos_resumo');

                if (elNome) elNome.textContent = data.cliente_nome || '-';
                if (elEndereco) elEndereco.textContent = data.endereco_texto || '-';
                if (elContatoTelefone) elContatoTelefone.textContent = data.contato_telefone || '-';
                if (elContatoNome) elContatoNome.textContent = data.contato_nome || '-';
                if (elContatoEmail) elContatoEmail.textContent = data.contato_email || '-';

                if (elEnderecoLink) {
                    const raw = (data.endereco_texto || '').trim();
                    elEnderecoLink.href = raw && raw !== '-' ? ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(raw)) : '#';
                }
                if (elContatoTelefoneLink) {
                    const digits = String(data.contato_telefone || '').replace(/\D+/g, '');
                    elContatoTelefoneLink.href = digits ? ('tel:' + digits) : '#';
                }

                if (elWhatsapp) {
                    if (data.whatsapp) {
                        elWhatsapp.innerHTML = `
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-4 w-4 text-green-500" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="currentColor" d="M12.04 2C6.52 2 2 6.48 2 12c0 1.94.54 3.83 1.56 5.47L2 22l4.74-1.54A9.96 9.96 0 0 0 12.04 22C17.56 22 22 17.52 22 12S17.56 2 12.04 2zm0 18.2c-1.74 0-3.44-.5-4.9-1.45l-.35-.22-2.8.9.92-2.72-.23-.35A8.18 8.18 0 0 1 3.8 12c0-4.53 3.7-8.2 8.24-8.2 4.54 0 8.16 3.67 8.16 8.2 0 4.53-3.62 8.2-8.16 8.2zm4.6-6.14c-.25-.12-1.47-.72-1.7-.8-.23-.08-.39-.12-.56.12-.17.25-.64.8-.78.96-.14.17-.29.19-.53.07-.25-.12-1.05-.38-2-1.2-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.1-.5.1-.1.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42l-.48-.01c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.9 2.4 1.02 2.56.12.17 1.77 2.7 4.3 3.79.6.26 1.07.42 1.44.54.61.19 1.17.16 1.61.1.49-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.47-.28z"/>
                                </svg>
                                ${data.whatsapp}
                            </span>
                        `;
                    } else {
                        elWhatsapp.textContent = '-';
                    }
                }
                if (elWhatsappLink) {
                    const digits = String(data.whatsapp || '').replace(/\D+/g, '');
                    elWhatsappLink.href = digits ? ('https://wa.me/' + digits) : '#';
                }

                if (elResumo && Array.isArray(data.contatos_resumo)) {
                    const list = data.contatos_resumo.length
                        ? data.contatos_resumo.map(item => {
                            const emailTxt = item.email ? ` • ${item.email}` : '';
                            return `<div>${item.nome || 'Contato'} — ${item.telefone || '-'}${emailTxt}</div>`;
                        }).join('')
                        : '<div>-</div>';

                    elResumo.querySelector('.mt-1')?.remove();
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mt-1 space-y-1 text-xs text-gray-600 dark:text-gray-400';
                    wrapper.innerHTML = list;
                    elResumo.appendChild(wrapper);
                }

                showToast('Cliente atualizado com sucesso.');
                const modal = document.getElementById('propostaClienteModal');
                if (modal) modal.classList.add('hidden');
                pcAdjustLayoutLayers(false);
            } catch (err) {
                showToast(err.message || 'Erro ao salvar dados do cliente.', 'error');
            }
        });
    }
});

function propostaComercialForm() {
    return {
        itens: @json($itensIniciais),
        dataEmissaoStr: @json($dataEmissaoInicial),
        validadeDiasConfig: @json($validadeOrcDiasCfg),
        validadeDiasStr: @json($validadeInicialStr),
        tabelaPrecoId: @json($tabelaPrecoInicialJs),
        totalProdutos: 0,
        totalServicos: 0,
        totalDescontos: 0,
        totalGeral: 0,
        _keyCounter: 1000,
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

        validadeDiasEfetiva() {
            const raw = String(this.validadeDiasStr || '').trim();
            const p = parseInt(raw, 10);
            if (!isNaN(p) && p > 0) return p;
            const c = parseInt(this.validadeDiasConfig, 10) || 0;
            return c > 0 ? c : 0;
        },

        usaValidadeEspecifica() {
            const raw = String(this.validadeDiasStr || '').trim();
            const p = parseInt(raw, 10);
            return !isNaN(p) && p > 0;
        },

        dataLimiteFormatada() {
            const d = this.validadeDiasEfetiva();
            const s = String(this.dataEmissaoStr || '').trim();
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

        textoLinhaValidade() {
            const d = this.validadeDiasEfetiva();
            if (!d) return '';
            const lim = this.dataLimiteFormatada();
            const espec = this.usaValidadeEspecifica();
            const origem = espec ? 'Validade desta proposta' : 'Prazo padrão da empresa (pedidos de venda)';
            if (!lim) {
                return origem + ': ' + d + ' dia(s) corrido(s) a partir da data de emissão.';
            }
            return origem + ': ' + d + ' dia(s). Referência: válido até ' + lim + '.';
        },

        async buscarProdutoPorIdNaTabela(item) {
            if (item.tipo !== 'produto' || !item.produto_id) return null;
            const tid = this.tabelaPrecoSelecionada();
            let url = `{{ route('propostas-comerciais.buscar-produto') }}?produto_id=${encodeURIComponent(item.produto_id)}&tipo=produto`;
            if (tid) url += `&tabela_preco_id=${encodeURIComponent(tid)}`;
            try {
                const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
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
                busca: '', resultados: [],
            });
        },

        removerItem(idx) {
            this.itens.splice(idx, 1);
            this.calcularTotais();
        },

        async buscar(item) {
            if (!item.busca || item.busca.length < 2) { item.resultados = []; return; }
            const tid = this.tabelaPrecoSelecionada();
            let url = `{{ route('propostas-comerciais.buscar-produto') }}?q=${encodeURIComponent(item.busca)}&tipo=${item.tipo}`;
            if (tid) url += `&tabela_preco_id=${encodeURIComponent(tid)}`;
            const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
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
            this.totalProdutos = tp;
            this.totalServicos = ts;
            this.totalDescontos = td;
            this.totalGeral = Math.max(tp + ts - td, 0);
        },
    };
}
</script>
@endpush
