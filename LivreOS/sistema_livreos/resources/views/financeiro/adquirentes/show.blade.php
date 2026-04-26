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
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $adquirente->nome }}</h1>
        <p class="text-gray-600 dark:text-gray-400">Detalhes da adquirente</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('financeiro.adquirentes.edit', $adquirente) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="{{ route('financeiro.adquirentes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações Gerais</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $adquirente->nome }}</dd>
            </div>
            @if($adquirente->codigo)
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Código</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $adquirente->codigo }}</dd>
            </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tipo de Antecipação</dt>
                <dd class="text-gray-800 dark:text-white/90">
                    @if($adquirente->tipo_antecipacao === 'fluxo')
                        Fluxo Normal
                    @elseif($adquirente->tipo_antecipacao === 'antecipado')
                        Antecipado
                    @else
                        Ambos
                    @endif
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Dias para Antecipação</dt>
                <dd class="text-gray-800 dark:text-white/90">{{ $adquirente->dias_antecipacao }} dias</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="text-gray-800 dark:text-white/90">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $adquirente->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $adquirente->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Contas Bancárias</h2>
        @if($adquirente->contas->count() > 0)
            <ul class="space-y-2 text-sm">
                @foreach($adquirente->contas as $conta)
                    <li class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">{{ $conta->nome }}</span>
                        @if($conta->pivot->padrao)
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs text-brand-800 dark:bg-brand-900 dark:text-brand-300">Padrão</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta vinculada</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Taxas Configuradas</h2>
            <a href="{{ route('financeiro.taxas.create', $adquirente) }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Taxa</a>
        </div>
        @if($adquirente->taxas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bandeira</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Modalidade</th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcelas</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa %</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa/Parcela</th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($adquirente->taxas as $taxa)
                            <tr>
                                <td class="px-3 py-2">{{ ucfirst($taxa->bandeira) }}</td>
                                <td class="px-3 py-2">{{ ucfirst(str_replace('_', ' ', $taxa->modalidade)) }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if($taxa->parcelas_min || $taxa->parcelas_max)
                                        {{ $taxa->parcelas_min ?? '1' }}x - {{ $taxa->parcelas_max ?? '∞' }}x
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format($taxa->taxa_percentual, 2, ',', '.') }}%</td>
                                <td class="px-3 py-2 text-right">{{ number_format($taxa->taxa_por_parcela, 2, ',', '.') }}%</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $taxa->ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $taxa->ativo ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma taxa configurada</p>
        @endif
    </div>
</div>

@if(function_exists('do_action'))
<?php do_action('financeiro.adquirentes.show.extra', $adquirente); ?>
@endif
@endsection
