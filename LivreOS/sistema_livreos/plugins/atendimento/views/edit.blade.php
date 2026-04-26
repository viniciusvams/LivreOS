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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Atendimento</h1>
        <p class="text-gray-600 dark:text-gray-400">Atendimento #{{ $atendimento->id }}</p>
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

<form action="{{ route('plugin.atendimento.update', $atendimento->id) }}" method="POST" class="space-y-6" id="form-atendimento" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Dados do atendimento</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
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
                        <option value="{{ $t->id }}" {{ old('tecnico_id', $atendimento->tecnico_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
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

    @if(isset($checklistModels) && $checklistModels->isNotEmpty())
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Checklist</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo de checklist</label>
                <select name="checklist_model_id" id="checklist_model_id" class="w-full max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Nenhum —</option>
                    @foreach($checklistModels as $cm)
                        <option value="{{ $cm->id }}" {{ old('checklist_model_id', $atendimento->checklist_model_id) == $cm->id ? 'selected' : '' }}>{{ $cm->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div id="checklist_campos_container" class="space-y-3"></div>
            <input type="hidden" name="checklist_respostas" id="checklist_respostas_input" value="{{ json_encode(old('checklist_respostas', $atendimento->checklist_respostas ?? [])) }}">
        </div>
    </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Fotos</h2>
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Fotos já enviadas e novas (antes, depois ou evidência).</p>
        @if($atendimento->fotos && $atendimento->fotos->count() > 0)
        <div class="mb-4">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Existentes:</span>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($atendimento->fotos as $f)
                <div class="relative">
                    <img src="{{ route('plugin.atendimento.foto.file', $f->id) }}" alt="{{ $f->legenda }}" class="h-20 w-20 rounded-lg border border-gray-200 object-cover dark:border-gray-600">
                    <span class="absolute bottom-0 left-0 right-0 rounded-b-lg bg-black/60 px-1 text-center text-xs text-white">{{ $f->tipo }}@if($f->legenda): {{ Str::limit($f->legenda, 12) }}@endif</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <div id="fotos_container" class="space-y-3">
            <div class="flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-gray-300 p-3 dark:border-gray-600" data-foto-index="0">
                <div class="min-w-[140px]">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nova foto</label>
                    <input type="file" name="fotos[]" accept="image/*" class="w-full text-sm">
                </div>
                <div class="min-w-[120px]">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipo</label>
                    <select name="fotos_tipo[]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                        <option value="evidencia">Evidência</option>
                        <option value="antes">Antes</option>
                        <option value="depois">Depois</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Legenda</label>
                    <input type="text" name="fotos_legenda[]" placeholder="Opcional" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
        </div>
        <button type="button" id="btn_add_foto" class="mt-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">+ Adicionar outra foto</button>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Assinatura do cliente</h2>
        @if($atendimento->assinatura_cliente_path)
        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">Assinatura atual:</p>
        <img src="{{ route('plugin.atendimento.assinatura.file', $atendimento->id) }}" alt="Assinatura" class="mb-3 max-h-24 rounded border border-gray-200 dark:border-gray-600">
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Substituir (opcional): desenhe abaixo ou envie nova imagem.</p>
        @else
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Desenhe a assinatura no quadro abaixo (ou use o campo de arquivo).</p>
        @endif
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Ao salvar uma nova assinatura, a <strong>data e hora</strong> serão registradas automaticamente e o <strong>local</strong> será capturado pelo GPS do dispositivo (permita o acesso à localização quando solicitado).</p>
        <input type="hidden" name="assinatura_local" id="assinatura_local" value="">
        <div class="flex flex-col items-start gap-3">
            <canvas id="assinatura_canvas" width="400" height="180" class="max-w-full rounded-lg border-2 border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-900" style="touch-action: none;"></canvas>
            <div class="flex gap-2">
                <button type="button" id="btn_limpar_assinatura" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Limpar</button>
            </div>
            <input type="hidden" name="assinatura_cliente_base64" id="assinatura_cliente_base64" value="">
            <div class="w-full max-w-xs">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Ou envie imagem da assinatura</label>
                <input type="file" name="assinatura_cliente" accept="image/*" class="w-full text-sm">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Status e prioridade</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fase do atendimento</label>
                <select name="fase" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="rascunho" {{ old('fase', $atendimento->fase) === 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                    <option value="concluido" {{ old('fase', $atendimento->fase) === 'concluido' ? 'selected' : '' }}>Concluído</option>
                </select>
            </div>
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
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar alterações</button>
        <a href="{{ route('plugin.atendimento.show', $atendimento->id) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
    </div>
</form>

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
    toggleEndereco();
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
        if (completoInput) completoInput.value = p.join(', ');
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

    // Checklist
    var checklistModelsData = @json(isset($checklistModels) ? $checklistModels->keyBy('id')->map(fn($m) => ['id' => $m->id, 'nome' => $m->nome, 'campos' => $m->campos ?? []]) : []);
    var checklistRespostas = @json(old('checklist_respostas', $atendimento->checklist_respostas ?? []));
    var checklistSelect = document.getElementById('checklist_model_id');
    var checklistContainer = document.getElementById('checklist_campos_container');
    var checklistRespostasInput = document.getElementById('checklist_respostas_input');
    if (checklistSelect && checklistContainer) {
        function renderChecklistCampos() {
            var id = checklistSelect.value;
            checklistContainer.innerHTML = '';
            if (!id || !checklistModelsData[id]) return;
            var campos = checklistModelsData[id].campos || [];
            campos.forEach(function(campo, i) {
                var div = document.createElement('div');
                div.className = 'rounded border border-gray-200 p-3 dark:border-gray-700';
                var label = document.createElement('label');
                label.className = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
                label.textContent = campo.label + (campo.obrigatorio ? ' *' : '');
                div.appendChild(label);
                var name = 'checklist_respostas_' + i;
                var val = checklistRespostas[i] !== undefined ? checklistRespostas[i] : '';
                if (campo.tipo === 'checkbox') {
                    var chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.name = name;
                    chk.value = '1';
                    chk.className = 'rounded border-gray-300 text-brand-500';
                    if (val === true || val === '1' || val === 1) chk.checked = true;
                    div.appendChild(chk);
                } else if (campo.tipo === 'selecao' && campo.opcoes && campo.opcoes.length) {
                    var sel = document.createElement('select');
                    sel.name = name;
                    sel.className = 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90';
                    var opt0 = document.createElement('option');
                    opt0.value = '';
                    opt0.textContent = '— Selecione —';
                    sel.appendChild(opt0);
                    campo.opcoes.forEach(function(op) {
                        var opt = document.createElement('option');
                        opt.value = op;
                        opt.textContent = op;
                        if (String(val) === String(op)) opt.selected = true;
                        sel.appendChild(opt);
                    });
                    div.appendChild(sel);
                } else {
                    var inp = document.createElement('input');
                    inp.type = campo.tipo === 'numero' ? 'number' : campo.tipo === 'data' ? 'date' : 'text';
                    inp.name = name;
                    inp.value = val;
                    inp.className = 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90';
                    if (campo.obrigatorio) inp.required = true;
                    div.appendChild(inp);
                }
                checklistContainer.appendChild(div);
            });
        }
        checklistSelect.addEventListener('change', renderChecklistCampos);
        renderChecklistCampos();
        document.getElementById('form-atendimento').addEventListener('submit', function() {
            var obj = {};
            checklistContainer.querySelectorAll('[name^="checklist_respostas_"]').forEach(function(el) {
                var i = el.name.replace('checklist_respostas_', '');
                if (el.type === 'checkbox') obj[i] = el.checked ? '1' : '0';
                else obj[i] = el.value;
            });
            if (checklistRespostasInput) checklistRespostasInput.value = JSON.stringify(obj);
        });
    }

    // Fotos: adicionar outra linha
    var fotoIndex = 1;
    document.getElementById('btn_add_foto')?.addEventListener('click', function() {
        var container = document.getElementById('fotos_container');
        var tpl = container.querySelector('[data-foto-index]');
        if (!tpl) return;
        var clone = tpl.cloneNode(true);
        clone.setAttribute('data-foto-index', fotoIndex++);
        clone.querySelector('input[type="file"]').value = '';
        clone.querySelector('input[type="text"]').value = '';
        container.appendChild(clone);
    });

    // Assinatura: canvas -> base64 no submit; captura GPS ao salvar nova assinatura
    var canvas = document.getElementById('assinatura_canvas');
    var ctx = canvas && canvas.getContext('2d');
    var drawing = false;
    var canvasUsed = false;
    if (canvas && ctx) {
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var scaleX = canvas.width / rect.width, scaleY = canvas.height / rect.height;
            var clientX = e.touches ? e.touches[0].clientX : e.clientX;
            var clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
        }
        function start(e) { e.preventDefault(); drawing = true; canvasUsed = true; }
        function move(e) {
            e.preventDefault();
            if (!drawing) return;
            var p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        }
        function end(e) { e.preventDefault(); drawing = false; ctx.beginPath(); }
        canvas.addEventListener('mousedown', function(e) { start(e); var p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', function(e) { start(e); var p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end, { passive: false });
        document.getElementById('btn_limpar_assinatura')?.addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }

    // Submit: preencher base64 da assinatura; se houver nova assinatura, capturar GPS antes de enviar
    var formAtendimento = document.getElementById('form-atendimento');
    var hiddenBase64 = document.getElementById('assinatura_cliente_base64');
    var fileAssinatura = document.querySelector('input[name="assinatura_cliente"]');
    var hiddenLocal = document.getElementById('assinatura_local');
    formAtendimento.addEventListener('submit', function(e) {
        if (hiddenBase64 && (!fileAssinatura || !fileAssinatura.files.length) && canvas && ctx) {
            try { hiddenBase64.value = canvas.toDataURL('image/png'); } catch (err) {}
        }
        var hasNewSignature = (canvasUsed && canvas && ctx && hiddenBase64 && hiddenBase64.value) || (fileAssinatura && fileAssinatura.files && fileAssinatura.files.length > 0);
        if (!hasNewSignature) return;
        e.preventDefault();
        if (!navigator.geolocation) {
            if (hiddenLocal) hiddenLocal.value = 'GPS não disponível';
            formAtendimento.submit();
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lon + '&format=json&accept-language=pt-BR', {
                    headers: { 'Accept': 'application/json', 'User-Agent': 'ERP-Atendimento-Plugin/1.0' }
                })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (hiddenLocal) hiddenLocal.value = (data.display_name || (lat + ', ' + lon)).substring(0, 255);
                        formAtendimento.submit();
                    })
                    .catch(function() {
                        if (hiddenLocal) hiddenLocal.value = (lat + ', ' + lon).substring(0, 255);
                        formAtendimento.submit();
                    });
            },
            function() {
                if (hiddenLocal) hiddenLocal.value = '';
                formAtendimento.submit();
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });
});
</script>
@endsection
