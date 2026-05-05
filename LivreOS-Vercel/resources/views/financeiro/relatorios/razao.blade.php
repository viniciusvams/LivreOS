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
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Razão por conta</h1>
        <p class="text-gray-600 dark:text-gray-400">Lançamentos por plano de conta e período</p>
    </div>
    @if($planoConta)
    <a href="{{ route('financeiro.relatorios.razao.pdf', array_merge(request()->only(['plano_conta_id', 'data_inicio', 'data_fim']), request('somente_conciliados') ? ['somente_conciliados' => '1'] : [])) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
    @endif
</div>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.relatorios.razao') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de conta</label>
            <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                <option value="">Selecione...</option>
                @foreach(collect($planoContas ?? [])->unique('id')->values() as $pc)
                    <option value="{{ $pc->id }}" {{ (request('plano_conta_id') == $pc->id || ($planoConta && $planoConta->id == $pc->id)) ? 'selected' : '' }}>{{ $pc->codigo }} - {{ $pc->nome }}</option>
                @endforeach
            </select>
            @if(collect($planoContas ?? [])->isEmpty())
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">Nenhum plano de conta encontrado. Cadastre em <a href="{{ route('financeiro.plano-contas.index') }}" class="underline">Plano de Contas</a> ou rode o seeder: <code class="text-xs">php artisan db:seed --class=PlanoContaSeeder</code></p>
            @endif
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data início</label>
            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data fim</label>
            <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex flex-col justify-end">
            <label class="mb-1.5 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="somente_conciliados" value="1" {{ ($somenteConciliados ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Somente conciliados
            </label>
        </div>
        <div class="flex items-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        </div>
    </form>
</div>

@if($planoConta)
<div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
    Conta: <strong>{{ $planoConta->codigo }} - {{ $planoConta->nome }}</strong> | Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
</div>
@endif

@if(isset($razaoDebug) && $razaoDebug['totalNoPeriodo'] > 0 && count($itens ?? []) === 0)
<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-800 dark:bg-amber-900/30">
    <p class="font-medium text-amber-800 dark:text-amber-200">Diagnóstico</p>
    <p class="mt-1 text-amber-700 dark:text-amber-300">Há <strong>{{ $razaoDebug['totalNoPeriodo'] }}</strong> movimentação(ões) no período, mas nenhuma com o plano de conta selecionado (ID {{ $planoConta->id ?? '' }} ou subcontas).</p>
    <p class="mt-2 text-amber-700 dark:text-amber-300">IDs considerados para esta conta: {{ implode(', ', $razaoDebug['idsConsiderados'] ?? []) }}</p>
    <p class="mt-2 text-amber-700 dark:text-amber-300">Plano de conta efetivo nas movimentações do período:</p>
    <ul class="mt-1 list-inside list-disc text-amber-700 dark:text-amber-300">
        @foreach($razaoDebug['planoEfetivoPorMov'] ?? [] as $planoId => $qtd)
            <li>Plano ID {{ $planoId }}: {{ $qtd }} lançamento(s)</li>
        @endforeach
    </ul>
    <p class="mt-2 text-amber-700 dark:text-amber-300">Se o seu lançamento está em outro plano (ex.: ID acima), selecione essa conta no filtro ou amplie o período.</p>
</div>
@elseif(isset($razaoDebug) && $razaoDebug['totalNoPeriodo'] === 0)
<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-800 dark:bg-amber-900/30">
    <p class="font-medium text-amber-800 dark:text-amber-200">Nenhuma movimentação no período</p>
    <p class="mt-1 text-amber-700 dark:text-amber-300">Não há movimentações entre <strong>{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}</strong> e <strong>{{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>. Tente ampliar o período (ex.: incluir o ano anterior).</p>
</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Entrada</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saída</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($itens as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">{{ $item['data'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item['descricao'] }}</td>
                        <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">{{ $item['entrada'] > 0 ? 'R$ ' . number_format($item['entrada'], 2, ',', '.') : '-' }}</td>
                        <td class="px-4 py-3 text-right text-sm text-red-600 dark:text-red-400">{{ $item['saida'] > 0 ? 'R$ ' . number_format($item['saida'], 2, ',', '.') : '-' }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium {{ ($item['saldo'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($item['saldo'] ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            @if($planoConta)
                                Nenhum lançamento no período para esta conta.
                                <br><span class="text-xs">Se há movimentações no período, verifique se elas (ou o título de CR/CP vinculado) têm plano de conta preenchido com esta conta ou uma subconta.</span>
                            @else
                                Selecione uma conta e período e clique em Filtrar.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
