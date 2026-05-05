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
    <title>Contas a Receber - {{ $dataInicio }} a {{ $dataFim }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 4px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 12px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Contas a Receber por Período</h1>
    <p class="subtitle">Vencimento: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Vencimento</th>
                <th class="text-right">Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contas as $c)
            <tr>
                <td>{{ $c->cliente->nome ?? $c->cliente->razao_social ?? '—' }}</td>
                <td>{{ $c->descricao }}</td>
                <td>{{ $c->data_vencimento ? $c->data_vencimento->format('d/m/Y') : '—' }}</td>
                <td class="text-right">R$ {{ number_format($c->valor, 2, ',', '.') }}</td>
                <td>{{ ucfirst($c->status ?? '') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Gerado em {{ now()->format('d/m/Y H:i') }} — {{ $contas->count() }} registro(s)</p>
</body>
</html>
