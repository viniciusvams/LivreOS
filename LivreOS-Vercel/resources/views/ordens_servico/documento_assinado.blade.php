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
    // Função para processar HTML e remover emojis para PDF
    function processarTextoParaPDF($html) {
        if (empty($html)) {
            return '';
        }
        
        // Converter HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remover todos os emojis usando regex (substituir por espaço vazio)
        // Ranges Unicode para emojis
        $html = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $html); // Emojis diversos
        $html = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $html); // Símbolos diversos
        $html = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $html); // Dingbats
        $html = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $html); // Emojis de faces
        $html = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $html); // Transporte e símbolos
        $html = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $html); // Emojis suplementares
        $html = preg_replace('/[\x{1FA00}-\x{1FAFF}]/u', '', $html); // Emojis estendidos
        $html = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $html); // Bandeiras
        $html = preg_replace('/[\x{200D}]/u', '', $html); // Zero-width joiner
        $html = preg_replace('/[\x{FE0F}]/u', '', $html); // Variation selector
        
        // Limpar HTML mantendo apenas tags básicas suportadas pelo DomPDF
        // Permitir: p, br, strong, b, em, i, u, ul, ol, li, div, span, h1-h6
        $html = strip_tags($html, '<p><br><br/><strong><b><em><i><u><ul><ol><li><div><span><h1><h2><h3><h4><h5><h6>');
        
        // Converter quebras de linha em <br> (apenas fora de tags HTML)
        $html = preg_replace('/(?<!>)\r\n|\r|\n(?!<)/', '<br>', $html);
        
        // Limpar múltiplos espaços (mas preservar dentro de tags)
        $html = preg_replace('/[ \t]+/', ' ', $html);
        
        // Limpar múltiplos <br> consecutivos (máximo 2)
        $html = preg_replace('/(<br\s*\/?>){3,}/i', '<br><br>', $html);
        
        // Garantir que parágrafos tenham espaçamento adequado
        $html = str_replace('</p>', '</p>', $html);
        $html = preg_replace('/<p>/i', '<p>', $html);
        
        // Limpar espaços no início e fim de cada tag
        $html = preg_replace('/>\s+/', '>', $html);
        $html = preg_replace('/\s+</', '<', $html);
        
        // Remover tags vazias
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        $html = preg_replace('/<div>\s*<\/div>/i', '', $html);
        
        return trim($html);
    }
    
    $empresaNome = $empresa?->nome ?? 'Empresa';
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogoPath = ($empresa?->logo_path) ? storage_path('app/public/' . $empresa->logo_path) : null;
    $empresaLogoData = null;
    if ($empresaLogoPath && file_exists($empresaLogoPath)) {
        $mime = @mime_content_type($empresaLogoPath) ?: 'image/png';
        $empresaLogoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($empresaLogoPath));
    }

    $clienteBase = $ordem->unidade ?? $ordem->cliente;
    $contato = $ordem->contato;
    $endereco = $ordem->endereco ?? $clienteBase?->enderecos?->firstWhere('principal', true);

    $enderecoTexto = $endereco
        ? trim($endereco->logradouro . ' ' . $endereco->numero . ($endereco->complemento ? ' - ' . $endereco->complemento : '') . ', ' . $endereco->bairro . ' - ' . $endereco->cidade . '/' . $endereco->estado)
        : '-';

    $dataAbertura = optional($ordem->data_abertura)->format('d/m/Y H:i') ?: '-';
    $previstaInicio = optional($ordem->prevista_inicio)->format('d/m/Y')
        ?: optional($ordem->data_abertura)->format('d/m/Y')
        ?: '-';
    $previstaConclusao = optional($ordem->prevista_conclusao)->format('d/m/Y')
        ?: optional($ordem->real_conclusao)->format('d/m/Y')
        ?: '-';

    $totalProdutos = $ordem->produtos->sum('total');
    $totalServicos = $ordem->servicos->sum('total');
    $totalGeral = $ordem->total_geral ?? ($totalProdutos + $totalServicos);
    $descontos = $ordem->total_descontos ?? 0;
    $acrescimos = $ordem->total_acrescimos ?? $ordem->acrescimos_global ?? 0;
    $garantiaDias = $ordem->garantia_dias ?? null;

    $assinaturaTecnico = $ordem->assinaturaTecnico;
    $assinaturaCliente = $ordem->assinaturaCliente;
    
    // Dados de segurança - podem vir do controller ou do documento ativo
    $hashSha256 = $hashSha256 ?? null;
    $hashMd5 = $hashMd5 ?? null;
    $assinaturaDigital = $assinaturaDigital ?? null;
    
    // Se não foram passados pelo controller, buscar do documento ativo
    if (!$hashSha256 && !$hashMd5 && !$assinaturaDigital) {
        $documentoAtivo = $ordem->documentoAtivo;
        if ($documentoAtivo) {
            $hashSha256 = $documentoAtivo->metadata['seguranca']['hash_sha256'] ?? null;
            $hashMd5 = $documentoAtivo->metadata['seguranca']['hash_md5'] ?? null;
            $assinaturaDigital = $documentoAtivo->metadata['seguranca']['assinatura_digital'] ?? null;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>{{ format_os_display($ordem) }} - Assinado</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { 
            size: A4; 
            margin-top: 25mm !important;
            margin-right: 20mm !important;
            margin-bottom: 20mm !important;
            margin-left: 20mm !important;
        }
        body { 
            font-family: Arial, Helvetica, sans-serif; 
            color: #111827; 
            font-size: 12px; 
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .page { 
            width: 100%;
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        header { 
            display: table; 
            width: 100%; 
            border-bottom: 2px solid #e5e7eb; 
            padding-bottom: 10px; 
            margin-bottom: 12px; 
        }
        .header-row { display: table-row; }
        .header-cell { 
            display: table-cell; 
            vertical-align: top; 
            padding: 0 6px; 
        }
        .logo { width: 120px; }
        .logo img { max-width: 120px; height: auto; }
        .emitente h1 { font-size: 16px; margin: 0 0 4px 0; }
        .emitente p { margin: 1px 0; font-size: 11px; color: #374151; }
        .contato { text-align: right; }
        .titulo { margin: 12px 0; display: table; width: 100%; }
        .titulo h2 { font-size: 18px; margin: 0; display: table-cell; }
        .titulo span { font-size: 10px; color: #6b7280; display: table-cell; text-align: right; vertical-align: bottom; }
        .bloco { margin-top: 10px; page-break-inside: avoid; }
        .bloco h3 { 
            margin: 0 0 5px 0; 
            font-size: 11px; 
            text-transform: uppercase; 
            color: #374151; 
            border-bottom: 1px solid #e5e7eb; 
            padding-bottom: 3px; 
        }
        .dados { display: table; width: 100%; font-size: 11px; }
        .dados-row { display: table-row; }
        .dados-cell { display: table-cell; padding: 2px 8px 2px 0; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px; 
            margin-top: 5px; 
            page-break-inside: avoid;
        }
        th, td { 
            border: 1px solid #e5e7eb; 
            padding: 5px 4px; 
        }
        th { 
            background: #f3f4f6; 
            text-align: left; 
            font-size: 9px; 
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totais { margin-top: 10px; display: table; width: 100%; }
        .totais table { width: 280px; margin-left: auto; }
        .assinaturas { 
            display: table; 
            width: 100%; 
            margin-top: 15px; 
            border-top: 2px solid #d1d5db; 
            padding-top: 10px; 
            page-break-inside: avoid;
        }
        .assinaturas-row { display: table-row; }
        .assinaturas-cell { 
            display: table-cell; 
            width: 50%; 
            vertical-align: top; 
            padding: 0 8px; 
        }
        .assinatura-box { 
            border: 1px solid #d1d5db; 
            padding: 6px; 
            min-height: 70px; 
            text-align: center; 
        }
        .assinatura-box img { 
            max-width: 100%; 
            max-height: 55px; 
        }
        .assinatura-info { 
            font-size: 8px; 
            color: #6b7280; 
            margin-top: 3px; 
            line-height: 1.3;
        }
        .observacao { 
            border: 1px solid #e5e7eb; 
            padding: 8px; 
            background: #f9fafb; 
            font-size: 10px; 
            margin-top: 5px; 
            line-height: 1.5;
            word-wrap: break-word;
        }
        .observacao p {
            margin: 0 0 5px 0;
        }
        .observacao p:last-child {
            margin-bottom: 0;
        }
        .observacao strong, .observacao b {
            font-weight: bold;
            color: #111827;
        }
        .observacao em, .observacao i {
            font-style: italic;
        }
        .observacao ul, .observacao ol {
            margin: 5px 0 5px 20px;
            padding-left: 15px;
        }
        .observacao li {
            margin: 2px 0;
        }
        .observacao h1, .observacao h2, .observacao h3, .observacao h4, .observacao h5, .observacao h6 {
            margin: 8px 0 4px 0;
            font-weight: bold;
        }
        .observacao h1 { font-size: 14px; }
        .observacao h2 { font-size: 13px; }
        .observacao h3 { font-size: 12px; }
        .observacao h4 { font-size: 11px; }
        .observacao h5 { font-size: 10px; }
        .observacao h6 { font-size: 9px; }
        .integridade { 
            margin-top: 15px; 
            border-top: 2px solid #d1d5db; 
            padding-top: 10px; 
            font-size: 8px; 
            color: #6b7280; 
            page-break-inside: avoid;
        }
        .integridade h4 { 
            font-size: 10px; 
            margin-bottom: 6px; 
            color: #374151; 
            font-weight: bold;
        }
        .integridade table { 
            font-size: 8px; 
            margin-top: 4px;
        }
        .integridade td { 
            padding: 3px 5px; 
            line-height: 1.4;
            vertical-align: top;
        }
        .integridade td strong {
            color: #111827;
            font-weight: bold;
        }
        .titulo { margin: 10px 0; }
        .totais { margin-top: 8px; }
        .totais table { width: 260px; }
        /* Equipamento & Segurança (split view + padrão senha) */
        .equipamento-container {
            display: table;
            width: 100%;
            border: 1px solid #e5e7eb;
            padding: 8px;
            border-radius: 6px;
            font-size: 11px;
            margin-top: 4px;
        }
        .equipamento-row { display: table-row; }
        .info-tecnica {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding-right: 12px;
        }
        .info-tecnica p { margin: 2px 0 4px 0; padding-bottom: 2px; border-bottom: 1px dashed #f3f4f6; font-size: 11px; }
        .info-seguranca {
            display: table-cell;
            width: 35%;
            vertical-align: middle;
            background-color: #f9fafb;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .pattern-grid { width: 56px; height: 56px; margin: 4px auto; display: block; }
        .desbloqueio-label { font-size: 9px; font-weight: bold; color: #374151; text-transform: uppercase; margin-bottom: 2px; }
        .desbloqueio-tipo { font-size: 10px; color: #111827; margin-top: 2px; }
        .desbloqueio-obs { font-size: 9px; color: #6b7280; margin-top: 4px; }
        /* Checklists */
        .checklist-print { border: 1px solid #e5e7eb; padding: 8px; margin-top: 6px; font-size: 10px; page-break-inside: avoid; }
        .checklist-print h4 { margin: 0 0 6px 0; font-size: 11px; color: #374151; }
        .checklist-print .campo { margin-bottom: 3px; }
        .checklist-print .campo-label { font-weight: bold; color: #4b5563; }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div class="header-row">
                <div class="header-cell logo">
                    @if($empresaLogoData)
                        <img src="{{ $empresaLogoData }}" alt="Logotipo">
                    @endif
                </div>
                <div class="header-cell emitente">
                    <h1>{{ $empresaNome }}</h1>
                    @if($empresaRazao)<p>{{ $empresaRazao }}</p>@endif
                    @if($empresaCnpj)<p>CNPJ: {{ $empresaCnpj }}</p>@endif
                    @if($empresa && ($empresa->logradouro || $empresa->cidade))
                        <p>{{ trim($empresa->logradouro . ' ' . $empresa->numero . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . $empresa->bairro . ' - ' . $empresa->cidade . '/' . $empresa->estado) }}</p>
                        @if($empresa->cep)<p>CEP: {{ $empresa->cep }}</p>@endif
                    @endif
                </div>
                <div class="header-cell contato">
                    @if($empresaTelefone)<p><strong>Tel:</strong> {{ $empresaTelefone }}</p>@endif
                    @if($empresaEmail)<p><strong>{{ $empresaEmail }}</strong></p>@endif
                </div>
            </div>
        </header>

        <div class="titulo">
            <h2>ORDEM DE SERVIÇO {{ $ordem->codigo_interno ?? $ordem->id }}</h2>
            <span>Emissão: {{ $dataAbertura }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center">Status</th>
                    <th class="text-center">Data inicial</th>
                    <th class="text-center">Data final</th>
                    <th class="text-center">Garantia</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">{{ format_status_os_display($ordem->status) }}</td>
                    <td class="text-center">{{ $previstaInicio }}</td>
                    <td class="text-center">{{ $previstaConclusao }}</td>
                    <td class="text-center">
                        @if($garantiaDias)
                            {{ $ordem->garantia_tipo ? $ordem->garantia_tipo . ' ' : '' }}({{ $garantiaDias }} dias)
                        @else
                            {{ $ordem->garantia_tipo ?? '-' }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="bloco">
            <h3>Dados do Cliente</h3>
            <div class="dados">
                <div class="dados-row">
                    <div class="dados-cell"><strong>{{ $clienteBase->nome ?? '-' }}</strong></div>
                    <div class="dados-cell text-right">CPF/CNPJ: {{ $clienteBase->cnpj ?? $clienteBase->cpf ?? $clienteBase->documento_estrangeiro ?? '-' }}</div>
                </div>
                <div class="dados-row">
                    <div class="dados-cell">{{ $contato->nome ?? '-' }} {{ $contato?->telefone ? '(' . $contato->telefone . ')' : '' }}</div>
                    <div class="dados-cell text-right">{{ $enderecoTexto }}</div>
                </div>
                <div class="dados-row">
                    <div class="dados-cell">{{ $contato->email ?? '-' }}</div>
                    <div class="dados-cell text-right">CEP: {{ $endereco->cep ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio || $ordem->equipamento_acessorios || $ordem->equipamento_unlock_type || $ordem->equipamento_condicoes || $ordem->equipamento)
        <div class="bloco">
            <h3>Equipamento &amp; Segurança</h3>
            <div class="equipamento-container">
                <div class="equipamento-row">
                    <div class="info-tecnica">
                        <p><strong>Tipo/Nome:</strong> {{ $ordem->equipamento_nome ?? $ordem->equipamento?->nome ?? '-' }}</p>
                        <p><strong>Marca:</strong> {{ $ordem->equipamento_marca ?? '-' }}</p>
                        <p><strong>Modelo:</strong> {{ $ordem->equipamento_modelo ?? '-' }}</p>
                        <p><strong>Nº Série:</strong> {{ $ordem->equipamento_numero_serie ?? '-' }}</p>
                        @if($ordem->equipamento_numero_patrimonio)
                            <p><strong>Nº Patrimônio:</strong> {{ $ordem->equipamento_numero_patrimonio }}</p>
                        @endif
                        @if($ordem->equipamento_acessorios)
                            <p><strong>Acessórios:</strong> {!! strip_tags($ordem->equipamento_acessorios) !!}</p>
                        @endif
                    </div>
                    <div class="info-seguranca">
                        @php
                            $unlockType = $ordem->equipamento_unlock_type ?? null;
                            $unlockCode = $ordem->equipamento_unlock_code ?? null;
                            $patternPoints = [];
                            if ($unlockType === 'pattern' && $unlockCode) {
                                $decoded = is_string($unlockCode) ? json_decode($unlockCode, true) : $unlockCode;
                                if (is_array($decoded)) {
                                    $patternPoints = array_values(array_map('intval', $decoded));
                                }
                            }
                            $coords = [ 0 => [20,20], 1 => [50,20], 2 => [80,20], 3 => [20,50], 4 => [50,50], 5 => [80,50], 6 => [20,80], 7 => [50,80], 8 => [80,80] ];
                            $patternSvgBase64 = null;
                            if ($unlockType === 'pattern' && count($patternPoints) > 0) {
                                $pathD = '';
                                foreach ($patternPoints as $i => $idx) {
                                    if (isset($coords[$idx])) {
                                        $x = $coords[$idx][0]; $y = $coords[$idx][1];
                                        $pathD .= ($i === 0 ? "M {$x},{$y}" : " L {$x},{$y}");
                                    }
                                }
                                $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">';
                                if ($pathD !== '') {
                                    $svg .= '<path d="' . htmlspecialchars($pathD) . '" fill="none" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>';
                                }
                                for ($i = 0; $i < 9; $i++) {
                                    $inPattern = in_array($i, $patternPoints, true);
                                    $c = $coords[$i] ?? [0, 0];
                                    $fill = $inPattern ? '#3b82f6' : '#9ca3af';
                                    $svg .= '<circle cx="' . $c[0] . '" cy="' . $c[1] . '" r="5" fill="' . $fill . '"/>';
                                }
                                $svg .= '</svg>';
                                $patternSvgBase64 = base64_encode($svg);
                            }
                        @endphp
                        @if($unlockType === 'pattern' && $patternSvgBase64)
                            <span class="desbloqueio-label">Desbloqueio</span>
                            <div class="pattern-grid">
                                <img src="data:image/svg+xml;base64,{{ $patternSvgBase64 }}" width="56" height="56" alt="Padrão de desbloqueio" style="display:block; margin:0 auto;" />
                            </div>
                            <div class="desbloqueio-tipo">Padrão (Desenho)</div>
                            <div class="desbloqueio-obs">Senha de desbloqueio: padrão Android</div>
                        @elseif($unlockType === 'pin')
                            <span class="desbloqueio-label">Desbloqueio</span>
                            <div class="desbloqueio-tipo">Senha numérica / PIN</div>
                            <div class="desbloqueio-obs">Senha cadastrada (não exibida por segurança)</div>
                        @elseif($unlockType === 'none')
                            <span class="desbloqueio-label">Desbloqueio</span>
                            <div class="desbloqueio-tipo">Biometria / Sem senha</div>
                            <div class="desbloqueio-obs">Cliente virá para desbloquear ou aparelho sem senha</div>
                        @else
                            <span class="desbloqueio-label">Desbloqueio</span>
                            <div class="desbloqueio-tipo">N/A</div>
                            <div class="desbloqueio-obs">Não informado</div>
                        @endif
                    </div>
                </div>
            </div>
            @if($ordem->equipamento_condicoes)
                <div class="observacao" style="margin-top:6px;"><strong>Condições do equipamento:</strong> {!! processarTextoParaPDF($ordem->equipamento_condicoes) !!}</div>
            @endif
        </div>
        @endif

        @if($ordem->relato_cliente)
        <div class="bloco">
            <h3>Problemas/Relato do Cliente</h3>
            <div class="observacao">{!! processarTextoParaPDF($ordem->relato_cliente) !!}</div>
        </div>
        @endif

        @if($ordem->diagnostico_tecnico)
        <div class="bloco">
            <h3>Diagnóstico Técnico</h3>
            <div class="observacao">{!! processarTextoParaPDF($ordem->diagnostico_tecnico) !!}</div>
        </div>
        @endif

        @if($ordem->servicos_realizados)
        <div class="bloco">
            <h3>Descrição</h3>
            <div class="observacao">{!! processarTextoParaPDF($ordem->servicos_realizados) !!}</div>
        </div>
        @endif

        @if($ordem->observacoes_cliente)
        <div class="bloco">
            <h3>Observações</h3>
            <div class="observacao">{!! processarTextoParaPDF($ordem->observacoes_cliente) !!}</div>
        </div>
        @endif

        @php
            $checklistsPreenchidos = $ordem->checklists->filter(function($c) {
                $respostas = $c->respostas ?? [];
                $temRespostas = collect($respostas)->filter(fn($v) => $v !== '' && $v !== null && $v !== false)->count() > 0;
                $temObservacoes = !empty(trim($c->observacoes ?? ''));
                return $temRespostas || $temObservacoes;
            });
        @endphp
        @if($checklistsPreenchidos->count() > 0)
        <div class="bloco">
            <h3>Checklists</h3>
            @foreach($checklistsPreenchidos as $checklist)
                @php
                    $modelo = $checklist->checklistModel;
                    $campos = $modelo->campos ?? [];
                    $respostas = $checklist->respostas ?? [];
                @endphp
                <div class="checklist-print">
                    <h4>{{ $modelo->nome }}</h4>
                    @foreach($campos as $index => $campo)
                        @php
                            $valor = $respostas[$index] ?? null;
                            if ($valor === '' || $valor === null) continue;
                            if (($campo['tipo'] ?? '') === 'checkbox') {
                                $valor = $valor ? 'Sim' : 'Não';
                            }
                            if (($campo['tipo'] ?? '') === 'data' && $valor) {
                                try { $valor = \Carbon\Carbon::parse($valor)->format('d/m/Y'); } catch (\Exception $e) {}
                            }
                        @endphp
                        <div class="campo">
                            <span class="campo-label">{{ $campo['label'] ?? 'Campo ' . ($index + 1) }}:</span>
                            {{ is_string($valor) ? $valor : (is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (string) $valor) }}
                        </div>
                    @endforeach
                    @if(!empty(trim($checklist->observacoes ?? '')))
                        <div class="campo" style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #e5e7eb;">
                            <span class="campo-label">Observações:</span>
                            {{ $checklist->observacoes }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        @if($ordem->servicos->count())
        <div class="bloco">
            <h3>Serviços</h3>
            <table>
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordem->servicos as $item)
                    <tr>
                        <td>{{ $item->descricao ?? $item->servico?->nome ?? '-' }}</td>
                        <td class="text-center">{{ $item->cobranca_tipo === 'horas' ? $item->quantidade_horas : $item->quantidade }}</td>
                        <td class="text-center">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->total ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total serviços</strong></td>
                        <td class="text-right"><strong>R$ {{ number_format($totalServicos, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        @if($ordem->produtos->count())
        <div class="bloco">
            <h3>Produtos / Peças</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordem->produtos as $item)
                    <tr>
                        <td>
                            @php
                                $produto = $item->produto;
                                $nomeProduto = $item->descricao ?? $produto?->nome ?? '-';
                                
                                // Verificar se é um kit (tem composições)
                                if ($produto) {
                                    // Carregar composições se não estiverem carregadas
                                    if (!$produto->relationLoaded('composicoes')) {
                                        $produto->load('composicoes.componente');
                                    }
                                    
                                    // Verificar se tem composições
                                    $composicoes = $produto->composicoes ?? collect();
                                    if ($composicoes->count() > 0) {
                                        // Montar lista de componentes
                                        $componentes = $composicoes->map(function($comp) {
                                            return $comp->componente?->nome ?? null;
                                        })->filter()->implode(', ');
                                        
                                        // Mostrar nome do kit + componentes
                                        echo $produto->nome . ($componentes ? ' (' . $componentes . ')' : '');
                                    } elseif ($item->variacao) {
                                        // Item com variação: mostrar nome do produto + variação
                                        $var = $item->variacao;
                                        $opcoes = is_array($var->opcoes_valores ?? null) ? implode(', ', $var->opcoes_valores) : ($var->opcoes_valores ?? '');
                                        $labelVariacao = trim(($var->referencia_sku ?? '') . ($opcoes ? (' (' . $opcoes . ')') : ''));
                                        echo $produto->nome . ($labelVariacao ? ' - ' . $labelVariacao : '');
                                    } else {
                                        // Produto normal: mostrar descricao ou nome
                                        echo $nomeProduto;
                                    }
                                } else {
                                    // Produto normal: mostrar descricao ou nome
                                    echo $nomeProduto;
                                }
                            @endphp
                        </td>
                        <td class="text-center">{{ format_quantity($item->quantidade ?? 0) }}</td>
                        <td class="text-center">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->total ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total produtos</strong></td>
                        <td class="text-right"><strong>R$ {{ number_format($totalProdutos, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <div class="totais">
            <table>
                <tbody>
                    <tr>
                        <td>Total serviços</td>
                        <td class="text-right">R$ {{ number_format($totalServicos, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Total produtos</td>
                        <td class="text-right">R$ {{ number_format($totalProdutos, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Descontos</td>
                        <td class="text-right">R$ {{ number_format($descontos, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Acréscimos</td>
                        <td class="text-right">R$ {{ number_format($acrescimos, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total geral</strong></td>
                        <td class="text-right"><strong>R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="assinaturas">
            <div class="assinaturas-row">
                <div class="assinaturas-cell">
                    <div class="assinatura-box">
                        @if($assinaturaCliente && Storage::disk('public')->exists($assinaturaCliente->signature_path))
                            @php
                                $clienteImgPath = storage_path('app/public/' . $assinaturaCliente->signature_path);
                                $clienteImgData = file_exists($clienteImgPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($clienteImgPath)) : null;
                            @endphp
                            @if($clienteImgData)
                                <img src="{{ $clienteImgData }}" alt="Assinatura do cliente">
                            @else
                                <span style="color: #9ca3af;">Assinatura do cliente</span>
                            @endif
                        @else
                            <span style="color: #9ca3af;">Assinatura do cliente</span>
                        @endif
                    </div>
                    <div class="assinatura-info">
                        @if($assinaturaCliente)
                            <strong>Assinatura do cliente já registrada em {{ optional($assinaturaCliente->signed_at)->format('d/m/Y H:i') }}.</strong>
                        @endif
                    </div>
                </div>
                <div class="assinaturas-cell">
                    <div class="assinatura-box">
                        @if($assinaturaTecnico && Storage::disk('public')->exists($assinaturaTecnico->signature_path))
                            @php
                                $tecnicoImgPath = storage_path('app/public/' . $assinaturaTecnico->signature_path);
                                $tecnicoImgData = file_exists($tecnicoImgPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($tecnicoImgPath)) : null;
                            @endphp
                            @if($tecnicoImgData)
                                <img src="{{ $tecnicoImgData }}" alt="Assinatura do técnico">
                            @else
                                <span style="color: #9ca3af;">Assinatura do técnico</span>
                            @endif
                        @else
                            <span style="color: #9ca3af;">Assinatura do técnico</span>
                        @endif
                    </div>
                    <div class="assinatura-info">
                        @if($assinaturaTecnico)
                            <strong>Assinatura do técnico registrada em {{ optional($assinaturaTecnico->signed_at)->format('d/m/Y H:i') }}.</strong><br>
                            Técnico: {{ $assinaturaTecnico->user->name ?? '-' }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="integridade">
            <h4>DADOS DE INTEGRIDADE DO DOCUMENTO</h4>
            <table>
                <tbody>
                    @if($assinaturaCliente)
                    <tr>
                        <td colspan="4"><strong>Sessão do Cliente</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Data/hora:</strong></td>
                        <td>{{ optional($assinaturaCliente->signed_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td><strong>IP:</strong></td>
                        <td>{{ $assinaturaCliente->signed_ip ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>User-agent:</strong></td>
                        <td colspan="3" style="font-size: 8px;">{{ $assinaturaCliente->signed_user_agent ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Sessão:</strong></td>
                        <td colspan="3" style="font-family: monospace; font-size: 8px;">{{ $assinaturaCliente->session_uuid ?? '-' }}</td>
                    </tr>
                    @if($assinaturaCliente->metadata['confirmacao']['cpf'] ?? null)
                    <tr>
                        <td><strong>CPF informado pelo cliente:</strong></td>
                        <td colspan="3">{{ $assinaturaCliente->metadata['confirmacao']['cpf'] }}</td>
                    </tr>
                    @endif
                    @if($assinaturaCliente->metadata['confirmacao']['celular'] ?? null)
                    <tr>
                        <td><strong>Celular informado pelo cliente:</strong></td>
                        <td colspan="3">{{ $assinaturaCliente->metadata['confirmacao']['celular'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($assinaturaCliente->geo) && (isset($assinaturaCliente->geo['lat']) || isset($assinaturaCliente->geo['lng'])))
                    <tr>
                        <td><strong>Localização do cliente (capturada na assinatura):</strong></td>
                        <td colspan="3">
                            @if(isset($assinaturaCliente->geo['lat']) && isset($assinaturaCliente->geo['lng']))
                                {{ number_format((float)$assinaturaCliente->geo['lat'], 6, ',', '') }}, {{ number_format((float)$assinaturaCliente->geo['lng'], 6, ',', '') }}
                                @if(isset($assinaturaCliente->geo['accuracy']))
                                    (precisão ± {{ (int)$assinaturaCliente->geo['accuracy'] }} m)
                                @endif
                            @else
                                {{ $assinaturaCliente->geo['lat'] ?? '-' }}, {{ $assinaturaCliente->geo['lng'] ?? '-' }}
                            @endif
                        </td>
                    </tr>
                    @endif
                    @php
                        $extraCliente = $assinaturaCliente->metadata['extra'] ?? [];
                    @endphp
                    @if(!empty($extraCliente))
                        @if($extraCliente['device_type'] ?? null)
                        <tr>
                            <td><strong>Tipo de dispositivo:</strong></td>
                            <td>{{ $extraCliente['device_type'] }}</td>
                            <td><strong>Fuso horário:</strong></td>
                            <td>{{ $extraCliente['timezone'] ?? '-' }}</td>
                        </tr>
                        @endif
                        @if($extraCliente['language'] ?? null)
                        <tr>
                            <td><strong>Idioma do navegador:</strong></td>
                            <td>{{ $extraCliente['language'] }}</td>
                            <td><strong>Tipo de assinatura:</strong></td>
                            <td>{{ $extraCliente['signature_type'] ?? 'desenho' }}</td>
                        </tr>
                        @endif
                        @if($extraCliente['signature_duration'] ?? null)
                        <tr>
                            <td><strong>Duração da assinatura:</strong></td>
                            <td>{{ number_format($extraCliente['signature_duration'], 1) }}s</td>
                            @if($extraCliente['typed_signature'] ?? null)
                            <td><strong>Assinatura digitada:</strong></td>
                            <td>{{ $extraCliente['typed_signature'] }}</td>
                            @else
                            <td colspan="2"></td>
                            @endif
                        </tr>
                        @endif
                    @endif
                    <tr>
                        <td colspan="4"><strong>Declaração de aceite do cliente:</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" style="font-style: italic;">{{ $assinaturaCliente->checkbox_text ?? '-' }}</td>
                    </tr>
                    @endif
                    @if($assinaturaTecnico)
                    <tr>
                        <td colspan="4"><strong>Sessão do Técnico</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Data/hora:</strong></td>
                        <td>{{ optional($assinaturaTecnico->signed_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        <td><strong>IP:</strong></td>
                        <td>{{ $assinaturaTecnico->signed_ip ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>User-agent:</strong></td>
                        <td colspan="3" style="font-size: 8px;">{{ $assinaturaTecnico->signed_user_agent ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Sessão:</strong></td>
                        <td colspan="3" style="font-family: monospace; font-size: 8px;">{{ $assinaturaTecnico->session_uuid ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Técnico:</strong></td>
                        <td colspan="3">{{ $assinaturaTecnico->user->name ?? '-' }}</td>
                    </tr>
                    @if(!empty($assinaturaTecnico->geo) && (isset($assinaturaTecnico->geo['lat']) || isset($assinaturaTecnico->geo['lng'])))
                    <tr>
                        <td><strong>Localização do técnico (capturada na assinatura):</strong></td>
                        <td colspan="3">
                            @if(isset($assinaturaTecnico->geo['lat']) && isset($assinaturaTecnico->geo['lng']))
                                {{ number_format((float)$assinaturaTecnico->geo['lat'], 6, ',', '') }}, {{ number_format((float)$assinaturaTecnico->geo['lng'], 6, ',', '') }}
                                @if(isset($assinaturaTecnico->geo['accuracy']))
                                    (precisão ± {{ (int)$assinaturaTecnico->geo['accuracy'] }} m)
                                @endif
                            @else
                                {{ $assinaturaTecnico->geo['lat'] ?? '-' }}, {{ $assinaturaTecnico->geo['lng'] ?? '-' }}
                            @endif
                        </td>
                    </tr>
                    @endif
                    @php
                        $extraTecnico = $assinaturaTecnico->metadata['extra'] ?? [];
                    @endphp
                    @if(!empty($extraTecnico))
                        @if($extraTecnico['device_type'] ?? null)
                        <tr>
                            <td><strong>Tipo de dispositivo:</strong></td>
                            <td>{{ $extraTecnico['device_type'] }}</td>
                            <td><strong>Fuso horário:</strong></td>
                            <td>{{ $extraTecnico['timezone'] ?? '-' }}</td>
                        </tr>
                        @endif
                        @if($extraTecnico['language'] ?? null)
                        <tr>
                            <td><strong>Idioma do navegador:</strong></td>
                            <td>{{ $extraTecnico['language'] }}</td>
                            <td><strong>Tipo de assinatura:</strong></td>
                            <td>{{ $extraTecnico['signature_type'] ?? 'desenho' }}</td>
                        </tr>
                        @endif
                        @if($extraTecnico['signature_duration'] ?? null)
                        <tr>
                            <td><strong>Duração da assinatura:</strong></td>
                            <td colspan="3">{{ number_format($extraTecnico['signature_duration'], 1) }}s</td>
                        </tr>
                        @endif
                    @endif
                    @endif
                    <tr>
                        <td colspan="4"><strong>Dados de Segurança e Integridade do Documento</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Documento gerado em:</strong></td>
                        <td>{{ now()->format('d/m/Y H:i:s') }}</td>
                        <td><strong>OS:</strong></td>
                        <td>{{ format_os_display($ordem) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Versão dos termos:</strong></td>
                        <td>{{ $assinaturaCliente->terms_version ?? $assinaturaTecnico->terms_version ?? '-' }}</td>
                        <td><strong>Versão da política:</strong></td>
                        <td>{{ $assinaturaCliente->privacy_version ?? $assinaturaTecnico->privacy_version ?? '-' }}</td>
                    </tr>
                    @if($hashSha256 || $hashMd5 || $assinaturaDigital)
                    <tr>
                        <td colspan="4" style="background: #f3f4f6; padding: 6px; font-weight: bold; border-top: 2px solid #d1d5db;">
                            <strong>Hashes de Integridade do PDF</strong>
                        </td>
                    </tr>
                    @if($hashSha256)
                    <tr>
                        <td style="width: 18%; vertical-align: top; padding-top: 8px;"><strong>Hash SHA-256:</strong></td>
                        <td colspan="3" style="font-family: monospace; font-size: 8px; word-break: break-all; background: #f9fafb; padding: 6px; line-height: 1.3;">{{ $hashSha256 }}</td>
                    </tr>
                    @endif
                    @if($hashMd5)
                    <tr>
                        <td style="width: 18%; vertical-align: top; padding-top: 8px;"><strong>Hash MD5:</strong></td>
                        <td colspan="3" style="font-family: monospace; font-size: 8px; word-break: break-all; background: #f9fafb; padding: 6px; line-height: 1.3;">{{ $hashMd5 }}</td>
                    </tr>
                    @endif
                    @if($assinaturaDigital)
                    <tr>
                        <td style="width: 18%; vertical-align: top; padding-top: 8px;"><strong>Assinatura Digital:</strong></td>
                        <td colspan="3" style="font-family: monospace; font-size: 8px; word-break: break-all; background: #f9fafb; padding: 6px; line-height: 1.3;">{{ $assinaturaDigital }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="4" style="font-size: 8px; font-style: italic; padding: 8px; background: #fef3c7; border: 1px solid #fbbf24; line-height: 1.4;">
                            <strong>⚠️ Integridade do Documento:</strong> Este documento possui mecanismos de segurança para garantir sua integridade. 
                            Qualquer alteração no conteúdo do arquivo PDF invalidará os hashes SHA-256 e MD5 acima, bem como a assinatura digital. 
                            Os hashes são calculados sobre o conteúdo binário completo do arquivo PDF gerado.
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
</body>
</html>
