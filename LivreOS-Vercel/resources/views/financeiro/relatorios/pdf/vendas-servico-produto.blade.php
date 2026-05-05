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

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vendas por Serviço e Produto</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        h2 { font-size: 11px; margin: 12px 0 6px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Vendas por Serviço e Produto</h1>
    @php $critPdf = $criterio ?? 'operacional'; @endphp
    <p>
        Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
        @if(!empty($tecnico_id))
            · Técnico #{{ $tecnico_id }}
        @endif
        @if(!empty($sla_filtro))
            · SLA: {{ $sla_filtro }}
        @endif
    </p>
    @php $incPdf = $incluir_pedidos ?? false; @endphp
    @if($critPdf === 'operacional')
    <p>Fonte (sem financeiro): OS fechadas (Encerrada/Concluída/Finalizado etc.) com COALESCE(data conclusão, última alteração) no período.@if($incPdf) Inclui pedidos (data do pedido).@else Sem pedidos de venda.@endif Linhas faturáveis; OS com os_gerada_id excluídas da soma por OS.</p>
    @else
    <p>Fonte: recebimentos em contas a receber (pago/parcial, data de recebimento no período), rateados pelas linhas faturáveis do pedido ou OS; linhas sem quantidade/horas na OS ficam fora (orçamento de referência). Adiantamentos de OS excluídos.</p>
    @endif
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Serviços</h2>
    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th class="text-right">Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($servicos ?? [] as $row)
            <tr>
                <td>{{ $row['nome'] }}</td>
                <td class="text-right">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Produtos</h2>
    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th class="text-right">Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($produtos ?? [] as $row)
            <tr>
                <td>{{ $row['nome'] }}</td>
                <td class="text-right">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
