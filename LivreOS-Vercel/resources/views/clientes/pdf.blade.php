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
    <title>Clientes</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .pdf-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $empresaNome }}</h1>
    <p class="subtitulo">Cadastro de Clientes — Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    @if($clientes->isEmpty())
        <p>Nenhum cliente encontrado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Razão Social</th>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Cidade/UF</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $c)
                @php $endereco = $c->enderecos->first(); $cidadeUf = $endereco ? ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? '') : '—'; @endphp
                <tr>
                    <td>{{ $c->nome }}</td>
                    <td>{{ $c->razao_social ?? '—' }}</td>
                    <td>{{ $c->tipo_pessoa_texto }}</td>
                    <td>{{ $c->documento_principal ?? '—' }}</td>
                    <td>{{ $cidadeUf }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">Total: {{ $clientes->count() }} clientes</p>
    @endif
    <p class="pdf-footer">{{ $pdfFooterTexto }}</p>
</body>
</html>
