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
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova OS</h1>
    <p class="text-gray-600 dark:text-gray-400">Abertura rápida em poucos passos</p>
</div>

<div class="mb-6 flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
    <span class="wizard-step-label font-medium text-brand-600" data-step-label="1">1. Cliente</span>
    <span>→</span>
    <span class="wizard-step-label" data-step-label="2">2. Equipamento</span>
    <span>→</span>
    <span class="wizard-step-label" data-step-label="3">3. Descrição</span>
    <span>→</span>
    <span class="wizard-step-label" data-step-label="4">4. Confirmar</span>
</div>

<form action="{{ route('ordens-servico.store') }}" method="POST" id="osWizardForm" enctype="multipart/form-data">
    @csrf
    <div class="wizard-step" data-step="1">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Selecionar cliente</h2>
                <button type="button" id="openClienteModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Novo cliente</button>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-error-500">*</span></label>
                <input
                    type="text"
                    id="wizard_cliente_busca"
                    list="wizard_clientes_datalist"
                    placeholder="Digite nome, razão social, CPF ou CNPJ..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    autocomplete="off"
                >
                <datalist id="wizard_clientes_datalist">
                    @foreach($clientes as $cliente)
                        @php
                            $docCli = $cliente->tipo_pessoa === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                            $rotuloCli = trim($cliente->nome . ($cliente->razao_social ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                        @endphp
                        <option value="{{ $rotuloCli }}"></option>
                    @endforeach
                </datalist>
                @php
                    $clienteIdPrefill = null;
                    if (isset($ordemDeRef)) {
                        $clienteIdPrefill = ($ordemDeRef->equipamento_id && $ordemDeRef->equipamento)
                            ? $ordemDeRef->equipamento->cliente_id
                            : $ordemDeRef->cliente_id;
                    }
                @endphp
                <select name="cliente_id" id="wizard_cliente_id" required class="hidden">
                    <option value="">Selecione...</option>
                    @foreach($clientes as $cliente)
                        @php
                            $docCli = $cliente->tipo_pessoa === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                            $rotuloCli = trim($cliente->nome . ($cliente->razao_social ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                            $buscaCli = strtolower(trim(
                                $cliente->nome . ' ' .
                                ($cliente->razao_social ?? '') . ' ' .
                                ($cliente->cpf ?? '') . ' ' .
                                ($cliente->cnpj ?? '')
                            ));
                        @endphp
                        <option value="{{ $cliente->id }}" data-grupo="{{ $cliente->grupo_economico_id }}" data-label="{{ $rotuloCli }}" data-search="{{ $buscaCli }}" {{ ($clienteIdPrefill && $clienteIdPrefill == $cliente->id) ? 'selected' : '' }}>{{ $rotuloCli }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="wizard-step hidden" data-step="2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Equipamento</h2>
                <button type="button" id="openEquipamentoModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Novo equipamento</button>
            </div>
            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Mostramos os equipamentos cadastrados do cliente. Se não houver, cadastre um novo.</p>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento cadastrado</label>
                <select name="equipamento_id" id="wizard_equipamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($equipamentos as $equipamento)
                        @php
                            $label = trim(($equipamento->marca ?? '') . ' ' . ($equipamento->modelo ?? '') . ' ' . ($equipamento->numero_serie ?? '')) ?: 'Equipamento #' . $equipamento->id;
                            $selected = isset($ordemDeRef) && $ordemDeRef->equipamento_id == $equipamento->id;
                        @endphp
                        <option value="{{ $equipamento->id }}" data-cliente="{{ $equipamento->cliente_id }}" {{ $selected ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @php
                $equipNomeInicial = '';
                if (isset($ordemDeRef)) {
                    $equipNomeInicial = trim(($ordemDeRef->equipamento_marca ?? '') . ' ' . ($ordemDeRef->equipamento_modelo ?? '')) ?: ($ordemDeRef->equipamento_nome ?? '');
                    if (!$equipNomeInicial && $ordemDeRef->equipamento_id && $ordemDeRef->equipamento) {
                        $e = $ordemDeRef->equipamento;
                        $equipNomeInicial = trim(($e->marca ?? '') . ' ' . ($e->modelo ?? '')) ?: 'Equipamento #' . $e->id;
                    }
                }
            @endphp
            <input type="hidden" name="equipamento_nome" id="equipamento_nome" value="{{ $equipNomeInicial }}">
            @if(isset($ordemDeRef) && !$ordemDeRef->equipamento_id)
            {{-- Equipamento inline (da OS anterior, sem equipamento cadastrado) --}}
            <input type="hidden" name="equipamento_marca" value="{{ $ordemDeRef->equipamento_marca ?? '' }}">
            <input type="hidden" name="equipamento_modelo" value="{{ $ordemDeRef->equipamento_modelo ?? '' }}">
            <input type="hidden" name="equipamento_numero_serie" value="{{ $ordemDeRef->equipamento_numero_serie ?? '' }}">
            <input type="hidden" name="equipamento_numero_patrimonio" value="{{ $ordemDeRef->equipamento_numero_patrimonio ?? '' }}">
            @endif
        </div>
    </div>

    <div class="wizard-step hidden" data-step="3">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Descrição e abertura</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Relato do cliente</label>
                    <textarea name="relato_cliente" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações internas</label>
                    <textarea name="observacoes_internas" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações para o cliente</label>
                    <textarea name="observacoes_cliente" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acessórios</label>
                    <textarea name="equipamento_acessorios" id="equipamento_acessorios" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ isset($ordemDeRef) ? strip_tags($ordemDeRef->equipamento_acessorios ?? '') : '' }}</textarea>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Desbloqueio</label>
                    <select name="equipamento_unlock_type" id="wizard_unlock_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="pattern">Padrão (desenho)</option>
                        <option value="pin">Senha / PIN</option>
                        <option value="none">Biometria / Sem senha</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <div id="wizard_unlock_pattern_wrap" class="hidden">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desenhe o padrão</label>
                        <div id="wizard_pattern_draw_area" class="flex justify-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900"></div>
                        <input type="hidden" id="wizard_pattern_code_input" value="">
                        <button type="button" id="wizard_clear_pattern_btn" class="mt-2 text-sm text-error-500 hover:text-error-700 dark:text-error-400">Limpar padrão</button>
                    </div>
                    <div id="wizard_unlock_pin_wrap" class="hidden">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Senha de Desbloqueio</label>
                        <input type="text" id="wizard_unlock_code" placeholder="Digite a senha ou PIN" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div id="wizard_unlock_none_wrap" class="hidden">
                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                            Cliente desbloqueia presencialmente ou aparelho sem senha.
                        </div>
                        <input type="hidden" id="wizard_unlock_none_code" value="">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de abertura</label>
                    <input type="datetime-local" name="data_abertura" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-step hidden" data-step="4">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Confirmar abertura</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Revise os dados principais e clique em abrir OS.</p>
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex justify-between">
                    <span>Cliente:</span>
                    <span id="confirm_cliente">-</span>
                </div>
                <div class="mt-2 flex justify-between">
                    <span>Equipamento cadastrado:</span>
                    <span id="confirm_equipamento">-</span>
                </div>
                <div class="mt-2 flex justify-between">
                    <span>Equipamento:</span>
                    <span id="confirm_equipamento_nome">-</span>
                </div>
                <div class="mt-2 flex justify-between">
                    <span>Data:</span>
                    <span id="confirm_data">-</span>
                </div>
            </div>
            @if(\App\Services\NotificacaoOsService::getTipoNotificacao() === \App\Services\NotificacaoOsService::TIPO_EMAIL_MANUAL)
            <div class="mt-4 flex items-center gap-3">
                <input type="hidden" name="enviar_email" value="0">
                <input type="checkbox" name="enviar_email" id="enviar_email" value="1"
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label for="enviar_email" class="text-sm text-gray-700 dark:text-gray-300">Enviar e-mail com a OS em anexo ao cadastrar</label>
            </div>
            @endif
        </div>
    </div>

    <div class="mt-6 flex justify-between gap-3">
        @if(function_exists('do_action'))
        <?php do_action('ordens_servico.form.extra', null); ?>
        @endif
        <button type="button" id="wizardPrev" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</button>
        <div class="flex gap-2">
            <button type="button" id="wizardNext" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Avançar</button>
            <button type="submit" id="wizardSubmit" class="hidden rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Abrir OS</button>
        </div>
    </div>
</form>

<div id="clienteModal" class="fixed inset-0 z-[2147483001] hidden items-start justify-center overflow-y-auto bg-black/40 p-4 pt-6" style="z-index:2147483647 !important;">
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-theme-sm dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between border-b border-gray-200 px-6 pt-6 pb-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Novo cliente (rápido)</h3>
            <button type="button" id="closeClienteModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
        </div>
        <form id="clienteQuickForm" class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto px-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 pb-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                    <input type="text" name="nome" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de pessoa</label>
                    <select name="tipo_pessoa" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="F">Pessoa Física</option>
                        <option value="J">Pessoa Jurídica</option>
                    </select>
                </div>
                <div id="clienteQuickCpfContainer">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                    <input type="text" name="cpf" id="clienteQuickCpf" placeholder="000.000.000-00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div id="clienteQuickCnpjContainer" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ</label>
                    <div class="flex gap-2">
                        <input type="text" name="cnpj" id="clienteQuickCnpj" placeholder="00.000.000/0000-00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <button type="button" id="clienteQuickBuscarCnpj" class="whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Buscar CNPJ</button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato (nome)</label>
                    <input type="text" name="contato_nome" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato (telefone)</label>
                    <input type="text" name="contato_telefone" id="clienteQuickTelefone" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contato (e-mail)</label>
                    <input type="email" name="contato_email" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                    <input type="text" name="cep" id="clienteQuickCep" data-cep placeholder="00000-000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</label>
                    <input type="text" name="logradouro" id="clienteQuickLogradouro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                    <input type="text" name="numero" id="clienteQuickNumero" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                    <input type="text" name="complemento" id="clienteQuickComplemento" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                    <input type="text" name="bairro" id="clienteQuickBairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UF</label>
                    <select name="estado" id="clienteQuickEstado" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione...</option>
                        <option value="AC">AC</option>
                        <option value="AL">AL</option>
                        <option value="AP">AP</option>
                        <option value="AM">AM</option>
                        <option value="BA">BA</option>
                        <option value="CE">CE</option>
                        <option value="DF">DF</option>
                        <option value="ES">ES</option>
                        <option value="GO">GO</option>
                        <option value="MA">MA</option>
                        <option value="MT">MT</option>
                        <option value="MS">MS</option>
                        <option value="MG">MG</option>
                        <option value="PA">PA</option>
                        <option value="PB">PB</option>
                        <option value="PR">PR</option>
                        <option value="PE">PE</option>
                        <option value="PI">PI</option>
                        <option value="RJ">RJ</option>
                        <option value="RN">RN</option>
                        <option value="RS">RS</option>
                        <option value="RO">RO</option>
                        <option value="RR">RR</option>
                        <option value="SC">SC</option>
                        <option value="SP">SP</option>
                        <option value="SE">SE</option>
                        <option value="TO">TO</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                    <select name="cidade" id="clienteQuickCidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Selecione a UF primeiro...</option>
                    </select>
                </div>
            </div>
            </div>
            <div class="mt-2 flex justify-end gap-2 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button type="button" id="cancelClienteModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div id="equipamentoModal" class="fixed inset-0 z-[2147483001] hidden items-start justify-center overflow-y-auto bg-black/40 p-4 pt-6" style="z-index:2147483647 !important;">
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-theme-sm dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between border-b border-gray-200 px-6 pt-6 pb-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Novo equipamento</h3>
            <button type="button" id="closeEquipamentoModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
        </div>
        <form id="equipamentoQuickForm" class="flex min-h-0 flex-1 flex-col">
            <input type="hidden" name="cliente_id" id="equipamento_cliente_id">
            <div class="min-h-0 flex-1 overflow-y-auto px-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 pb-4">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Equipamento <span class="text-error-500">*</span></label>
                    <input type="text" id="equipamento_nome_modal" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                    <input type="text" name="marca" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo</label>
                    <input type="text" name="modelo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número de série</label>
                    <input type="text" name="numero_serie" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número patrimônio</label>
                    <input type="text" name="numero_patrimonio" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Acessórios</label>
                    <textarea name="acessorios" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
            </div>
                </div>
            </div>
            <div class="mt-2 flex justify-end gap-2 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button type="button" id="cancelEquipamentoModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Componente UnlockPattern (inline)
@include('ordens_servico._unlock_pattern_script')

const steps = Array.from(document.querySelectorAll('.wizard-step'));
const labels = Array.from(document.querySelectorAll('[data-step-label]'));
const prevBtn = document.getElementById('wizardPrev');
const nextBtn = document.getElementById('wizardNext');
const submitBtn = document.getElementById('wizardSubmit');
let currentStep = {{ isset($startStep) ? (int) $startStep : 1 }};

function updateWizard() {
    steps.forEach(step => {
        step.classList.toggle('hidden', parseInt(step.dataset.step, 10) !== currentStep);
    });
    labels.forEach(label => {
        const step = parseInt(label.dataset.stepLabel, 10);
        label.classList.toggle('text-brand-600', step === currentStep);
        label.classList.toggle('font-medium', step === currentStep);
    });
    prevBtn.disabled = currentStep === 1;
    nextBtn.classList.toggle('hidden', currentStep === steps.length);
    submitBtn.classList.toggle('hidden', currentStep !== steps.length);
    atualizarResumo();
}

function atualizarResumo() {
    const clienteSelect = document.getElementById('wizard_cliente_id');
    const equipamentoSelect = document.getElementById('wizard_equipamento_id');
    const equipamentoNomeInput = document.getElementById('equipamento_nome');
    const dataAbertura = document.querySelector('input[name="data_abertura"]');
    const clienteLabel = clienteSelect?.selectedOptions[0]?.textContent || '-';
    const equipamentoLabel = equipamentoSelect?.selectedOptions[0]?.textContent || 'Não informado';
    const equipamentoNome = equipamentoNomeInput?.value?.trim() || '-';
    const dataLabel = dataAbertura?.value || '-';
    const confirmCliente = document.getElementById('confirm_cliente');
    const confirmEquipamento = document.getElementById('confirm_equipamento');
    const confirmEquipamentoNome = document.getElementById('confirm_equipamento_nome');
    const confirmData = document.getElementById('confirm_data');
    if (confirmCliente) confirmCliente.textContent = clienteLabel;
    if (confirmEquipamento) confirmEquipamento.textContent = equipamentoLabel;
    if (confirmEquipamentoNome) confirmEquipamentoNome.textContent = equipamentoNome;
    if (confirmData) confirmData.textContent = dataLabel;
}

function filtrarEquipamentos() {
    const clienteSelect = document.getElementById('wizard_cliente_id');
    const equipamentoSelect = document.getElementById('wizard_equipamento_id');
    if (!clienteSelect || !equipamentoSelect) return;
    const clienteId = clienteSelect.value;
    Array.from(equipamentoSelect.options).forEach(option => {
        if (!option.value) return;
        const matchCliente = option.dataset.cliente === clienteId;
        option.hidden = !matchCliente;
    });
    if (equipamentoSelect.selectedOptions[0]?.hidden) {
        equipamentoSelect.value = '';
    }
}

if (prevBtn && nextBtn) {
    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep -= 1;
            updateWizard();
        }
    });
    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length) {
            if (currentStep === 1) {
                const clienteSelect = document.getElementById('wizard_cliente_id');
                if (!clienteSelect?.value) {
                    alert('Selecione um cliente.');
                    return;
                }
                filtrarEquipamentos();
            }
            currentStep += 1;
            updateWizard();
        }
    });
}

const clienteSelect = document.getElementById('wizard_cliente_id');
const clienteBuscaInput = document.getElementById('wizard_cliente_busca');
const equipamentoNomeInput = document.getElementById('equipamento_nome');
let clienteLabelToId = {};
function rebuildClienteLookup() {
    clienteLabelToId = {};
    if (!clienteSelect) return;
    Array.from(clienteSelect.options).forEach(opt => {
        if (!opt.value) return;
        const label = (opt.dataset.label || opt.textContent || '').trim();
        if (label) clienteLabelToId[label] = opt.value;
    });
}
rebuildClienteLookup();

function filtrarClientesPorBusca(termo) {
    if (!clienteSelect) return;
    const q = (termo || '').toLowerCase().trim();
    Array.from(clienteSelect.options).forEach(opt => {
        if (!opt.value) return;
        const hay = (opt.dataset.search || (opt.textContent || '').toLowerCase());
        opt.hidden = q !== '' && !hay.includes(q);
    });
}
if (clienteSelect) {
    clienteSelect.addEventListener('change', () => {
        filtrarEquipamentos();
        const equipClienteInput = document.getElementById('equipamento_cliente_id');
        if (equipClienteInput) {
            equipClienteInput.value = clienteSelect.value || '';
        }
        const sel = clienteSelect.selectedOptions && clienteSelect.selectedOptions[0];
        if (clienteBuscaInput && sel && sel.value) {
            clienteBuscaInput.value = sel.dataset.label || sel.textContent || '';
        }
    });
}
if (clienteBuscaInput) {
    clienteBuscaInput.addEventListener('input', () => {
        filtrarClientesPorBusca(clienteBuscaInput.value);
        const typed = clienteBuscaInput.value.trim();
        const id = clienteLabelToId[typed];
        if (id && clienteSelect) {
            clienteSelect.value = id;
            clienteSelect.dispatchEvent(new Event('change'));
        }
    });
    clienteBuscaInput.addEventListener('change', () => {
        const typed = clienteBuscaInput.value.trim();
        const id = clienteLabelToId[typed];
        if (id && clienteSelect) {
            clienteSelect.value = id;
            clienteSelect.dispatchEvent(new Event('change'));
        }
    });
}
if (clienteSelect && clienteBuscaInput && clienteSelect.value) {
    const selInit = clienteSelect.selectedOptions && clienteSelect.selectedOptions[0];
    if (selInit) clienteBuscaInput.value = selInit.dataset.label || selInit.textContent || '';
}

const equipamentoSelect = document.getElementById('wizard_equipamento_id');
const unlockTypeSelect = document.getElementById('wizard_unlock_type');
const unlockPatternWrap = document.getElementById('wizard_unlock_pattern_wrap');
const unlockPinWrap = document.getElementById('wizard_unlock_pin_wrap');
const unlockNoneWrap = document.getElementById('wizard_unlock_none_wrap');
const patternCodeInput = document.getElementById('wizard_pattern_code_input');
const unlockCodeInput = document.getElementById('wizard_unlock_code');
const unlockNoneInput = document.getElementById('wizard_unlock_none_code');
const clearPatternBtn = document.getElementById('wizard_clear_pattern_btn');
let wizardPatternComponent = null;
if (equipamentoSelect) {
    equipamentoSelect.addEventListener('change', () => {
        if (equipamentoNomeInput) {
            equipamentoNomeInput.value = equipamentoSelect.selectedOptions[0]?.textContent?.trim() || '';
        }
        atualizarResumo();
    });
}
if (equipamentoNomeInput) {
    equipamentoNomeInput.addEventListener('input', atualizarResumo);
}

function setUnlockInputState(input, enabled) {
    if (!input) return;
    if (enabled) {
        input.disabled = false;
        input.setAttribute('name', 'equipamento_unlock_code');
    } else {
        input.disabled = true;
        input.removeAttribute('name');
    }
}

function ensureWizardPattern() {
    if (wizardPatternComponent || typeof UnlockPattern === 'undefined') return;
    wizardPatternComponent = new UnlockPattern('wizard_pattern_draw_area', {
        rows: 3,
        cols: 3,
        readonly: false,
        onPatternChange: (pattern) => {
            if (patternCodeInput) {
                patternCodeInput.value = (pattern && pattern.length) ? JSON.stringify(pattern) : '';
            }
        }
    });
    if (clearPatternBtn) {
        clearPatternBtn.addEventListener('click', () => {
            if (wizardPatternComponent) wizardPatternComponent.clear();
            if (patternCodeInput) patternCodeInput.value = '';
        });
    }
}

function toggleUnlockWizard() {
    if (!unlockTypeSelect) return;
    const type = unlockTypeSelect.value;
    if (unlockPatternWrap) unlockPatternWrap.classList.toggle('hidden', type !== 'pattern');
    if (unlockPinWrap) unlockPinWrap.classList.toggle('hidden', type !== 'pin');
    if (unlockNoneWrap) unlockNoneWrap.classList.toggle('hidden', type !== 'none');

    setUnlockInputState(patternCodeInput, type === 'pattern');
    setUnlockInputState(unlockCodeInput, type === 'pin');
    setUnlockInputState(unlockNoneInput, type === 'none');

    if (unlockCodeInput) {
        unlockCodeInput.required = type === 'pin';
        if (type !== 'pin') unlockCodeInput.value = '';
    }
    if (patternCodeInput && type !== 'pattern') {
        patternCodeInput.value = '';
    }
    if (unlockNoneInput && type === 'none') {
        unlockNoneInput.value = '';
    }
    if (type === 'pattern') ensureWizardPattern();
}
if (unlockTypeSelect) {
    unlockTypeSelect.addEventListener('change', toggleUnlockWizard);
    toggleUnlockWizard();
}

updateWizard();

@if(isset($ordemDeRef))
@php
    $nomeEquipRef = trim(($ordemDeRef->equipamento_marca ?? '') . ' ' . ($ordemDeRef->equipamento_modelo ?? '')) ?: ($ordemDeRef->equipamento_nome ?? '');
    $refClienteId = ($ordemDeRef->equipamento_id && $ordemDeRef->equipamento)
        ? $ordemDeRef->equipamento->cliente_id
        : $ordemDeRef->cliente_id;
    $refData = [
        'cliente_id' => $refClienteId,
        'equipamento_id' => $ordemDeRef->equipamento_id,
        'equipamento_nome' => $nomeEquipRef,
    ];
@endphp
// Preencher a partir da OS de referência (vindo da página pública do equipamento)
(function() {
    const ref = {!! json_encode($refData) !!};
    const clienteSelect = document.getElementById('wizard_cliente_id');
    const equipamentoSelect = document.getElementById('wizard_equipamento_id');
    const equipamentoNomeInput = document.getElementById('equipamento_nome');
    if (clienteSelect && ref.cliente_id) {
        clienteSelect.value = ref.cliente_id;
        if (typeof filtrarEquipamentos === 'function') filtrarEquipamentos();
    }
    if (equipamentoSelect && ref.equipamento_id) {
        const opt = equipamentoSelect.querySelector('option[value="' + ref.equipamento_id + '"]');
        if (opt && !opt.hidden) {
            equipamentoSelect.value = ref.equipamento_id;
            if (equipamentoNomeInput) equipamentoNomeInput.value = opt.textContent?.trim() || ref.equipamento_nome || '';
        }
    } else if (equipamentoNomeInput && ref.equipamento_nome) {
        equipamentoNomeInput.value = ref.equipamento_nome;
    }
    atualizarResumo();
    if (typeof currentStep !== 'undefined' && currentStep >= 3 && typeof updateWizard === 'function') {
        updateWizard();
    }
})();
@endif

const clienteModal = document.getElementById('clienteModal');
const openClienteModal = document.getElementById('openClienteModal');
const closeClienteModal = document.getElementById('closeClienteModal');
const cancelClienteModal = document.getElementById('cancelClienteModal');
const clienteQuickForm = document.getElementById('clienteQuickForm');

let clienteQuickCepDebounceTimer = null;
let clienteQuickCepUltimoOk = '';
let quickOsModalOpenCount = 0;

function ajustarCamadasLayoutQuickModal(open) {
    const targets = [
        document.querySelector('header.sticky'),
        document.getElementById('sidebar'),
        document.getElementById('sidebar-backdrop'),
    ].filter(Boolean);

    if (open) {
        quickOsModalOpenCount += 1;
        if (quickOsModalOpenCount > 1) return;
        targets.forEach((el) => {
            if (el.dataset.quickModalPrevZ === undefined) {
                el.dataset.quickModalPrevZ = el.style.zIndex || '';
            }
            // Evita header/sidebar sobre o modal em telas com stacking contexts agressivos.
            el.style.zIndex = '1';
        });
        return;
    }

    quickOsModalOpenCount = Math.max(0, quickOsModalOpenCount - 1);
    if (quickOsModalOpenCount > 0) return;
    targets.forEach((el) => {
        if (el.dataset.quickModalPrevZ !== undefined) {
            el.style.zIndex = el.dataset.quickModalPrevZ;
            delete el.dataset.quickModalPrevZ;
        } else {
            el.style.zIndex = '';
        }
    });
}

function resetClienteQuickCepLookup() {
    clienteQuickCepUltimoOk = '';
    if (clienteQuickCepDebounceTimer) {
        clearTimeout(clienteQuickCepDebounceTimer);
        clienteQuickCepDebounceTimer = null;
    }
}

function toggleClienteModal(open) {
    if (!clienteModal) return;
    if (open) {
        resetClienteQuickCepLookup();
    }
    clienteModal.classList.toggle('hidden', !open);
    clienteModal.classList.toggle('flex', open);
    ajustarCamadasLayoutQuickModal(open);
}

if (openClienteModal) openClienteModal.addEventListener('click', () => toggleClienteModal(true));
if (closeClienteModal) closeClienteModal.addEventListener('click', () => toggleClienteModal(false));
if (cancelClienteModal) cancelClienteModal.addEventListener('click', () => toggleClienteModal(false));

if (clienteQuickForm) {
    clienteQuickForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const tipoPessoa = clienteQuickForm.querySelector('select[name="tipo_pessoa"]')?.value;
        const cpfInput = document.getElementById('clienteQuickCpf');
        const cnpjInput = document.getElementById('clienteQuickCnpj');
        if (tipoPessoa === 'F' && cpfInput && cpfInput.value && !validarCPF(cpfInput.value)) {
            alert('CPF inválido.');
            cpfInput.focus();
            return;
        }
        if (tipoPessoa === 'J' && cnpjInput && cnpjInput.value && !validarCNPJ(cnpjInput.value)) {
            alert('CNPJ inválido.');
            cnpjInput.focus();
            return;
        }
        const formData = new FormData(clienteQuickForm);
        const response = await fetch('{{ route('clientes.quick-store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData,
        });
        if (!response.ok) {
            alert('Erro ao salvar cliente.');
            return;
        }
        const data = await response.json();
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.nome;
        option.dataset.grupo = data.grupo_economico_id || '';
        option.dataset.label = data.nome;
        option.dataset.search = String(data.nome || '').toLowerCase();
        document.getElementById('wizard_cliente_id').appendChild(option);
        document.getElementById('wizard_cliente_id').value = data.id;
        rebuildClienteLookup();
        if (clienteBuscaInput) clienteBuscaInput.value = data.nome || '';
        toggleClienteModal(false);
        clienteQuickForm.reset();
    });
}

function onlyDigits(value, max) {
    return (value || '').replace(/\D/g, '').slice(0, max);
}

function formatarCPF(value) {
    const v = onlyDigits(value, 11);
    return v.replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function formatarCNPJ(value) {
    const alfanumerico = (value || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    if (/[A-Z]/.test(alfanumerico)) {
        return alfanumerico;
    }
    const v = onlyDigits(alfanumerico, 14);
    return v.replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

function formatarTelefone(value) {
    const v = onlyDigits(value, 11);
    if (v.length <= 10) {
        return v.replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }
    return v.replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
}

function formatarCep(value) {
    const v = onlyDigits(value, 8);
    return v.replace(/(\d{5})(\d)/, '$1-$2');
}

const ibgeCidadesCacheQuick = {};

async function carregarCidadesPorUfQuick(uf) {
    const ufUpper = String(uf || '').trim().toUpperCase();
    if (ufUpper.length !== 2) return [];
    if (ibgeCidadesCacheQuick[ufUpper]) return ibgeCidadesCacheQuick[ufUpper];
    const res = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${ufUpper}/municipios`);
    if (!res.ok) return [];
    const data = await res.json();
    const cidades = Array.isArray(data) ? data.map((c) => String(c?.nome || '').trim()).filter(Boolean) : [];
    ibgeCidadesCacheQuick[ufUpper] = cidades;
    return cidades;
}

async function popularSelectCidadeQuick(ufEl, cidadeEl, cidadeSelecionada = '') {
    if (!ufEl || !cidadeEl) return;
    const uf = String(ufEl.value || '').trim().toUpperCase();
    const cidadeAtual = String(cidadeSelecionada || cidadeEl.dataset.selected || cidadeEl.value || '').trim();
    cidadeEl.innerHTML = '';
    if (uf.length !== 2) {
        cidadeEl.innerHTML = '<option value="">Selecione a UF primeiro...</option>';
        return;
    }
    cidadeEl.innerHTML = '<option value="">Carregando cidades...</option>';
    const cidades = await carregarCidadesPorUfQuick(uf);
    cidadeEl.innerHTML = '<option value="">Selecione...</option>';
    cidades.forEach((cidade) => {
        const opt = document.createElement('option');
        opt.value = cidade;
        opt.textContent = cidade;
        if (cidadeAtual && cidade.toLowerCase() === cidadeAtual.toLowerCase()) {
            opt.selected = true;
        }
        cidadeEl.appendChild(opt);
    });
    if (cidadeAtual && !cidades.some((c) => c.toLowerCase() === cidadeAtual.toLowerCase())) {
        const opt = document.createElement('option');
        opt.value = cidadeAtual;
        opt.textContent = `${cidadeAtual} (fora da lista)`;
        opt.selected = true;
        cidadeEl.appendChild(opt);
    }
}

function validarCPF(cpf) {
    const clean = onlyDigits(cpf, 11);
    if (clean.length !== 11 || /^(\d)\1{10}$/.test(clean)) return false;
    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(clean[i], 10) * (10 - i);
    let d1 = (sum * 10) % 11; d1 = d1 === 10 ? 0 : d1;
    if (d1 !== parseInt(clean[9], 10)) return false;
    sum = 0;
    for (let i = 0; i < 10; i++) sum += parseInt(clean[i], 10) * (11 - i);
    let d2 = (sum * 10) % 11; d2 = d2 === 10 ? 0 : d2;
    return d2 === parseInt(clean[10], 10);
}

function validarCNPJ(cnpj) {
    const clean = (cnpj || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    if (clean.length !== 14 || /^([A-Z0-9])\1{13}$/.test(clean)) return false;
    const base = clean.substring(0, 12);
    const dvInformado = clean.substring(12, 14);
    if (!/^\d{2}$/.test(dvInformado)) return false;

    const calc = (length) => {
        const weights = length === 12
            ? [5,4,3,2,9,8,7,6,5,4,3,2]
            : [6,5,4,3,2,9,8,7,6,5,4,3,2];
        let sum = 0;
        const source = length === 12 ? base : (base + calc(12));
        for (let i = 0; i < weights.length; i++) {
            const char = source[i];
            const code = char.charCodeAt(0);
            const value = /[0-9]/.test(char)
                ? parseInt(char, 10)
                : (code >= 65 && code <= 90 ? code - 48 : 0);
            sum += value * weights[i];
        }
        const rest = sum % 11;
        return rest < 2 ? 0 : 11 - rest;
    };
    const d1 = calc(12);
    const d2 = calc(13);
    return dvInformado === `${d1}${d2}`;
}

async function buscarDadosCnpjPublicoQuick(cnpj) {
    const urls = [
        `https://publica.cnpj.ws/cnpj/${cnpj}`,
        `https://api.opencnpj.org/${cnpj}`,
    ];
    const headers = {
        'Accept': 'application/json',
        'User-Agent': 'LivreOS-ERP/1.0',
    };
    const responses = await Promise.all(urls.map((url) => fetch(url, { headers })));
    const valid = [];
    for (const res of responses) {
        if (!res.ok) continue;
        try {
            const data = await res.json();
            if (data && typeof data === 'object') valid.push(data);
        } catch (err) {
        }
    }
    if (!valid.length) return null;
    return mesclarDadosCnpjQuick(valid.map(normalizarCnpjResponseQuick));
}

function normalizarCnpjResponseQuick(data) {
    const estabelecimento = data.estabelecimento || data.endereco || data.address || data;
    const atividadePrincipal = data.estabelecimento?.atividade_principal
        || data.atividade_principal
        || data.atividadePrincipal
        || data.cnae_principal
        || data.main_activity
        || data.mainActivity
        || null;

    let atividadeDescricao = '';
    if (Array.isArray(atividadePrincipal)) {
        if (atividadePrincipal[0] && typeof atividadePrincipal[0] === 'object') {
            atividadeDescricao = String(atividadePrincipal[0].descricao || atividadePrincipal[0].text || '');
        } else if (atividadePrincipal.length) {
            atividadeDescricao = String(atividadePrincipal[0] || '');
        }
    } else if (atividadePrincipal && typeof atividadePrincipal === 'object') {
        atividadeDescricao = String(atividadePrincipal.descricao || atividadePrincipal.text || '');
    } else if (atividadePrincipal) {
        atividadeDescricao = String(atividadePrincipal);
    }

    const cidadeValor = (
        estabelecimento.cidade
        || estabelecimento.municipio
        || estabelecimento.city
        || estabelecimento.cidade_exterior
        || ''
    );
    const ufValor = (
        estabelecimento.estado
        || estabelecimento.uf
        || estabelecimento.state
        || ''
    );
    const cidade = valorTextoCnpjQuick(cidadeValor, ['nome', 'descricao', 'name', 'descricao_sem_acento']);
    const uf = valorTextoCnpjQuick(ufValor, ['sigla', 'uf', 'codigo', 'name', 'nome']);

    return {
        razao_social: data.razao_social || data.razaoSocial || data.nome || data.company?.name || '',
        nome_fantasia: data.nome_fantasia || data.nomeFantasia || data.fantasia || data.trade_name || data.tradeName || '',
        cep: (estabelecimento.cep || estabelecimento.postal_code || estabelecimento.postalCode || '').toString(),
        logradouro: estabelecimento.logradouro || estabelecimento.tipo_logradouro || estabelecimento.address_line || estabelecimento.street || '',
        numero: (estabelecimento.numero || estabelecimento.number || '').toString(),
        bairro: estabelecimento.bairro || estabelecimento.district || '',
        cidade: cidade,
        uf: uf,
        ramo_atividade: atividadeDescricao,
        contatos: extrairContatosCnpjQuick(data),
    };
}

function valorTextoCnpjQuick(v, keys = []) {
    if (v === null || v === undefined) return '';
    if (typeof v === 'string' || typeof v === 'number' || typeof v === 'boolean') {
        return String(v).trim();
    }
    if (Array.isArray(v)) {
        for (const item of v) {
            const s = valorTextoCnpjQuick(item, keys);
            if (s) return s;
        }
        return '';
    }
    if (typeof v === 'object') {
        for (const k of keys) {
            if (v[k] !== undefined && v[k] !== null) {
                const s = valorTextoCnpjQuick(v[k], keys);
                if (s) return s;
            }
        }
        for (const k of Object.keys(v)) {
            const s = valorTextoCnpjQuick(v[k], keys);
            if (s) return s;
        }
        return '';
    }
    return '';
}

function extrairContatosCnpjQuick(data) {
    const contatos = [];
    const nomeContato = data.nome_fantasia || data.nomeFantasia || data.fantasia || data.razao_social || data.razaoSocial || data.nome || '';
    const addContato = (telefone, telefone2, email) => {
        const tel1 = (telefone || '').toString().trim();
        const tel2 = (telefone2 || '').toString().trim();
        const mail = (email || '').toString().trim();
        if (!tel1 && !tel2 && !mail) return;
        contatos.push({ nome: nomeContato || '', telefone: tel1, telefone2: tel2, email: mail });
    };

    if (data.estabelecimento) {
        addContato(
            data.estabelecimento.telefone1 || data.estabelecimento.telefone || data.estabelecimento.ddd1,
            data.estabelecimento.telefone2 || data.estabelecimento.ddd2,
            data.estabelecimento.email,
        );
    }
    addContato(data.telefone || data.phone, data.telefone2 || data.phone2, data.email);

    if (Array.isArray(data.contato) && data.contato.length) {
        data.contato.forEach((c) => addContato(c.telefone || c.phone, c.telefone2 || c.phone2, c.email));
    }
    if (Array.isArray(data.contatos) && data.contatos.length) {
        data.contatos.forEach((c) => addContato(c.telefone || c.phone, c.telefone2 || c.phone2, c.email));
    }
    return contatos;
}

function mesclarDadosCnpjQuick(lista) {
    const base = lista[0] || {};
    const out = { ...base };
    for (let i = 1; i < lista.length; i++) {
        const atual = lista[i] || {};
        Object.keys(atual).forEach((k) => {
            const v = atual[k];
            if (k === 'contatos') {
                const a = Array.isArray(out.contatos) ? out.contatos : [];
                const b = Array.isArray(v) ? v : [];
                out.contatos = [...a, ...b];
                return;
            }
            if ((out[k] === null || out[k] === undefined || out[k] === '') && v !== null && v !== undefined && v !== '') {
                out[k] = v;
            }
        });
    }
    return out;
}

async function buscarCepDadosViaCep(clean) {
    if (clean.length !== 8) return null;
    try {
        const res = await fetch(`https://viacep.com.br/ws/${clean}/json/`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return null;
        const data = await res.json();
        if (data.erro) return null;
        return data;
    } catch (err) {
        return null;
    }
}

async function preencherEnderecoClienteQuick(cepRaw) {
    const clean = onlyDigits(cepRaw, 8);
    if (clean.length < 8) {
        clienteQuickCepUltimoOk = '';
        return;
    }
    if (clean === clienteQuickCepUltimoOk) return;
    const data = await buscarCepDadosViaCep(clean);
    if (!data) return;
    clienteQuickCepUltimoOk = clean;
    const logradouroInput = document.getElementById('clienteQuickLogradouro');
    const bairroInput = document.getElementById('clienteQuickBairro');
    const cidadeInput = document.getElementById('clienteQuickCidade');
    const estadoInput = document.getElementById('clienteQuickEstado');
    const complementoInput = document.getElementById('clienteQuickComplemento');
    const numeroInput = document.getElementById('clienteQuickNumero');
    if (logradouroInput) logradouroInput.value = data.logradouro || '';
    if (bairroInput) bairroInput.value = data.bairro || '';
    if (estadoInput) estadoInput.value = (data.uf || '').toUpperCase();
    if (cidadeInput) {
        cidadeInput.dataset.selected = data.localidade || '';
        await popularSelectCidadeQuick(estadoInput, cidadeInput, data.localidade || '');
    }
    if (complementoInput && data.complemento && !complementoInput.value.trim()) {
        complementoInput.value = data.complemento;
    }
    if (numeroInput) numeroInput.focus();
}

const equipamentoModal = document.getElementById('equipamentoModal');
const openEquipamentoModal = document.getElementById('openEquipamentoModal');
const closeEquipamentoModal = document.getElementById('closeEquipamentoModal');
const cancelEquipamentoModal = document.getElementById('cancelEquipamentoModal');
const equipamentoQuickForm = document.getElementById('equipamentoQuickForm');
const equipamentoNomeModal = document.getElementById('equipamento_nome_modal');

function toggleEquipamentoModal(open) {
    if (!equipamentoModal) return;
    equipamentoModal.classList.toggle('hidden', !open);
    equipamentoModal.classList.toggle('flex', open);
    ajustarCamadasLayoutQuickModal(open);
}

if (openEquipamentoModal) openEquipamentoModal.addEventListener('click', () => {
    const clienteId = document.getElementById('wizard_cliente_id')?.value;
    if (!clienteId) {
        alert('Selecione um cliente primeiro.');
        return;
    }
    document.getElementById('equipamento_cliente_id').value = clienteId;
    if (equipamentoNomeModal && equipamentoNomeInput) {
        equipamentoNomeModal.value = equipamentoNomeInput.value || '';
    }
    toggleEquipamentoModal(true);
});
if (closeEquipamentoModal) closeEquipamentoModal.addEventListener('click', () => toggleEquipamentoModal(false));
if (cancelEquipamentoModal) cancelEquipamentoModal.addEventListener('click', () => toggleEquipamentoModal(false));

if (equipamentoQuickForm) {
    equipamentoQuickForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const nomeEquipamento = equipamentoNomeModal?.value?.trim() || '';
        if (!nomeEquipamento) {
            alert('Informe o nome do equipamento.');
            equipamentoNomeModal?.focus();
            return;
        }
        const formData = new FormData(equipamentoQuickForm);
        const response = await fetch('{{ route('equipamentos.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData,
        });
        if (!response.ok) {
            alert('Erro ao salvar equipamento.');
            return;
        }
        const data = await response.json();
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = data.label;
        option.dataset.cliente = data.cliente_id;
        document.getElementById('wizard_equipamento_id').appendChild(option);
        document.getElementById('wizard_equipamento_id').value = data.id;
        if (equipamentoNomeInput) {
            equipamentoNomeInput.value = nomeEquipamento || data.label || option.textContent || '';
        }
        atualizarResumo();
        toggleEquipamentoModal(false);
        equipamentoQuickForm.reset();
        if (equipamentoNomeModal) equipamentoNomeModal.value = '';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const tipoPessoaSelect = document.querySelector('#clienteQuickForm select[name="tipo_pessoa"]');
    const cpfContainer = document.getElementById('clienteQuickCpfContainer');
    const cnpjContainer = document.getElementById('clienteQuickCnpjContainer');
    const cpfInput = document.getElementById('clienteQuickCpf');
    const cnpjInput = document.getElementById('clienteQuickCnpj');
    const btnBuscarCnpj = document.getElementById('clienteQuickBuscarCnpj');
    const telInput = document.getElementById('clienteQuickTelefone');
    const cepInput = document.getElementById('clienteQuickCep');
    const ufEl = document.getElementById('clienteQuickEstado');
    const cidadeSelect = document.getElementById('clienteQuickCidade');

    const toggleDoc = () => {
        const tipo = tipoPessoaSelect?.value || 'F';
        if (cpfContainer && cnpjContainer) {
            cpfContainer.classList.toggle('hidden', tipo !== 'F');
            cnpjContainer.classList.toggle('hidden', tipo !== 'J');
        }
    };
    if (tipoPessoaSelect) {
        tipoPessoaSelect.addEventListener('change', toggleDoc);
        toggleDoc();
    }

    if (cidadeSelect && ufEl) {
        popularSelectCidadeQuick(ufEl, cidadeSelect, cidadeSelect.dataset.selected || cidadeSelect.value || '');
        ufEl.addEventListener('change', () => {
            popularSelectCidadeQuick(ufEl, cidadeSelect, '');
        });
    }

    if (cpfInput) {
        cpfInput.addEventListener('input', (e) => {
            e.target.value = formatarCPF(e.target.value);
        });
        cpfInput.addEventListener('blur', (e) => {
            if (e.target.value && !validarCPF(e.target.value)) {
                e.target.setCustomValidity('CPF inválido.');
            } else {
                e.target.setCustomValidity('');
            }
        });
    }

    if (cnpjInput) {
        cnpjInput.addEventListener('input', (e) => {
            e.target.value = formatarCNPJ(e.target.value);
        });
        cnpjInput.addEventListener('blur', (e) => {
            if (e.target.value && !validarCNPJ(e.target.value)) {
                e.target.setCustomValidity('CNPJ inválido.');
            } else {
                e.target.setCustomValidity('');
            }
        });
    }

    if (btnBuscarCnpj && cnpjInput) {
        btnBuscarCnpj.addEventListener('click', async () => {
            const cnpjLimpo = (cnpjInput.value || '').replace(/\D/g, '');
            if (cnpjLimpo.length !== 14) {
                alert('Informe um CNPJ válido com 14 dígitos.');
                cnpjInput.focus();
                return;
            }
            if (!validarCNPJ(cnpjInput.value)) {
                alert('CNPJ inválido.');
                cnpjInput.focus();
                return;
            }
            btnBuscarCnpj.disabled = true;
            const txt = btnBuscarCnpj.textContent;
            btnBuscarCnpj.textContent = 'Buscando...';
            try {
                const dados = await buscarDadosCnpjPublicoQuick(cnpjLimpo);
                if (!dados) {
                    alert('Não foi possível obter dados desse CNPJ.');
                    return;
                }
                const nomeInput = clienteQuickForm?.querySelector('input[name="nome"]');
                const contatoNomeInput = clienteQuickForm?.querySelector('input[name="contato_nome"]');
                const contatoEmailInput = clienteQuickForm?.querySelector('input[name="contato_email"]');
                const contatoTelInput = document.getElementById('clienteQuickTelefone');
                const cepEl = document.getElementById('clienteQuickCep');
                const logEl = document.getElementById('clienteQuickLogradouro');
                const numEl = document.getElementById('clienteQuickNumero');
                const bairroEl = document.getElementById('clienteQuickBairro');
                const cidadeEl = document.getElementById('clienteQuickCidade');
                const ufEl = document.getElementById('clienteQuickEstado');

                const nomeFinal = (dados.nome_fantasia || dados.razao_social || '').toString().trim();
                if (nomeInput && !String(nomeInput.value || '').trim() && nomeFinal) nomeInput.value = nomeFinal;
                if (contatoNomeInput && !String(contatoNomeInput.value || '').trim() && nomeFinal) contatoNomeInput.value = nomeFinal;

                const contato = Array.isArray(dados.contatos) && dados.contatos.length ? dados.contatos[0] : null;
                if (contato) {
                    if (contatoEmailInput && !String(contatoEmailInput.value || '').trim() && contato.email) contatoEmailInput.value = contato.email;
                    if (contatoTelInput && !String(contatoTelInput.value || '').trim()) {
                        const tel = (contato.telefone || contato.telefone2 || '').toString();
                        if (tel) contatoTelInput.value = formatarTelefone(tel);
                    }
                }

                if (cepEl && !String(cepEl.value || '').trim() && dados.cep) cepEl.value = formatarCep(String(dados.cep));
                if (logEl && !String(logEl.value || '').trim() && dados.logradouro) logEl.value = String(dados.logradouro);
                if (numEl && !String(numEl.value || '').trim() && dados.numero) numEl.value = String(dados.numero);
                if (bairroEl && !String(bairroEl.value || '').trim() && dados.bairro) bairroEl.value = String(dados.bairro);
                if (ufEl && !String(ufEl.value || '').trim() && dados.uf) {
                    ufEl.value = String(dados.uf).toUpperCase().slice(0, 2);
                }
                if (cidadeEl && dados.cidade) {
                    const cidadeValor = String(dados.cidade).trim();
                    cidadeEl.dataset.selected = cidadeValor;
                    await popularSelectCidadeQuick(ufEl, cidadeEl, cidadeValor);
                }
            } catch (err) {
                alert('Erro ao consultar CNPJ.');
            } finally {
                btnBuscarCnpj.disabled = false;
                btnBuscarCnpj.textContent = txt;
            }
        });
    }

    if (telInput) {
        telInput.addEventListener('input', (e) => {
            e.target.value = formatarTelefone(e.target.value);
        });
    }

    if (cepInput) {
        cepInput.addEventListener('input', (e) => {
            e.target.value = formatarCep(e.target.value);
            const clean = onlyDigits(e.target.value, 8);
            if (clean.length < 8) {
                clienteQuickCepUltimoOk = '';
            }
            if (clienteQuickCepDebounceTimer) {
                clearTimeout(clienteQuickCepDebounceTimer);
                clienteQuickCepDebounceTimer = null;
            }
            if (clean.length === 8) {
                clienteQuickCepDebounceTimer = setTimeout(() => {
                    clienteQuickCepDebounceTimer = null;
                    preencherEnderecoClienteQuick(e.target.value);
                }, 400);
            }
        });
        cepInput.addEventListener('blur', (e) => {
            if (clienteQuickCepDebounceTimer) {
                clearTimeout(clienteQuickCepDebounceTimer);
                clienteQuickCepDebounceTimer = null;
            }
            preencherEnderecoClienteQuick(e.target.value);
        });
    }
});
</script>
@endsection
