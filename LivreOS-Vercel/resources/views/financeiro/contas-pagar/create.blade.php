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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Conta a Pagar</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova conta a pagar</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.contas-pagar.store') }}" method="POST" class="p-6" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor / Cliente</label>
                <input
                    type="text"
                    id="conta_pagar_fornecedor_busca"
                    list="conta_pagar_fornecedores_datalist"
                    placeholder="Digite nome, razão social, CPF ou CNPJ..."
                    class="mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    autocomplete="off"
                >
                <datalist id="conta_pagar_fornecedores_datalist">
                    @foreach($fornecedores as $fornecedor)
                        @php
                            $nomeF = $fornecedor->nome ?? $fornecedor->razao_social ?? ('#' . $fornecedor->id);
                            $docF = $fornecedor->cnpj ?? $fornecedor->cpf ?? '';
                            $rotuloF = trim($nomeF . ($fornecedor->razao_social ? ' - ' . $fornecedor->razao_social : '') . ($docF ? ' - ' . $docF : ''));
                        @endphp
                        <option value="{{ $rotuloF }}"></option>
                    @endforeach
                </datalist>
                <select name="fornecedor_id" id="conta_pagar_fornecedor_id" class="hidden">
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
                        <option value="{{ $fornecedor->id }}" data-label="{{ $rotuloF }}" data-search="{{ $buscaF }}" {{ old('fornecedor_id') == $fornecedor->id ? 'selected' : '' }}>{{ $rotuloF }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de Serviço</label>
                <select name="ordem_servico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhuma</option>
                    @foreach($ordensServico as $os)
                        <option value="{{ $os->id }}" {{ old('ordem_servico_id') == $os->id ? 'selected' : '' }}>{{ $os->codigo_interno }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do Documento</label>
                <input type="text" name="numero_documento" value="{{ old('numero_documento') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                <select name="tipo" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="operacional" {{ old('tipo') === 'operacional' ? 'selected' : '' }}>Operacional</option>
                    <option value="insumo" {{ old('tipo') === 'insumo' ? 'selected' : '' }}>Insumo</option>
                    <option value="outro" {{ old('tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento *</label>
                <input type="date" name="data_vencimento" value="{{ old('data_vencimento', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                <select name="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($formasPagamento as $forma)
                        <option value="{{ $forma->id }}" {{ old('forma_pagamento_id') == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias as $conta)
                        <option value="{{ $conta->id }}" {{ old('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de Contas</label>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($planoContas as $conta)
                        <option value="{{ $conta->id }}" {{ old('plano_conta_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
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

            @include('financeiro.partials.categoria-tags-titulo', [
                'escopoCategoria' => 'pagar',
                'tagEscopo' => 'conta_pagar',
                'categoriaOpcoes' => $categoriaFinanceiraOpcoes ?? [],
                'selectedCategoriaId' => old('categoria_financeira_id'),
                'selectedTags' => $tagsFormulario ?? collect(),
            ])

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anexos (opcional)</label>
                <input type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione um ou mais arquivos (máx. 10 MB cada).</p>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.contas-pagar.form.extra', null); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fornecedorSelect = document.getElementById('conta_pagar_fornecedor_id');
    const fornecedorBusca = document.getElementById('conta_pagar_fornecedor_busca');
    if (!fornecedorSelect || !fornecedorBusca) return;

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
});
</script>
@endsection
