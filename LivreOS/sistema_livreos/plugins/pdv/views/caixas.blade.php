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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Gerenciar PDV</h1>
    <p class="text-gray-600 dark:text-gray-400">Caixas abertos, histórico de vendas, pedidos e orçamentos.</p>
</div>

<div x-data="{ aba: '{{ request()->get('aba', 'caixas') }}' }" class="space-y-4">
    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
        <button type="button" @click="aba = 'caixas'" :class="aba === 'caixas' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'" class="border-b-2 px-4 py-2 text-sm font-medium transition">Caixas</button>
        <button type="button" @click="aba = 'vendas'" :class="aba === 'vendas' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'" class="border-b-2 px-4 py-2 text-sm font-medium transition">Histórico de vendas</button>
        <button type="button" @click="aba = 'orcamentos'" :class="aba === 'orcamentos' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'" class="border-b-2 px-4 py-2 text-sm font-medium transition">Orçamentos</button>
    </div>

    {{-- Aba Caixas --}}
    <div x-show="aba === 'caixas'" x-cloak class="space-y-8">
        {{-- Caixas abertos --}}
        <div>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Caixas abertos</h2>
            @if($caixasAbertos->isEmpty())
                <p class="rounded-lg border border-gray-200 bg-white p-4 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nenhum caixa aberto no momento.</p>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Caixa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Operador</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Abertura (R$)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Saldo atual (R$)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Aberto em</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($caixasAbertos as $c)
                            <tr class="bg-white dark:bg-gray-800">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $c->numero_caixa }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c->user->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($c->valor_abertura, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($c->saldo_atual, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c->opened_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('plugin.pdv.index') }}?numero_caixa={{ urlencode($c->numero_caixa) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600" target="_blank" rel="noopener">Abrir PDV</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Caixas fechados (histórico recente) --}}
        <div>
            <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Últimos caixas fechados</h2>
            @if($caixasFechados->isEmpty())
                <p class="rounded-lg border border-gray-200 bg-white p-4 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nenhum fechamento recente.</p>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Caixa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Operador</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Abertura (R$)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Fechamento (R$)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Quebra / Sobra</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Fechado em</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Detalhe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700" x-data="{ abertoCaixaId: null }">
                            @foreach($caixasFechados as $c)
                            @php
                                $qb = $c->fechamentoQuebraSobra;
                                $temQuebraSobra = $qb && $qb->valor_quebra_sobra != 0;
                            @endphp
                            <tr class="bg-white dark:bg-gray-800">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $c->numero_caixa }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c->user->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($c->valor_abertura, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ $c->valor_fechamento_sistema !== null ? number_format($c->valor_fechamento_sistema, 2, ',', '.') : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if($qb)
                                        @if((float)$qb->valor_quebra_sobra > 0)
                                            <span class="font-semibold text-green-700 dark:text-green-400" title="Sobra">+ R$ {{ number_format($qb->valor_quebra_sobra, 2, ',', '.') }}</span>
                                        @elseif((float)$qb->valor_quebra_sobra < 0)
                                            <span class="font-semibold text-red-700 dark:text-red-400" title="Quebra">− R$ {{ number_format(abs($qb->valor_quebra_sobra), 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">R$ 0,00</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if($qb)
                                        <button type="button" @click="abertoCaixaId = abertoCaixaId === {{ $c->id }} ? null : {{ $c->id }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                            <span x-text="abertoCaixaId === {{ $c->id }} ? 'Ocultar' : 'Ver detalhes'"></span>
                                        </button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if($qb)
                            <tr x-show="abertoCaixaId === {{ $c->id }}" x-cloak class="bg-gray-50 dark:bg-gray-800/80" style="display: none;">
                                <td colspan="7" class="px-4 py-3">
                                    <div class="border-l-2 border-blue-400 pl-3 space-y-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rastreio da quebra/sobra no fechamento</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                                            <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-2">
                                                <p class="text-gray-500 dark:text-gray-400">Valor informado</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">R$ {{ number_format($c->valor_fechamento_informado ?? 0, 2, ',', '.') }}</p>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-2">
                                                <p class="text-gray-500 dark:text-gray-400">Valor em sistema</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">R$ {{ number_format($c->valor_fechamento_sistema ?? 0, 2, ',', '.') }}</p>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-2">
                                                <p class="text-gray-500 dark:text-gray-400">Diferença</p>
                                                <p class="font-semibold {{ (float)$qb->valor_quebra_sobra >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                                    {{ (float)$qb->valor_quebra_sobra >= 0 ? '+' : '−' }} R$ {{ number_format(abs($qb->valor_quebra_sobra), 2, ',', '.') }}
                                                    ({{ (float)$qb->valor_quebra_sobra >= 0 ? 'sobra' : 'quebra' }})
                                                </p>
                                            </div>
                                            @if($qb->aprovado_por_user_id && $qb->aprovadoPor)
                                            <div class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-2">
                                                <p class="text-gray-600 dark:text-gray-400">Aprovado por</p>
                                                <p class="font-semibold text-amber-800 dark:text-amber-200">{{ $qb->aprovadoPor->name }}</p>
                                            </div>
                                            @endif
                                        </div>
                                        @if(!empty($qb->dados['valores_informados_por_forma']) && is_array($qb->dados['valores_informados_por_forma']))
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                                <thead class="bg-gray-100 dark:bg-gray-700/50">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Forma de pagamento</th>
                                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 dark:text-gray-300">Informado (R$)</th>
                                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 dark:text-gray-300">Sistema (R$)</th>
                                                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 dark:text-gray-300">Diferença (R$)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                    @php
                                                        $informados = $qb->dados['valores_informados_por_forma'] ?? [];
                                                        $calculados = $qb->dados['valores_calculados_por_forma'] ?? [];
                                                        $diferencas = $qb->dados['diferencas_por_forma'] ?? [];
                                                        $formaIds = array_unique(array_merge(array_keys($informados), array_keys($calculados)));
                                                    @endphp
                                                    @foreach($formaIds as $formaId)
                                                    <tr>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $formasPagamento->get($formaId)?->nome ?? 'Forma #' . $formaId }}</td>
                                                        <td class="px-3 py-2 text-right">{{ number_format($informados[$formaId] ?? 0, 2, ',', '.') }}</td>
                                                        <td class="px-3 py-2 text-right">{{ number_format($calculados[$formaId] ?? 0, 2, ',', '.') }}</td>
                                                        @php $diff = $diferencas[$formaId] ?? 0; @endphp
                                                        <td class="px-3 py-2 text-right font-medium {{ $diff >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2, ',', '.') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Aba Histórico de vendas --}}
    <div x-show="aba === 'vendas'" x-cloak>
        <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Histórico de vendas</h2>

        <form method="get" action="{{ route('plugin.pdv.caixas') }}" class="mb-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <input type="hidden" name="aba" value="vendas">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Caixa</label>
                    <select name="caixa_id" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        @foreach($caixasParaFiltro as $cf)
                        <option value="{{ $cf->id }}" {{ ($filtrosVendas['caixa_id'] ?? '') == $cf->id ? 'selected' : '' }}>{{ $cf->numero_caixa }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Data de</label>
                    <input type="date" name="data_de" value="{{ $filtrosVendas['data_de'] ?? '' }}" class="rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Data até</label>
                    <input type="date" name="data_ate" value="{{ $filtrosVendas['data_ate'] ?? '' }}" class="rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cliente</label>
                    <select name="cliente_id" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        @foreach($clientesParaFiltro as $cl)
                        <option value="{{ $cl->id }}" {{ ($filtrosVendas['cliente_id'] ?? '') == $cl->id ? 'selected' : '' }}>{{ $cl->nome ?? $cl->razao_social ?? '#' . $cl->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Busca</label>
                    <input type="text" name="q" value="{{ $filtrosVendas['q'] ?? '' }}" placeholder="ID, cliente ou caixa" class="rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" style="min-width: 180px;">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">Filtrar</button>
                    <a href="{{ route('plugin.pdv.caixas') }}?aba=vendas" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Limpar</a>
                </div>
            </div>
        </form>

        @if($vendas->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-white p-4 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nenhuma venda encontrada.</p>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Caixa / Operador</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total (R$)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                            @if($canVerAutorizacoes ?? false)
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Autorizações</th>
                            @endif
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Itens</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700" x-data="{ abertoId: null }">
                        @foreach($vendas as $v)
                        <tr class="bg-white dark:bg-gray-800">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $v->id }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->caixa?->numero_caixa ?? '—' }} / {{ $v->caixa?->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? '—') : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ number_format($v->total_final, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $v->status === 'finalizada' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($v->status === 'cancelada' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">{{ $v->status }}</span>
                            </td>
                            @if($canVerAutorizacoes ?? false)
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                @if($v->desconto_autorizado_por_user_id && $v->descontoAutorizadoPorUser)
                                    <span class="block" title="Desconto acima do permitido foi autorizado por este usuário">Desconto: {{ $v->descontoAutorizadoPorUser->name }}</span>
                                @endif
                                @if($v->cliente_bloqueado_autorizado_por_user_id && $v->clienteBloqueadoAutorizadoPorUser)
                                    <span class="block" title="Cliente bloqueado para vendas foi liberado por este usuário">Cliente bloqueado: {{ $v->clienteBloqueadoAutorizadoPorUser->name }}</span>
                                @endif
                                @if($v->limite_credito_autorizado_por_user_id && $v->limiteCreditoAutorizadoPorUser)
                                    <span class="block" title="Limite de crédito (boleto) foi liberado por este usuário">Limite crédito: {{ $v->limiteCreditoAutorizadoPorUser->name }}</span>
                                @endif
                                @if($v->estoque_zerado_autorizado_por_user_id && $v->estoqueZeradoAutorizadoPorUser)
                                    <span class="block" title="Venda com estoque zerado/insuficiente autorizada por este usuário">Estoque zerado: {{ $v->estoqueZeradoAutorizadoPorUser->name }}</span>
                                @endif
                                @if($v->status === 'cancelada')
                                    @if($v->cancelado_por_user_id && $v->canceladoPorUser)
                                        <span class="block">Cancelado por: {{ $v->canceladoPorUser->name }}</span>
                                    @endif
                                    @if($v->cancelamento_autorizado_por_user_id && $v->cancelamentoAutorizadoPorUser)
                                        <span class="block">Autorizado por: {{ $v->cancelamentoAutorizadoPorUser->name }}</span>
                                    @endif
                                @endif
                                @if(!$v->desconto_autorizado_por_user_id && !$v->cliente_bloqueado_autorizado_por_user_id && !$v->limite_credito_autorizado_por_user_id && !$v->estoque_zerado_autorizado_por_user_id && ($v->status !== 'cancelada' || (!$v->cancelado_por_user_id && !$v->cancelamento_autorizado_por_user_id)))
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            @endif
                            <td class="px-4 py-3 text-center">
                                <button type="button" @click="abertoId = abertoId === {{ $v->id }} ? null : {{ $v->id }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    <span x-text="abertoId === {{ $v->id }} ? 'Ocultar itens' : 'Ver itens ({{ $v->itens->count() }})'"></span>
                                </button>
                            </td>
                        </tr>
                        <tr x-show="abertoId === {{ $v->id }}" x-cloak class="bg-gray-50 dark:bg-gray-800/80" style="display: none;">
                            <td colspan="{{ ($canVerAutorizacoes ?? false) ? 8 : 7 }}" class="px-4 py-3">
                                <div class="border-l-2 border-blue-400 pl-3">
                                    @if($canVerAutorizacoes ?? false)
                                    <div class="mb-3 rounded border border-amber-200 bg-amber-50 p-2 text-xs dark:border-amber-800 dark:bg-amber-900/20">
                                        <p class="font-semibold text-amber-800 dark:text-amber-200 mb-1">Autorizações</p>
                                        @if($v->desconto_autorizado_por_user_id && $v->descontoAutorizadoPorUser)
                                            <p>Desconto acima do permitido autorizado por: <strong>{{ $v->descontoAutorizadoPorUser->name }}</strong></p>
                                        @endif
                                        @if($v->cliente_bloqueado_autorizado_por_user_id && $v->clienteBloqueadoAutorizadoPorUser)
                                            <p>Cliente bloqueado para vendas liberado por: <strong>{{ $v->clienteBloqueadoAutorizadoPorUser->name }}</strong></p>
                                        @endif
                                        @if($v->limite_credito_autorizado_por_user_id && $v->limiteCreditoAutorizadoPorUser)
                                            <p>Limite de crédito (boleto) liberado por: <strong>{{ $v->limiteCreditoAutorizadoPorUser->name }}</strong></p>
                                        @endif
                                        @if($v->estoque_zerado_autorizado_por_user_id && $v->estoqueZeradoAutorizadoPorUser)
                                            <p>Venda com estoque zerado/insuficiente autorizada por: <strong>{{ $v->estoqueZeradoAutorizadoPorUser->name }}</strong></p>
                                        @endif
                                        @if($v->status === 'cancelada')
                                            @if($v->cancelado_por_user_id && $v->canceladoPorUser)
                                                <p>Cancelado por: <strong>{{ $v->canceladoPorUser->name }}</strong></p>
                                            @endif
                                            @if($v->cancelamento_autorizado_por_user_id && $v->cancelamentoAutorizadoPorUser)
                                                <p>Cancelamento autorizado por (senha): <strong>{{ $v->cancelamentoAutorizadoPorUser->name }}</strong></p>
                                            @endif
                                            @if($v->canceled_at)
                                                <p class="text-gray-500 dark:text-gray-400">Em: {{ $v->canceled_at->format('d/m/Y H:i') }}</p>
                                            @endif
                                        @endif
                                        @if(!$v->desconto_autorizado_por_user_id && !$v->cliente_bloqueado_autorizado_por_user_id && !$v->limite_credito_autorizado_por_user_id && !$v->estoque_zerado_autorizado_por_user_id && ($v->status !== 'cancelada' || (!$v->cancelado_por_user_id && !$v->cancelamento_autorizado_por_user_id)))
                                            <p class="text-gray-500 dark:text-gray-400">Nenhuma autorização registrada.</p>
                                        @endif
                                    </div>
                                    @endif
                                    <p class="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Itens da venda #{{ $v->id }}</p>
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-600 dark:text-gray-400">
                                                <th class="pb-1 pr-4">Descrição</th>
                                                <th class="pb-1 text-right">Qtd</th>
                                                <th class="pb-1 text-right">Unit. (R$)</th>
                                                <th class="pb-1 text-right">Total (R$)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($v->itens as $item)
                                            <tr>
                                                <td class="py-0.5 pr-4">{{ $item->descricao }}{{ $item->serial ? ' (' . $item->serial . ')' : '' }}</td>
                                                <td class="py-0.5 text-right">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                                                <td class="py-0.5 text-right">{{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                                                <td class="py-0.5 text-right font-medium">{{ number_format($item->total, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-700/30">
                    {{ $vendas->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Aba Orçamentos --}}
    <div x-show="aba === 'orcamentos'" x-cloak>
        <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Orçamentos</h2>
        @if($orcamentos->isEmpty())
            <p class="rounded-lg border border-gray-200 bg-white p-4 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Nenhum orçamento cadastrado.</p>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Total (R$)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($orcamentos as $o)
                        <tr class="bg-white dark:bg-gray-800">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $o->id }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $o->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $o->nome_cliente ?: ($o->cliente ? ($o->cliente->nome ?? $o->cliente->razao_social ?? '—') : '—') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ number_format($o->total, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $o->status === 'convertido' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $o->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-700/30">
                    {{ $orcamentos->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
