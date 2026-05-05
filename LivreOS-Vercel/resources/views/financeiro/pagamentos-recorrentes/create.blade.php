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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Pagamento Recorrente</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre um recebimento que se repete (ex.: aluguel, assinatura). As contas a receber serão geradas automaticamente.</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.pagamentos-recorrentes.store') }}" method="POST" class="p-6">
        @csrf
        @if(request('voltar'))<input type="hidden" name="voltar" value="{{ request('voltar') }}">@endif
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                @include('partials.cliente-autocomplete-field', [
                    'prefix' => 'pagamento_recorrente',
                    'clientes' => $clientes,
                    'selectedId' => old('cliente_id'),
                    'label' => 'Cliente',
                ])
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="{{ old('descricao') }}" required placeholder="Ex.: Aluguel mensal" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('descricao')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo / Finalidade</label>
                <select name="tipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Selecione —</option>
                    <option value="aluguel" {{ old('tipo') === 'aluguel' ? 'selected' : '' }}>Aluguel</option>
                    <option value="contrato" {{ old('tipo') === 'contrato' ? 'selected' : '' }}>Contrato</option>
                    <option value="assinatura" {{ old('tipo') === 'assinatura' ? 'selected' : '' }}>Assinatura</option>
                    <option value="manutencao" {{ old('tipo') === 'manutencao' ? 'selected' : '' }}>Manutenção</option>
                    <option value="outro" {{ old('tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Para filtros e relatórios (opcional)</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @error('valor')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência *</label>
                <select name="frequencia" id="frequencia" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="mensal" {{ old('frequencia', 'mensal') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                    <option value="diaria" {{ old('frequencia') === 'diaria' ? 'selected' : '' }}>Diária</option>
                    <option value="semanal" {{ old('frequencia') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                    <option value="quinzenal" {{ old('frequencia') === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                    <option value="bimestral" {{ old('frequencia') === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                    <option value="trimestral" {{ old('frequencia') === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                    <option value="semestral" {{ old('frequencia') === 'semestral' ? 'selected' : '' }}>Semestral</option>
                    <option value="anual" {{ old('frequencia') === 'anual' ? 'selected' : '' }}>Anual</option>
                </select>
            </div>
            <div id="gerar_ultimo_dia_wrap" class="{{ old('frequencia', 'mensal') !== 'mensal' ? 'hidden' : '' }}">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento mensal</label>
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="gerar_ultimo_dia_mes" id="gerar_ultimo_dia_mes" value="1" {{ old('gerar_ultimo_dia_mes') ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label for="gerar_ultimo_dia_mes" class="text-sm text-gray-700 dark:text-gray-300">Sempre no último dia do mês</label>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data início *</label>
                <input type="date" name="data_inicio" value="{{ old('data_inicio', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Primeira data de vencimento a ser gerada</p>
                @error('data_inicio')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data fim (opcional)</label>
                <input type="date" name="data_fim" value="{{ old('data_fim') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Deixe em branco para sem data fim</p>
                @error('data_fim')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de pagamento</label>
                <select name="forma_pagamento_id" id="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($formasPagamento as $f)
                        <option value="{{ $f->id }}" data-conta-bancaria-id="{{ $f->conta_bancaria_id ?? '' }}" {{ old('forma_pagamento_id') == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta bancária</label>
                <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($contasBancarias as $cb)
                        <option value="{{ $cb->id }}" {{ old('conta_bancaria_id') == $cb->id ? 'selected' : '' }}>{{ $cb->nome }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Preenchida pela forma de pagamento; você pode alterar.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de contas</label>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($planoContas as $pc)
                        <option value="{{ $pc->id }}" {{ old('plano_conta_id') == $pc->id ? 'selected' : '' }}>{{ $pc->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="ativo" id="ativo" value="1" {{ old('ativo', true) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo (gerar contas automaticamente)</label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes') }}</textarea>
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-800 dark:bg-blue-900/10">
            <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Campos de controle (integrações/plugins)</h3>
            <p class="mb-4 text-xs text-blue-800 dark:text-blue-200">
                Campos opcionais para vincular este recorrente a entidades externas (ex.: contratos de locação em plugin),
                sem acoplamento direto no core.
            </p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Origem da integração</label>
                    <input type="text" name="controle_origem" maxlength="60" value="{{ old('controle_origem') }}"
                           placeholder="Ex.: plugin:locacao"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('controle_origem')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo da entidade</label>
                    <input type="text" name="controle_entidade_tipo" maxlength="80" value="{{ old('controle_entidade_tipo') }}"
                           placeholder="Ex.: contrato_locacao"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('controle_entidade_tipo')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">ID da entidade externa</label>
                    <input type="text" name="controle_entidade_id" maxlength="120" value="{{ old('controle_entidade_id') }}"
                           placeholder="Ex.: 12345 ou UUID"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('controle_entidade_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Referência adicional</label>
                    <input type="text" name="controle_referencia" maxlength="120" value="{{ old('controle_referencia') }}"
                           placeholder="Ex.: LOC-2026-0001"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @error('controle_referencia')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Reajuste: integração futura com módulo de contrato --}}
        <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-800 dark:bg-amber-900/10">
            <div class="mb-3 flex items-center gap-2">
                <input type="checkbox" name="reajuste_habilitado" id="reajuste_habilitado" value="1" {{ old('reajuste_habilitado') ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label for="reajuste_habilitado" class="text-sm font-medium text-gray-800 dark:text-gray-200">Configurar reajuste (para uso com módulo de contrato)</label>
            </div>
            <p class="mb-4 text-xs text-amber-800 dark:text-amber-200">Permite atualizar o valor após X períodos, por índice (IGPM, IPCA, etc.) ou percentual manual, com opção de desconto sobre o reajuste. A aplicação do reajuste será feita pelo módulo de contrato quando disponível.</p>
            <div id="reajuste_campos" class="{{ old('reajuste_habilitado') ? '' : 'hidden' }} grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Reajustar a cada (meses)</label>
                    <input type="number" name="reajuste_periodo_meses" value="{{ old('reajuste_periodo_meses') }}" min="1" max="120" placeholder="Ex.: 12 (anual)" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo de reajuste</label>
                    <select name="reajuste_tipo" id="reajuste_tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach(\App\Models\PagamentoRecorrente::reajusteTipos() as $v => $l)
                            <option value="{{ $v }}" {{ old('reajuste_tipo', 'nenhum') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="reajuste_indice_wrap" class="{{ old('reajuste_tipo') === 'indice' ? '' : 'hidden' }}">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Índice</label>
                    <select name="reajuste_indice" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach(\App\Models\PagamentoRecorrente::reajusteIndices() as $v => $l)
                            <option value="{{ $v }}" {{ old('reajuste_indice') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="reajuste_manual_wrap" class="{{ old('reajuste_tipo') === 'manual' ? '' : 'hidden' }}">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Percentual manual (%)</label>
                    <input type="number" step="0.01" name="reajuste_manual_pct" value="{{ old('reajuste_manual_pct') }}" min="0" max="999.99" placeholder="Ex.: 5,5" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Desconto sobre o reajuste (%)</label>
                    <input type="number" step="0.01" name="reajuste_desconto_pct" value="{{ old('reajuste_desconto_pct') }}" min="0" max="100" placeholder="Ex.: 10 = aplicar 90% do índice" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ request('voltar', route('financeiro.pagamentos-recorrentes.index')) }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<script>
document.getElementById('frequencia').addEventListener('change', function() {
    document.getElementById('gerar_ultimo_dia_wrap').classList.toggle('hidden', this.value !== 'mensal');
});
document.getElementById('reajuste_habilitado').addEventListener('change', function() {
    document.getElementById('reajuste_campos').classList.toggle('hidden', !this.checked);
});
document.getElementById('reajuste_tipo').addEventListener('change', function() {
    document.getElementById('reajuste_indice_wrap').classList.toggle('hidden', this.value !== 'indice');
    document.getElementById('reajuste_manual_wrap').classList.toggle('hidden', this.value !== 'manual');
});
// Ao selecionar forma de pagamento, preenche a conta bancária padrão (usuário pode alterar)
function aplicarContaPadraoFormaPagamento() {
    var formaSelect = document.getElementById('forma_pagamento_id');
    var contaSelect = document.getElementById('conta_bancaria_id');
    if (!formaSelect || !contaSelect) return;
    var opt = formaSelect.options[formaSelect.selectedIndex];
    var contaId = opt ? opt.getAttribute('data-conta-bancaria-id') : null;
    if (contaId && contaSelect.querySelector('option[value="' + contaId + '"]')) {
        contaSelect.value = contaId;
    }
}
document.getElementById('forma_pagamento_id').addEventListener('change', aplicarContaPadraoFormaPagamento);
document.addEventListener('DOMContentLoaded', function() {
    var contaSelect = document.getElementById('conta_bancaria_id');
    if (contaSelect && !contaSelect.value) aplicarContaPadraoFormaPagamento();
});
</script>
@endsection
