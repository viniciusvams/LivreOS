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
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('configuracoes-sistema.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Configurações — Pedidos de Venda</h1>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('configuracoes-sistema.pedidos-venda.update') }}">
    @csrf @method('PUT')

    <div class="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">

        {{-- Numeração dos pedidos --}}
        <div class="mb-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Numeração dos pedidos</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Defina máscara, quantidade de dígitos e o próximo número, no mesmo conceito da <a href="{{ route('configuracoes-sistema.numeracao-os') }}" class="text-brand-600 hover:underline dark:text-brand-400">numeração de OS</a>. O código final fica em até <strong>30 caracteres</strong> (único no sistema).</p>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Último pedido (mais recente)</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $ultimo_codigo_recente ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Maior índice na sequência</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ ($maior_indice_numerico ?? 0) > 0 ? $maior_indice_numerico : 'Nenhum' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo natural</p>
                    <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $proximo_natural ?? 1 }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo efetivo</p>
                    <p class="mt-1 text-lg font-semibold text-brand-600 dark:text-brand-400">{{ $proximo_efetivo ?? 1 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="mascara_numero" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Máscara do número</label>
                    <input type="text" id="mascara_numero" name="mascara_numero" value="{{ old('mascara_numero', $mascara_numero ?? 'PV{numero}') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
                           placeholder="PV{numero}" autocomplete="off">
                    <p class="mt-1 text-xs text-gray-400">Use o texto fixo desejado e <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">{numero}</code> onde entram os dígitos (ex.: <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">PED-{numero}</code>).</p>
                </div>
                <div>
                    <label for="numero_pad" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantidade de dígitos</label>
                    <input type="number" id="numero_pad" name="numero_pad" min="1" max="12" step="1"
                           value="{{ old('numero_pad', $numero_pad ?? 6) }}"
                           class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                </div>
                <div>
                    <label for="proximo_numero" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Próximo número da sequência</label>
                    <input type="number" id="proximo_numero" name="proximo_numero" min="1" max="99999999" step="1"
                           value="{{ old('proximo_numero', $proximo_configurado ?? 1) }}"
                           class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-400">Se já existem pedidos, deve ser maior que o <strong>maior índice</strong> acima (ex.: pular para 1000).</p>
                </div>
            </div>
        </div>

        <hr class="mb-8 border-gray-200 dark:border-gray-700">

        {{-- Orçamento / proposta --}}
        <div class="mb-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Orçamento / proposta</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Quando preenchido, o prazo aparece no <strong>cadastro e edição</strong> do pedido (rascunho/confirmado), na <strong>tela do pedido</strong> e no <strong>PDF</strong>, com referência de data limite com base na data do pedido.</p>
            <div class="max-w-xs">
                <label for="validade_orcamento_dias" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Validade do orçamento (dias corridos)</label>
                <input type="number" id="validade_orcamento_dias" name="validade_orcamento_dias" min="1" max="3650" step="1"
                       value="{{ old('validade_orcamento_dias', $validade_orcamento_dias ?? '') }}"
                       placeholder="Ex.: 10"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-400">Deixe em branco para não exibir. Máximo 3650 dias.</p>
            </div>
        </div>

        <hr class="mb-8 border-gray-200 dark:border-gray-700">

        {{-- Estoque --}}
        <div class="mb-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Controle de Estoque</h2>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Quando baixar o estoque</label>
            <select name="estoque_modo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                <option value="faturar" {{ ($estoque_modo ?? 'faturar') === 'faturar' ? 'selected' : '' }}>
                    Ao faturar o pedido (recomendado)
                </option>
                <option value="reservar_confirmar" {{ ($estoque_modo ?? '') === 'reservar_confirmar' ? 'selected' : '' }}>
                    Reservar ao confirmar, baixar ao faturar
                </option>
            </select>
            <p class="mt-1 text-xs text-gray-400">"Ao faturar" é o mais seguro: estoque só é comprometido quando o pedido é pago.</p>
        </div>

        {{-- Gerar OS --}}
        <div class="mb-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Serviços</h2>
            <label class="flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="gerar_os" value="1" {{ $gerar_os ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Gerar automaticamente OS para itens de serviço ao faturar
                </span>
            </label>
            <p class="mt-1 ml-7 text-xs text-gray-400">Se marcado, ao faturar um pedido que contenha itens do tipo "Serviço" com a opção "Gerar OS" ativada, uma OS será criada automaticamente.</p>
        </div>

        {{-- Plano de Contas --}}
        <div class="mb-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Financeiro</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de Conta Padrão (Receita)</label>
                    <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        <option value="">— Usar padrão do sistema —</option>
                        @foreach($planos_conta as $pc)
                            <option value="{{ $pc->id }}" {{ (string)($plano_conta_id ?? '') === (string)$pc->id ? 'selected' : '' }}>
                                {{ $pc->codigo ? "[{$pc->codigo}] " : '' }}{{ $pc->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo Padrão</label>
                    <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        <option value="">— Nenhum —</option>
                        @foreach($centros_custo as $cc)
                            <option value="{{ $cc->id }}" {{ (string)($centro_custo_id ?? '') === (string)$cc->id ? 'selected' : '' }}>
                                {{ $cc->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de conta — estorno ao cancelar (despesa)</label>
                    <select name="plano_conta_estorno_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                        <option value="">— Primeira despesa ativa (padrão) —</option>
                        @foreach($planos_conta_despesa ?? [] as $pc)
                            <option value="{{ $pc->id }}" {{ (string)($plano_conta_estorno_id ?? '') === (string)$pc->id ? 'selected' : '' }}>
                                {{ $pc->codigo ? "[{$pc->codigo}] " : '' }}{{ $pc->nome }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Usado nos lançamentos em <strong>Contas a pagar</strong> ao cancelar pedido já faturado (total ou parcial); não estorna o recebimento no caixa. Exige permissões <code class="text-xs">pedidos_venda.cancelar_total</code> / <code class="text-xs">pedidos_venda.cancelar_parcial</code> (ou legado <code class="text-xs">pedidos_venda.cancelar</code>). <strong>Não</strong> é permitido cancelar enquanto existir conta a receber <strong>em aberto</strong> (não quitada) vinculada ao pedido.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Voltar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">Salvar Configurações</button>
        </div>
    </div>
</form>
@endsection
