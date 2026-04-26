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
    <title>Receitas x Despesas - {{ $dataInicio }} a {{ $dataFim }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 4px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .valor-pos { color: #0d9488; }
        .valor-neg { color: #dc2626; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Receitas x Despesas por Mês</h1>
    <p class="subtitle">Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}@if(!empty($centroCusto)) | Centro de custo: {{ $centroCusto->nome }}@endif</p>

    <table>
        <thead>
            <tr>
                <th>Mês</th>
                <th class="text-right">Receitas</th>
                <th class="text-right">Despesas</th>
                <th class="text-right">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($meses as $row)
            <tr>
                <td>{{ $row['mes_nome'] }}</td>
                <td class="text-right valor-pos">R$ {{ number_format($row['receitas'], 2, ',', '.') }}</td>
                <td class="text-right valor-neg">R$ {{ number_format($row['despesas'], 2, ',', '.') }}</td>
                <td class="text-right {{ $row['resultado'] >= 0 ? 'valor-pos' : 'valor-neg' }}">R$ {{ number_format($row['resultado'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Gerado em {{ now()->format('d/m/Y H:i') }} — Relatório Financeiro</p>
</body>
</html>
