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
    $dataFormatada = \Carbon\Carbon::parse($data)->format('d/m/Y');
    $tituloTecnico = $tecnicoIdFiltro && isset($tecnicos[$tecnicoIdFiltro])
        ? ' - ' . ($tecnicos[$tecnicoIdFiltro]->name ?? '')
        : '';
    $appName = config('app.name', 'LivreOS');
    $emitidoEm = now()->format('d/m/Y \à\s H:i');
    $empresaNome = $empresa?->nome ?? 'Empresa';
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaEndereco = $empresa && ($empresa->logradouro || $empresa->cidade)
        ? trim($empresa->logradouro . ' ' . ($empresa->numero ?? '') . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . ($empresa->bairro ?? '') . ' - ' . ($empresa->cidade ?? '') . '/' . ($empresa->estado ?? ''))
        : null;
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/' . $empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Rota do dia {{ $dataFormatada }}{{ $tituloTecnico }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 12mm; margin-bottom: 14mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111827; margin: 0; padding: 0; padding-bottom: 36px; }
        .pdf-wrap { width: 100%; max-width: 100%; }
        .pdf-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px 0; margin-bottom: 16px; }
        .pdf-header table { width: auto; max-width: 100%; border: 0; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: top; font-size: 10px; color: #475569; }
        .pdf-header .td-logo { width: 1px; padding-right: 13px; white-space: nowrap; }
        .pdf-header .td-logo img { max-width: 56px; max-height: 52px; display: block; }
        .pdf-header .empresa-nome { font-size: 14px; font-weight: bold; color: #1e293b; margin: 0 0 4px 0; line-height: 1.2; }
        .pdf-header .empresa-linha { margin: 0 0 1px 0; line-height: 1.35; }
        .pdf-body { width: 100%; }
        .pdf-footer { position: fixed; bottom: 0; left: 0; right: 0; height: 32px; padding: 6px 12mm; border-top: 1px solid #e2e8f0; background: #f8fafc; font-size: 9px; color: #9ca3af; text-align: center; }
        h1 { font-size: 16px; margin: 0 0 12px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; }
        .tecnico-header { background: #e5e7eb; font-weight: bold; padding: 8px; margin-top: 16px; }
        .deslocamento { background: #f9fafb; font-size: 11px; color: #6b7280; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="pdf-wrap">
        <header class="pdf-header">
            <table>
                <tr>
                    @if($empresaLogo)
                        <td class="td-logo"><img src="{{ $empresaLogo }}" alt=""></td>
                    @endif
                    <td>
                        <p class="empresa-nome">{{ $empresaNome }}</p>
                        @if($empresaRazao)<p class="empresa-linha">{{ $empresaRazao }}</p>@endif
                        @if($empresaCnpj)<p class="empresa-linha">CNPJ: {{ $empresaCnpj }}</p>@endif
                        @if($empresaEndereco)<p class="empresa-linha">{{ $empresaEndereco }}</p>@endif
                        @if($empresaTelefone || $empresaEmail)
                            <p class="empresa-linha">@if($empresaTelefone)Tel: {{ $empresaTelefone }}@endif @if($empresaTelefone && $empresaEmail) · @endif @if($empresaEmail){{ $empresaEmail }}@endif</p>
                        @endif
                    </td>
                </tr>
            </table>
        </header>
        <div class="pdf-body">
            <h1>Rota do dia {{ $dataFormatada }}{{ $tituloTecnico }}</h1>
            <p class="subtitulo">Atendimentos agrupados por técnico</p>

    @foreach($porTecnico as $tecnicoId => $lista)
        @php
            $nomeTecnico = $tecnicoId ? ($tecnicos->get($tecnicoId)->name ?? 'Sem técnico') : 'Sem técnico';
            $listaOrdenada = $lista->sortBy('data_hora_atendimento')->values();
            $segmentos = $segmentosPorTecnico[$tecnicoId] ?? [];
        @endphp
        <div class="tecnico-header">{{ $nomeTecnico }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:10%">Hora</th>
                    <th style="width:22%">Cliente</th>
                    <th>Endereço</th>
                    <th style="width:12%">Status</th>
                    <th style="width:12%">Prioridade</th>
                    <th style="width:14%">Deslocamento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($listaOrdenada as $index => $atendimento)
                    @if($index > 0 && isset($segmentos[$index - 1]))
                        <tr class="deslocamento">
                            <td colspan="5" style="border-left:1px solid #e5e7eb;">↳ Deslocamento</td>
                            <td>{{ $segmentos[$index - 1]['distance'] }} · ~{{ $segmentos[$index - 1]['duration'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $atendimento->data_hora_atendimento->format('H:i') }}</td>
                        <td>{{ $atendimento->cliente->nome ?? '—' }}</td>
                        <td>{{ $atendimento->endereco_para_mapa ?: '—' }}</td>
                        <td>{{ $atendimento->status->nome ?? '—' }}</td>
                        <td>{{ $atendimento->prioridade->nome ?? '—' }}</td>
                        <td>—</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if($porTecnico->isEmpty())
        <p>Nenhum atendimento para esta data.</p>
    @endif
        </div>
    </div>
    <footer class="pdf-footer">Emitido em {{ $emitidoEm }} · Gerado por: {{ $userName ?? 'Sistema' }} · {{ config('app.name', 'LivreOS') }} - {{ config('app.tagline', 'ERP Open Source Livre') }}</footer>
</body>
</html>
