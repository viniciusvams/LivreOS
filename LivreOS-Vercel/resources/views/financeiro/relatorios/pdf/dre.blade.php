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
    <title>DRE - {{ $dataInicio }} a {{ $dataFim }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 0; border-bottom: 1px solid #eee; }
        td:last-child { text-align: right; font-weight: 500; }
        .total { border-top: 2px solid #333; font-weight: bold; font-size: 12px; padding-top: 8px; }
        .valor-pos { color: #0d9488; }
        .valor-neg { color: #dc2626; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>DRE - Demonstrativo do Resultado do Exercício</h1>
    <p class="subtitle">Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}@if(!empty($centroCusto)) | Centro de custo: {{ $centroCusto->nome }}@endif</p>

    <table>
        <tr>
            <td>Receitas Operacionais</td>
            <td class="valor-pos">R$ {{ number_format($receitasOperacionais, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>(-) Despesas Operacionais</td>
            <td class="valor-neg">R$ {{ number_format($despesasOperacionais, 2, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Receita Líquida</td>
            <td class="{{ $receitaLiquida >= 0 ? 'valor-pos' : 'valor-neg' }}">R$ {{ number_format($receitaLiquida, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>(+) Receitas Não Operacionais</td>
            <td class="valor-pos">R$ {{ number_format($receitasNaoOperacionais, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>(-) Despesas Financeiras</td>
            <td class="valor-neg">R$ {{ number_format($despesasFinanceiras, 2, ',', '.') }}</td>
        </tr>
        <tr class="total">
            <td>Resultado Antes do IR</td>
            <td class="{{ $resultadoAntesIR >= 0 ? 'valor-pos' : 'valor-neg' }}">R$ {{ number_format($resultadoAntesIR, 2, ',', '.') }}</td>
        </tr>
    </table>

    <p class="footer">Gerado em {{ now()->format('d/m/Y H:i') }} — Relatório Financeiro</p>
</body>
</html>
