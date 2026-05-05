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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante OS {{ format_os_display($ordem) }}</title>
    <style>
        @page { margin: 2mm; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }
        @if($formato === '58mm')
        body { font-size: 10px; line-height: 1.1; }
        @else
        body { font-size: 11px; line-height: 1.2; }
        @endif
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .mt-1 { margin-top: 5px; }
        .mt-2 { margin-top: 10px; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        
        .header { text-align: center; margin-bottom: 10px; }
        .emitente { margin-bottom: 5px; }
        .contato-emitente { margin-bottom: 10px; }
        
        .title { font-size: 1.2em; font-weight: bold; text-align: center; margin-top: 5px; margin-bottom: 5px; }
        .emissao { font-size: 0.9em; text-align: center; margin-bottom: 10px; }
        
        .subtitle { font-weight: bold; margin-top: 10px; margin-bottom: 2px; text-transform: uppercase; border-bottom: 1px dashed #000; padding-bottom: 2px;}
        
        table { width: 100%; border-collapse: collapse; margin-top: 5px; table-layout: fixed; word-wrap: break-word; }
        .table-bordered th, .table-bordered td { border: 1px solid #000; padding: 3px; vertical-align: middle; overflow-wrap: break-word; }
        .table-bordered th { font-weight: bold; background-color: #f2f2f2; }
        
        .dados { text-align: left; }
        
        .footer-info { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 5px; }
        .assinaturas { text-align: center; margin-top: 30px; }
        
        .espaco-final { height: 20px; }
    </style>
</head>
<body>
    <div class="header">
        @if($empresa && $empresa->nome)
            <div class="emitente">
                <span class="font-bold" style="font-size: 1.2em;">{{ mb_strtoupper($empresa->nome) }}</span><br>
                @if($empresa->cnpj)
                <span>CNPJ: {{ $empresa->cnpj }}</span><br>
                @endif
                @if($empresa->logradouro)
                <span>{{ $empresa->logradouro }}, {{ $empresa->numero }}, {{ $empresa->bairro }}</span><br>
                <span>{{ $empresa->cidade }} - {{ $empresa->estado }} - {{ $empresa->cep }}</span>
                @endif
            </div>
            <div class="contato-emitente">
                @if($empresa->telefone || $empresa->celular)
                <span class="font-bold">Tel: {{ $empresa->telefone ?: $empresa->celular }}</span><br>
                @endif
                @if($empresa->email)
                <span class="font-bold">{{ $empresa->email }}</span>
                @endif
            </div>
        @else
            <div class="font-bold">EMPRESA NÃO CONFIGURADA</div>
        @endif
    </div>

    <div class="title">
        COMPROVANTE DE ENTREGA<br>
        OS {{ format_os_display($ordem) }}
    </div>
    <div class="emissao">
        Data: {{ $emitidoEm }}
    </div>

    <div class="subtitle">DADOS DO CLIENTE</div>
    <div class="dados">
        <span class="font-bold">{{ mb_strtoupper($ordem->cliente?->nome) }}</span><br>
        @if($ordem->cliente?->cpf)
        <span>CPF: {{ $ordem->cliente->cpf }}</span><br>
        @elseif($ordem->cliente?->cnpj)
        <span>CNPJ: {{ $ordem->cliente->cnpj }}</span><br>
        @endif
    </div>

    @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo || $ordem->equipamento_numero_serie || $ordem->equipamento_acessorios)
    <div class="subtitle">EQUIPAMENTO ENTREGUE</div>
    <div class="dados">
        @if($ordem->equipamento_nome) <span class="font-bold">{{ mb_strtoupper($ordem->equipamento_nome) }}</span><br> @endif
        @if($ordem->equipamento_marca) <span>Marca: {{ mb_strtoupper($ordem->equipamento_marca) }}</span><br> @endif
        @if($ordem->equipamento_modelo) <span>Modelo: {{ mb_strtoupper($ordem->equipamento_modelo) }}</span><br> @endif
        @if($ordem->equipamento_numero_serie) <span>N/S: {{ mb_strtoupper($ordem->equipamento_numero_serie) }}</span><br> @endif
        @if($ordem->equipamento_acessorios) <span>Acessórios: {{ mb_strtoupper($ordem->equipamento_acessorios) }}</span> @endif
    </div>
    @endif

    @if($ordem->relato_cliente)
    <div class="subtitle">RELATO DO CLIENTE</div>
    <div class="dados text-justify">
        {!! $ordem->relato_cliente !!}
    </div>
    @endif

    @if($ordem->diagnostico_tecnico)
    <div class="subtitle">DIAGNÓSTICO TÉCNICO</div>
    <div class="dados text-justify">
        {!! $ordem->diagnostico_tecnico !!}
    </div>
    @endif

    @if($ordem->servicos_realizados)
    <div class="subtitle">DESCRIÇÃO DOS SERVIÇOS</div>
    <div class="dados text-justify">
        {!! $ordem->servicos_realizados !!}
    </div>
    @endif

    <?php 
    $temServicos = $ordem->servicos && $ordem->servicos->count() > 0;
    $temProdutos = $ordem->produtos && $ordem->produtos->count() > 0;
    ?>

    @if($temProdutos)
    <div class="subtitle mt-2">PRODUTOS</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th class="text-left">PRODUTO(S)</th>
                <th class="text-center" width="15%">QTD</th>
                <th class="text-center" width="20%">UNT</th>
                <th class="text-right" width="25%">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordem->produtos as $produto)
            <tr>
                <td class="text-left">{{ mb_strtoupper($produto->descricao ?: $produto->produto?->nome) }}</td>
                <td class="text-center">{{ number_format($produto->quantidade, 2, ',', '') }}</td>
                <td class="text-center">{{ number_format($produto->valor_unitario ?: ($produto->total / $produto->quantidade), 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($produto->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-right font-bold">TOTAL PRODUTOS:</td>
                <td class="text-right font-bold">R$ {{ number_format($ordem->total_produtos, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if($temServicos)
    <div class="subtitle mt-2">SERVIÇOS</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th class="text-left">SERVIÇO(S)</th>
                <th class="text-center" width="15%">QTD</th>
                <th class="text-center" width="20%">UNT</th>
                <th class="text-right" width="25%">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordem->servicos as $servico)
            <tr>
                <td class="text-left">{{ mb_strtoupper($servico->descricao ?: $servico->servico?->nome) }}</td>
                <td class="text-center">{{ number_format($servico->quantidade, 2, ',', '') }}</td>
                <td class="text-center">{{ number_format($servico->valor_unitario ?: ($servico->total / $servico->quantidade), 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($servico->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-right font-bold">TOTAL SERVIÇOS:</td>
                <td class="text-right font-bold">R$ {{ number_format($ordem->total_servicos, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="mt-2">
        <table class="table-bordered">
            <thead>
                <tr class="table-secondary">
                    <th colspan="2" class="text-left">RESUMO DOS VALORES</th>
                </tr>
            </thead>
            <tbody>
                @if($ordem->total_desconto > 0 || $ordem->total_acrescimos > 0)
                <tr>
                    <td width="65%">TOTAL SERVIÇOS</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_servicos, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>TOTAL PRODUTOS</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_produtos, 2, ',', '.') }}</td>
                </tr>
                @if($ordem->total_desconto > 0)
                <tr>
                    <td>DESCONTO</td>
                    <td class="text-right font-bold">- R$ {{ number_format($ordem->total_desconto, 2, ',', '.') }}</td>
                </tr>
                @endif
                @if($ordem->total_acrescimos > 0)
                <tr>
                    <td>ACRÉSCIMOS</td>
                    <td class="text-right font-bold">+ R$ {{ number_format($ordem->total_acrescimos, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td>TOTAL DA OS</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td width="65%">TOTAL DA OS</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td>DATA DE CONCLUSÃO</td>
                    <td class="text-right">{{ $ordem->real_conclusao ? \Carbon\Carbon::parse($ordem->real_conclusao)->format('d/m/Y') : '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(!empty($comprovanteHtmlAntesAssinatura))
    <div class="mt-2 text-justify" style="font-size: 0.9em; padding: 10px; border: 1px solid #000; background-color: #f9fafb; margin-top: 10px;">
        {!! $comprovanteHtmlAntesAssinatura !!}
    </div>
    @else
    <div class="mt-2 text-center" style="font-size: 0.9em; padding-top: 10px;">
        Declaro ter recebido o equipamento acima discriminado e testado em perfeitas condições.
    </div>
    @endif

    <div class="assinaturas">
        <div style="border-top: 1px solid #000; margin: 0 auto; width: 80%;"></div>
        <div class="mt-1">Assinatura do Cliente</div>
    </div>

    <div class="mt-2 text-center" style="font-size: 0.8em; color: #555; margin-top: 20px;">
        Emitido em {{ date('d/m/Y') }} às {{ date('H:i') }} &middot; LivreOS - ERP Open Source Livre
    </div>

    <div class="espaco-final"></div>
</body>
</html>
