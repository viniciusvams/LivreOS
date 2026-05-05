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
    <title>Posição de Estoque e Curva ABC</title>
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
    <h1>Posição de Estoque e Curva ABC</h1>
    <p>Valor total em estoque: R$ {{ number_format($valorTotalEstoque, 2, ',', '.') }}</p>
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Código</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Valor total</th>
                <th>ABC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lista ?? [] as $item)
            <tr class="classe-{{ strtolower($item['classe_abc'] ?? 'c') }}">
                <td>{{ $item['nome'] }}</td>
                <td>{{ $item['codigo'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($item['quantidade'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item['valor_total'] ?? 0, 2, ',', '.') }}</td>
                <td>{{ $item['classe_abc'] ?? 'C' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
