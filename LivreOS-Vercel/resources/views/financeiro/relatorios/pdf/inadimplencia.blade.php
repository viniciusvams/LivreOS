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
    <title>Inadimplência - Aging de Cobrança</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        .faixa { margin-bottom: 14px; }
        .faixa-titulo { font-weight: bold; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Inadimplência (Aging de Cobrança)</h1>
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @foreach($faixas ?? [] as $faixa)
    @if(count($faixa['itens'] ?? []) > 0)
    <div class="faixa">
        <div class="faixa-titulo">{{ $faixa['label'] }} — Total: R$ {{ number_format($faixa['total'], 2, ',', '.') }}</div>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Vencimento</th>
                    <th>Dias</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Juros sug.</th>
                    <th class="text-right">Multa sug.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($faixa['itens'] as $item)
                <tr>
                    <td>{{ $item['cliente'] }}</td>
                    <td>{{ $item['vencimento'] }}</td>
                    <td>{{ $item['dias'] }}</td>
                    <td class="text-right">R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item['juros_sugerido'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item['multa_sugerido'] ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endforeach

    <p class="footer">Relatório Financeiro</p>
</body>
</html>
