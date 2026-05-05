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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Testar recorrência e reajuste</h1>
    <p class="text-gray-600 dark:text-gray-400">Execute manualmente a geração de contas recorrentes e o reajuste por índice para validar a configuração.</p>
</div>

@if(session('result_gerar'))
    <div class="mb-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">{{ session('result_gerar') }}</div>
@endif
@if(session('result_reajuste_simular'))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <p class="mb-2 text-sm font-medium text-amber-800 dark:text-amber-200">Simulação de reajuste (nenhum valor foi alterado):</p>
        <pre class="whitespace-pre-wrap text-xs text-amber-900 dark:text-amber-100">{{ session('result_reajuste_simular') }}</pre>
    </div>
@endif
@if(session('result_reajuste_aplicar'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
        <p class="mb-2 text-sm font-medium text-green-800 dark:text-green-200">Reajuste aplicado:</p>
        <pre class="whitespace-pre-wrap text-xs text-green-900 dark:text-green-100">{{ session('result_reajuste_aplicar') }}</pre>
    </div>
@endif
@if(session('result_indice'))
    <div class="mb-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ session('result_indice') }}</div>
@endif

<div class="grid gap-6 md:grid-cols-2">
    {{-- Gerar contas recorrentes --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">1. Gerar contas a receber</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Processa todos os pagamentos recorrentes ativos com data de próxima geração &le; hoje e cria as contas a receber correspondentes (igual ao que roda diariamente pela URL ou cron).</p>
        <form action="{{ route('financeiro.tarefas-recorrentes.gerar') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Executar geração agora</button>
        </form>
    </div>

    {{-- Testar API de índice --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">2. Testar API de índice</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Consulta a API do BCB e mostra a variação acumulada do índice nos últimos N meses (IPCA, INPC, IGP-M).</p>
        <form action="{{ route('financeiro.tarefas-recorrentes.testar-indice') }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Índice</label>
                <select name="indice" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="IPCA">IPCA</option>
                    <option value="INPC">INPC</option>
                    <option value="IGPM">IGP-M</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Meses</label>
                <input type="number" name="meses" value="12" min="1" max="24" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Consultar</button>
        </form>
    </div>

    {{-- Simular reajuste --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">3. Simular reajuste por índice</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Mostra quais recorrentes seriam reajustados e os novos valores, <strong>sem alterar</strong> nada no banco. Use para conferir antes de aplicar.</p>
        <form action="{{ route('financeiro.tarefas-recorrentes.reajuste-simular') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="rounded-lg border border-amber-400 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-200">Simular reajuste (dry-run)</button>
        </form>
    </div>

    {{-- Aplicar reajuste --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">4. Aplicar reajuste por índice</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Atualiza o valor dos recorrentes configurados com reajuste por índice (IPCA, INPC, IGP-M) que estiverem due. <strong>Altera os valores</strong> no banco.</p>
        <form action="{{ route('financeiro.tarefas-recorrentes.reajuste-aplicar') }}" method="POST" class="inline" onsubmit="return confirm('Confirma aplicar o reajuste? Os valores dos recorrentes due serão atualizados.');">
            @csrf
            <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Aplicar reajuste</button>
        </form>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        <strong>Dica:</strong> Para testar o reajuste, cadastre um pagamento recorrente com tipo "Por índice", escolha um índice (ex.: IPCA), defina "Reajustar a cada" 12 meses e data início há mais de 12 meses. Depois use "Simular reajuste" e, se estiver correto, "Aplicar reajuste".
    </p>
</div>
@endsection
