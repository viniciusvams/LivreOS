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
    $empresa = $empresa ?? null;
    $ordem = $ordem ?? null;
    if (!$ordem) {
        echo '<p>Ordem não encontrada.</p>';
        return;
    }
    $empresaNome = $empresa?->nome ?? 'Empresa';
    $empresaRazao = ($empresa && $empresa->razao_social && trim($empresa->razao_social) !== '' && trim($empresa->razao_social) !== trim($empresaNome)) ? $empresa->razao_social : null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/' . $empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    $clienteBase = $ordem->unidade ?? $ordem->cliente;
    $contato = $ordem->contato;
    $endereco = $ordem->endereco ?? ($clienteBase?->enderecos ? $clienteBase->enderecos->firstWhere('principal', true) : null);

    $enderecoTexto = '-';
    if ($endereco) {
        $enderecoTexto = trim(($endereco->logradouro ?? '') . ' ' . ($endereco->numero ?? '') . (($endereco->complemento ?? '') ? ' - ' . $endereco->complemento : '') . ', ' . ($endereco->bairro ?? '') . ' - ' . ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? ''));
    }

    $dataAbertura = optional($ordem->data_abertura)->format('d/m/Y H:i') ?: '-';
    $previstaInicio = optional($ordem->prevista_inicio)->format('d/m/Y') ?: optional($ordem->data_abertura)->format('d/m/Y') ?: '-';
    $previstaConclusao = optional($ordem->prevista_conclusao)->format('d/m/Y') ?: optional($ordem->real_conclusao)->format('d/m/Y') ?: '-';

    $totalProdutos = $ordem->produtos ? $ordem->produtos->sum('total') : 0;
    $totalServicos = $ordem->servicos ? $ordem->servicos->sum('total') : 0;
    $totalGeral = $ordem->total_geral ?? ($totalProdutos + $totalServicos);
    $descontos = $ordem->total_descontos ?? 0;
    $acrescimos = $ordem->total_acrescimos ?? $ordem->acrescimos_global ?? 0;
    $garantiaDias = $ordem->garantia_dias ?? null;

    $geradoPor = $geradoPor ?? 'Cliente';
    $geradoEm = $geradoEm ?? now()->format('d/m/Y H:i');
    $documentoUrl = $documentoUrl ?? '';
    $documentoId = $documentoId ?? ($ordem->codigo_interno ?: 'OS-' . $ordem->id);
    $ipDispositivo = $ipDispositivo ?? request()->ip() ?? '-';

    $checklistsPreenchidos = collect();
    if (method_exists($ordem, 'checklists') && $ordem->checklists) {
        $checklistsPreenchidos = $ordem->checklists->filter(function ($c) {
            $respostas = $c->respostas ?? [];
            $temRespostas = collect($respostas)->filter(fn ($v) => $v !== '' && $v !== null && $v !== false)->count() > 0;
            $temObservacoes = !empty(trim($c->observacoes ?? ''));
            return $temRespostas || $temObservacoes;
        });
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Comprovante - {{ format_os_display($ordem) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: A4; margin: 20mm; }
        html, body { font-family: Arial, Helvetica, sans-serif; color: #334155; margin: 0; padding: 0; font-size: 11px; line-height: 1.45; word-wrap: break-word; overflow-wrap: break-word; }
        .page { width: 100%; max-width: 170mm; margin: 0 auto; padding: 15mm; }

        .header { width: 100%; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 12px; }
        .header td { vertical-align: middle; padding: 0 10px 0 0; word-wrap: break-word; overflow-wrap: break-word; }
        .header .logo-cell { width: 70px; min-width: 70px; max-width: 70px; vertical-align: middle; text-align: left; }
        .header .logo-cell img { max-width: 60px; max-height: 52px; display: block; }
        .header .dados-cell { text-align: left; vertical-align: middle; }
        .header .dados-cell h1 { font-size: 13px; font-weight: 600; color: #0f172a; margin: 0 0 4px 0; word-wrap: break-word; text-align: left; }
        .header .dados-cell p { font-size: 9px; color: #64748b; margin: 0; line-height: 1.35; word-wrap: break-word; text-align: left; }
        .header .contato-cell { width: 100px; text-align: right; vertical-align: middle; font-size: 9px; color: #64748b; word-wrap: break-word; }

        .doc-meta { width: 100%; table-layout: fixed; margin-bottom: 12px; font-size: 9px; color: #64748b; border-collapse: collapse; }
        .doc-meta td { padding: 6px 8px; border: 1px solid #e2e8f0; border-left: none; background: #f8fafc; word-wrap: break-word; overflow-wrap: break-word; }
        .doc-meta td:first-child { border-left: 1px solid #e2e8f0; }
        .doc-meta .url-cell { word-break: break-all; }

        .titulo { margin-bottom: 12px; padding: 10px 14px; background: #f1f5f9; border-left: 3px solid #64748b; }
        .titulo h2 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0 0 2px 0; }
        .titulo .sub { font-size: 10px; color: #64748b; }

        .bloco { margin-top: 12px; }
        .bloco h3 { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; margin: 0 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0; }
        .bloco table.dados-tbl { width: 100%; border-collapse: collapse; font-size: 10px; }
        .bloco table.dados-tbl td { padding: 4px 8px; border: 1px solid #e2e8f0; word-wrap: break-word; overflow-wrap: break-word; }
        .bloco table.dados-tbl td:first-child { width: 90px; font-weight: 600; color: #475569; background: #f8fafc; }

        table.principal { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 6px; }
        table.principal.status-tbl { table-layout: fixed; }
        table.principal th, table.principal td { border: 1px solid #e2e8f0; padding: 6px 8px; word-wrap: break-word; overflow-wrap: break-word; }
        table.principal th { background: #f1f5f9; font-weight: 600; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totais-wrap { width: 100%; margin-top: 12px; }
        .totais-tbl { width: 280px; margin-left: auto; border-collapse: collapse; font-size: 11px; }
        .totais-tbl td { padding: 5px 10px; border: 1px solid #e2e8f0; }
        .totais-tbl .total-row { font-weight: 700; font-size: 12px; background: #f1f5f9; }

        .observacao { border: 1px solid #e2e8f0; padding: 8px 10px; background: #fafafa; font-size: 10px; margin-top: 4px; }

        .checklist-print { border: 1px solid #e2e8f0; padding: 8px 10px; margin-top: 6px; background: #fafafa; font-size: 10px; }
        .checklist-print h4 { margin: 0 0 6px 0; font-size: 11px; }
        .checklist-print .campo { margin-bottom: 3px; }
        .checklist-print .campo-label { font-weight: 600; display: inline-block; min-width: 100px; }

        .rodape { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <table class="header" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                @if($empresaLogo)
                <td class="logo-cell"><img src="{{ $empresaLogo }}" alt="Logo"></td>
                @endif
                <td class="dados-cell">
                    <h1>{{ $empresaNome }}</h1>
                    @if($empresaRazao)<p>{{ $empresaRazao }}</p>@endif
                    @if($empresaCnpj)<p>CNPJ: {{ $empresaCnpj }}</p>@endif
                    @if($empresa && trim(($empresa->logradouro ?? '') . ($empresa->cidade ?? '')))
                        <p>{{ trim(($empresa->logradouro ?? '') . ' ' . ($empresa->numero ?? '') . (($empresa->complemento ?? '') ? ' - ' . $empresa->complemento : '') . ', ' . ($empresa->bairro ?? '') . ' - ' . ($empresa->cidade ?? '') . '/' . ($empresa->estado ?? '')) }}</p>
                    @endif
                </td>
                <td class="contato-cell">
                    @if($empresaTelefone)<p>Tel: {{ $empresaTelefone }}</p>@endif
                    @if($empresaEmail)<p>{{ $empresaEmail }}</p>@endif
                </td>
            </tr>
        </table>

        <table class="doc-meta" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td style="width:28%"><strong>Documento:</strong> {{ $documentoId }}</td>
                <td style="width:22%"><strong>Gerado por:</strong> {{ $geradoPor }}</td>
                <td style="width:22%"><strong>Emissão:</strong> {{ $geradoEm }}</td>
                @if($documentoUrl)
                <td style="width:28%" class="url-cell"><strong>URL:</strong> {{ $documentoUrl }}</td>
                @endif
            </tr>
        </table>

        <div class="titulo">
            <h2>COMPROVANTE — {{ format_os_display($ordem) }}</h2>
            <p class="sub">Área do Cliente · Abertura: {{ $dataAbertura }}</p>
        </div>

        <table class="principal status-tbl" width="100%">
            <thead>
                <tr>
                    <th class="text-center" style="width:20%">Status</th>
                    <th class="text-center" style="width:25%">Data inicial</th>
                    <th class="text-center" style="width:25%">Data final</th>
                    <th class="text-center" style="width:30%">Garantia</th>
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
            <table class="dados-tbl" cellpadding="0" cellspacing="0">
                <tr><td>Cliente</td><td>{{ $clienteBase->nome ?? $clienteBase->razao_social ?? '-' }}</td></tr>
                <tr><td>CPF/CNPJ</td><td>{{ $clienteBase->cnpj ?? $clienteBase->cpf ?? $clienteBase->documento_estrangeiro ?? '-' }}</td></tr>
                @if($contato?->nome || $contato?->telefone || $contato?->email)
                <tr><td>Contato</td><td>{{ implode(' · ', array_filter([$contato?->nome ?? null, $contato?->telefone ?? null, $contato?->email ?? null])) ?: '-' }}</td></tr>
                @endif
                @if($enderecoTexto !== '-' || ($endereco?->cep ?? null))
                <tr><td>Endereço</td><td>{{ $enderecoTexto !== '-' ? $enderecoTexto : '' }}{{ ($endereco?->cep ?? null) ? ($enderecoTexto !== '-' ? ' · ' : '') . 'CEP: ' . $endereco->cep : '' }}</td></tr>
                @endif
            </table>
        </div>

        <div class="bloco">
            <h3>Equipamento</h3>
            <table class="dados-tbl" cellpadding="0" cellspacing="0">
                <tr><td>Equipamento</td><td>{{ $ordem->equipamento_nome ?? $ordem->equipamento?->nome ?? '-' }}</td></tr>
                <tr><td>Marca / Modelo</td><td>{{ trim(($ordem->equipamento_marca ?? '') . ' / ' . ($ordem->equipamento_modelo ?? ''), ' /') ?: '-' }}</td></tr>
                <tr><td>Série / Patrimônio</td><td>{{ trim(($ordem->equipamento_numero_serie ?? '') . ' / ' . ($ordem->equipamento_numero_patrimonio ?? ''), ' /') ?: '-' }}</td></tr>
            </table>
            @if($ordem->equipamento_acessorios)
                <div class="observacao"><strong>Acessórios:</strong> {!! \AreaCliente\AreaClienteHelper::htmlForPdf($ordem->equipamento_acessorios) !!}</div>
            @endif
        </div>

        @if($ordem->relato_cliente)
        <div class="bloco">
            <h3>Relato do Cliente</h3>
            <div class="observacao">{!! \AreaCliente\AreaClienteHelper::htmlForPdf($ordem->relato_cliente) !!}</div>
        </div>
        @endif

        @if($ordem->diagnostico_tecnico)
        <div class="bloco">
            <h3>Diagnóstico Técnico</h3>
            <div class="observacao">{!! \AreaCliente\AreaClienteHelper::htmlForPdf($ordem->diagnostico_tecnico) !!}</div>
        </div>
        @endif

        @if($ordem->servicos_realizados)
        <div class="bloco">
            <h3>Serviços Realizados</h3>
            <div class="observacao">{!! \AreaCliente\AreaClienteHelper::htmlForPdf($ordem->servicos_realizados) !!}</div>
        </div>
        @endif

        @if($ordem->observacoes_cliente)
        <div class="bloco">
            <h3>Observações</h3>
            <div class="observacao">{!! \AreaCliente\AreaClienteHelper::htmlForPdf($ordem->observacoes_cliente) !!}</div>
        </div>
        @endif

        @if($checklistsPreenchidos->count() > 0)
        <div class="bloco">
            <h3>Checklists</h3>
            @foreach($checklistsPreenchidos as $checklist)
                @php
                    $modelo = $checklist->checklistModel ?? null;
                    $campos = $modelo->campos ?? [];
                    $respostas = $checklist->respostas ?? [];
                @endphp
                @if($modelo)
                <div class="checklist-print">
                    <h4>{{ $modelo->nome }}</h4>
                    @foreach($campos as $index => $campo)
                        @php
                            $valor = $respostas[$index] ?? null;
                            if ($valor === '' || $valor === null) continue;
                            if (($campo['tipo'] ?? '') === 'checkbox') { $valor = $valor ? 'Sim' : 'Não'; }
                            if (($campo['tipo'] ?? '') === 'data' && $valor) { try { $valor = \Carbon\Carbon::parse($valor)->format('d/m/Y'); } catch (\Exception $e) {} }
                        @endphp
                        <div class="campo">
                            <span class="campo-label">{{ $campo['label'] ?? 'Campo ' . ($index + 1) }}:</span>
                            <span>{{ is_string($valor) ? $valor : (is_bool($valor) ? ($valor ? 'Sim' : 'Não') : (string) $valor) }}</span>
                        </div>
                    @endforeach
                    @if(!empty(trim($checklist->observacoes ?? '')))
                        <div class="campo" style="margin-top: 8px; border-top: 1px dashed #e5e7eb; padding-top: 8px;">
                            <span class="campo-label">Observações:</span>
                            <span>{{ $checklist->observacoes }}</span>
                        </div>
                    @endif
                </div>
                @endif
            @endforeach
        </div>
        @endif

        @if($ordem->servicos && $ordem->servicos->count())
        <div class="bloco">
            <h3>Serviços</h3>
            <table class="principal" width="100%">
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
                        <td class="text-center">{{ $item->cobranca_tipo === 'horas' ? ($item->quantidade_horas ?? $item->quantidade) : ($item->quantidade ?? 0) }}</td>
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

        @if($ordem->produtos && $ordem->produtos->count())
        <div class="bloco">
            <h3>Produtos / Peças</h3>
            <table class="principal" width="100%">
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
                        <td>{{ $item->descricao ?? $item->produto?->nome ?? '-' }}</td>
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

        <div class="totais-wrap">
        <table class="totais-tbl" cellpadding="0" cellspacing="0">
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
                    <tr class="total-row">
                        <td><strong>Total geral</strong></td>
                        <td class="text-right"><strong>R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
        </table>
        </div>

        <div class="rodape">
            Documento: {{ $documentoId }} · Gerado em {{ $geradoEm }} por {{ $geradoPor }} · IP: {{ $ipDispositivo }} · Área do Cliente · {{ format_os_display($ordem) }}
        </div>
    </div>
</body>
</html>
