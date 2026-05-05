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
    <title>Contas a Pagar</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; line-height: 1.35; padding: 30px 25px; }
        h1, h2, h3, p, table { margin: 0; padding: 0; }

        .header { margin-bottom: 10px; border-bottom: 2px solid #ef4444; padding-bottom: 8px; }
        .header h1 { font-size: 15px; font-weight: 700; color: #7f1d1d; margin-bottom: 1px; }
        .header .subtitle { font-size: 9px; color: #6b7280; }
        .header .filtros { font-size: 8px; color: #9ca3af; margin-top: 3px; }

        .resumo { margin-bottom: 10px; }
        .resumo table { width: 100%; border-collapse: collapse; }
        .resumo td { padding: 4px 6px; text-align: center; }
        .resumo .box { border: 1px solid #e5e7eb; border-radius: 3px; background: #f9fafb; padding: 4px 2px; }
        .resumo .box-danger { border-color: #fca5a5; background: #fef2f2; }
        .resumo .box-success { border-color: #86efac; background: #f0fdf4; }
        .resumo .label { font-size: 6.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; display: block; margin-bottom: 1px; }
        .resumo .value { font-size: 10px; font-weight: 700; color: #111827; }
        .resumo .value-danger { color: #dc2626; }
        .resumo .value-success { color: #16a34a; }
        .resumo .count { font-size: 6.5px; color: #9ca3af; display: block; margin-top: 1px; }

        .grupo { margin-bottom: 8px; page-break-inside: avoid; }
        .grupo-header { background: #7f1d1d; color: #fff; padding: 4px 6px; font-size: 8.5px; font-weight: 700; border-radius: 2px 2px 0 0; }
        .grupo-header .grupo-count { font-weight: 400; font-size: 7.5px; opacity: 0.8; float: right; }

        table.dados { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        table.dados th { background: #f3f4f6; color: #374151; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; padding: 3px 4px; border: 1px solid #e5e7eb; }
        table.dados td { padding: 2.5px 4px; border: 1px solid #e5e7eb; font-size: 7.5px; }
        table.dados tr:nth-child(even) td { background: #fafafa; }
        table.dados .text-right { text-align: right; }
        table.dados .text-center { text-align: center; }
        table.dados .font-bold { font-weight: 700; }
        table.dados .text-red { color: #dc2626; }
        table.dados .text-green { color: #16a34a; }
        table.dados .text-orange { color: #ea580c; }
        table.dados .text-gray { color: #6b7280; }
        table.dados .text-muted { color: #9ca3af; font-size: 6.5px; }

        .subtotal-row td { background: #fef2f2 !important; font-weight: 700; font-size: 8px; border-top: 2px solid #fca5a5; }
        .total-row td { background: #7f1d1d !important; color: #fff !important; font-weight: 700; font-size: 8.5px; border: none; padding: 4px; }

        .vencida-marker { color: #dc2626; font-weight: 700; font-size: 6.5px; }

        .footer { margin-top: 10px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 7px; color: #9ca3af; }
        .footer .page-info { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Contas a Pagar</h1>
        <div class="subtitle">Relatório detalhado com agrupamento por status</div>
        @if(!empty($filtrosTexto))
            <div class="filtros">Filtros: {{ implode(' | ', $filtrosTexto) }}</div>
        @endif
    </div>

    <div class="resumo">
        <table>
            <tr>
                <td width="20%">
                    <div class="box">
                        <span class="label">Total Bruto</span>
                        <span class="value">R$ {{ number_format($totais['valor'], 2, ',', '.') }}</span>
                        <span class="count">{{ $totalRegistros }} título(s)</span>
                    </div>
                </td>
                <td width="20%">
                    <div class="box box-success">
                        <span class="label">Total Pago</span>
                        <span class="value value-success">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</span>
                    </div>
                </td>
                <td width="20%">
                    <div class="box box-danger">
                        <span class="label">Total Pendente</span>
                        <span class="value value-danger">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</span>
                    </div>
                </td>
                <td width="14%">
                    <div class="box">
                        <span class="label">Juros</span>
                        <span class="value">R$ {{ number_format($totais['juros'], 2, ',', '.') }}</span>
                    </div>
                </td>
                <td width="13%">
                    <div class="box">
                        <span class="label">Multa</span>
                        <span class="value">R$ {{ number_format($totais['multa'], 2, ',', '.') }}</span>
                    </div>
                </td>
                <td width="13%">
                    <div class="box">
                        <span class="label">Desconto</span>
                        <span class="value">R$ {{ number_format($totais['desconto'], 2, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($grupos as $nomeGrupo => $grupo)
    <div class="grupo">
        <div class="grupo-header">
            {{ $nomeGrupo }}
            <span class="grupo-count">{{ $grupo['itens']->count() }} título(s) — Valor: R$ {{ number_format($grupo['subtotais']['valor'], 2, ',', '.') }}</span>
        </div>
        <table class="dados">
            <thead>
                <tr>
                    <th style="width: 16%;">Fornecedor</th>
                    <th style="width: 18%;">Descrição</th>
                    <th style="width: 8%;" class="text-center">Doc.</th>
                    <th style="width: 8%;" class="text-center">Vencimento</th>
                    <th style="width: 8%;" class="text-center">Pagamento</th>
                    <th style="width: 10%;" class="text-right">Valor</th>
                    <th style="width: 7%;" class="text-right">Juros</th>
                    <th style="width: 7%;" class="text-right">Multa</th>
                    <th style="width: 7%;" class="text-right">Desconto</th>
                    <th style="width: 10%;" class="text-right">Pago</th>
                    <th style="width: 10%;" class="text-right">Pendente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupo['itens'] as $c)
                @php
                    $vencida = in_array($c->status, ['aberto', 'parcial']) && $c->data_vencimento && $c->data_vencimento->format('Y-m-d') < now()->toDateString();
                @endphp
                <tr>
                    <td>{{ Str::limit($c->fornecedor->nome ?? $c->fornecedor->razao_social ?? '—', 24) }}</td>
                    <td>{{ Str::limit($c->descricao, 28) }}</td>
                    <td class="text-center">{{ $c->numero_documento ? Str::limit($c->numero_documento, 10) : '—' }}</td>
                    <td class="text-center">
                        {{ $c->data_vencimento ? $c->data_vencimento->format('d/m/Y') : '—' }}
                        @if($vencida)<br><span class="vencida-marker">VENCIDA</span>@endif
                    </td>
                    <td class="text-center">
                        @if($c->formaPagamento)
                            {{ Str::limit($c->formaPagamento->nome, 12) }}
                        @else
                            <span class="text-gray">—</span>
                        @endif
                    </td>
                    <td class="text-right font-bold">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $c->juros > 0 ? 'R$ ' . number_format($c->juros, 2, ',', '.') : '—' }}</td>
                    <td class="text-right">{{ $c->multa > 0 ? 'R$ ' . number_format($c->multa, 2, ',', '.') : '—' }}</td>
                    <td class="text-right">{{ $c->desconto > 0 ? 'R$ ' . number_format($c->desconto, 2, ',', '.') : '—' }}</td>
                    <td class="text-right text-green">R$ {{ number_format($c->valor_pago, 2, ',', '.') }}</td>
                    <td class="text-right {{ ($c->valor_pendente ?? 0) > 0 ? 'text-orange font-bold' : 'text-gray' }}">
                        R$ {{ number_format($c->valor_pendente ?? 0, 2, ',', '.') }}
                    </td>
                </tr>
                @endforeach

                <tr class="subtotal-row">
                    <td colspan="5" class="text-right">Subtotal {{ $nomeGrupo }}:</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['valor'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['juros'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['multa'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['desconto'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['pago'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grupo['subtotais']['pendente'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    <table class="dados">
        <tr class="total-row">
            <td style="width: 50%;" class="text-right">TOTAL GERAL ({{ $totalRegistros }} títulos):</td>
            <td style="width: 10%;" class="text-right">R$ {{ number_format($totais['valor'], 2, ',', '.') }}</td>
            <td style="width: 7%;" class="text-right">R$ {{ number_format($totais['juros'], 2, ',', '.') }}</td>
            <td style="width: 7%;" class="text-right">R$ {{ number_format($totais['multa'], 2, ',', '.') }}</td>
            <td style="width: 7%;" class="text-right">R$ {{ number_format($totais['desconto'], 2, ',', '.') }}</td>
            <td style="width: 10%;" class="text-right">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</td>
            <td style="width: 10%;" class="text-right">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Gerado em {{ now()->format('d/m/Y H:i') }}
        <span class="page-info">{{ $totalRegistros }} registro(s) — Valores em Reais (R$)</span>
    </div>
</body>
</html>
