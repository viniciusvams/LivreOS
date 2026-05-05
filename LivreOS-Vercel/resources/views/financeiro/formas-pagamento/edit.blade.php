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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Forma de Pagamento</h1>
    <p class="text-gray-600 dark:text-gray-400">Atualize as informações da forma de pagamento</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.formas-pagamento.update', $forma) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome', $forma->nome) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                <select name="tipo" id="forma_tipo" required onchange="toggleAdquirentes()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="pix" {{ old('tipo', $forma->tipo) === 'pix' ? 'selected' : '' }}>Pix</option>
                    <option value="boleto" {{ old('tipo', $forma->tipo) === 'boleto' ? 'selected' : '' }}>Boleto</option>
                    <option value="cartao_credito" {{ old('tipo', $forma->tipo) === 'cartao_credito' ? 'selected' : '' }}>Cartão de Crédito</option>
                    <option value="cartao_debito" {{ old('tipo', $forma->tipo) === 'cartao_debito' ? 'selected' : '' }}>Cartão de Débito</option>
                    <option value="dinheiro" {{ old('tipo', $forma->tipo) === 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                    <option value="transferencia" {{ old('tipo', $forma->tipo) === 'transferencia' ? 'selected' : '' }}>Transferência</option>
                    <option value="cheque" {{ old('tipo', $forma->tipo) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Percentual (%)</label>
                <input type="number" step="0.01" name="taxa_percentual" value="{{ old('taxa_percentual', $forma->taxa_percentual) }}" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Fixa (R$)</label>
                <input type="number" step="0.01" name="taxa_fixa" value="{{ old('taxa_fixa', $forma->taxa_fixa) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias para Recebimento</label>
                <input type="number" name="dias_recebimento" value="{{ old('dias_recebimento', $forma->dias_recebimento) }}" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de Parcelas</label>
                <input type="number" name="max_parcelas" value="{{ old('max_parcelas', $forma->max_parcelas) }}" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária Padrão</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias ?? [] as $contaBancaria)
                        <option value="{{ $contaBancaria->id }}" {{ old('conta_bancaria_id', $forma->conta_bancaria_id) == $contaBancaria->id ? 'selected' : '' }}>{{ $contaBancaria->nome }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Conta onde os pagamentos desta forma serão creditados por padrão</p>
            </div>

            <div id="pix_chaves_wrap" class="{{ old('tipo', $forma->tipo) === 'pix' ? '' : 'hidden' }} md:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Chaves PIX de recebimento</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Cada chave pode ter conta bancária e taxa própria.</p>
                        </div>
                        <button type="button" onclick="addPixChaveRow()" class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white hover:bg-brand-600">Adicionar chave</button>
                    </div>
                    <div id="pix_chaves_container" class="space-y-3"></div>
                </div>
            </div>

            <div id="adquirentes_wrap" class="md:col-span-2 {{ in_array(old('tipo', $forma->tipo), ['cartao_credito', 'cartao_debito']) ? '' : 'hidden' }}">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Adquirentes (taxas por bandeira)</h3>
                    <p class="mb-3 text-xs text-gray-600 dark:text-gray-400">Para cartão de crédito e débito, as taxas são definidas por adquirente e bandeira em <strong>Financeiro &gt; Adquirentes &gt; Gerenciar taxas</strong>. Associe aqui quais adquirentes podem ser usados com esta forma de pagamento.</p>
                    <div class="space-y-2">
                        @foreach($adquirentes ?? [] as $adq)
                            @php
                                $adquirentesIds = old('adquirente_ids', $forma->adquirentes->pluck('id')->toArray());
                                $pivot = $forma->adquirentes->firstWhere('id', $adq->id)?->pivot;
                                $isPadrao = ($pivot && $pivot->padrao) || (old('adquirente_padrao_id') && old('adquirente_padrao_id') == $adq->id);
                            @endphp
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="adquirente_ids[]" value="{{ $adq->id }}" {{ in_array($adq->id, $adquirentesIds) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $adq->nome }}</span>
                                <input type="radio" name="adquirente_padrao_id" value="{{ $adq->id }}" {{ $isPadrao ? 'checked' : '' }} class="adquirente-padrao-radio rounded border-gray-300 text-brand-500 focus:ring-brand-500" title="Adquirente padrão">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Padrão</span>
                            </label>
                        @endforeach
                    </div>
                    @if(empty($adquirentes))
                        <p class="text-xs text-amber-600 dark:text-amber-400">Nenhuma adquirente ativa. Cadastre em Financeiro &gt; Adquirentes.</p>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $forma->observacoes) }}</textarea>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $forma->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Forma de pagamento ativa</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="permite_parcela" value="1" {{ old('permite_parcela', $forma->permite_parcela) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Permite parcelamento</span>
                </label>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.formas-pagamento.form.extra', $forma); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.formas-pagamento.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<script>
let pixChaveIndex = 0;
const pixChavesInit = @json(old('pix_chaves', $forma->pix_chaves ?? []));

function addPixChaveRow(data = {}) {
    const container = document.getElementById('pix_chaves_container');
    if (!container) return;
    const idx = pixChaveIndex++;
    const html = `
        <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900 md:grid-cols-6">
            <input type="hidden" name="pix_chaves[${idx}][id]" value="${data.id || ''}">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Nome</label>
                <input type="text" name="pix_chaves[${idx}][nome]" value="${data.nome || ''}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Chave PIX</label>
                <input type="text" name="pix_chaves[${idx}][chave]" value="${data.chave || ''}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Conta bancária</label>
                <select name="pix_chaves[${idx}][conta_bancaria_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias ?? [] as $conta)
                    <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Tipo da chave</label>
                <select name="pix_chaves[${idx}][tipo]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="cpf">CPF</option>
                    <option value="cnpj">CNPJ</option>
                    <option value="email">E-mail</option>
                    <option value="telefone">Telefone</option>
                    <option value="aleatoria">Aleatória</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Taxa %</label>
                <input type="number" step="0.01" min="0" max="100" name="pix_chaves[${idx}][taxa_percentual]" value="${data.taxa_percentual ?? 0}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-600 dark:text-gray-400">Taxa fixa R$</label>
                <input type="number" step="0.01" min="0" name="pix_chaves[${idx}][taxa_fixa]" value="${data.taxa_fixa ?? 0}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="flex items-end gap-3">
                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="pix_chaves[${idx}][ativo]" value="1" checked class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    Ativa
                </label>
                <button type="button" onclick="this.closest('.grid').remove()" class="rounded-lg border border-error-300 px-3 py-2 text-xs text-error-600 hover:bg-error-50">Remover</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    const row = container.lastElementChild;
    if (row && data.conta_bancaria_id) row.querySelector('select[name$="[conta_bancaria_id]"]').value = String(data.conta_bancaria_id);
    if (row && data.tipo) row.querySelector('select[name$="[tipo]"]').value = String(data.tipo);
    if (row && data.ativo === false) row.querySelector('input[name$="[ativo]"]').checked = false;
}

function toggleAdquirentes() {
    const tipo = document.getElementById('forma_tipo').value;
    const wrap = document.getElementById('adquirentes_wrap');
    const pixWrap = document.getElementById('pix_chaves_wrap');
    if (tipo === 'cartao_credito' || tipo === 'cartao_debito') {
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
    }
    if (pixWrap) {
        if (tipo === 'pix') {
            pixWrap.classList.remove('hidden');
            if (!document.getElementById('pix_chaves_container').children.length) {
                if (Array.isArray(pixChavesInit) && pixChavesInit.length) {
                    pixChavesInit.forEach(addPixChaveRow);
                } else {
                    addPixChaveRow();
                }
            }
        } else {
            pixWrap.classList.add('hidden');
        }
    }
}
document.addEventListener('DOMContentLoaded', toggleAdquirentes);
</script>
@endsection
