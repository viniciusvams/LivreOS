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
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Cadastrar Atendimento</h1>
        <p class="text-gray-600 dark:text-gray-400">Preencha os dados do atendimento externo</p>
    </div>
    <a href="{{ route('plugin.atendimento.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Voltar</a>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
    <ul class="list-inside list-disc">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ route('plugin.atendimento.store') }}" method="POST" class="space-y-6" id="form-atendimento">
    @csrf
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados do atendimento</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
                    <button type="button" id="openClienteModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">+ Novo cliente</button>
                </div>
                <select name="cliente_id" id="cliente_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Digite o nome do cliente / Selecione...</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ old('cliente_id', $atendimento->cliente_id) == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data e Hora do Atendimento <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="data_hora_atendimento" value="{{ old('data_hora_atendimento', $atendimento->data_hora_atendimento ? $atendimento->data_hora_atendimento->format('Y-m-d\TH:i') : '') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tempo estimado</label>
                <select name="tempo_estimado_minutos" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Selecione —</option>
                    @foreach($temposEstimadosList as $t)
                        <option value="{{ $t->minutos }}" {{ old('tempo_estimado_minutos', $atendimento->tempo_estimado_minutos) == $t->minutos ? 'selected' : '' }}>{{ $t->label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Técnico responsável</label>
                <select name="tecnico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Nenhum —</option>
                    @foreach($tecnicos as $t)
                        <option value="{{ $t->id }}" {{ old('tecnico_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Endereço do Atendimento</h2>
        <div class="space-y-3">
            <label class="flex items-center gap-2">
                <input type="radio" name="endereco_tipo" value="usar_cadastrado" {{ old('endereco_tipo', $atendimento->endereco_tipo) === 'usar_cadastrado' ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Usar endereço cadastrado (principal do cliente)</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="endereco_tipo" value="endereco_cliente" {{ old('endereco_tipo', $atendimento->endereco_tipo) === 'endereco_cliente' ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Endereço cadastrado do cliente</span>
            </label>
            <div id="box-endereco-cliente" class="ml-6 hidden">
                <select name="endereco_id" id="endereco_id" class="w-full max-w-xl rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione o endereço...</option>
                    @foreach($clientes as $c)
                        @foreach($c->enderecos ?? [] as $e)
                            <option value="{{ $e->id }}" data-cliente="{{ $c->id }}" {{ old('endereco_id', $atendimento->endereco_id) == $e->id ? 'selected' : '' }}>{{ $e->getEnderecoCompletoAttribute() }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2">
                <input type="radio" name="endereco_tipo" value="novo" {{ old('endereco_tipo', $atendimento->endereco_tipo) === 'novo' ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Informar novo endereço</span>
            </label>
            <div id="box-endereco-novo" class="ml-6 hidden space-y-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">Informe o CEP para preencher automaticamente (ViaCEP).</p>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                        <input type="text" name="endereco_cep" id="endereco_cep" value="{{ old('endereco_cep', $atendimento->endereco_cep) }}" placeholder="00000-000" maxlength="9" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Logradouro</label>
                        <input type="text" name="endereco_logradouro" id="endereco_logradouro" value="{{ old('endereco_logradouro', $atendimento->endereco_logradouro) }}" placeholder="Rua, avenida..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                        <input type="text" name="endereco_numero" id="endereco_numero" value="{{ old('endereco_numero', $atendimento->endereco_numero) }}" placeholder="Nº" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Complemento</label>
                        <input type="text" name="endereco_complemento" value="{{ old('endereco_complemento', $atendimento->endereco_complemento) }}" placeholder="Apto, sala..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bairro</label>
                        <input type="text" name="endereco_bairro" id="endereco_bairro" value="{{ old('endereco_bairro', $atendimento->endereco_bairro) }}" placeholder="Bairro" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                            <input type="text" name="endereco_cidade" id="endereco_cidade" value="{{ old('endereco_cidade', $atendimento->endereco_cidade) }}" placeholder="Cidade" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">UF</label>
                            <input type="text" name="endereco_uf" id="endereco_uf" value="{{ old('endereco_uf', $atendimento->endereco_uf) }}" placeholder="UF" maxlength="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="endereco_completo" id="endereco_completo" value="{{ old('endereco_completo', $atendimento->endereco_completo) }}">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Problema e diagnóstico</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Problema Reportado pelo Cliente</label>
                <textarea name="problema_reportado" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Descreva o problema...">{{ old('problema_reportado', $atendimento->problema_reportado) }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Diagnóstico do Técnico</label>
                <textarea name="diagnostico_tecnico" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Diagnóstico...">{{ old('diagnostico_tecnico', $atendimento->diagnostico_tecnico) }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações técnicas</label>
                <textarea name="observacoes_tecnicas" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90" placeholder="Observações adicionais...">{{ old('observacoes_tecnicas', $atendimento->observacoes_tecnicas) }}</textarea>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Status e prioridade</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status do Atendimento</label>
                <select name="status_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    @foreach($statusList as $s)
                        <option value="{{ $s->id }}" {{ old('status_id', $atendimento->status_id) == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
                <select name="prioridade_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    @foreach($prioridadesList as $p)
                        <option value="{{ $p->id }}" {{ old('prioridade_id', $atendimento->prioridade_id) == $p->id ? 'selected' : '' }}>{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar atendimento</button>
        <a href="{{ route('plugin.atendimento.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
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
                <div class="grid grid-cols-1 gap-4 pb-4 md:grid-cols-2">
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
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}">{{ $uf }}</option>
                            @endforeach
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tipo = document.querySelectorAll('input[name="endereco_tipo"]');
    var boxCliente = document.getElementById('box-endereco-cliente');
    var boxNovo = document.getElementById('box-endereco-novo');
    var enderecoSelect = document.getElementById('endereco_id');
    var clienteSelect = document.getElementById('cliente_id');

    function toggleEndereco() {
        var v = document.querySelector('input[name="endereco_tipo"]:checked')?.value;
        boxCliente.classList.toggle('hidden', v !== 'endereco_cliente');
        boxNovo.classList.toggle('hidden', v !== 'novo');
        if (v === 'endereco_cliente') {
            var cid = clienteSelect.value;
            [].forEach.call(enderecoSelect.options, function(opt) {
                opt.style.display = (!opt.value || (opt.dataset.cliente === cid)) ? '' : 'none';
                opt.disabled = opt.dataset.cliente && opt.dataset.cliente !== cid;
            });
        }
    }
    tipo.forEach(function(r) { r.addEventListener('change', toggleEndereco); });
    clienteSelect.addEventListener('change', toggleEndereco);
    // ViaCEP: CEP primeiro, preenche endereço e foca no número
    var cepInput = document.getElementById('endereco_cep');
    var logradouroInput = document.getElementById('endereco_logradouro');
    var numeroInput = document.getElementById('endereco_numero');
    var bairroInput = document.getElementById('endereco_bairro');
    var cidadeInput = document.getElementById('endereco_cidade');
    var ufInput = document.getElementById('endereco_uf');
    var completoInput = document.getElementById('endereco_completo');

    function apenasDigitos(s) { return (s || '').replace(/\D/g, ''); }
    function montarCompleto() {
        var p = [logradouroInput.value, numeroInput.value, bairroInput.value, cidadeInput.value, ufInput.value].filter(Boolean);
        completoInput.value = p.join(', ');
    }
    [logradouroInput, numeroInput, bairroInput, cidadeInput, ufInput].forEach(function(el) {
        if (el) el.addEventListener('input', montarCompleto);
    });

    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            var cep = apenasDigitos(cepInput.value);
            if (cep.length !== 8) return;
            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.erro) return;
                    if (logradouroInput) logradouroInput.value = data.logradouro || '';
                    if (bairroInput) bairroInput.value = data.bairro || '';
                    if (cidadeInput) cidadeInput.value = data.localidade || '';
                    if (ufInput) ufInput.value = (data.uf || '').toUpperCase();
                    montarCompleto();
                    if (numeroInput) { numeroInput.focus(); numeroInput.select && numeroInput.select(); }
                })
                .catch(function() {});
        });
        cepInput.addEventListener('input', function() {
            var v = apenasDigitos(cepInput.value);
            if (v.length > 5) cepInput.value = v.slice(0, 5) + '-' + v.slice(5, 8);
            else cepInput.value = v;
        });
    }

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clienteModal = document.getElementById('clienteModal');
    const openClienteModal = document.getElementById('openClienteModal');
    const closeClienteModal = document.getElementById('closeClienteModal');
    const cancelClienteModal = document.getElementById('cancelClienteModal');
    const clienteQuickForm = document.getElementById('clienteQuickForm');
    const clienteSelect = document.getElementById('cliente_id');

    const toggleClienteModal = (open) => {
        if (!clienteModal) return;
        clienteModal.classList.toggle('hidden', !open);
        clienteModal.classList.toggle('flex', open);
    };

    if (openClienteModal) openClienteModal.addEventListener('click', () => toggleClienteModal(true));
    if (closeClienteModal) closeClienteModal.addEventListener('click', () => toggleClienteModal(false));
    if (cancelClienteModal) cancelClienteModal.addEventListener('click', () => toggleClienteModal(false));

    function onlyDigits(value, max) { return (value || '').replace(/\D/g, '').slice(0, max); }
    function formatarCPF(value) {
        const v = onlyDigits(value, 11);
        return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    function formatarCNPJ(value) {
        const v = onlyDigits(value, 14);
        return v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
    }
    function formatarTelefone(value) {
        const v = onlyDigits(value, 11);
        if (v.length <= 10) return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
        return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
    }
    function formatarCep(value) {
        const v = onlyDigits(value, 8);
        return v.replace(/(\d{5})(\d)/, '$1-$2');
    }

    const tipoPessoaSelect = document.querySelector('#clienteQuickForm select[name="tipo_pessoa"]');
    const cpfContainer = document.getElementById('clienteQuickCpfContainer');
    const cnpjContainer = document.getElementById('clienteQuickCnpjContainer');
    const cpfInput = document.getElementById('clienteQuickCpf');
    const cnpjInput = document.getElementById('clienteQuickCnpj');
    const telInput = document.getElementById('clienteQuickTelefone');
    const cepInput = document.getElementById('clienteQuickCep');
    const logradouroInput = document.getElementById('clienteQuickLogradouro');
    const bairroInput = document.getElementById('clienteQuickBairro');
    const cidadeInput = document.getElementById('clienteQuickCidade');
    const ufInput = document.getElementById('clienteQuickEstado');
    const numeroInput = document.getElementById('clienteQuickNumero');
    const btnBuscarCnpj = document.getElementById('clienteQuickBuscarCnpj');

    const toggleDoc = () => {
        const tipo = tipoPessoaSelect?.value || 'F';
        if (cpfContainer && cnpjContainer) {
            cpfContainer.classList.toggle('hidden', tipo !== 'F');
            cnpjContainer.classList.toggle('hidden', tipo !== 'J');
        }
    };
    if (tipoPessoaSelect) { tipoPessoaSelect.addEventListener('change', toggleDoc); toggleDoc(); }
    if (cpfInput) cpfInput.addEventListener('input', (e) => { e.target.value = formatarCPF(e.target.value); });
    if (cnpjInput) cnpjInput.addEventListener('input', (e) => { e.target.value = formatarCNPJ(e.target.value); });
    if (telInput) telInput.addEventListener('input', (e) => { e.target.value = formatarTelefone(e.target.value); });
    if (cepInput) cepInput.addEventListener('input', (e) => { e.target.value = formatarCep(e.target.value); });

    if (cepInput) {
        cepInput.addEventListener('blur', async () => {
            const cep = onlyDigits(cepInput.value, 8);
            if (cep.length !== 8) return;
            try {
                const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                if (!res.ok) return;
                const data = await res.json();
                if (data.erro) return;
                if (logradouroInput) logradouroInput.value = data.logradouro || '';
                if (bairroInput) bairroInput.value = data.bairro || '';
                if (cidadeInput) cidadeInput.innerHTML = `<option value="${data.localidade || ''}" selected>${data.localidade || 'Selecione...'}</option>`;
                if (ufInput) ufInput.value = (data.uf || '').toUpperCase();
                if (numeroInput) numeroInput.focus();
            } catch (err) {}
        });
    }

    if (btnBuscarCnpj && cnpjInput) {
        btnBuscarCnpj.addEventListener('click', async () => {
            const cnpj = onlyDigits(cnpjInput.value, 14);
            if (cnpj.length !== 14) {
                alert('Informe um CNPJ válido com 14 dígitos.');
                cnpjInput.focus();
                return;
            }
            const nomeInput = clienteQuickForm?.querySelector('input[name="nome"]');
            btnBuscarCnpj.disabled = true;
            const txt = btnBuscarCnpj.textContent;
            btnBuscarCnpj.textContent = 'Buscando...';
            try {
                const res = await fetch(`https://publica.cnpj.ws/cnpj/${cnpj}`);
                if (!res.ok) throw new Error('Falha na consulta');
                const data = await res.json();
                const nomeFantasia = data?.estabelecimento?.nome_fantasia || data?.nome_fantasia || data?.razao_social || '';
                if (nomeInput && nomeFantasia && !nomeInput.value.trim()) nomeInput.value = nomeFantasia;
            } catch (err) {
                alert('Não foi possível consultar o CNPJ agora.');
            } finally {
                btnBuscarCnpj.disabled = false;
                btnBuscarCnpj.textContent = txt;
            }
        });
    }

    if (clienteQuickForm) {
        clienteQuickForm.addEventListener('submit', async (e) => {
            e.preventDefault();
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
            if (clienteSelect) {
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.nome || `Cliente #${data.id}`;
                clienteSelect.appendChild(option);
                clienteSelect.value = data.id;
                clienteSelect.dispatchEvent(new Event('change'));
            }
            clienteQuickForm.reset();
            toggleClienteModal(false);
        });
    }
});
</script>
@endsection
