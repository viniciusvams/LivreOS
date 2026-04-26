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
    // Função para processar HTML mantendo emojis para PDF
    function processarTextoParaPDF($html) {
        if (empty($html)) {
            return '';
        }
        
        // Converter HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Não remover emojis - mantê-los no texto
        
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
    // Logo em base64 para garantir exibição na impressão (não depende de storage:link)
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/' . $empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            $empresaLogo = asset('storage/' . $empresa->logo_path);
        }
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
    $impostos = $ordem->total_impostos ?? $ordem->impostos_global ?? 0;
    $garantiaDias = $ordem->garantia_dias ?? null;
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Ordem de Serviço {{ format_os_display($ordem) }} — Impressão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; background: #f3f4f6; min-height: 100vh; display: flex; flex-direction: column; }
        .page { max-width: 960px; margin: 24px auto; background: #fff; padding: 20px; padding-bottom: 32px; position: relative; flex: 1 0 auto; width: 100%; }
        header { display: grid; grid-template-columns: 140px 1fr 220px; gap: 16px; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; }
        .logo img { max-width: 140px; height: auto; }
        .emitente h1 { font-size: 18px; margin: 0 0 6px 0; }
        .emitente p, .contato p { margin: 2px 0; font-size: 13px; color: #374151; }
        .contato { text-align: right; }
        .titulo { margin: 18px 0 12px; }
        .titulo h2 { margin: 0; font-size: 20px; }
        .bloco { margin-top: 16px; }
        .bloco h3 { margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #374151; }
        .dados { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; color: #374151; }
        .dados p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totais { margin-top: 12px; display: flex; justify-content: flex-end; }
        .totais table { width: 320px; }
        .assinaturas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; }
        .assinaturas div { border-top: 1px solid #d1d5db; padding-top: 6px; text-align: center; font-size: 12px; color: #6b7280; }
        .observacao { border: 1px solid #e5e7eb; padding: 10px; background: #f9fafb; }
        .checklist-print { border: 1px solid #e5e7eb; padding: 12px; margin-top: 8px; border-radius: 6px; }
        .checklist-print h4 { margin: 0 0 8px 0; font-size: 14px; color: #374151; }
        .checklist-print .campo { display: flex; margin-bottom: 6px; font-size: 12px; }
        .checklist-print .campo-label { font-weight: 600; min-width: 140px; color: #4b5563; }
        .checklist-print .campo-valor { color: #111827; }
        .equipamento-container { display: grid; grid-template-columns: 1.8fr 1fr; gap: 12px; border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; font-size: 12px; margin-top: 6px; }
        .info-tecnica p { margin: 2px 0 4px 0; padding-bottom: 2px; border-bottom: 1px dashed #f3f4f6; }
        .info-seguranca { background: #f9fafb; padding: 10px; border-radius: 6px; border: 1px solid #e5e7eb; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .pattern-grid { width: 56px; height: 56px; margin: 4px auto; }
        .pattern-grid img { width: 56px; height: 56px; display: block; }
        .desbloqueio-label { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase; margin-bottom: 2px; }
        .desbloqueio-tipo { font-size: 11px; color: #111827; margin-top: 2px; }
        .desbloqueio-obs { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .pdf-footer { margin-top: auto; padding: 12px 12mm; border-top: 1px solid #e2e8f0; background: #f8fafc; font-size: 9px; color: #9ca3af; text-align: center; flex-shrink: 0; }
        @media print {
            @page { margin: 8mm 10mm; }
            html, body {
                height: auto;
                min-height: 100%;
            }
            body {
                background: #fff;
                display: flex;
                flex-direction: column;
                /* Altura útil ~A4 menos margens: empurra o rodapé para o fim da folha quando o conteúdo é curto */
                min-height: 280mm;
                font-size: 11px;
                line-height: 1.35;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page {
                flex: 1 1 auto;
                margin: 0 auto;
                max-width: 100%;
                box-shadow: none;
                padding: 0 0 6mm;
                width: 100%;
            }
            header {
                grid-template-columns: 110px 1fr 180px;
                gap: 10px;
                padding-bottom: 10px;
                border-bottom-width: 1px;
            }
            .logo img { max-width: 110px; max-height: 52px; object-fit: contain; }
            .emitente h1 { font-size: 16px; margin-bottom: 3px; }
            .emitente p, .contato p { font-size: 11px; margin: 2px 0; }
            .titulo { margin: 10px 0 8px; }
            .titulo h2 { font-size: 17px; }
            .bloco { margin-top: 10px; page-break-inside: avoid; }
            .bloco h3 { font-size: 12px; margin-bottom: 5px; }
            .dados { gap: 10px; font-size: 11px; }
            table { font-size: 11px; margin-top: 6px; }
            th, td { padding: 5px 7px; }
            .observacao {
                padding: 8px 10px;
                font-size: 11px;
                line-height: 1.35;
            }
            .observacao p, .observacao li { margin: 0.2em 0; }
            .checklist-print { padding: 8px 10px; margin-top: 6px; }
            .checklist-print h4 { font-size: 12px; margin-bottom: 5px; }
            .checklist-print .campo { font-size: 11px; margin-bottom: 4px; }
            .checklist-print .campo-label { min-width: 120px; }
            .equipamento-container {
                grid-template-columns: 1.5fr 1fr;
                gap: 10px;
                padding: 8px 10px;
                font-size: 11px;
            }
            .pattern-grid, .pattern-grid img { width: 48px; height: 48px; }
            .totais { margin-top: 10px; margin-bottom: 6mm; }
            .totais table { width: 280px; font-size: 11px; }
            .assinaturas {
                margin-top: 16mm;
                padding-top: 4mm;
                gap: 20px;
                page-break-inside: avoid;
            }
            .assinaturas div { font-size: 11px; padding-top: 8px; }
            .pdf-footer {
                margin-top: auto;
                flex-shrink: 0;
                padding: 6mm 4mm 2mm;
                font-size: 9px;
                color: #6b7280;
                border-top: 1px solid #e2e8f0;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div class="logo">
                @if($empresaLogo)
                    <img src="{{ $empresaLogo }}" alt="Logotipo">
                @endif
            </div>
            <div class="emitente">
                <h1>{{ $empresaNome }}</h1>
                @if($empresaRazao)<p>{{ $empresaRazao }}</p>@endif
                @if($empresaCnpj)<p>CNPJ: {{ $empresaCnpj }}</p>@endif
                @if($empresa && ($empresa->logradouro || $empresa->cidade))
                    <p>{{ trim($empresa->logradouro . ' ' . $empresa->numero . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . $empresa->bairro . ' - ' . $empresa->cidade . '/' . $empresa->estado) }}</p>
                    @if($empresa->cep)<p>CEP: {{ $empresa->cep }}</p>@endif
                @endif
            </div>
            <div class="contato">
                @if($empresaTelefone)<p><strong>Tel:</strong> {{ $empresaTelefone }}</p>@endif
                @if($empresaEmail)<p><strong>{{ $empresaEmail }}</strong></p>@endif
            </div>
        </header>

        <div class="titulo">
            <h2>Ordem de Serviço — {{ format_os_display($ordem) }}</h2>
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
                <div>
                    <p><strong>{{ $clienteBase->nome ?? '-' }}</strong></p>
                    <p>CPF/CNPJ: {{ $clienteBase->cnpj ?? $clienteBase->cpf ?? $clienteBase->documento_estrangeiro ?? '-' }}</p>
                    <p>{{ $contato->nome ?? '-' }} {{ $contato?->telefone ? '(' . $contato->telefone . ')' : '' }}</p>
                    <p>{{ $contato->email ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p>{{ $enderecoTexto }}</p>
                    <p>CEP: {{ $endereco->cep ?? '-' }}</p>
                </div>
            </div>
        </div>

        @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio || $ordem->equipamento_acessorios || $ordem->equipamento_unlock_type || $ordem->equipamento_condicoes || $ordem->equipamento)
        <div class="bloco">
            <h3>Equipamento &amp; Segurança</h3>
            <div class="equipamento-container">
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
                            <img src="data:image/svg+xml;base64,{{ $patternSvgBase64 }}" width="56" height="56" alt="Padrão de desbloqueio" />
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
            @if($ordem->equipamento_condicoes)
                <div class="observacao" style="margin-top:8px;"><strong>Condições do equipamento:</strong> {!! processarTextoParaPDF($ordem->equipamento_condicoes) !!}</div>
            @endif
        </div>
        @endif

        @php
            $relatoClienteImpressao = isset($ordem->relato_cliente)
                ? trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $ordem->relato_cliente), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
                : '';
        @endphp
        @if($relatoClienteImpressao !== '')
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
                            <span class="campo-valor">{{ is_string($valor) ? $valor : (is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (string) $valor) }}</span>
                        </div>
                    @endforeach
                    @if(!empty(trim($checklist->observacoes ?? '')))
                        <div class="campo" style="margin-top: 8px; border-top: 1px dashed #e5e7eb; padding-top: 8px;">
                            <span class="campo-label">Observações:</span>
                            <span class="campo-valor">{{ $checklist->observacoes }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        @if($ordem->servicos->count())
        <div class="bloco">
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
                        <td class="text-center">{{ $item->cobranca_tipo === 'horas' ? format_quantity($item->quantidade_horas) : format_quantity($item->quantidade) }}</td>
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

        @php
            $obsImpressaoOs = trim((string) ($osImpressaoObservacaoAntesAssinatura ?? ''));
            $mostrarObsPrint = !empty($osImpressaoObservacaoAntesAssinaturaAtivo) && $obsImpressaoOs !== '';
        @endphp
        @if($mostrarObsPrint)
        <div class="bloco impressao-observacao-antes-assinatura">
            <div class="observacao">{!! processarTextoParaPDF($obsImpressaoOs) !!}</div>
        </div>
        @endif

        <div class="assinaturas">
            <div>Assinatura do cliente</div>
            <div>Assinatura do técnico</div>
        </div>
    </div>

    @php
        $footerUser = auth()->user();
        $mostrarGeradoPor = $footerUser && !$footerUser->is_admin;
    @endphp
    <footer class="pdf-footer">Emitido em {{ $emitidoEm ?? now()->format('d/m/Y \à\s H:i') }}@if($mostrarGeradoPor) · Gerado por: {{ $userName ?? $footerUser->name ?? 'Sistema' }}@endif · {{ config('app.name') }} - {{ config('app.tagline', 'ERP Open Source Livre') }}</footer>

    <script>
        window.print();
    </script>
</body>
</html>
