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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Conta a Receber</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova conta a receber</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.contas-receber.store') }}" method="POST" class="p-6" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                @include('partials.cliente-autocomplete-field', [
                    'prefix' => 'conta_receber',
                    'clientes' => $clientes,
                    'selectedId' => old('cliente_id'),
                    'label' => 'Cliente',
                ])
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de Serviço</label>
                <select name="ordem_servico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhuma</option>
                    @foreach($ordensServico as $os)
                        <option value="{{ $os->id }}" {{ old('ordem_servico_id') == $os->id ? 'selected' : '' }}>{{ $os->codigo_interno }} - R$ {{ number_format($os->total_geral, 2, ',', '.') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('descricao')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total *</label>
                <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('valor')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento *</label>
                <input type="date" name="data_vencimento" value="{{ old('data_vencimento', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('data_vencimento')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Parcelas</label>
                <input type="number" name="numero_parcelas" value="{{ old('numero_parcelas', 1) }}" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias entre Parcelas</label>
                <input type="number" name="dias_entre_parcelas" value="{{ old('dias_entre_parcelas', 30) }}" min="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                <select name="forma_pagamento_id" id="forma_pagamento_id" onchange="atualizarContaBancaria()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($formasPagamento as $forma)
                        <option value="{{ $forma->id }}" data-tipo="{{ $forma->tipo }}" data-conta-bancaria-id="{{ $forma->conta_bancaria_id ?? '' }}" {{ old('forma_pagamento_id') == $forma->id ? 'selected' : '' }}>{{ $forma->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias as $conta)
                        <option value="{{ $conta->id }}" {{ old('conta_bancaria_id') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se não informada, será usada a conta padrão da forma de pagamento selecionada</p>
            </div>

            @include('financeiro.partials.conta-receber-cartao-bloco', [
                'prefix' => 'cr_nova',
                'bandeirasCartao' => $bandeirasCartao,
                'formasCartaoMeta' => $formasCartaoMeta,
                'cfg' => [
                    'formaSel' => '#forma_pagamento_id',
                    'valorSel' => 'input[name="valor"]',
                    'parcelasSel' => 'input[name="numero_parcelas"]',
                    'dataRefSel' => 'input[name="data_vencimento"]',
                    'contaBancariaSel' => '#conta_bancaria_id',
                    'defaultAdquirenteId' => old('adquirente_id'),
                    'defaultBandeira' => old('bandeira', 'master'),
                ],
            ])

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
                'escopoCategoria' => 'receber',
                'tagEscopo' => 'conta_receber',
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
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione um ou mais arquivos (máx. 10 MB cada). Em parcelas múltiplas, anexos não são salvos.</p>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.contas-receber.form.extra', null); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.contas-receber.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<script>
function atualizarContaBancaria() {
    const formaPagamentoSelect = document.getElementById('forma_pagamento_id');
    const contaBancariaSelect = document.getElementById('conta_bancaria_id');
    const selectedOption = formaPagamentoSelect.options[formaPagamentoSelect.selectedIndex];
    const contaBancariaId = selectedOption.getAttribute('data-conta-bancaria-id');
    
    // Só atualizar se a conta bancária não estiver preenchida manualmente
    if (contaBancariaId && !contaBancariaSelect.value) {
        contaBancariaSelect.value = contaBancariaId;
    }
}

// Executar ao carregar a página se já houver uma forma de pagamento selecionada
document.addEventListener('DOMContentLoaded', function() {
    atualizarContaBancaria();
});
</script>
@endsection
