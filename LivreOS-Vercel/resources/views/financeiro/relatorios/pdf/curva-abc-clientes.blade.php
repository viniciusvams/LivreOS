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
    <title>Curva ABC de Clientes</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .classe-a { background: #d4edda; }
        .classe-b { background: #fff3cd; }
        .classe-c { background: #e2e3e5; }
    </style>
</head>
<body>
    <h1>Curva ABC de Clientes</h1>
    <p>Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</p>
    <p>Total recebido: R$ {{ number_format($totalGeral, 2, ',', '.') }}</p>
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th class="text-right">Receita</th>
                <th class="text-right">% Acum.</th>
                <th>Classe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lista ?? [] as $i => $item)
            <tr class="classe-{{ strtolower($item['classe'] ?? 'c') }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['nome'] }}</td>
                <td class="text-right">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item['acumulado_pct'] ?? 0, 1, ',', '.') }}%</td>
                <td>{{ $item['classe'] ?? 'C' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
