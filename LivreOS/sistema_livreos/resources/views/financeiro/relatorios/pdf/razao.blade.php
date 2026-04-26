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
    <title>Razão - {{ $planoConta->codigo ?? '' }} {{ $planoConta->nome ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Razão por conta</h1>
    <p class="subtitle">Conta: {{ $planoConta->codigo ?? '' }} - {{ $planoConta->nome ?? '' }} | Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th class="text-right">Entrada</th>
                <th class="text-right">Saída</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            <tr>
                <td>{{ $item['data'] }}</td>
                <td>{{ $item['descricao'] }}</td>
                <td class="text-right">{{ $item['entrada'] > 0 ? 'R$ ' . number_format($item['entrada'], 2, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $item['saida'] > 0 ? 'R$ ' . number_format($item['saida'], 2, ',', '.') : '-' }}</td>
                <td class="text-right">R$ {{ number_format($item['saldo'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Gerado em {{ now()->format('d/m/Y H:i') }} — Relatório Financeiro</p>
</body>
</html>
