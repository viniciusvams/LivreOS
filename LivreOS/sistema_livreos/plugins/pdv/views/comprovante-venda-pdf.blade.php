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
    if ($empresa === null && class_exists(\App\Models\Empresa::class)) {
        try {
            $empresa = \App\Models\Empresa::first();
        } catch (\Throwable $e) {
            $empresa = null;
        }
    }
    $empresaNome = $empresa?->nome ?? 'Empresa';
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogoBase64 = $empresaLogoBase64 ?? null;
    $empresaLogoUrl = $empresaLogoUrl ?? null;
    $empresaLogo = null;
    if (!$empresaLogoBase64 && !$empresaLogoUrl && $empresa?->logo_path) {
        $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
        if ($logoPathRel !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPathRel)) {
            $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($logoPathRel) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($logoPathRel));
        }
    }
    $logoParaExibir = $empresaLogoBase64 ?: $empresaLogoUrl ?: $empresaLogo;
    $itens = $itens ?? [];
    $itensServicos = array_values(array_filter($itens, fn($i) => ($i['tipo'] ?? 'produto') === 'servico'));
    $itensProdutos = array_values(array_filter($itens, fn($i) => ($i['tipo'] ?? 'produto') === 'produto'));
    $totalServicos = array_sum(array_column($itensServicos, 'total'));
    $totalProdutos = array_sum(array_column($itensProdutos, 'total'));
    $clienteNome = $clienteNome ?? 'Cliente balcão';
    $clienteDados = $clienteDados ?? null;
    $totalGeral = (float) ($subtotal ?? $venda->total ?? 0);
    $desconto = (float) ($desconto ?? $venda->desconto ?? 0);
    $totalFinal = (float) ($totalFinal ?? $venda->total_final ?? 0);
    $dataHora = $dataHora ?? $venda->created_at->format('d/m/Y H:i');
    $numeroInicial = (int) (function_exists('get_option') ? get_option('pdv_numero_venda_inicial', 1, 'pdv') : 1);
    if ($numeroInicial < 1) $numeroInicial = 1;
    $numeroCupom = $numeroCupom ?? (($numeroInicial - 1) + $venda->id);
    $pagamentos = $pagamentos ?? [];
    $seriaisGarantia = $seriaisGarantia ?? [];
    $observacoesVenda = $observacoesVenda ?? (isset($venda) ? trim((string) ($venda->observacoes ?? '')) : '');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Comprovante de Venda - {{ $numeroCupom }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; }
        .page { max-width: 100%; margin: 0; padding: 0 0 16px 0; }
        .header-table { width: 100%; border-collapse: collapse; border: none; margin-bottom: 4px; border-bottom: 2px solid #e5e7eb; }
        .header-table td { border: none; vertical-align: middle; padding: 0 16px 16px 0; }
        .header-table .logo-cell { width: 140px; padding-right: 16px; }
        .header-table .logo-cell img { max-width: 140px; height: auto; display: block; }
        .header-table .emitente-cell { padding-left: 0; }
        .header-table .contato-cell { width: 220px; text-align: right; }
        .emitente h1 { font-size: 18px; margin: 0 0 6px 0; }
        .emitente p, .contato p { margin: 2px 0; font-size: 13px; color: #374151; }
        .titulo { margin: 18px 0 12px 0; }
        .titulo-table { width: 100%; border-collapse: collapse; }
        .titulo-table td { padding: 0; vertical-align: baseline; }
        .titulo h2 { margin: 0; font-size: 20px; }
        .titulo span { font-size: 12px; color: #6b7280; }
        .titulo .titulo-right { text-align: right; }
        .bloco { margin-top: 16px; }
        .bloco h3 { margin: 0 0 8px 0; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #374151; }
        .dados { font-size: 13px; color: #374151; }
        .dados p { margin: 2px 0; }
        table.tabela-itens { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
        table.tabela-itens th, table.tabela-itens td { border: 1px solid #e5e7eb; padding: 8px; }
        table.tabela-itens th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totais { margin-top: 12px; }
        .totais table { width: 320px; margin-left: auto; border-collapse: collapse; }
        .totais td { border: 1px solid #e5e7eb; padding: 8px; }
        table.tabela-pagamentos { width: 100%; max-width: 400px; margin-top: 8px; border-collapse: collapse; font-size: 13px; }
        table.tabela-pagamentos th, table.tabela-pagamentos td { border: 1px solid #e5e7eb; padding: 8px; }
        table.tabela-pagamentos th { background: #f3f4f6; }
        .rodape-comprovante { margin-top: 20px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; text-align: center; }
        .observacao-bloco { border: 1px solid #e5e7eb; padding: 10px; background: #f9fafb; white-space: pre-wrap; font-size: 13px; color: #374151; }
    </style>
</head>
<body>
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoParaExibir)
                        <img src="{{ $logoParaExibir }}" alt="Logotipo">
                    @endif
                </td>
                <td class="emitente-cell">
                    <div class="emitente">
                        <h1>{{ $empresaNome }}</h1>
                        @if($empresaRazao)<p>{{ $empresaRazao }}</p>@endif
                        @if($empresaCnpj)<p>CNPJ: {{ $empresaCnpj }}</p>@endif
                        @if($empresa && ($empresa->logradouro || $empresa->cidade))
                            <p>{{ trim($empresa->logradouro . ' ' . $empresa->numero . ($empresa->complemento ? ' - ' . $empresa->complemento : '') . ', ' . $empresa->bairro . ' - ' . $empresa->cidade . '/' . $empresa->estado) }}</p>
                            @if($empresa->cep)<p>CEP: {{ $empresa->cep }}</p>@endif
                        @endif
                    </div>
                </td>
                <td class="contato-cell">
                    <div class="contato">
                        @if($empresaTelefone)<p><strong>Tel:</strong> {{ $empresaTelefone }}</p>@endif
                        @if($empresaEmail)<p><strong>{{ $empresaEmail }}</strong></p>@endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="titulo">
            <table class="titulo-table"><tr>
                <td><h2>COMPROVANTE DE VENDA nº {{ $numeroCupom }}</h2></td>
                <td class="titulo-right"><span>Data/Hora: {{ $dataHora }}</span></td>
            </tr></table>
        </div>

        <div class="bloco">
            <h3>Cliente</h3>
            <div class="dados">
                <p><strong>{{ $clienteNome }}</strong></p>
                @if(!empty($clienteDados))
                    @if(!empty($clienteDados['documento']))
                        <p>CPF/CNPJ: {{ $clienteDados['documento'] }}</p>
                    @endif
                    @if(!empty($clienteDados['endereco']))
                        <p>{{ $clienteDados['endereco'] }}</p>
                    @endif
                    @if(!empty($clienteDados['telefone']))
                        <p>Tel: {{ $clienteDados['telefone'] }}</p>
                    @endif
                    @if(!empty($clienteDados['email']))
                        <p>E-mail: {{ $clienteDados['email'] }}</p>
                    @endif
                @endif
            </div>
        </div>

        @if(count($itensServicos) > 0)
        <div class="bloco">
            <h3>Serviços</h3>
            <table class="tabela-itens">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itensServicos as $item)
                    <tr>
                        <td>
                            {{ $item['descricao'] ?? '-' }}
                            @if(!empty($item['observacao'] ?? null) && trim((string)($item['observacao'] ?? '')) !== '')
                                <br><span style="font-size: 11px; color: #6b7280;">Obs: {{ trim($item['observacao']) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item['quantidade'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($item['preco_unitario'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
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

        @if(count($itensProdutos) > 0)
        <div class="bloco">
            <h3>Produtos / Peças</h3>
            <table class="tabela-itens">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-center" width="15%">Unt</th>
                        <th class="text-right" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itensProdutos as $item)
                    <tr>
                        <td>
                            {{ $item['descricao'] ?? '-' }}
                            @if(!empty($item['serial']))
                                <br><span style="font-size: 11px; color: #6b7280;">S/N: {{ $item['serial'] }}</span>
                            @endif
                            @php
                                $kitComp = $item['kit_componentes'] ?? null;
                                $kitLista = is_array($kitComp) ? array_filter($kitComp) : [];
                            @endphp
                            @if(!empty($kitLista))
                                <span style="color: #6b7280;"> ({{ implode(', ', $kitLista) }})</span>
                            @endif
                            @if(!empty($item['observacao'] ?? null) && trim((string)($item['observacao'] ?? '')) !== '')
                                <br><span style="font-size: 11px; color: #6b7280;">Obs: {{ trim($item['observacao']) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item['quantidade'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($item['preco_unitario'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
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
                    @if(count($itensServicos) > 0)
                    <tr>
                        <td>Total serviços</td>
                        <td class="text-right">R$ {{ number_format($totalServicos, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(count($itensProdutos) > 0)
                    <tr>
                        <td>Total produtos</td>
                        <td class="text-right">R$ {{ number_format($totalProdutos, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($desconto > 0)
                    <tr>
                        <td>Desconto</td>
                        <td class="text-right">- R$ {{ number_format($desconto, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Total geral</strong></td>
                        <td class="text-right"><strong>R$ {{ number_format($totalFinal, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(count($pagamentos) > 0)
        <div class="bloco">
            <h3>Formas de pagamento</h3>
            <table class="tabela-pagamentos">
                <thead>
                    <tr>
                        <th>Forma</th>
                        <th class="text-right" width="25%">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pagamentos as $p)
                    <tr>
                        <td>{{ $p['forma_nome'] ?? 'Pagamento' }}@if(($p['parcelas'] ?? 1) > 1) ({{ $p['parcelas'] }}x)@endif</td>
                        <td class="text-right">R$ {{ number_format($p['valor'] ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($observacoesVenda !== '')
        <div class="bloco">
            <h3>Observação</h3>
            <div class="observacao-bloco">{{ $observacoesVenda }}</div>
        </div>
        @endif

        <div class="rodape-comprovante">
            @if(count($seriaisGarantia) > 0)
                Garantia para os produtos com Serial Number listados acima. Guarde este comprovante.
            @else
                Guarde este comprovante para sua referência.
            @endif
            <br>Obrigado pela preferência!
        </div>
    </div>
</body>
</html>
