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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plano de Contas e Centro de Custo - Encerramento de OS</h1>
    <p class="text-gray-600 dark:text-gray-400">Escolha em qual conta do plano de contas e centro de custo os lançamentos financeiros (contas a receber) serão registrados quando uma ordem de serviço for encerrada.</p>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700 dark:border-green-800 dark:bg-green-500/10 dark:text-green-400">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
    {{ session('error') }}
</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('configuracoes-sistema.plano-conta-os.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label for="plano_conta_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta para lançamentos do encerramento de OS</label>
                <select name="plano_conta_id" id="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Padrão (Receita de Serviços - código 1.1)</option>
                    @foreach($planoContasReceita as $pc)
                        <option value="{{ $pc->id }}" {{ ($plano_conta_id ?? null) == $pc->id ? 'selected' : '' }}>
                            {{ $pc->codigo ? $pc->codigo . ' - ' : '' }}{{ $pc->nome }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    Ao encerrar uma OS, o sistema cria contas a receber e movimentações financeiras. Esta configuração define em qual conta do plano de contas esses lançamentos serão classificados. Se deixar em branco, será usado o padrão "Receita de Serviços" (código 1.1).
                </p>
            </div>
            <div>
                <label for="centro_custo_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo para lançamentos do encerramento de OS</label>
                <select name="centro_custo_id" id="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhum (não classificar por centro de custo)</option>
                    @foreach($centrosCusto as $cc)
                        <option value="{{ $cc->id }}" {{ ($centro_custo_id ?? null) == $cc->id ? 'selected' : '' }}>
                            {{ $cc->codigo ? $cc->codigo . ' - ' : '' }}{{ $cc->nome }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    Opcional. Define o centro de custo associado às contas a receber e movimentações geradas no encerramento da OS. Útil para relatórios por centro de custo.
                </p>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
        </div>
    </form>
</div>
@endsection
