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

@if($contas->isEmpty())
    <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum lançamento (conta a receber) gerado por recorrente para este cliente.</p>
@else
    <div class="max-h-96 overflow-y-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Vencimento</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($contas as $c)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $c->descricao }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $c->data_vencimento->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-white/90">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($c->status === 'pago') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @elseif($c->status === 'parcial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @elseif($c->status === 'vencido') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @elseif($c->status === 'cancelado') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @endif">{{ ucfirst($c->status) }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('financeiro.contas-receber.edit', $c) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300" title="Abrir conta">Abrir</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($contas->count() >= 200)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Exibindo os 200 lançamentos mais recentes.</p>
    @endif
@endif
