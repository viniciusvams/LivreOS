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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Adquirente</h1>
    <p class="text-gray-600 dark:text-gray-400">Atualize as informações da adquirente</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('financeiro.adquirentes.update', $adquirente) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome', $adquirente->nome) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" name="codigo" value="{{ old('codigo', $adquirente->codigo) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Antecipação *</label>
                <select name="tipo_antecipacao" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="fluxo" {{ old('tipo_antecipacao', $adquirente->tipo_antecipacao) === 'fluxo' ? 'selected' : '' }}>Fluxo Normal</option>
                    <option value="antecipado" {{ old('tipo_antecipacao', $adquirente->tipo_antecipacao) === 'antecipado' ? 'selected' : '' }}>Antecipado</option>
                    <option value="ambos" {{ old('tipo_antecipacao', $adquirente->tipo_antecipacao) === 'ambos' ? 'selected' : '' }}>Ambos</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias para Antecipação *</label>
                <input type="number" name="dias_antecipacao" value="{{ old('dias_antecipacao', $adquirente->dias_antecipacao) }}" min="1" max="30" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contas Bancárias</label>
                <div class="space-y-2">
                    @php
                        $contasVinculadas = $adquirente->contas->pluck('id')->toArray();
                        $contaPadrao = $adquirente->contas->where('pivot.padrao', true)->first()?->id;
                    @endphp
                    @forelse($contasBancarias as $conta)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="contas[]" value="{{ $conta->id }}" {{ in_array($conta->id, $contasVinculadas) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <input type="radio" name="contas_padrao" value="{{ $conta->id }}" {{ $conta->id == $contaPadrao ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $conta->nome }} - {{ $conta->banco ?? 'N/A' }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta bancária cadastrada</p>
                    @endforelse
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Formas de Pagamento</label>
                <div class="space-y-2">
                    @php
                        $formasVinculadas = $adquirente->formasPagamento->pluck('id')->toArray();
                        $formaPadrao = $adquirente->formasPagamento->where('pivot.padrao', true)->first()?->id;
                    @endphp
                    @forelse($formasPagamento as $forma)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="formas_pagamento[]" value="{{ $forma->id }}" {{ in_array($forma->id, $formasVinculadas) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <input type="radio" name="formas_pagamento_padrao" value="{{ $forma->id }}" {{ $forma->id == $formaPadrao ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $forma->nome }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma forma de pagamento cadastrada</p>
                    @endforelse
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('observacoes', $adquirente->observacoes) }}</textarea>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" {{ old('ativo', $adquirente->ativo) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Adquirente ativa</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="permite_antecipacao_parcelado" value="1" {{ old('permite_antecipacao_parcelado', $adquirente->permite_antecipacao_parcelado) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Permite antecipação de parcelas</span>
                </label>
            </div>
        </div>

        {{-- Seção: Taxas por bandeira (bandeiras aceitas, taxas e antecipado) --}}
        <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-700">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Taxas por bandeira</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cadastre as bandeiras aceitas (Master, Visa, Elo, etc.), o valor das taxas por modalidade (débito, crédito à vista, parcelado) e se a taxa é para recebimento antecipado ou não.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('financeiro.taxas.index', $adquirente) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Gerenciar taxas
                    </a>
                    <a href="{{ route('financeiro.taxas.create', $adquirente) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nova taxa
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bandeira</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Modalidade</th>
                            <th class="px-4 py-2.5 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcelas</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa %</th>
                            <th class="px-4 py-2.5 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Antecipado</th>
                            <th class="px-4 py-2.5 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-2.5 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse($adquirente->taxas as $taxa)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2.5 text-sm font-medium text-gray-800 dark:text-white/90">{{ ucfirst($taxa->bandeira) }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $taxa->modalidade)) }}</td>
                                <td class="px-4 py-2.5 text-center text-sm text-gray-700 dark:text-gray-300">
                                    @if($taxa->parcelas_min || $taxa->parcelas_max)
                                        {{ $taxa->parcelas_min ?? '1' }}x - {{ $taxa->parcelas_max ?? '∞' }}x
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($taxa->taxa_percentual, 2, ',', '.') }}%</td>
                                <td class="px-4 py-2.5 text-center text-sm">
                                    @if($taxa->usa_antecipacao)
                                        <span class="text-green-600 dark:text-green-400">Sim</span>
                                        @if($taxa->taxa_antecipacao_percentual)
                                            <span class="text-xs text-gray-500">(+{{ number_format($taxa->taxa_antecipacao_percentual, 2, ',', '.') }}%)</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Não</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $taxa->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $taxa->ativo ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <a href="{{ route('financeiro.taxas.edit', [$adquirente, $taxa]) }}" class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Editar taxa">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma taxa cadastrada. Clique em <strong>Nova taxa</strong> para cadastrar bandeiras, valores e se é antecipado ou não.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('financeiro.adquirentes.form.extra', $adquirente); ?>
        @endif
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('financeiro.adquirentes.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        </div>
    </form>
</div>
@endsection
