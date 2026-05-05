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
    $empresa = class_exists(\App\Models\Empresa::class) ? \App\Models\Empresa::first() : null;
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaCelular = $empresa?->celular ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/'.$empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }
    }
    $enderecoEmpresa = null;
    if ($empresa && ($empresa->logradouro || $empresa->cidade)) {
        $enderecoEmpresa = trim(
            ($empresa->logradouro ? $empresa->logradouro.' ' : '')
            .($empresa->numero ?? '')
            .($empresa->complemento ? ' - '.$empresa->complemento : '')
            .($empresa->bairro ? ', '.$empresa->bairro : '')
            .($empresa->cidade ? ' - '.$empresa->cidade.'/'.($empresa->estado ?? '') : '')
        );
    }
@endphp
<div class="page">
    <table class="cabecalho-empresa">
        <tr>
            <td class="col-logo">
                <div class="logo-box">
                    @if($empresaLogo)
                        <img src="{{ $empresaLogo }}" alt="">
                    @endif
                </div>
            </td>
            <td>
                <div class="emitente-nome">{{ $empresaNome }}</div>
                @if($empresaRazao)<p class="emitente-linha">{{ $empresaRazao }}</p>@endif
                @if($empresaCnpj)<p class="emitente-linha"><strong>CNPJ:</strong> {{ $empresaCnpj }}</p>@endif
                @if($enderecoEmpresa)<p class="emitente-linha">{{ $enderecoEmpresa }}</p>@endif
            </td>
            <td class="col-contato">
                @if($empresaTelefone)<p class="contato-linha"><strong>Tel.</strong> {{ $empresaTelefone }}</p>@endif
                @if($empresaCelular)<p class="contato-linha"><strong>Cel.</strong> {{ $empresaCelular }}</p>@endif
                @if($empresaEmail)<p class="contato-linha">{{ $empresaEmail }}</p>@endif
            </td>
        </tr>
    </table>

    <table class="faixa-doc">
        <tr>
            <td width="58%">
                <div class="titulo-doc">Proposta comercial</div>
                @if($proposta->titulo)<p style="font-size:10px;color:#6b7280;margin-top:4px">{{ $proposta->titulo }}</p>@endif
            </td>
            <td width="42%">
                <div class="num-doc">{{ $proposta->referenciaGrupo() }}</div>
                <p style="text-align:right;font-size:9px;color:#6b7280;margin-top:4px">{{ $proposta->status_label }}</p>
            </td>
        </tr>
    </table>

    <div class="section-title">Dados</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Cliente</div>
            <div class="info-value">{{ $proposta->cliente?->nome ?? '—' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Emissão</div>
            <div class="info-value">{{ $proposta->data_emissao?->format('d/m/Y') ?? '—' }}</div>
        </div>
        @if($proposta->validade_em)
        <div class="info-row">
            <div class="info-label">Válido até</div>
            <div class="info-value">{{ $proposta->validade_em->format('d/m/Y') }}</div>
        </div>
        @endif
        @if($proposta->vendedor)
        <div class="info-row">
            <div class="info-label">Vendedor</div>
            <div class="info-value">{{ $proposta->vendedor->name }}</div>
        </div>
        @endif
    </div>

    @if(filled($proposta->descricao))
    <div class="section-title">Descrição geral</div>
    <div class="descricao-geral">{!! $proposta->descricao !!}</div>
    @endif

    <div class="section-title">Itens</div>
    <table class="itens">
        <thead>
            <tr>
                <th style="width:14px">#</th>
                <th style="width:56px">Tipo</th>
                <th>Descrição</th>
                <th class="r" style="width:46px">Qtd</th>
                <th class="r" style="width:68px">Unit.</th>
                <th class="r" style="width:56px">Desc.</th>
                <th class="r" style="width:72px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proposta->itens as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <span class="{{ $item->tipo === 'servico' ? 'tag-servico' : 'tag-produto' }}">
                        {{ $item->tipo === 'servico' ? 'Serviço' : 'Produto' }}
                    </span>
                </td>
                <td>{{ $item->descricao }}</td>
                <td class="r">{{ format_quantidade_br($item->quantidade) }}</td>
                <td class="r">R$ {{ format_br_decimal($item->preco_unitario) }}</td>
                <td class="r">{{ $item->desconto > 0 ? '- R$ '.format_br_decimal($item->desconto) : '—' }}</td>
                <td class="r">R$ {{ format_br_decimal($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="r">Subtotal produtos</td>
                <td class="r">R$ {{ format_br_decimal($proposta->total_produtos) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="r">Subtotal serviços</td>
                <td class="r">R$ {{ format_br_decimal($proposta->total_servicos) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="color:#dc2626">Descontos</td>
                <td class="r" style="color:#dc2626">- R$ {{ format_br_decimal($proposta->total_descontos) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">Total geral</td>
                <td class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">R$ {{ format_br_decimal($proposta->total_geral) }}</td>
            </tr>
        </tfoot>
    </table>

    @if(filled($proposta->observacoes))
    <div class="section-title">Observações</div>
    <div class="obs">{!! $proposta->observacoes !!}</div>
    @endif

    <div class="footer">
        Gerado em {{ now()->format('d/m/Y H:i') }} · Proposta #{{ $proposta->id }} v{{ $proposta->versao }} · LivreOS — ERP Open Source Livre
    </div>
</div>
