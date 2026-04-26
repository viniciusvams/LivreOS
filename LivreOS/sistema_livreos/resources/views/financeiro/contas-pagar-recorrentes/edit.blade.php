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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar despesa recorrente</h1>
    <p class="text-gray-600 dark:text-gray-400">Altere os dados da despesa recorrente</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">{{ session('success') }}</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.contas-pagar-recorrentes.update', $recorrente) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        @if(request('voltar'))<input type="hidden" name="voltar" value="{{ request('voltar') }}">@endif
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor (opcional)</label>
                <select name="fornecedor_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Nenhum —</option>
                    @foreach($fornecedores as $f)
                        <option value="{{ $f->id }}" {{ old('fornecedor_id', $recorrente->fornecedor_id) == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao', $recorrente->descricao) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('descricao')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Selecione —</option>
                    <option value="agua" {{ old('tipo', $recorrente->tipo) === 'agua' ? 'selected' : '' }}>Água</option>
                    <option value="luz" {{ old('tipo', $recorrente->tipo) === 'luz' ? 'selected' : '' }}>Luz</option>
                    <option value="gas" {{ old('tipo', $recorrente->tipo) === 'gas' ? 'selected' : '' }}>Gás</option>
                    <option value="aluguel" {{ old('tipo', $recorrente->tipo) === 'aluguel' ? 'selected' : '' }}>Aluguel</option>
                    <option value="condominio" {{ old('tipo', $recorrente->tipo) === 'condominio' ? 'selected' : '' }}>Condomínio</option>
                    <option value="telefone" {{ old('tipo', $recorrente->tipo) === 'telefone' ? 'selected' : '' }}>Telefone</option>
                    <option value="internet" {{ old('tipo', $recorrente->tipo) === 'internet' ? 'selected' : '' }}>Internet</option>
                    <option value="outro" {{ old('tipo', $recorrente->tipo) === 'outro' ? 'selected' : '' }}>Outro</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                <input type="number" step="0.01" name="valor" value="{{ old('valor', $recorrente->valor) }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('valor')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência *</label>
                <select name="frequencia" id="frequencia" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="mensal" {{ old('frequencia', $recorrente->frequencia) === 'mensal' ? 'selected' : '' }}>Mensal</option>
                    <option value="diaria" {{ old('frequencia', $recorrente->frequencia) === 'diaria' ? 'selected' : '' }}>Diária</option>
                    <option value="semanal" {{ old('frequencia', $recorrente->frequencia) === 'semanal' ? 'selected' : '' }}>Semanal</option>
                    <option value="quinzenal" {{ old('frequencia', $recorrente->frequencia) === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                    <option value="bimestral" {{ old('frequencia', $recorrente->frequencia) === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                    <option value="trimestral" {{ old('frequencia', $recorrente->frequencia) === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                    <option value="semestral" {{ old('frequencia', $recorrente->frequencia) === 'semestral' ? 'selected' : '' }}>Semestral</option>
                    <option value="anual" {{ old('frequencia', $recorrente->frequencia) === 'anual' ? 'selected' : '' }}>Anual</option>
                </select>
            </div>
            <div id="gerar_ultimo_dia_wrap" class="{{ old('frequencia', $recorrente->frequencia) !== 'mensal' ? 'hidden' : '' }}">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento mensal</label>
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="gerar_ultimo_dia_mes" id="gerar_ultimo_dia_mes" value="1" {{ old('gerar_ultimo_dia_mes', $recorrente->gerar_ultimo_dia_mes) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label for="gerar_ultimo_dia_mes" class="text-sm text-gray-700 dark:text-gray-300">Sempre no último dia do mês</label>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data início *</label>
                <input type="date" name="data_inicio" value="{{ old('data_inicio', $recorrente->data_inicio->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('data_inicio')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data fim (opcional)</label>
                <input type="date" name="data_fim" value="{{ old('data_fim', $recorrente->data_fim?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('data_fim')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Próxima geração em</label>
                <input type="text" value="{{ $recorrente->proxima_geracao_em->format('d/m/Y') }}" readonly class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de pagamento</label>
                <select name="forma_pagamento_id" id="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($formasPagamento as $f)
                        <option value="{{ $f->id }}" data-conta-bancaria-id="{{ $f->conta_bancaria_id ?? '' }}" {{ old('forma_pagamento_id', $recorrente->forma_pagamento_id) == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta bancária</label>
                <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias as $cb)
                        <option value="{{ $cb->id }}" {{ old('conta_bancaria_id', $recorrente->conta_bancaria_id) == $cb->id ? 'selected' : '' }}>{{ $cb->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de contas</label>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($planoContas as $pc)
                        <option value="{{ $pc->id }}" {{ old('plano_conta_id', $recorrente->plano_conta_id) == $pc->id ? 'selected' : '' }}>{{ $pc->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', $recorrente->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo (gerar contas automaticamente)</label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $recorrente->observacoes) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes'])) }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        </div>
    </form>
</div>
<script>
document.getElementById('frequencia').addEventListener('change', function() {
    document.getElementById('gerar_ultimo_dia_wrap').classList.toggle('hidden', this.value !== 'mensal');
});
document.getElementById('forma_pagamento_id').addEventListener('change', function() {
    var formaSelect = document.getElementById('forma_pagamento_id');
    var contaSelect = document.getElementById('conta_bancaria_id');
    if (!formaSelect || !contaSelect) return;
    var opt = formaSelect.options[formaSelect.selectedIndex];
    var contaId = opt ? opt.getAttribute('data-conta-bancaria-id') : null;
    if (contaId && contaSelect.querySelector('option[value="' + contaId + '"]')) contaSelect.value = contaId;
});
</script>
@endsection
