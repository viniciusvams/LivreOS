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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Simulador de Transações</h1>
    <p class="text-gray-600 dark:text-gray-400">Simule transações e compare diferentes adquirentes</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Parâmetros da Simulação</h2>
        <form id="simulador-form" class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor da Venda (R$) *</label>
                <input type="number" step="0.01" id="valor_venda" name="valor_venda" value="1000.00" min="0.01" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Parcelas *</label>
                <input type="number" id="parcelas" name="parcelas" value="1" min="1" max="120" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Adquirente *</label>
                <select id="adquirente_id" name="adquirente_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    @foreach($adquirentes as $adquirente)
                        <option value="{{ $adquirente->id }}">{{ $adquirente->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bandeira *</label>
                <select id="bandeira" name="bandeira" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="master">Mastercard</option>
                    <option value="visa">Visa</option>
                    <option value="elo">Elo</option>
                    <option value="amex">American Express</option>
                    <option value="hipercard">Hipercard</option>
                    <option value="outros">Outros</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Venda</label>
                <input type="date" id="data_venda" name="data_venda" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="antecipado" name="antecipado" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Recebimento Antecipado</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Simular</button>
        </form>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Resultado da Simulação</h2>
        <div id="resultado-container" class="space-y-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Preencha os parâmetros e clique em "Simular" para ver o resultado</p>
        </div>
    </div>
</div>

<div id="agenda-container" class="mt-6 hidden rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Agenda de Recebíveis</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcela</th>
                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                </tr>
            </thead>
            <tbody id="agenda-body" class="divide-y divide-gray-100 dark:divide-gray-700">
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('simulador-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.antecipado = document.getElementById('antecipado').checked;
    
    try {
        const response = await fetch('{{ route("financeiro.simulador.simular") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            const dados = result.data;
            const container = document.getElementById('resultado-container');
            
            container.innerHTML = `
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Valor da Venda:</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">R$ ${parseFloat(dados.valor_venda).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Taxa Total:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">R$ ${parseFloat(dados.taxa_total_aplicada).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
                        <span class="text-gray-600 dark:text-gray-400">Valor Líquido:</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">R$ ${parseFloat(dados.valor_liquido).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                        <p class="text-xs text-gray-600 dark:text-gray-400"><strong>Adquirente:</strong> ${dados.adquirente.nome}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400"><strong>Modalidade:</strong> ${dados.modalidade.replace('_', ' ')}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400"><strong>Parcelas:</strong> ${dados.parcelas}x</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400"><strong>Bandeira:</strong> ${dados.bandeira}</p>
                    </div>
                </div>
            `;
            
            // Preencher agenda
            const agendaBody = document.getElementById('agenda-body');
            agendaBody.innerHTML = '';
            dados.agenda_recebiveis.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2">${new Date(item.data).toLocaleDateString('pt-BR')}</td>
                    <td class="px-3 py-2 text-center">${item.parcela ? `${item.parcela}/${item.total_parcelas}` : '-'}</td>
                    <td class="px-3 py-2 text-right font-medium">R$ ${parseFloat(item.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                `;
                agendaBody.appendChild(row);
            });
            
            document.getElementById('agenda-container').classList.remove('hidden');
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao simular: ' + error.message);
    }
});
</script>
@endsection
