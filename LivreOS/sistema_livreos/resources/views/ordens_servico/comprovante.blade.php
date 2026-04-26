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
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        try {
            $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            $logoPath = storage_path('app/public/' . $logoPathRel);
            if ($logoPathRel !== '' && file_exists($logoPath) && is_readable($logoPath)) {
                $mime = @mime_content_type($logoPath) ?: 'image/png';
                $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            } else {
                $empresaLogo = asset('storage/' . $logoPathRel);
            }
        } catch (\Throwable $e) {
            $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
            $empresaLogo = $logoPathRel !== '' ? asset('storage/' . $logoPathRel) : null;
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
    <title>Comprovante de Entrega - {{ format_os_display($ordem) }} - {{ $clienteBase->nome ?? '-' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; background: #f3f4f6; }
        .page { max-width: 960px; margin: 24px auto; background: #fff; padding: 20px; position: relative; }
        header { display: grid; grid-template-columns: 140px 1fr 220px; gap: 16px; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; }
        .logo img { max-width: 140px; height: auto; }
        .emitente h1 { font-size: 18px; margin: 0 0 6px 0; }
        .emitente p, .contato p { margin: 2px 0; font-size: 13px; color: #374151; }
        .contato { text-align: right; }
        .titulo { margin: 18px 0 12px; display: flex; justify-content: space-between; align-items: baseline; }
        .titulo h2 { margin: 0; font-size: 20px; }
        .titulo span { font-size: 12px; color: #6b7280; }
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
        .comprovante-texto { margin-top: 24px; padding: 16px; border: 2px solid #374151; background: #f9fafb; font-size: 14px; line-height: 1.6; }
        .comprovante-texto p { margin: 8px 0; }
        @media print {
            html, body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; padding: 0; }
            th {
                background: #f3f4f6 !important;
                color: #111827 !important;
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
            <h2>COMPROVANTE DE ENTREGA - {{ format_os_display($ordem) }}</h2>
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

        @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_numero_patrimonio || $ordem->equipamento_acessorios || $ordem->equipamento)
        <div class="bloco">
            <h3>Equipamento</h3>
            <div class="dados">
                <div>
                    <p><strong>{{ $ordem->equipamento_nome ?? $ordem->equipamento?->nome ?? '-' }}</strong></p>
                    <p>Marca: {{ $ordem->equipamento_marca ?? '-' }}</p>
                    <p>Modelo: {{ $ordem->equipamento_modelo ?? '-' }}</p>
                </div>
                <div>
                    <p>Nº Série: {{ $ordem->equipamento_numero_serie ?? '-' }}</p>
                    <p>Nº Patrimônio: {{ $ordem->equipamento_numero_patrimonio ?? '-' }}</p>
                </div>
            </div>
            @if($ordem->equipamento_acessorios)
                <div class="observacao"><strong>Acessórios:</strong> {!! $ordem->equipamento_acessorios !!}</div>
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
                    @php
                        $produto = $item->produto;
                        $ehKit = $produto && $produto->formato === 'composicao' && $produto->composicoes->count() > 0;
                        $itensKit = $ehKit ? $produto->composicoes->map(fn($c) => ($c->componente?->nome ?? 'Componente'))->implode(', ') : '';
                        $ehVariacao = !$ehKit && $item->variacao;
                        $labelVariacao = $ehVariacao ? (($item->variacao->referencia_sku ?? '') . (is_array($item->variacao->opcoes_valores ?? null) ? (' (' . implode(', ', $item->variacao->opcoes_valores) . ')') : ($item->variacao->opcoes_valores ? (' (' . $item->variacao->opcoes_valores . ')') : ''))) : '';
                        $labelVariacao = trim($labelVariacao);
                    @endphp
                    <tr>
                        <td>
                            @if($ehKit)
                                {{ $produto?->nome ?? '-' }}
                                @if($itensKit)<span class="text-gray-500">({{ $itensKit }})</span>@endif
                            @elseif($ehVariacao)
                                {{ ($produto?->nome ?? '-') . ($labelVariacao ? ' - ' . $labelVariacao : '') }}
                            @else
                                {{ $item->descricao ?? $produto?->nome ?? '-' }}
                            @endif
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
            $htmlAntesAssinatura = trim((string) ($comprovanteHtmlAntesAssinatura ?? ''));
        @endphp
        @if($htmlAntesAssinatura !== '')
        <div class="comprovante-texto observacao">
            {!! processarTextoParaPDF($htmlAntesAssinatura) !!}
        </div>
        @endif

        <div class="assinaturas">
            <div>Assinatura do cliente</div>
            <div>Assinatura do técnico</div>
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
