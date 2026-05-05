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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Tarefas Agendadas</h1>
    <p class="text-gray-600 dark:text-gray-400">Configure a execução das tarefas agendadas (geração de contas a receber e a pagar recorrentes, reajustes) quando não for possível usar cron na hospedagem compartilhada.</p>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
    {{ session('error') }}
</div>
@endif

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('configuracoes-sistema.tarefas-agendadas.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="hidden" name="run_tarefas_ao_acessar" value="0">
                <input type="checkbox" name="run_tarefas_ao_acessar" value="1" {{ $run_tarefas_ao_acessar ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                <div>
                    <span class="font-medium text-gray-800 dark:text-white/90">Executar tarefas ao acessar o sistema</span>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Quando habilitado, o sistema executa as tarefas agendadas de forma oculta na primeira vez que alguém acessa o sistema naquele dia. São executados: geração de contas a receber (recebimentos recorrentes), geração de contas a pagar (despesas recorrentes: água, luz, aluguel, etc.) e reajuste por índice nos recorrentes quando devido. Um cookie evita disparos repetidos no mesmo dia. <strong>Ideal para hospedagem compartilhada sem cron.</strong>
                    </p>
                </div>
            </label>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
        </div>
    </form>

    <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">O sistema executa as tarefas no máximo uma vez por dia (controle por cache no servidor). Se você apagou o cookie ou acessou em outro navegador e mesmo assim as contas não foram geradas, é porque o servidor já rodou hoje. Use o botão abaixo para forçar a execução agora.</p>
        <form action="{{ route('configuracoes-sistema.tarefas-agendadas.executar-agora') }}" method="POST" class="inline" onsubmit="return confirm('Executar todas as tarefas agendadas agora? (geração de contas a receber e a pagar recorrentes, reajustes)');">
            @csrf
            <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Executar tarefas agora</button>
        </form>
    </div>
</div>

<div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
    <strong>Alternativa com cron:</strong> Se sua hospedagem permitir cron, você pode usar a URL
    <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/50">{{ url('/run-tarefas') }}?token=SEU_TOKEN</code>
    (defina <code>CRON_SECRET_TOKEN</code> no .env) e agendar uma chamada diária. Com a opção acima habilitada, o cron não é necessário.
</div>
@endsection
