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
    $nome = $ordem->equipamento_nome ?? $ordem->equipamento?->marca ?? 'Equipamento';
    if ($ordem->equipamento) {
        $nome = trim(($ordem->equipamento->marca ?? '') . ' ' . ($ordem->equipamento->modelo ?? '')) ?: $nome;
    }
    $marca = $ordem->equipamento_marca ?? $ordem->equipamento?->marca ?? '-';
    $modelo = $ordem->equipamento_modelo ?? $ordem->equipamento?->modelo ?? '-';
    $numeroSerie = $ordem->equipamento_numero_serie ?? $ordem->equipamento?->numero_serie ?? '-';
    $numeroPatrimonio = $ordem->equipamento_numero_patrimonio ?? $ordem->equipamento?->numero_patrimonio ?? null;
    $osDisplay = format_os_display($ordem);
    $unlockType = $ordem->equipamento_unlock_type ?? null;
    $unlockCode = $ordem->equipamento_unlock_code ?? null;
    $senhaExibir = null;
    $patternSvgBase64 = null;
    if ($unlockType === 'pin' && !empty(trim((string)$unlockCode))) {
        $senhaExibir = 'Senha: ' . $unlockCode;
    } elseif ($unlockType === 'pattern' && !empty(trim((string)$unlockCode))) {
        $patternPoints = [];
        $decoded = is_string($unlockCode) ? json_decode($unlockCode, true) : $unlockCode;
        if (is_array($decoded)) {
            $patternPoints = array_values(array_map('intval', $decoded));
        }
        $coords = [ 0 => [20,20], 1 => [50,20], 2 => [80,20], 3 => [20,50], 4 => [50,50], 5 => [80,50], 6 => [20,80], 7 => [50,80], 8 => [80,80] ];
        if (count($patternPoints) > 0) {
            $pathD = '';
            foreach ($patternPoints as $i => $idx) {
                if (isset($coords[$idx])) {
                    $x = $coords[$idx][0]; $y = $coords[$idx][1];
                    $pathD .= ($i === 0 ? "M {$x},{$y}" : " L {$x},{$y}");
                }
            }
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">';
            if ($pathD !== '') {
                $svg .= '<path d="' . htmlspecialchars($pathD) . '" fill="none" stroke="#1f2937" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>';
            }
            for ($i = 0; $i < 9; $i++) {
                $inPattern = in_array($i, $patternPoints, true);
                $c = $coords[$i] ?? [0, 0];
                $fill = $inPattern ? '#1f2937' : '#d1d5db';
                $svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="6" fill="' . $fill . '"/>';
            }
            $svg .= '</svg>';
            $patternSvgBase64 = base64_encode($svg);
        } else {
            $senhaExibir = 'Desbloqueio: Padrão (desenho)';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Etiqueta - {{ $nome }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; padding: 12px; }
        .btn-print { margin-top: 12px; padding: 8px 16px; background: #3b82f6; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-print:hover { background: #2563eb; }
        .etiqueta {
            width: 70mm;
            padding: 3mm;
            border: 1px dashed #ccc;
            display: flex;
            flex-direction: row;
            gap: 3mm;
            align-items: flex-start;
            page-break-inside: avoid;
        }
        .etiqueta-qr { flex-shrink: 0; }
        .etiqueta-qr img { width: 35mm; height: 35mm; min-width: 35mm; min-height: 35mm; display: block; }
        .etiqueta-dados { flex: 1; min-width: 0; font-size: 9px; }
        .etiqueta-dados .linha { margin-bottom: 0.5mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .etiqueta-dados .linha-senha { white-space: normal; overflow: visible; text-overflow: unset; word-break: break-all; font-weight: 600; font-size: 10px; margin-top: 1mm; }
        .etiqueta-dados .pattern-padrao { margin-top: 1mm; }
        .etiqueta-dados .pattern-padrao img { width: 18mm; height: 18mm; display: block; }
        .etiqueta-dados .linha strong { font-size: 9px; color: #374151; }
        .etiqueta-hint { font-size: 7px; color: #9ca3af; margin-top: 1mm; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            html, body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .etiqueta { border: none; }
            .btn-print { display: none !important; }
            body { padding: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="etiqueta">
        <div class="etiqueta-qr">
            <img src="{{ $qrUrl }}" alt="QR Code" width="300" height="300">
        </div>
        <div class="etiqueta-dados">
            <div class="linha"><strong>{{ $nome }}</strong></div>
            @if($marca !== '-')<div class="linha">Marca: {{ $marca }}</div>@endif
            @if($modelo !== '-')<div class="linha">Modelo: {{ $modelo }}</div>@endif
            @if($numeroSerie !== '-')<div class="linha">Série: {{ $numeroSerie }}</div>@endif
            @if($numeroPatrimonio)<div class="linha">Patrimônio: {{ $numeroPatrimonio }}</div>@endif
            <div class="linha">OS: {{ $osDisplay }}</div>
            @if($senhaExibir)<div class="linha linha-senha">{{ $senhaExibir }}</div>@endif
            @if($patternSvgBase64)
                <div class="pattern-padrao">
                    <span style="font-size: 8px; font-weight: 600;">Padrão:</span>
                    <img src="data:image/svg+xml;base64,{{ $patternSvgBase64 }}" alt="Padrão de desbloqueio" />
                </div>
            @endif
            <div class="etiqueta-hint">Escaneie o QR para acessar a OS</div>
        </div>
    </div>
    <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
</body>
</html>
