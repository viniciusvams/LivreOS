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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Movimentação (Ajuste)</h1>
    <p class="text-gray-600 dark:text-gray-400">Lançamento manual de entrada ou saída</p>
</div>

<div class="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.movimentacoes.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária *</label>
                <select name="conta_bancaria_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias as $conta)
                        <option value="{{ $conta->id }}" {{ old('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
                @error('conta_bancaria_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                <select name="tipo" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="entrada" {{ old('tipo', 'entrada') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                    <option value="saida" {{ old('tipo') === 'saida' ? 'selected' : '' }}>Saída</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('valor')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Movimentação *</label>
                <input type="date" name="data_movimentacao" value="{{ old('data_movimentacao', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('data_movimentacao')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao') }}" required maxlength="255" placeholder="Ex: Ajuste de saldo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('descricao')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de Contas *</label>
                <select name="plano_conta_id" id="plano_conta_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione o tipo acima primeiro...</option>
                    @foreach($planoContas ?? [] as $pc)
                        <option value="{{ $pc->id }}" data-tipo="{{ $pc->tipo }}" {{ old('plano_conta_id') == $pc->id ? 'selected' : '' }}>{{ $pc->tipo === 'receita' ? 'Receita' : 'Despesa' }}: {{ $pc->nome }}</option>
                    @endforeach
                </select>
                @error('plano_conta_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Para entrada use uma conta de receita; para saída use uma conta de despesa.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo</label>
                <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhum</option>
                    @foreach($centrosCusto ?? [] as $cc)
                        <option value="{{ $cc->id }}" {{ old('centro_custo_id') == $cc->id ? 'selected' : '' }}>{{ $cc->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <a href="{{ route('financeiro.movimentacoes.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<script>
(function() {
    var tipoSelect = document.querySelector('select[name="tipo"]');
    var planoSelect = document.getElementById('plano_conta_id');
    if (!tipoSelect || !planoSelect) return;
    function filtrarPlanos() {
        var tipo = tipoSelect.value;
        var tipoPlano = tipo === 'entrada' ? 'receita' : 'despesa';
        var opts = planoSelect.querySelectorAll('option[data-tipo]');
        var hasSelected = false;
        opts.forEach(function(opt) {
            var show = opt.getAttribute('data-tipo') === tipoPlano;
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
            if (show && opt.selected) hasSelected = true;
        });
        if (!hasSelected) planoSelect.value = '';
    }
    tipoSelect.addEventListener('change', filtrarPlanos);
    filtrarPlanos();
})();
</script>
@endsection
