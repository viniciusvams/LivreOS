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

@php
    $empresa = \App\Models\Empresa::first();
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Movimentações Financeiras</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        tr.entrada .valor { color: #059669; }
        tr.saida .valor { color: #dc2626; }
        tr.origem-ajuste { background: #f3f4f6; border-left: 3px solid #9ca3af; }
        .totais { margin-top: 10px; font-weight: bold; }
        .gerencial { margin: 10px 0; padding: 8px 10px; background: #eff6ff; border: 1px solid #bfdbfe; font-size: 9px; }
        .gerencial table { margin-top: 6px; font-size: 9px; }
        .gerencial td { padding: 2px 6px; }
        .pdf-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $empresaNome }}</h1>
    <p class="subtitulo">Movimentações Financeiras — Gerado em {{ now()->format('d/m/Y H:i') }}</p>
    @if(!empty($filtros['data_inicio']) || !empty($filtros['data_fim']))
        <p class="subtitulo">
            Período: {{ !empty($filtros['data_inicio']) ? \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') : '—' }} a {{ !empty($filtros['data_fim']) ? \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y') : '—' }}
            @if(!empty($filtros['conta_bancaria_id']) && $contaExtrato)
                | Conta: {{ $contaExtrato->nome }}
            @endif
            @if(!empty($filtros['tipo']))
                | Tipo: {{ $filtros['tipo'] === 'entrada' ? 'Entrada' : 'Saída' }}
            @endif
        </p>
    @endif

    @if(!empty($extratoGerencial))
        @php $g = $extratoGerencial; @endphp
        <div class="gerencial">
            <strong>Visão gerencial</strong>
            <table>
                <tr><td>Faturamento bruto (rec. vendas)</td><td class="text-right">R$ {{ number_format($g['faturamento_bruto'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><td>Faturamento líquido</td><td class="text-right"><strong>R$ {{ number_format($g['faturamento_liquido'] ?? 0, 2, ',', '.') }}</strong></td></tr>
                <tr><td>Ajustes crédito (entr. ajuste)</td><td class="text-right">R$ {{ number_format($g['ajustes_credito_entrada'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><td>Estornos / ajustes débito (saída ajuste)</td><td class="text-right">R$ {{ number_format($g['ajustes_debito_saida'] ?? 0, 2, ',', '.') }}</td></tr>
                <tr><td>Taxas (bruto) − créd. estorno taxa</td><td class="text-right">R$ {{ number_format($g['custos_taxas_liquido'] ?? 0, 2, ',', '.') }}</td></tr>
            </table>
        </div>
    @endif

    @if($movimentacoes->isEmpty())
        <p>Nenhuma movimentação encontrada para os filtros selecionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Conta</th>
                    <th>Origem</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-right">Valor</th>
                    <th class="text-center">Conciliado</th>
                    <th>Data Conciliação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimentacoes as $m)
                <tr class="{{ $m->tipo }}{{ ($m->origem ?? '') === 'ajuste' ? ' origem-ajuste' : '' }}">
                    <td>{{ $m->data_movimentacao->format('d/m/Y') }}</td>
                    <td>{{ Str::limit($m->descricao, 50) }}</td>
                    <td>{{ $m->contaBancaria?->nome ?? '—' }}</td>
                    <td>{{ $m->origem ?? '—' }}</td>
                    <td class="text-center">{{ $m->tipo === 'entrada' ? 'Entrada' : 'Saída' }}</td>
                    <td class="text-right valor">{{ $m->tipo === 'entrada' ? '' : '-' }}R$ {{ number_format($m->valor, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $m->conciliado ? 'Sim' : 'Não' }}</td>
                    <td>{{ $m->data_conciliacao?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totais">
            <p><strong>Visão contábil (todas as linhas)</strong></p>
            <p>Total entradas: R$ {{ number_format($totalEntradas, 2, ',', '.') }}</p>
            <p>Total saídas: R$ {{ number_format($totalSaidas, 2, ',', '.') }}</p>
            <p>Saldo do período: R$ {{ number_format($totalEntradas - $totalSaidas, 2, ',', '.') }}</p>
            <p>Registros: {{ $movimentacoes->count() }}</p>
        </div>
    @endif
    <p class="pdf-footer">{{ $pdfFooterTexto }}</p>
</body>
</html>
