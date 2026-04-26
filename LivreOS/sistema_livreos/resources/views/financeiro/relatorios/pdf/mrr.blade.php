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
    <title>MRR - Receita Recorrente Mensal</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Receita Recorrente Mensal (MRR)</h1>
    <p>MRR Total: R$ {{ number_format($mrrTotal, 2, ',', '.') }} — {{ $totalContratos }} contrato(s) ativo(s)</p>
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="text-right">Valor mensal</th>
                <th class="text-right">Contratos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($porCliente ?? [] as $row)
            <tr>
                <td>{{ $row['nome'] }}</td>
                <td class="text-right">R$ {{ number_format($row['valor_mensal'], 2, ',', '.') }}</td>
                <td class="text-right">{{ $row['quantidade'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
