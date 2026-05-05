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
    $_podeBaixar  = $_auditSvc->canBaixar(auth()->user(), 'conta_pagar');
    $_podeEstornar= $_auditSvc->canEstornar(auth()->user(), 'conta_pagar');
    $_podeCorrigirDatasBaixa = auth()->user()->is_admin || auth()->user()->hasPermission('financeiro.contas-pagar.corrigir_datas_baixa');
    $_podeCancelar= $_auditSvc->canCancel(auth()->user(), 'conta_pagar');
    $_podeExcluir = $_auditSvc->canExcluir(auth()->user(), 'conta_pagar');
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Conta a Pagar</h1>
        <p class="text-gray-600 dark:text-gray-400">Atualize as informações da conta</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Formulário -->
    <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <form action="{{ route('financeiro.contas-pagar.update.post', $conta) }}" method="POST" class="p-6" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor / Cliente</label>
                    <input
                        type="text"
                        id="conta_pagar_fornecedor_busca_edit"
                        list="conta_pagar_fornecedores_datalist_edit"
                        placeholder="Digite nome, razão social, CPF ou CNPJ..."
                        class="mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        autocomplete="off"
                    >
                    <datalist id="conta_pagar_fornecedores_datalist_edit">
                        @foreach($fornecedores as $fornecedor)
                            @php
                                $nomeF = $fornecedor->nome ?? $fornecedor->razao_social ?? ('#' . $fornecedor->id);
                                $docF = $fornecedor->cnpj ?? $fornecedor->cpf ?? '';
                                $rotuloF = trim($nomeF . ($fornecedor->razao_social ? ' - ' . $fornecedor->razao_social : '') . ($docF ? ' - ' . $docF : ''));
                            @endphp
                            <option value="{{ $rotuloF }}"></option>
                        @endforeach
                    </datalist>
                    <select name="fornecedor_id" id="conta_pagar_fornecedor_id_edit" class="hidden">
                        <option value="">Selecione...</option>
                        @foreach($fornecedores as $fornecedor)
                            @php
                                $nomeF = $fornecedor->nome ?? $fornecedor->razao_social ?? ('#' . $fornecedor->id);
                                $docF = $fornecedor->cnpj ?? $fornecedor->cpf ?? '';
                                $rotuloF = trim($nomeF . ($fornecedor->razao_social ? ' - ' . $fornecedor->razao_social : '') . ($docF ? ' - ' . $docF : ''));
                                $buscaF = strtolower(trim(
                                    ($fornecedor->nome ?? '') . ' ' .
                                    ($fornecedor->razao_social ?? '') . ' ' .
                                    ($fornecedor->cnpj ?? '') . ' ' .
                                    ($fornecedor->cpf ?? '')
                                ));
                            @endphp
                            <option value="{{ $fornecedor->id }}" data-label="{{ $rotuloF }}" data-search="{{ $buscaF }}" {{ old('fornecedor_id', $conta->fornecedor_id) == $fornecedor->id ? 'selected' : '' }}>{{ $rotuloF }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                    <input type="text" name="descricao" value="{{ old('descricao', $conta->descricao) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do Documento</label>
                    <input type="text" name="numero_documento" value="{{ old('numero_documento', $conta->numero_documento) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                    <select name="tipo" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="operacional" {{ old('tipo', $conta->tipo) === 'operacional' ? 'selected' : '' }}>Operacional</option>
                        <option value="insumo" {{ old('tipo', $conta->tipo) === 'insumo' ? 'selected' : '' }}>Insumo</option>
                        <option value="outro" {{ old('tipo', $conta->tipo) === 'outro' ? 'selected' : '' }}>Outro</option>
                    </select>
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
                            <option value="{{ $forma->id }}" {{ old('forma_pagamento_id', $conta->forma_pagamento_id) == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                    <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($contasBancarias as $contaBancaria)
                            <option value="{{ $contaBancaria->id }}" {{ old('conta_bancaria_id', $conta->conta_bancaria_id) == $contaBancaria->id ? 'selected' : '' }}>{{ $contaBancaria->nome }}</option>
                        @endforeach
                    </select>
                </div>

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
                    'escopoCategoria' => 'pagar',
                    'tagEscopo' => 'conta_pagar',
                    'categoriaOpcoes' => $categoriaFinanceiraOpcoes ?? [],
                    'selectedCategoriaId' => old('categoria_financeira_id', $conta->categoria_financeira_id),
                    'selectedTags' => $tagsFormulario ?? collect(),
                ])

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
                                    <a href="{{ route('financeiro.contas-pagar.anexos.file', $anexo) }}" target="_blank" class="text-brand-600 hover:underline dark:text-brand-400">Visualizar</a>
                                @endif
                                <a href="{{ route('financeiro.contas-pagar.anexos.file', $anexo) }}" download="{{ $anexo->nome_arquivo }}" class="text-brand-600 hover:underline dark:text-brand-400">Baixar</a>
                                <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400" data-url="{{ route('financeiro.contas-pagar.anexos.destroy.post', [$conta, $anexo]) }}" data-csrf="{{ csrf_token() }}" onclick="excluirAnexoContaPagar(this)">Excluir</button>
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
            <?php do_action('financeiro.contas-pagar.form.extra', $conta); ?>
            @endif
            <div class="mt-6 flex justify-between items-center">
                <div class="flex gap-2" id="cancelar">
                    @if($_podeCancelar && $conta->status !== 'cancelado' && $conta->status !== 'pago')
                        <button type="button" onclick="abrirModalCancelar()" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-700 shadow-sm">
                            Cancelar Conta
                        </button>
                    @endif
                    @if($_podeExcluir && ($conta->status !== 'pago' || $conta->valor_pago == 0))
                        <button type="button" onclick="abrirModalExcluir()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900 dark:text-red-300">
                            Excluir
                        </button>
                    @endif
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('financeiro.contas-pagar.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
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
                    <span class="text-gray-600 dark:text-gray-400">Valor Total:</span>
                    <span class="font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($conta->valor, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Pago:</span>
                    <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($conta->valor_pago, 2, ',', '.') }}</span>
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
                        @elseif($conta->status === 'cancelado') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @elseif($conta->esta_vencido) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                        @endif">
                        {{ $conta->esta_vencido && $conta->status !== 'cancelado' ? 'Vencido' : ucfirst($conta->status) }}
                    </span>
                </div>
            </div>
            @if($conta->status !== 'pago' && $conta->status !== 'cancelado')
                <button type="button" onclick="abrirModalBaixa({{ $conta->id }})" class="mt-4 w-full rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">Baixar Conta</button>
                @if($conta->estrutura_tipo === 'normal')
                    <button type="button" onclick="abrirModalDesmembrarPagar()" class="mt-3 w-full rounded bg-indigo-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-600">Desmembrar Conta (N parcelas)</button>
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
            $totalFilhosPago = (float) $conta->children->sum('valor_pago');
            $totalFilhosPendente = max(0, $totalFilhos - $totalFilhosPago);
        @endphp
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-800 dark:bg-indigo-900/30">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">Detalhe do Lote (Espelho Total)</h3>
                @if((float)$conta->valor_pago <= 0 && $conta->status !== 'pago')
                <form method="POST" action="{{ route('financeiro.contas-pagar.desagrupar', $conta) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Deseja realmente desfazer este agrupamento?')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Desfazer agrupamento</button>
                </form>
                @endif
            </div>
            <div class="mb-3 grid grid-cols-1 gap-2 text-xs text-indigo-700 dark:text-indigo-300 md:grid-cols-4">
                <div>Filhos vinculados: <strong>{{ $conta->children->count() }}</strong></div>
                <div>Total filhos: <strong>R$ {{ number_format($totalFilhos, 2, ',', '.') }}</strong></div>
                <div>Pago filhos: <strong>R$ {{ number_format($totalFilhosPago, 2, ',', '.') }}</strong></div>
                <div>Pendente filhos: <strong>R$ {{ number_format($totalFilhosPendente, 2, ',', '.') }}</strong></div>
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                @foreach($conta->children as $filho)
                    <div class="flex items-center justify-between rounded border border-indigo-200 bg-white px-3 py-2 dark:border-indigo-700 dark:bg-gray-900">
                        <span>#{{ $filho->id }} - {{ $filho->descricao }}</span>
                        <span class="flex items-center gap-2">
                            <span>R$ {{ number_format($filho->valor, 2, ',', '.') }} | {{ ucfirst($filho->status) }}</span>
                            <a href="{{ route('financeiro.contas-pagar.edit', $filho) }}" class="rounded border border-indigo-300 px-2 py-0.5 text-[11px] text-indigo-700 hover:bg-indigo-100 dark:border-indigo-600 dark:text-indigo-300 dark:hover:bg-indigo-900/50">Abrir</a>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Histórico de Baixas -->
        @if($conta->baixas->count() > 0)
        <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Histórico de Baixas</h3>
            <div class="space-y-3">
                @foreach($conta->baixas as $baixa)
                    <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ $baixa->data_baixa->format('d/m/Y') }}</span>
                            <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($baixa->valor_baixa, 2, ',', '.') }}</span>
                        </div>
                        @if($baixa->movimentacao_id)
                            <div class="mt-1 text-xs">
                                <a href="{{ url('/financeiro/movimentacoes?' . http_build_query(['data_inicio' => $baixa->data_baixa->format('Y-m-d'), 'data_fim' => $baixa->data_baixa->format('Y-m-d'), 'origem' => 'conta_pagar'])) }}" class="text-blue-600 hover:underline dark:text-blue-400">Ver na Movimentação</a>
                            </div>
                        @endif
                        @if($baixa->juros > 0 || $baixa->multa > 0 || $baixa->desconto > 0)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                @if($baixa->juros > 0) Juros: R$ {{ number_format($baixa->juros, 2, ',', '.') }} @endif
                                @if($baixa->multa > 0) Multa: R$ {{ number_format($baixa->multa, 2, ',', '.') }} @endif
                                @if($baixa->desconto > 0) Desc: R$ {{ number_format($baixa->desconto, 2, ',', '.') }} @endif
                            </div>
                        @endif
                        @if($baixa->estornado)
                            <div class="mt-1 text-xs text-red-600 dark:text-red-400">Estornado em {{ $baixa->data_estorno->format('d/m/Y') }}</div>
                        @else
                            @if($_podeEstornar)
                            <button type="button" onclick="abrirModalEstorno({{ $baixa->id }})" class="mt-2 text-xs text-red-600 hover:text-red-800 dark:text-red-400 underline">Estornar Baixa</button>
                            @endif
                            @if($_podeCorrigirDatasBaixa && $baixa->movimentacao)
                            <button
                                type="button"
                                onclick="abrirModalCorrigirDatasBaixaPagar(this)"
                                data-url="{{ route('financeiro.contas-pagar.corrigir-datas-baixa', [$conta, $baixa]) }}"
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
                    <input type="number" step="0.01" name="valor_baixa" id="valor_baixa" value="{{ $conta->valor_pendente }}" max="{{ $conta->valor_pendente }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Máximo: R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Baixa *</label>
                    <input type="date" name="data_baixa" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                    <select name="conta_bancaria_id" id="modal_conta_bancaria_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($contasBancarias as $contaBancaria)
                            <option value="{{ $contaBancaria->id }}" {{ old('conta_bancaria_id', $conta->conta_bancaria_id) == $contaBancaria->id ? 'selected' : '' }}>{{ $contaBancaria->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento *</label>
                    <select name="forma_pagamento_id" id="modal_forma_pagamento_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        @foreach($formasPagamento as $forma)
                            <option value="{{ $forma->id }}" {{ old('forma_pagamento_id', $conta->forma_pagamento_id) == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Juros</label>
                        <input type="number" step="0.01" name="juros" value="{{ old('juros', $juros_sugerido ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Multa</label>
                        <input type="number" step="0.01" name="multa" value="{{ old('multa', $multa_sugerido ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desconto</label>
                        <input type="number" step="0.01" name="desconto" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalBaixa()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Confirmar Baixa</button>
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
        <form id="formEstorno" method="POST" action="{{ route('financeiro.contas-pagar.estornar', $conta) }}" class="p-6">
            @csrf
            <input type="hidden" name="baixa_id" id="estorno_baixa_id" value="">
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja estornar esta baixa? Esta ação não pode ser desfeita.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do Estorno *</label>
                <textarea name="motivo" rows="4" required placeholder="Descreva o motivo do estorno..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalEstorno()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar Estorno</button>
            </div>
        </form>
    </div>
</div>

<!-- Corrigir datas da baixa (sem estorno) -->
<div id="modalCorrigirDatasBaixaPagar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50" onclick="if(event.target===this) fecharModalCorrigirDatasBaixaPagar()">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Corrigir datas da baixa</h2>
            <button type="button" onclick="fecharModalCorrigirDatasBaixaPagar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formCorrigirDatasBaixaPagar" method="POST" class="p-6">
            @csrf
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Ajusta a data do movimento no extrato e a data da baixa; a <strong>data de vencimento</strong> desta conta passa a ser a mesma da data do movimento informada. Não gera estorno nem novas linhas.</p>
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data do movimento (extrato) *</label>
                <input type="date" name="data_movimentacao" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div id="corrigir_datas_conc_wrap_pagar" class="mb-4 hidden">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da conciliação *</label>
                <input type="date" name="data_conciliacao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lançamento está conciliado; informe a data que consta na conferência bancária.</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalCorrigirDatasBaixaPagar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar datas</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar Conta -->
<div id="modalCancelar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar Conta a Pagar</h2>
            <button type="button" onclick="fecharModalCancelar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('financeiro.contas-pagar.cancelar', $conta) }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Informe o motivo do cancelamento (mín. 10 caracteres).</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo *</label>
                <textarea name="motivo" id="motivoCancelar" rows="4" required minlength="10" placeholder="Descreva o motivo do cancelamento..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalCancelar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Fechar</button>
                <button type="submit" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">Confirmar cancelamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Excluir Conta -->
<div id="modalExcluir" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Excluir Conta a Pagar</h2>
            <button type="button" onclick="fecharModalExcluir()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('financeiro.contas-pagar.destroy', $conta) }}" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Tem certeza que deseja excluir esta conta a pagar? Informe o motivo (mín. 10 caracteres).</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo *</label>
                <textarea name="motivo" id="motivoExcluirEdit" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalExcluir()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Fechar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar exclusão</button>
            </div>
        </form>
    </div>
</div>

<div id="modalDesmembrarPagar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Desmembrar Conta</h2>
            <button type="button" onclick="fecharModalDesmembrarPagar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('financeiro.contas-pagar.desmembrar', $conta) }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade de parcelas *</label>
                    <input type="number" id="qtdParcelasDesmembrarPagar" value="2" min="2" max="24" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div id="parcelasContainerDesmembrarPagar" class="space-y-2"></div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total pendente atual: R$ {{ number_format($conta->valor_pendente, 2, ',', '.') }}</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="fecharModalDesmembrarPagar()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Confirmar desmembramento</button>
            </div>
        </form>
    </div>
</div>

<script>
function excluirAnexoContaPagar(btn) {
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
    document.getElementById('formBaixa').action = `/financeiro/contas-pagar/${contaId}/baixar`;
    document.getElementById('modalBaixa').classList.remove('hidden');
    document.getElementById('modalBaixa').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalBaixa() {
    document.getElementById('modalBaixa').classList.add('hidden');
    document.getElementById('modalBaixa').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalDesmembrarPagar() {
    const modal = document.getElementById('modalDesmembrarPagar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    gerarParcelasDesmembrarPagar();
}

function fecharModalDesmembrarPagar() {
    const modal = document.getElementById('modalDesmembrarPagar');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function gerarParcelasDesmembrarPagar() {
    const qtd = Math.max(2, parseInt(document.getElementById('qtdParcelasDesmembrarPagar').value || '2', 10));
    const total = {{ (float) $conta->valor_pendente }};
    const container = document.getElementById('parcelasContainerDesmembrarPagar');
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
    const fornecedorSelect = document.getElementById('conta_pagar_fornecedor_id_edit');
    const fornecedorBusca = document.getElementById('conta_pagar_fornecedor_busca_edit');
    if (fornecedorSelect && fornecedorBusca) {
        const labelToId = {};
        Array.from(fornecedorSelect.options).forEach(opt => {
            if (!opt.value) return;
            const label = (opt.dataset.label || opt.textContent || '').trim();
            if (label) labelToId[label] = opt.value;
        });

        const syncBuscaFromSelect = () => {
            const sel = fornecedorSelect.selectedOptions && fornecedorSelect.selectedOptions[0];
            if (sel && sel.value) fornecedorBusca.value = sel.dataset.label || sel.textContent || '';
        };

        const filtrar = () => {
            const q = (fornecedorBusca.value || '').toLowerCase().trim();
            Array.from(fornecedorSelect.options).forEach(opt => {
                if (!opt.value) return;
                const hay = (opt.dataset.search || '').toLowerCase();
                opt.hidden = q !== '' && !hay.includes(q);
            });
        };

        const syncSelectFromBusca = () => {
            const typed = fornecedorBusca.value.trim();
            const id = labelToId[typed];
            if (id) fornecedorSelect.value = id;
        };

        fornecedorBusca.addEventListener('input', () => {
            filtrar();
            syncSelectFromBusca();
        });
        fornecedorBusca.addEventListener('change', syncSelectFromBusca);
        fornecedorSelect.addEventListener('change', syncBuscaFromSelect);
        syncBuscaFromSelect();
    }

    const qtdInput = document.getElementById('qtdParcelasDesmembrarPagar');
    if (qtdInput) qtdInput.addEventListener('input', gerarParcelasDesmembrarPagar);
});

function abrirModalEstorno(baixaId) {
    document.getElementById('estorno_baixa_id').value = baixaId;
    document.getElementById('modalEstorno').classList.remove('hidden');
    document.getElementById('modalEstorno').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharModalEstorno() {
    document.getElementById('modalEstorno').classList.add('hidden');
    document.getElementById('modalEstorno').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalCorrigirDatasBaixaPagar(btn) {
    const form = document.getElementById('formCorrigirDatasBaixaPagar');
    if (!form || !btn) return;
    form.setAttribute('action', btn.getAttribute('data-url') || '');
    const dm = form.querySelector('[name="data_movimentacao"]');
    const dc = form.querySelector('[name="data_conciliacao"]');
    const wrap = document.getElementById('corrigir_datas_conc_wrap_pagar');
    if (dm) dm.value = btn.getAttribute('data-data-mov') || '';
    const conc = btn.getAttribute('data-conciliado') === '1';
    if (wrap) wrap.classList.toggle('hidden', !conc);
    if (dc) {
        dc.required = conc;
        const pref = btn.getAttribute('data-data-conc');
        dc.value = (pref && pref.length) ? pref : (dm ? dm.value : '');
    }
    const modal = document.getElementById('modalCorrigirDatasBaixaPagar');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    document.body.style.overflow = 'hidden';
}

function fecharModalCorrigirDatasBaixaPagar() {
    const modal = document.getElementById('modalCorrigirDatasBaixaPagar');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.style.overflow = '';
}

function abrirModalCancelar() {
    document.getElementById('motivoCancelar').value = '';
    document.getElementById('modalCancelar').classList.remove('hidden');
    document.getElementById('modalCancelar').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function fecharModalCancelar() {
    document.getElementById('modalCancelar').classList.add('hidden');
    document.getElementById('modalCancelar').classList.remove('flex');
    document.body.style.overflow = '';
}

function abrirModalExcluir() {
    document.getElementById('motivoExcluirEdit').value = '';
    document.getElementById('modalExcluir').classList.remove('hidden');
    document.getElementById('modalExcluir').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function fecharModalExcluir() {
    document.getElementById('modalExcluir').classList.add('hidden');
    document.getElementById('modalExcluir').classList.remove('flex');
    document.body.style.overflow = '';
}
</script>
@endsection
