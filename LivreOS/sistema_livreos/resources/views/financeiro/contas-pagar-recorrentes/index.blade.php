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
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Despesas recorrentes</h1>
        <p class="text-gray-600 dark:text-gray-400">Despesas que se repetem (água, luz, aluguel, condomínio). As contas a pagar são geradas automaticamente.</p>
    </div>
    <a href="{{ route('financeiro.contas-pagar-recorrentes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova despesa recorrente
    </a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">{{ session('error') }}</div>
@endif

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" action="{{ route('financeiro.contas-pagar-recorrentes.index') }}" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
            <select name="fornecedor_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                @foreach($fornecedores as $f)
                    <option value="{{ $f->id }}" {{ request('fornecedor_id') == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo</label>
            <select name="tipo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="agua" {{ request('tipo') === 'agua' ? 'selected' : '' }}>Água</option>
                <option value="luz" {{ request('tipo') === 'luz' ? 'selected' : '' }}>Luz</option>
                <option value="gas" {{ request('tipo') === 'gas' ? 'selected' : '' }}>Gás</option>
                <option value="aluguel" {{ request('tipo') === 'aluguel' ? 'selected' : '' }}>Aluguel</option>
                <option value="condominio" {{ request('tipo') === 'condominio' ? 'selected' : '' }}>Condomínio</option>
                <option value="telefone" {{ request('tipo') === 'telefone' ? 'selected' : '' }}>Telefone</option>
                <option value="internet" {{ request('tipo') === 'internet' ? 'selected' : '' }}>Internet</option>
                <option value="outro" {{ request('tipo') === 'outro' ? 'selected' : '' }}>Outro</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Frequência</label>
            <select name="frequencia" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todas</option>
                <option value="diaria" {{ request('frequencia') === 'diaria' ? 'selected' : '' }}>Diária</option>
                <option value="semanal" {{ request('frequencia') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                <option value="quinzenal" {{ request('frequencia') === 'quinzenal' ? 'selected' : '' }}>Quinzenal</option>
                <option value="mensal" {{ request('frequencia') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                <option value="bimestral" {{ request('frequencia') === 'bimestral' ? 'selected' : '' }}>Bimestral</option>
                <option value="trimestral" {{ request('frequencia') === 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                <option value="semestral" {{ request('frequencia') === 'semestral' ? 'selected' : '' }}>Semestral</option>
                <option value="anual" {{ request('frequencia') === 'anual' ? 'selected' : '' }}>Anual</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="ativo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Todos</option>
                <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativos</option>
                <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="{{ route('financeiro.contas-pagar-recorrentes.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Fornecedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Frequência</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Próxima geração</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recorrentes as $r)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->fornecedor->nome ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->descricao }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\ContaPagarRecorrente::tipoLabel($r->tipo) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($r->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\ContaPagarRecorrente::frequenciaLabel($r->frequencia) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $r->proxima_geracao_em->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $r->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $r->ativo ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('financeiro.contas-pagar-recorrentes.edit', $r) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button type="button" onclick="abrirModalExcluirMotivo('{{ route('financeiro.contas-pagar-recorrentes.destroy', $r) }}')" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma despesa recorrente cadastrada. Crie uma para gerar contas a pagar automaticamente (água, luz, aluguel, etc.).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        {{ $recorrentes->links() }}
    </div>
</div>

<p class="mt-4 text-xs text-gray-500 dark:text-gray-400">As contas a pagar são criadas automaticamente pelo agendamento diário (comando <code>financeiro:gerar-contas-pagar-recorrentes</code>). Em hospedagem compartilhada sem cron, habilite <strong>Configurações → Tarefas agendadas → Executar tarefas ao acessar o sistema</strong> para que a geração rode na primeira visita do dia.</p>

<x-modal-excluir-motivo action="" titulo="Excluir despesa recorrente" descricao="Tem certeza que deseja excluir esta despesa recorrente? Informe o motivo." />
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
</script>
@endsection
