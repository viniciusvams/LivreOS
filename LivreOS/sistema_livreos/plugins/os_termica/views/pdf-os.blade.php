<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>OS {{ format_os_display($ordem) }}</title>
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
        .text-justify { text-align: justify; }
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
        ORDEM DE SERVIÇO {{ format_os_display($ordem) }}
    </div>
    <div class="emissao">
        Emissão: {{ $emitidoEm }}
    </div>

    <div class="subtitle">DADOS DO CLIENTE</div>
    <div class="dados">
        <span class="font-bold">{{ mb_strtoupper($ordem->cliente?->nome) }}</span><br>
        @if($ordem->cliente?->cpf)
        <span>CPF: {{ $ordem->cliente->cpf }}</span><br>
        @elseif($ordem->cliente?->cnpj)
        <span>CNPJ: {{ $ordem->cliente->cnpj }}</span><br>
        @endif
        @if($ordem->cliente?->celular || $ordem->cliente?->telefone)
        <span>Tel: {{ $ordem->cliente->celular ?: $ordem->cliente->telefone }}</span><br>
        @endif
        @if($ordem->cliente?->email)
        <span>{{ $ordem->cliente->email }}</span><br>
        @endif
        @if($ordem->cliente?->logradouro)
        <span>{{ $ordem->cliente->logradouro }}, {{ $ordem->cliente->numero }}, {{ $ordem->cliente->bairro }}</span><br>
        <span>{{ $ordem->cliente->cidade }} - {{ $ordem->cliente->estado }} - {{ $ordem->cliente->cep }}</span>
        @endif
    </div>

    @if($ordem->equipamento_nome || $ordem->equipamento_marca || $ordem->equipamento_modelo)
    <div class="subtitle">EQUIPAMENTO</div>
    <div class="dados">
        @if($ordem->equipamento_nome) <span class="font-bold">{{ mb_strtoupper($ordem->equipamento_nome) }}</span><br> @endif
        @if($ordem->equipamento_marca) <span>Marca: {{ mb_strtoupper($ordem->equipamento_marca) }}</span><br> @endif
        @if($ordem->equipamento_modelo) <span>Modelo: {{ mb_strtoupper($ordem->equipamento_modelo) }}</span><br> @endif
        @if($ordem->equipamento_numero_serie) <span>N/S: {{ mb_strtoupper($ordem->equipamento_numero_serie) }}</span> @endif
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
    <div class="subtitle">SERVIÇOS REALIZADOS</div>
    <div class="dados text-justify">
        {!! $ordem->servicos_realizados !!}
    </div>
    @endif

    @if($ordem->observacoes_cliente)
    <div class="subtitle">OBSERVAÇÕES</div>
    <div class="dados text-justify">
        {!! $ordem->observacoes_cliente !!}
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
                @if($ordem->total_desconto > 0)
                <tr>
                    <td width="65%">SUBTOTAL</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_produtos + $ordem->total_servicos, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>DESCONTO</td>
                    <td class="text-right font-bold">- R$ {{ number_format($ordem->total_desconto, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>TOTAL</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</td>
                </tr>
                @else
                <tr>
                    <td width="65%">TOTAL</td>
                    <td class="text-right font-bold">R$ {{ number_format($ordem->total_geral, 2, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="footer-info">
        <span class="font-bold">STATUS: {{ mb_strtoupper($ordem->status) }}</span><br>
        <span>Data Inicial: {{ $ordem->created_at->format('d/m/Y') }}</span>
    </div>

    @if(!empty($osImpressaoObservacaoAntesAssinaturaAtivo) && !empty(trim($osImpressaoObservacaoAntesAssinatura)))
    <div class="mt-2 text-justify" style="font-size: 0.9em; padding: 10px; border: 1px solid #000; background-color: #f9fafb; margin-top: 10px;">
        {!! $osImpressaoObservacaoAntesAssinatura !!}
    </div>
    @endif

    <div class="assinaturas">
        <div style="border-top: 1px solid #000; margin: 0 auto; width: 80%;"></div>
        <div class="mt-1">Assinatura do Cliente</div>
        
        <div style="border-top: 1px solid #000; margin: 40px auto 0; width: 80%;"></div>
        <div class="mt-1">Assinatura do Técnico</div>
    </div>

    <div class="mt-2 text-center" style="font-size: 0.8em; color: #555; margin-top: 20px;">
        Emitido em {{ date('d/m/Y') }} às {{ date('H:i') }} &middot; LivreOS - ERP Open Source Livre
    </div>

    <div class="espaco-final"></div>
</body>
</html>
