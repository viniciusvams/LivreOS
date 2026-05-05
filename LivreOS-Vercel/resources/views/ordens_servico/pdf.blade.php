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
    $empresa = \App\Models\Empresa::first();
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Ordens de Serviço</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .text-right { text-align: right; }
        .pdf-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $empresaNome }}</h1>
    <p class="subtitulo">Ordens de Serviço — Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @if($ordens->isEmpty())
        <p>Nenhuma ordem de serviço encontrada.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Abertura</th>
                    <th>Conclusão</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordens as $o)
                <tr>
                    <td>{{ format_os_display($o) }}</td>
                    <td>{{ $o->cliente?->nome ?? '—' }}</td>
                    <td>{{ $o->status ?? '—' }}</td>
                    <td>{{ $o->prioridade ?? '—' }}</td>
                    <td>{{ $o->data_abertura ? $o->data_abertura->format('d/m/Y') : '—' }}</td>
                    <td>{{ $o->real_conclusao ? \Carbon\Carbon::parse($o->real_conclusao)->format('d/m/Y') : ($o->prevista_conclusao ? \Carbon\Carbon::parse($o->prevista_conclusao)->format('d/m/Y') : '—') }}</td>
                    <td class="text-right">{{ $o->total_geral ? number_format($o->total_geral, 2, ',', '.') : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">Total: {{ $ordens->count() }} ordens</p>
    @endif
    <p class="pdf-footer">{{ $pdfFooterTexto }}</p>
</body>
</html>
