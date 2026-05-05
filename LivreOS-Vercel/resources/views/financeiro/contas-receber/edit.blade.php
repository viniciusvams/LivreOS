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
    $_auditSvc    = app(\App\Services\AuditCancelExcluirService::class);
    $_podeBaixar  = $_auditSvc->canBaixar(auth()->user(), 'conta_receber');
    $_podeEstornar= $_auditSvc->canEstornar(auth()->user(), 'conta_receber');
    $_podeCorrigirDatasBaixa = auth()->user()->is_admin || auth()->user()->hasPermission('financeiro.contas-receber.corrigir_datas_baixa');
    $_podeCancelar= $_auditSvc->canCancel(auth()->user(), 'conta_receber');
    $_podeExcluir = $_auditSvc->canExcluir(auth()->user(), 'conta_receber');
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Conta a Receber</h1>
        <p class="text-gray-600 dark:text-gray-400">Atualize as informações da conta</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
        <p class="font-medium">Corrija os seguintes pontos:</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Formulário -->
    <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <form id="formEditContaReceber" action="{{ route('financeiro.contas-receber.update.post', $conta) }}" method="POST" class="p-6" data-valor-recebido="{{ $conta->valor_recebido }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    @include('partials.cliente-autocomplete-field', [
                        'prefix' => 'conta_receber',
                        'clientes' => $clientes,
                        'selectedId' => old('cliente_id', $conta->cliente_id),
                        'idSuffix' => '_edit',
                        'label' => 'Cliente',
                    ])
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                    <input type="text" name="descricao" value="{{ old('descricao', $conta->descricao) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                    <input type="number" step="0.01" name="valor" value="{{ old('valor', $conta->valor) }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento *</label>
                    <input type="date" name="data_vencimento" value="{{ old('data_vencimento', $conta->data_vencimento->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                    <select name="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo }}" {{ old('forma_pagamento_id', $conta->forma_pagamento_id) == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                    <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($contasBancarias as $contaBancaria)
                            <option value="{{ $contaBancaria->id }}" {{ old('conta_bancaria_id', $conta->conta_bancaria_id) == $contaBancaria->id ? 'selected' : '' }}>{{ $contaBancaria->nome }}</option>
                        @endforeach
                    </select>
                </div>

                @include('financeiro.partials.conta-receber-cartao-bloco', [
                    'prefix' => 'cr_edit',
                    'bandeirasCartao' => $bandeirasCartao,
                    'formasCartaoMeta' => $formasCartaoMeta,
                    'cfg' => [
                        'formaSel' => '#formEditContaReceber select[name="forma_pagamento_id"]',
                        'valorSel' => '#formEditContaReceber input[name="valor"]',
                        'parcelasSel' => null,
                        'totalParcelasFixed' => (int) ($conta->total_parcelas ?? 1),
                        'dataRefSel' => '#formEditContaReceber input[name="data_vencimento"]',
                        'contaBancariaSel' => '#conta_bancaria_id',
                        'defaultAdquirenteId' => old('adquirente_id', $conta->adquirente_id),
                        'defaultBandeira' => old('bandeira', $conta->bandeira ?? 'master'),
                    ],
                ])

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de Contas</label>
                    <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($planoContas as $planoConta)
                            <option value="{{ $planoConta->id }}" {{ old('plano_conta_id', $conta->plano_conta_id) == $planoConta->id ? 'selected' : '' }}>{{ $planoConta->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo</label>
                    <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Nenhum</option>
                        @foreach($centrosCusto ?? [] as $cc)
                            <option value="{{ $cc->id }}" {{ old('centro_custo_id', $conta->centro_custo_id) == $cc->id ? 'selected' : '' }}>{{ $cc->nome }}</option>
                        @endforeach
                    </select>
                </div>

                @include('financeiro.partials.categoria-tags-titulo', [
                    'escopoCategoria' => 'receber',
                    'tagEscopo' => 'conta_receber',
                    'categoriaOpcoes' => $categoriaFinanceiraOpcoes ?? [],
                    'selectedCategoriaId' => old('categoria_financeira_id', $conta->categoria_financeira_id),
                    'selectedTags' => $tagsFormulario ?? collect(),
                ])

                <div class="md:col-span-2 grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros (R$)</label>
                        <input type="number" step="0.01" name="juros" value="{{ old('juros', $conta->juros ?? 0) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Multa (R$)</label>
                        <input type="number" step="0.01" name="multa" value="{{ old('multa', $conta->multa ?? 0) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto (R$)</label>
                        <input type="number" step="0.01" name="desconto" value="{{ old('desconto', $conta->desconto ?? 0) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex.: taxa da forma de pagamento; editável antes de conciliar.</p>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $conta->observacoes) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anexos</label>
                    @if($conta->anexos && $conta->anexos->count() > 0)
                    <ul class="mb-3 space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                        @foreach($conta->anexos as $anexo)
                        <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $anexo->nome_arquivo }}</span>
                            <span class="flex items-center gap-2 shrink-0">
                                @if($anexo->tipo_mime && str_starts_with($anexo->tipo_mime, 'image/'))
                                    <a href="{{ route('financeiro.contas-receber.anexos.file', $anexo) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-400">Visualizar</a>
                                @endif
                                <a href="{{ route('financeiro.contas-receber.anexos.file', $anexo) }}" download="{{ $anexo->nome_arquivo }}" class="text-brand-600 hover:underline dark:text-brand-400">Baixar</a>
                                <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400" data-url="{{ route('financeiro.contas-receber.anexos.destroy.post', [$conta, $anexo]) }}" data-csrf="{{ csrf_token() }}" onclick="excluirAnexoContaReceber(this)">Excluir</button>
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    <input type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione um ou mais arquivos (máx. 10 MB cada).</p>
                </div>
            </div>

            @if(function_exists('do_action'))
            <?php do_action('financeiro.contas-receber.form.extra', $conta); ?>
            @endif
            <div class="mt-6 flex justify-between items-center">
                <div class="flex gap-2">
                    @if($_podeCancelar && $conta->status !== 'cancelado' && $conta->status !== 'pago')
                        <button type="button" onclick="abrirModalCancelar()" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-700 shadow-sm">
                            Cancelar Conta
                        </button>
                    @endif
                    @if($_podeExcluir && ($conta->status !== 'pago' || $conta->valor_recebido == 0))
                        <button type="button" onclick="abrirModalExcluir()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-300">
                            Excluir
                        </button>
                    @endif
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('financeiro.contas-receber.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
                    <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Informações e Baixas -->
    <div class="space-y-6">
        <!-- Resumo -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Resumo</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Valor Original:</span>
                    <span class="font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</span>
                </div>
                @if($conta->juros > 0 || $conta->multa > 0 || $conta->desconto > 0)
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-500">
                    <span>+ Juros:</span>
                    <span>R$ {{ number_format($conta->juros ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-500">
                    <span>+ Multa:</span>
                    <span>R$ {{ number_format($conta->multa ?? 0, 2, ',', '.') }}</span>
                </div>
                @if($conta->desconto > 0)
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-500">
                    <span>- Desconto:</span>
                    <span>R$ {{ number_format($conta->desconto ?? 0, 2, ',', '.') }}</span>
                </div>
                @endif
                <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400 font-medium">Valor Total a Receber:</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor + ($conta->juros ?? 0) + ($conta->multa ?? 0) - ($conta->desconto ?? 0), 2, ',', '.') }}</span>
                    </div>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Recebido:</span>
                    <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($conta->valor_recebido, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Pendente:</span>
                    <span class="font-medium text-orange-600 dark:text-orange-400">R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        @if($conta->status === 'pago') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                        @elseif($conta->status === 'parcial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                        @elseif($conta->status === 'vencido') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @elseif($conta->status === 'cancelado') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @endif">
                        {{ ucfirst($conta->status) }}
                    </span>
                </div>
            </div>
            @if($conta->status !== 'pago' && $conta->status !== 'cancelado')
                <button type="button" onclick="abrirModalBaixa({{ $conta->id }})" class="mt-4 w-full rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">Baixar Conta</button>
                @if($conta->estrutura_tipo === 'normal')
                    <button type="button" onclick="abrirModalDesmembrar()" class="mt-3 w-full rounded bg-indigo-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-600">Desmembrar Conta (N parcelas)</button>
                @else
                    <p class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        Esta conta já está vinculada a uma estrutura ({{ str_replace('_', ' ', $conta->estrutura_tipo) }}) e não pode ser desmembrada novamente.
                    </p>
                @endif
            @endif
        </div>

        @if($conta->estrutura_tipo === 'lote_pai')
        @php
            $totalFilhos = (float) $conta->children->sum('valor');
            $totalFilhosRecebido = (float) $conta->children->sum('valor_recebido');
            $totalFilhosPendente = max(0, $totalFilhos - $totalFilhosRecebido);
        @endphp
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-800 dark:bg-indigo-900/30">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">Detalhe do Lote (Espelho Total)</h3>
                @if((float)$conta->valor_recebido <= 0 && $conta->status !== 'pago')
                <form method="POST" action="{{ route('financeiro.contas-receber.desagrupar', $conta) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Deseja realmente desfazer este agrupamento?')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Desfazer agrupamento</button>
                </form>
                @endif
            </div>
            <div class="mb-3 grid grid-cols-1 gap-2 text-xs text-indigo-700 dark:text-indigo-300 md:grid-cols-4">
                <div>Filhos vinculados: <strong>{{ $conta->children->count() }}</strong></div>
                <div>Total filhos: <strong>R$ {{ number_format($totalFilhos, 2, ',', '.') }}</strong></div>
                <div>Recebido filhos: <strong>R$ {{ number_format($totalFilhosRecebido, 2, ',', '.') }}</strong></div>
                <div>Pendente filhos: <strong>R$ {{ number_format($totalFilhosPendente, 2, ',', '.') }}</strong></div>
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                @foreach($conta->children as $filho)
                    <div class="flex items-center justify-between rounded border border-indigo-200 bg-white px-3 py-2 dark:border-indigo-700 dark:bg-gray-900">
                        <span>#{{ $filho->id }} - {{ $filho->descricao }}</span>
                        <span class="flex items-center gap-2">
                            <span>R$ {{ number_format($filho->valor, 2, ',', '.') }} | {{ ucfirst($filho->status) }}</span>
                            <a href="{{ route('financeiro.contas-receber.edit', $filho) }}" class="rounded border border-indigo-300 px-2 py-0.5 text-[11px] text-indigo-700 hover:bg-indigo-100 dark:border-indigo-600 dark:text-indigo-300 dark:hover:bg-indigo-900/50">Abrir</a>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Histórico de Baixas -->
        @if($conta->baixasTodas && $conta->baixasTodas->count() > 0)
        <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Histórico de Baixas</h3>
            <div class="space-y-3">
                @foreach($conta->baixasTodas as $baixa)
                    <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ $baixa->data_baixa->format('d/m/Y') }}</span>
                            <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($baixa->valor_baixa, 2, ',', '.') }}</span>
                        </div>
                        @if($baixa->juros > 0 || $baixa->multa > 0 || $baixa->desconto > 0)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                @if($baixa->juros > 0) Juros: R$ {{ number_format($baixa->juros, 2, ',', '.') }} @endif
                                @if($baixa->multa > 0) Multa: R$ {{ number_format($baixa->multa, 2, ',', '.') }} @endif
                                @if($baixa->desconto > 0) Desc: R$ {{ number_format($baixa->desconto, 2, ',', '.') }} @endif
                            </div>
                        @endif
                        @if($baixa->estornado)
                            <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                                <strong>Estornado</strong> em {{ $baixa->data_estorno->format('d/m/Y H:i') }}
                                @if($baixa->motivo_estorno)
                                    <br><span class="text-gray-500">Motivo: {{ $baixa->motivo_estorno }}</span>
                                @endif
                            </div>
                        @else
                            @if($_podeEstornar)
                            <button
                                type="button"
                                onclick="abrirModalEstorno({{ $baixa->id }}, this)"
                                data-valor-baixa="{{ (float) $baixa->valor_baixa }}"
                                data-juros="{{ (float) ($baixa->juros ?? 0) }}"
                                data-multa="{{ (float) ($baixa->multa ?? 0) }}"
                                data-desconto="{{ (float) ($baixa->desconto ?? 0) }}"
                                class="mt-2 text-xs text-red-600 hover:text-red-800 dark:text-red-400 underline"
                            >Estornar Baixa</button>
                            @endif
                            @if($_podeCorrigirDatasBaixa && $baixa->movimentacao)
                            <button
                                type="button"
                                onclick="abrirModalCorrigirDatasBaixa(this)"
                                data-url="{{ route('financeiro.contas-receber.corrigir-datas-baixa', [$conta, $baixa]) }}"
                                data-data-mov="{{ $baixa->movimentacao->data_movimentacao->format('Y-m-d') }}"
                                data-conciliado="{{ $baixa->movimentacao->conciliado ? '1' : '0' }}"
                                data-data-conc="{{ $baixa->movimentacao->conciliado && $baixa->movimentacao->data_conciliacao ? $baixa->movimentacao->data_conciliacao->format('Y-m-d') : '' }}"
                                class="mt-2 ml-3 text-xs text-brand-600 hover:text-brand-800 dark:text-brand-400 underline"
                            >Corrigir datas</button>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal de Baixa -->
<div id="modalBaixa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Baixar Conta</h2>
            <button type="button" onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formBaixa" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da Baixa *</label>
                    <input type="number" step="0.01" name="valor_baixa" id="valor_baixa" value="{{ $conta->valor_pendente }}" max="{{ $conta->valor_pendente }}" required oninput="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Máximo: R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Baixa *</label>
                    <input type="date" name="data_baixa" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                    <select name="conta_bancaria_id" id="conta_bancaria_baixa" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($contasBancarias as $contaBancaria)
                            <option value="{{ $contaBancaria->id }}" {{ old('conta_bancaria_id', $conta->conta_bancaria_id) == $contaBancaria->id ? 'selected' : '' }}>{{ $contaBancaria->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="forma_pagamento_id" id="forma_pagamento_baixa" required onchange="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo }}" data-taxa-percentual="{{ $forma->taxa_percentual }}" data-taxa-fixa="{{ $forma->taxa_fixa }}" data-pix-chaves="{{ json_encode($forma->pix_chaves_ativas ?? [], JSON_UNESCAPED_UNICODE) }}" {{ old('forma_pagamento_id', $conta->forma_pagamento_id) == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}@if($forma->taxa_percentual > 0 || $forma->taxa_fixa > 0) (Taxa: {{ number_format($forma->taxa_percentual, 2, ',', '.') }}% + R$ {{ number_format($forma->taxa_fixa, 2, ',', '.') }})@endif</option>
                        @endforeach
                    </select>
                    <div id="taxa_info" class="mt-2 hidden rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        <p class="font-medium">Taxa será descontada automaticamente:</p>
                        <p id="taxa_valor" class="mt-1"></p>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <input type="checkbox" name="ignorar_taxa" id="ignorar_taxa_baixa" value="1" class="mt-1 rounded border-gray-300 dark:border-gray-600" onchange="calcularTaxaBaixa()">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Não descontar taxa da forma de pagamento</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Use quando o valor da parcela já for líquido (ex.: importação MapOS, taxa já deduzida na operadora). A conciliação segue a regra da forma (cartão, etc.).</span>
                        </span>
                    </label>
                </div>
                <div id="pix_chave_wrap_baixa" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Chave PIX de recebimento</label>
                    <select name="pix_chave_id" id="pix_chave_baixa" onchange="calcularTaxaBaixa()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                @include('financeiro.partials.conta-receber-cartao-bloco', [
                    'prefix' => 'cr_baixa_modal',
                    'bandeirasCartao' => $bandeirasCartao,
                    'formasCartaoMeta' => $formasCartaoMeta,
                    'cfg' => [
                        'formaSel' => '#forma_pagamento_baixa',
                        'valorSel' => '#valor_baixa',
                        'parcelasSel' => null,
                        'totalParcelasFixed' => (int) ($conta->total_parcelas ?? 1),
                        'dataRefSel' => '#formBaixa input[name="data_baixa"]',
                        'contaBancariaSel' => '#conta_bancaria_baixa',
                        'defaultAdquirenteId' => old('adquirente_id', $conta->adquirente_id),
                        'defaultBandeira' => old('bandeira', $conta->bandeira ?? 'master'),
                    ],
                ])
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros</label>
                        <input type="number" step="0.01" name="juros" value="{{ old('juros', $juros_sugerido ?? $conta->juros ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Multa</label>
                        <input type="number" step="0.01" name="multa" value="{{ old('multa', $multa_sugerido ?? $conta->multa ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                        <input type="number" step="0.01" name="desconto" value="{{ old('desconto', $conta->desconto ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $conta->observacoes) }}</textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalBaixa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Confirmar Baixa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div id="modalCancelar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar Conta</h2>
            <button type="button" onclick="fecharModalCancelar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('financeiro.contas-receber.cancelar', $conta) }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja cancelar esta conta? Esta ação marcará a conta como cancelada.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do Cancelamento *</label>
                <textarea name="motivo" rows="4" required placeholder="Descreva o motivo do cancelamento..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalCancelar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Fechar</button>
                <button type="submit" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-700 shadow-sm">Confirmar Cancelamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Exclusão (motivo obrigatório) -->
<div id="modalExcluir" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Excluir Conta a Receber</h2>
            <button type="button" onclick="fecharModalExcluir()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formExcluirConta" action="{{ route('financeiro.contas-receber.destroy', $conta) }}" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Exclusão * (mín. 10 caracteres)</label>
                <textarea name="motivo" id="motivoExcluirEdit" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalExcluir()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Exclusão (motivo obrigatório) -->
<div id="modalExcluir" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Excluir Conta a Receber</h2>
            <button type="button" onclick="fecharModalExcluir()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('financeiro.contas-receber.destroy', $conta) }}" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja excluir esta conta? Esta ação não pode ser desfeita.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Exclusão * (mín. 10 caracteres)</label>
                <textarea name="motivo" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalExcluir()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Fechar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Estorno -->
<div id="modalEstorno" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Estornar Baixa</h2>
            <button type="button" onclick="fecharModalEstorno()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="formEstorno" action="{{ route('financeiro.contas-receber.estornar', $conta) }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="baixa_id" id="baixa_id_estorno">
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja estornar esta baixa? Esta ação reverterá o recebimento e atualizará o status da conta.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do Estorno *</label>
                <textarea name="motivo" rows="4" required placeholder="Descreva o motivo do estorno..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Como está sendo estornado? *</label>
                <div class="space-y-2">
                    <label class="flex items-start gap-3">
                        <input type="radio" name="modo_estorno" value="finaliza" required class="mt-1" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Mesma forma que recebeu e finaliza o estorno</span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="radio" name="modo_estorno" value="pagou" class="mt-1" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Marcar como pagou o estorno (informar conta e valores)</span>
                    </label>
                </div>
            </div>

            <div id="estorno_pagou_fields" class="mt-4 hidden">
                <div class="mb-3 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                    <strong>Valor total a ser estornado:</strong>
                    <span id="estorno_total_valor">R$ 0,00</span>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta bancária de saída *</label>
                    <select name="conta_bancaria_id_estorno" id="conta_bancaria_estorno"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($contasBancarias as $contaBancaria)
                            <option value="{{ $contaBancaria->id }}">{{ $contaBancaria->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de pagamento usada para devolver *</label>
                    <select name="forma_pagamento_id_estorno" id="forma_pagamento_estorno"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}">{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acrescimo</label>
                        <input type="number" step="0.01" min="0" name="acrescimo" value="0"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros</label>
                        <input type="number" step="0.01" min="0" name="juros" value="0"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                        <input type="number" step="0.01" min="0" name="desconto" value="0"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalEstorno()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Estorno</button>
            </div>
        </form>
    </div>
</div>

<!-- Corrigir datas da baixa (sem estorno) -->
<div id="modalCorrigirDatasBaixa" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50" onclick="if(event.target===this) fecharModalCorrigirDatasBaixa()">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Corrigir datas da baixa</h2>
            <button type="button" onclick="fecharModalCorrigirDatasBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formCorrigirDatasBaixa" method="POST" class="p-6">
            @csrf
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Ajusta a data do movimento no extrato e a data da baixa; a <strong>data de vencimento</strong> desta conta passa a ser a mesma da data do movimento informada. Não gera estorno nem novas linhas. Se houver taxa vinculada, ela recebe as mesmas datas.</p>
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data do movimento (extrato) *</label>
                <input type="date" name="data_movimentacao" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div id="corrigir_datas_conc_wrap" class="mb-4 hidden">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da conciliação *</label>
                <input type="date" name="data_conciliacao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lançamento está conciliado; informe a data que consta na conferência bancária.</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalCorrigirDatasBaixa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar datas</button>
            </div>
        </form>
    </div>
</div>

<div id="modalDesmembrar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Desmembrar Conta</h2>
            <button type="button" onclick="fecharModalDesmembrar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formDesmembrar" action="{{ route('financeiro.contas-receber.desmembrar', $conta) }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade de parcelas *</label>
                    <input type="number" id="qtdParcelasDesmembrar" value="2" min="2" max="24" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div id="parcelasContainerDesmembrar" class="space-y-2"></div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total pendente atual: R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalDesmembrar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Confirmar desmembramento</button>
            </div>
        </form>
    </div>
</div>

<script>
function excluirAnexoContaReceber(btn) {
    if (!confirm('Excluir este anexo?')) return;
    var url = btn.getAttribute('data-url');
    var csrf = btn.getAttribute('data-csrf');
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';
    var token = document.createElement('input');
    token.name = '_token';
    token.value = csrf;
    token.type = 'hidden';
    form.appendChild(token);
    document.body.appendChild(form);
    form.submit();
}

function abrirModalBaixa(contaId) {
    var formEdit = document.getElementById('formEditContaReceber');
    var formBaixa = document.getElementById('formBaixa');
    if (formEdit && formBaixa) {
        var contaBancariaEdit = formEdit.querySelector('select[name="conta_bancaria_id"]');
        var contaBancariaModal = formBaixa.querySelector('select[name="conta_bancaria_id"]');
        if (contaBancariaEdit && contaBancariaModal) contaBancariaModal.value = contaBancariaEdit.value;

        var formaEdit = formEdit.querySelector('select[name="forma_pagamento_id"]');
        var formaModal = formBaixa.querySelector('select[name="forma_pagamento_id"]');
        if (formaEdit && formaModal) formaModal.value = formaEdit.value;

        var obsEdit = formEdit.querySelector('textarea[name="observacoes"]');
        var obsModal = formBaixa.querySelector('textarea[name="observacoes"]');
        if (obsEdit && obsModal) obsModal.value = obsEdit.value;

        var valorTotal = parseFloat(formEdit.querySelector('input[name="valor"]').value) || 0;
        var valorRecebido = parseFloat(formEdit.getAttribute('data-valor-recebido')) || 0;
        var valorPendente = Math.max(0, valorTotal - valorRecebido);
        var valorBaixaInput = formBaixa.querySelector('input[name="valor_baixa"]');
        if (valorBaixaInput) {
            valorBaixaInput.max = valorPendente;
            valorBaixaInput.value = Math.min(parseFloat(valorBaixaInput.value) || 0, valorPendente) || valorPendente;
        }
    }

    document.getElementById('formBaixa').action = `/financeiro/contas-receber/${contaId}/baixar`;
    const ignTaxa = document.getElementById('ignorar_taxa_baixa');
    if (ignTaxa) ignTaxa.checked = false;
    document.getElementById('modalBaixa').classList.remove('hidden');
    document.getElementById('modalBaixa').classList.add('flex');
    document.body.style.overflow = 'hidden';
    calcularTaxaBaixa();
    const fpBaixa = document.getElementById('forma_pagamento_baixa');
    if (fpBaixa) fpBaixa.dispatchEvent(new Event('change'));
}

function fecharModalBaixa() {
    document.getElementById('modalBaixa').classList.add('hidden');
    document.getElementById('modalBaixa').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalDesmembrar() {
    const modal = document.getElementById('modalDesmembrar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    gerarParcelasDesmembrar();
}

function fecharModalDesmembrar() {
    const modal = document.getElementById('modalDesmembrar');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function gerarParcelasDesmembrar() {
    const qtd = Math.max(2, parseInt(document.getElementById('qtdParcelasDesmembrar').value || '2', 10));
    const total = {{ (float) $conta->valor_pendente }};
    const container = document.getElementById('parcelasContainerDesmembrar');
    container.innerHTML = '';

    const base = Math.floor((total / qtd) * 100) / 100;
    let acumulado = 0;
    for (let i = 0; i < qtd; i++) {
        let valor = (i === qtd - 1) ? (total - acumulado) : base;
        acumulado += valor;
        const dataPadrao = new Date();
        dataPadrao.setMonth(dataPadrao.getMonth() + i);
        const yyyy = dataPadrao.getFullYear();
        const mm = String(dataPadrao.getMonth() + 1).padStart(2, '0');
        const dd = String(dataPadrao.getDate()).padStart(2, '0');
        const data = `${yyyy}-${mm}-${dd}`;

        const linha = document.createElement('div');
        linha.className = 'grid grid-cols-12 gap-2';
        linha.innerHTML = `
            <input type="hidden" name="parcelas[${i}][descricao]" value="">
            <input type="number" step="0.01" min="0.01" name="parcelas[${i}][valor]" value="${valor.toFixed(2)}" class="col-span-5 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
            <input type="date" name="parcelas[${i}][data_vencimento]" value="${data}" class="col-span-7 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
        `;
        container.appendChild(linha);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const qtdInput = document.getElementById('qtdParcelasDesmembrar');
    if (qtdInput) qtdInput.addEventListener('input', gerarParcelasDesmembrar);
});

function abrirModalEstorno(baixaId, btnEl) {
    document.getElementById('baixa_id_estorno').value = baixaId;
    // Força o usuário a escolher o modo do estorno
    document.querySelectorAll('input[name="modo_estorno"]').forEach(r => { r.checked = false; });
    const pagouFields = document.getElementById('estorno_pagou_fields');
    if (pagouFields) pagouFields.classList.add('hidden');

    // Guarda os valores base da baixa para calcular o total
    window._estornoBase = {
        valorBaixa: btnEl?.dataset?.valorBaixa ? parseFloat(btnEl.dataset.valorBaixa) : 0,
        juros: btnEl?.dataset?.juros ? parseFloat(btnEl.dataset.juros) : 0,
        multa: btnEl?.dataset?.multa ? parseFloat(btnEl.dataset.multa) : 0,
        desconto: btnEl?.dataset?.desconto ? parseFloat(btnEl.dataset.desconto) : 0,
    };

    // Reseta campos extras
    const acrescimoInput = document.querySelector('#formEstorno input[name="acrescimo"]');
    const jurosInput = document.querySelector('#formEstorno input[name="juros"]');
    const descontoInput = document.querySelector('#formEstorno input[name="desconto"]');
    if (acrescimoInput) acrescimoInput.value = 0;
    if (jurosInput) jurosInput.value = 0;
    if (descontoInput) descontoInput.value = 0;

    document.getElementById('modalEstorno').classList.remove('hidden');
    document.getElementById('modalEstorno').classList.add('flex');
    document.body.style.overflow = 'hidden';

    atualizarTotalEstorno();
}

function fecharModalEstorno() {
    document.getElementById('modalEstorno').classList.add('hidden');
    document.getElementById('modalEstorno').classList.remove('flex');
    document.body.style.overflow = '';
    // Mantém o input hidden no DOM para não quebrar o próximo abrirModalEstorno()
    const input = document.querySelector('#formEstorno input[name="baixa_id"]');
    if (input) input.value = '';
    const motivo = document.querySelector('#formEstorno textarea[name="motivo"]');
    if (motivo) motivo.value = '';
    atualizarCamposEstorno();
}

function abrirModalCorrigirDatasBaixa(btn) {
    const form = document.getElementById('formCorrigirDatasBaixa');
    if (!form || !btn) return;
    form.setAttribute('action', btn.getAttribute('data-url') || '');
    const dm = form.querySelector('[name="data_movimentacao"]');
    const dc = form.querySelector('[name="data_conciliacao"]');
    const wrap = document.getElementById('corrigir_datas_conc_wrap');
    if (dm) dm.value = btn.getAttribute('data-data-mov') || '';
    const conc = btn.getAttribute('data-conciliado') === '1';
    if (wrap) wrap.classList.toggle('hidden', !conc);
    if (dc) {
        dc.required = conc;
        const pref = btn.getAttribute('data-data-conc');
        dc.value = (pref && pref.length) ? pref : (dm ? dm.value : '');
    }
    const modal = document.getElementById('modalCorrigirDatasBaixa');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    document.body.style.overflow = 'hidden';
}

function fecharModalCorrigirDatasBaixa() {
    const modal = document.getElementById('modalCorrigirDatasBaixa');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.style.overflow = '';
}

function atualizarCamposEstorno() {
    const selected = document.querySelector('input[name="modo_estorno"]:checked')?.value;
    const pagouFields = document.getElementById('estorno_pagou_fields');
    if (!pagouFields) return;

    const contaSelect = document.getElementById('conta_bancaria_estorno');
    const formaSelect = document.getElementById('forma_pagamento_estorno');
    if (selected === 'pagou') {
        pagouFields.classList.remove('hidden');
        if (contaSelect) contaSelect.required = true;
        if (formaSelect) formaSelect.required = true;
    } else {
        pagouFields.classList.add('hidden');
        if (contaSelect) contaSelect.required = false;
        if (formaSelect) formaSelect.required = false;
    }

    atualizarTotalEstorno();
}

// Mantém consistência quando o usuário alterna a opção dentro do modal
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="modo_estorno"]').forEach(radio => {
        radio.addEventListener('change', atualizarCamposEstorno);
    });
    atualizarCamposEstorno();
});

function formatarMoedaBR(valor) {
    const num = Number(valor);
    if (Number.isNaN(num)) return 'R$ 0,00';
    return 'R$ ' + num.toFixed(2).replace('.', ',');
}

function atualizarTotalEstorno() {
    const selected = document.querySelector('input[name="modo_estorno"]:checked')?.value;
    const span = document.getElementById('estorno_total_valor');
    if (!span) return;

    const base = window._estornoBase ?? { valorBaixa: 0, juros: 0, multa: 0, desconto: 0 };
    const baseTotal = (parseFloat(base.valorBaixa) || 0)
        + (parseFloat(base.juros) || 0)
        + (parseFloat(base.multa) || 0)
        - (parseFloat(base.desconto) || 0);

    // O total é baseTotal + extras (quando modo pagou)
    let total = baseTotal;
    if (selected === 'pagou') {
        const acrescimoInput = document.querySelector('#formEstorno input[name="acrescimo"]');
        const jurosInput = document.querySelector('#formEstorno input[name="juros"]');
        const descontoInput = document.querySelector('#formEstorno input[name="desconto"]');

        const extraAcrescimo = acrescimoInput ? parseFloat(acrescimoInput.value) || 0 : 0;
        const extraJuros = jurosInput ? parseFloat(jurosInput.value) || 0 : 0;
        const extraDesconto = descontoInput ? parseFloat(descontoInput.value) || 0 : 0;

        total = baseTotal + extraJuros + extraAcrescimo - extraDesconto;
    }

    span.textContent = formatarMoedaBR(total);
}

document.addEventListener('DOMContentLoaded', () => {
    const acrescimoInput = document.querySelector('#formEstorno input[name="acrescimo"]');
    const jurosInput = document.querySelector('#formEstorno input[name="juros"]');
    const descontoInput = document.querySelector('#formEstorno input[name="desconto"]');
    if (acrescimoInput) acrescimoInput.addEventListener('input', atualizarTotalEstorno);
    if (jurosInput) jurosInput.addEventListener('input', atualizarTotalEstorno);
    if (descontoInput) descontoInput.addEventListener('input', atualizarTotalEstorno);
});

function abrirModalCancelar() {
    document.getElementById('modalCancelar').classList.remove('hidden');
    document.getElementById('modalCancelar').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function abrirModalExcluir() {
    document.getElementById('modalExcluir').classList.remove('hidden');
    document.getElementById('modalExcluir').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalExcluir() {
    document.getElementById('modalExcluir').classList.add('hidden');
    document.getElementById('modalExcluir').classList.remove('flex');
    document.body.style.overflow = '';
}

function fecharModalCancelar() {
    document.getElementById('modalCancelar').classList.add('hidden');
    document.getElementById('modalCancelar').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalExcluir() {
    document.getElementById('modalExcluir').classList.remove('hidden');
    document.getElementById('modalExcluir').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalExcluir() {
    document.getElementById('modalExcluir').classList.add('hidden');
    document.getElementById('modalExcluir').classList.remove('flex');
    document.body.style.overflow = '';
}

function calcularTaxaBaixa() {
    const formaSelect = document.getElementById('forma_pagamento_baixa');
    const valorBaixaInput = document.getElementById('valor_baixa');
    const taxaInfo = document.getElementById('taxa_info');
    const taxaValor = document.getElementById('taxa_valor');
    const ignorarTaxa = document.getElementById('ignorar_taxa_baixa')?.checked;

    let selectedOption = null;
    let tipoFp = '';
    if (formaSelect && formaSelect.value) {
        selectedOption = formaSelect.options[formaSelect.selectedIndex];
        tipoFp = (selectedOption.getAttribute('data-tipo') || '').trim().toLowerCase();
    }

    const pixWrap = document.getElementById('pix_chave_wrap_baixa');
    const pixSelect = document.getElementById('pix_chave_baixa');
    if (pixWrap && pixSelect) {
        if (!formaSelect.value || !selectedOption) {
            pixWrap.classList.add('hidden');
            pixSelect.innerHTML = '<option value="">Selecione...</option>';
        } else {
            pixWrap.classList.toggle('hidden', tipoFp !== 'pix');
            const selectedPixId = pixSelect.value;
            if (tipoFp === 'pix') {
                let chaves = [];
                try { chaves = JSON.parse(selectedOption.getAttribute('data-pix-chaves') || '[]'); } catch (e) { chaves = []; }
                pixSelect.innerHTML = '<option value="">Selecione...</option>';
                chaves.forEach((k) => {
                    const opt = document.createElement('option');
                    opt.value = k.id || '';
                    opt.textContent = `${k.nome || 'Chave PIX'} (${k.chave || ''})`;
                    opt.dataset.contaBancariaId = k.conta_bancaria_id || '';
                    opt.dataset.taxaPercentual = k.taxa_percentual || 0;
                    opt.dataset.taxaFixa = k.taxa_fixa || 0;
                    pixSelect.appendChild(opt);
                });
                if (selectedPixId) pixSelect.value = selectedPixId;
            } else {
                pixSelect.innerHTML = '<option value="">Selecione...</option>';
            }
            const pixOpt = pixSelect.selectedOptions?.[0];
            if (pixOpt?.dataset?.contaBancariaId) {
                const cb = document.getElementById('conta_bancaria_baixa');
                if (cb) cb.value = pixOpt.dataset.contaBancariaId;
            }
        }
    }

    if (!formaSelect.value || !valorBaixaInput.value) {
        taxaInfo.classList.add('hidden');
        return;
    }

    const valorBaixa = parseFloat(valorBaixaInput.value || 0);

    if (ignorarTaxa) {
        taxaValor.innerHTML = `
            <strong>Sem desconto de taxa da forma.</strong> Entrada no caixa: R$ ${valorBaixa.toFixed(2).replace('.', ',')}
            <span class="mt-1 block text-xs">(Valor tratado como líquido — ex. importação MapOS.)</span>
        `;
        taxaInfo.classList.remove('hidden');
        return;
    }
    if (tipoFp === 'cartao_credito' || tipoFp === 'cartao_debito') {
        taxaValor.innerHTML = `
            <strong>Cartão:</strong> a taxa descontada na baixa <strong>não</strong> é a porcentagem genérica da forma; ela vem do <strong>adquirente</strong>, <strong>bandeira</strong> e <strong>parcelas</strong> da conta. Veja a <strong>prévia</strong> no bloco &quot;Cartão&quot; abaixo.
        `;
        taxaInfo.classList.remove('hidden');
        return;
    }

    let taxaPercentual = parseFloat(selectedOption.getAttribute('data-taxa-percentual') || 0);
    let taxaFixa = parseFloat(selectedOption.getAttribute('data-taxa-fixa') || 0);
    if (tipoFp === 'pix' && pixSelect && pixSelect.value) {
        const p = pixSelect.selectedOptions[0];
        taxaPercentual = parseFloat(p?.dataset?.taxaPercentual || taxaPercentual || 0);
        taxaFixa = parseFloat(p?.dataset?.taxaFixa || taxaFixa || 0);
    }

    if (taxaPercentual > 0 || taxaFixa > 0) {
        const taxa = (valorBaixa * taxaPercentual / 100) + taxaFixa;
        const valorLiquido = valorBaixa - taxa;

        taxaValor.innerHTML = `
            <strong>Valor bruto:</strong> R$ ${valorBaixa.toFixed(2).replace('.', ',')}<br>
            <strong>Taxa:</strong> R$ ${taxa.toFixed(2).replace('.', ',')}<br>
            <strong>Valor líquido a receber:</strong> R$ ${valorLiquido.toFixed(2).replace('.', ',')}
        `;
        taxaInfo.classList.remove('hidden');
    } else {
        taxaInfo.classList.add('hidden');
    }
}
</script>
@endsection
