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

@php
    $empresa = null;
    if (class_exists(\App\Models\Empresa::class)) {
        try {
            $empresa = \App\Models\Empresa::first();
        } catch (\Throwable $e) {
            $empresa = null;
        }
    }
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Histórico de Vendas</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .subtitulo { font-size: 12px; color: #6b7280; margin-bottom: 12px; }
        .venda-bloco { margin-bottom: 16px; page-break-inside: avoid; }
        .venda-header { background: #f3f4f6; padding: 8px 10px; border: 1px solid #e5e7eb; border-bottom: none; font-weight: bold; }
        .venda-header .numero { font-size: 13px; }
        .venda-header .data { font-size: 10px; color: #6b7280; }
        table.tabela-itens { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.tabela-itens th, table.tabela-itens td { border: 1px solid #e5e7eb; padding: 5px 8px; }
        table.tabela-itens th { background: #f9fafb; text-align: left; }
        .text-right { text-align: right; }
        .venda-total { background: #f3f4f6; padding: 6px 10px; border: 1px solid #e5e7eb; border-top: none; font-weight: bold; text-align: right; }
        .venda-desconto { background: #fff7ed; padding: 4px 10px; border: 1px solid #e5e7eb; border-top: none; font-size: 10px; color: #c2410c; text-align: right; }
        .venda-obs { padding: 6px 10px; border: 1px solid #e5e7eb; border-top: none; font-size: 10px; color: #6b7280; background: #f9fafb; white-space: pre-wrap; }
        .sem-itens { padding: 8px; border: 1px solid #e5e7eb; border-top: none; font-style: italic; color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ $empresaNome }}</h1>
    <p class="subtitulo">Histórico de Vendas — Gerado em {{ now()->format('d/m/Y H:i') }}</p>
    @if(!empty($filtros['data_de']) || !empty($filtros['data_ate']))
        <p class="subtitulo">
            Período: {{ $filtros['data_de'] ?? '—' }} a {{ $filtros['data_ate'] ?? '—' }}
            @if(!empty($filtros['status']))
                | Status: {{ $filtros['status'] }}
            @endif
        </p>
    @endif

    @if($vendas->isEmpty())
        <p>Nenhuma venda encontrada para os filtros selecionados.</p>
    @else
        @foreach($vendas as $v)
        <div class="venda-bloco">
            <div class="venda-header">
                <span class="numero">Venda #{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span class="data"> — {{ $v->created_at?->format('d/m/Y H:i') ?? '—' }}
                    | {{ $v->cliente ? ($v->cliente->nome ?? $v->cliente->razao_social ?? 'Consumidor Final') : 'Consumidor Final' }}
                    | {{ $v->status }}
                </span>
            </div>
            @if($v->itens->isEmpty())
                <div class="sem-itens">Sem itens registrados</div>
            @else
                <table class="tabela-itens">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right" width="12%">Qtd</th>
                            <th class="text-right" width="15%">Preço Unit.</th>
                            <th class="text-right" width="15%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($v->itens as $item)
                        <tr>
                            <td>{{ $item->descricao ?? '—' }}</td>
                            <td class="text-right">{{ number_format((float) $item->quantidade, 4, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format((float) $item->total, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @if((float) $v->desconto > 0)
            <div class="venda-desconto">Desconto: - R$ {{ number_format((float) $v->desconto, 2, ',', '.') }}</div>
            @endif
            <div class="venda-total">Total: R$ {{ number_format((float) $v->total_final, 2, ',', '.') }}</div>
            @if(trim((string) ($v->observacoes ?? '')) !== '')
            <div class="venda-obs"><strong>Observação:</strong> {{ trim($v->observacoes) }}</div>
            @endif
        </div>
        @endforeach
    @endif
</body>
</html>
