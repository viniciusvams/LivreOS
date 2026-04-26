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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Taxa</h1>
    <p class="text-gray-600 dark:text-gray-400">Atualize as informações da taxa</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.taxas.update', [$adquirente, $taxa]) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bandeira *</label>
                <select name="bandeira" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="master" {{ old('bandeira', $taxa->bandeira) === 'master' ? 'selected' : '' }}>Mastercard</option>
                    <option value="visa" {{ old('bandeira', $taxa->bandeira) === 'visa' ? 'selected' : '' }}>Visa</option>
                    <option value="elo" {{ old('bandeira', $taxa->bandeira) === 'elo' ? 'selected' : '' }}>Elo</option>
                    <option value="amex" {{ old('bandeira', $taxa->bandeira) === 'amex' ? 'selected' : '' }}>American Express</option>
                    <option value="hipercard" {{ old('bandeira', $taxa->bandeira) === 'hipercard' ? 'selected' : '' }}>Hipercard</option>
                    <option value="outros" {{ old('bandeira', $taxa->bandeira) === 'outros' ? 'selected' : '' }}>Outros</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modalidade *</label>
                <select name="modalidade" id="modalidade" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="debito" {{ old('modalidade', $taxa->modalidade) === 'debito' ? 'selected' : '' }}>Débito</option>
                    <option value="credito_vista" {{ old('modalidade', $taxa->modalidade) === 'credito_vista' ? 'selected' : '' }}>Crédito à Vista</option>
                    <option value="credito_parcelado" {{ old('modalidade', $taxa->modalidade) === 'credito_parcelado' ? 'selected' : '' }}>Crédito Parcelado</option>
                </select>
            </div>

            <div id="parcelas-container" class="md:col-span-2 {{ old('modalidade', $taxa->modalidade) === 'credito_parcelado' ? '' : 'hidden' }}">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Parcelas Mínimas</label>
                        <input type="number" name="parcelas_min" value="{{ old('parcelas_min', $taxa->parcelas_min) }}" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Parcelas Máximas</label>
                        <input type="number" name="parcelas_max" value="{{ old('parcelas_max', $taxa->parcelas_max) }}" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Percentual (%) *</label>
                <input type="number" step="0.01" name="taxa_percentual" value="{{ old('taxa_percentual', $taxa->taxa_percentual !== null ? (float) $taxa->taxa_percentual : '') }}" min="0" max="100" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa por Parcela (%)</label>
                <input type="number" step="0.01" name="taxa_por_parcela" value="{{ old('taxa_por_parcela', $taxa->taxa_por_parcela !== null ? (float) $taxa->taxa_por_parcela : '') }}" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Fixa (R$)</label>
                <input type="number" step="0.01" name="taxa_fixa" value="{{ old('taxa_fixa', $taxa->taxa_fixa !== null ? (float) $taxa->taxa_fixa : '') }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias Recebimento Débito</label>
                <input type="number" name="dias_recebimento_debito" value="{{ old('dias_recebimento_debito', $taxa->dias_recebimento_debito) }}" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias Recebimento Crédito à Vista</label>
                <input type="number" name="dias_recebimento_credito_vista" value="{{ old('dias_recebimento_credito_vista', $taxa->dias_recebimento_credito_vista) }}" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias entre Parcelas</label>
                <input type="number" name="dias_recebimento_parcela" value="{{ old('dias_recebimento_parcela', $taxa->dias_recebimento_parcela) }}" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                <input type="date" name="data_inicio" value="{{ old('data_inicio', $taxa->data_inicio?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
                <input type="date" name="data_fim" value="{{ old('data_fim', $taxa->data_fim?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $taxa->observacoes) }}</textarea>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $taxa->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Taxa ativa</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="usa_antecipacao" id="usa_antecipacao" value="1" {{ old('usa_antecipacao', $taxa->usa_antecipacao) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Usa antecipação</span>
                </label>
            </div>

            <div id="taxa_antecipacao_container" class="md:col-span-2 {{ old('usa_antecipacao', $taxa->usa_antecipacao) ? '' : 'hidden' }}">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa de Antecipação (%)</label>
                <input type="number" step="0.01" name="taxa_antecipacao_percentual" value="{{ old('taxa_antecipacao_percentual', $taxa->taxa_antecipacao_percentual !== null ? (float) $taxa->taxa_antecipacao_percentual : '') }}" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.taxas-adquirente.form.extra', $taxa); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.taxas.index', $adquirente) }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <a href="{{ route('financeiro.adquirentes.edit', $adquirente) }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar para adquirente</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalidade = document.getElementById('modalidade');
    const parcelasContainer = document.getElementById('parcelas-container');
    const usaAntecipacao = document.getElementById('usa_antecipacao');
    const taxaAntecipacaoContainer = document.getElementById('taxa_antecipacao_container');

    modalidade.addEventListener('change', function() {
        if (this.value === 'credito_parcelado') {
            parcelasContainer.classList.remove('hidden');
        } else {
            parcelasContainer.classList.add('hidden');
        }
    });

    usaAntecipacao.addEventListener('change', function() {
        if (this.checked) {
            taxaAntecipacaoContainer.classList.remove('hidden');
        } else {
            taxaAntecipacaoContainer.classList.add('hidden');
        }
    });
});
</script>
@endsection
