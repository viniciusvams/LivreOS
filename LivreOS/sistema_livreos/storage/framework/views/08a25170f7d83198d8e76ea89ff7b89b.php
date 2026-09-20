

<?php
    $empresaNome = $empresa?->nome ?? $empresa?->razao_social ?? 'Empresa';
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaLogo = null;
    if ($empresa && !empty($empresa->logo_path)) {
        $logoPathRel = ltrim(str_replace('\\', '/', (string) $empresa->logo_path), '/');
        $logoPath = storage_path('app/public/' . $logoPathRel);
        if ($logoPathRel !== '' && file_exists($logoPath) && is_readable($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
    $municipio = $empresa && !empty(trim((string) $empresa->cidade)) ? trim($empresa->cidade) : null;
    $dataExtenso = \Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y');
    $dataEmissao = now()->format('d/m/Y \à\s H:i');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
    if ($empresa && ($empresa->logradouro || $empresa->cidade)) {
        $parts = [
            trim($empresa->logradouro ?? ''),
            trim($empresa->numero ?? ''),
            $empresa->complemento ? trim($empresa->complemento) : null,
            trim($empresa->bairro ?? ''),
            $empresa->cidade ? trim($empresa->cidade) . '/' . trim($empresa->estado ?? '') : null,
        ];
        $parts = array_filter($parts, fn($p) => $p !== null && $p !== '');
        $enderecoTexto = implode(' - ', $parts);
    } else {
        $enderecoTexto = '';
    }
    $clienteNome = optional($cliente)->nome ?? optional($cliente)->razao_social ?? '-';
    $clienteDoc = $cliente ? (optional($cliente)->tipo_pessoa === 'F' ? (optional($cliente)->cpf ?? '-') : (optional($cliente)->cnpj ?? '-')) : '-';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Recibo de Pagamento</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 15mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 9pt;
            color: #333;
        }
        .page {
            width: 100%;
            max-width: 180mm;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }
        /* Cores: Azul Escuro (#1A237E), Azul Claro (#E3F2FD), Laranja (#FF8C00) */

        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1A237E; padding-bottom: 6px; margin-bottom: 12px; }
        .header-table td { vertical-align: top; padding: 0 6px 6px 0; }
        .header-table td:first-child { width: 70px; padding-right: 8px; vertical-align: top; }
        .header-table td:last-child { text-align: right; padding-right: 0; padding-left: 6px; width: 80px; }
        .header-logo img { max-width: 70px; max-height: 50px; display: block; width: auto; height: auto; }
        .empresa-info h1 { color: #1A237E; margin: 0 0 4px 0; font-size: 14pt; text-transform: uppercase; line-height: 1.2; }
        .empresa-detalhes { font-size: 8pt; color: #555; line-height: 1.35; }
        .recibo-titulo { color: #FF8C00; font-size: 16pt; font-weight: bold; margin: 0 0 2px 0; }
        .recibo-numero { font-size: 11pt; color: #1A237E; font-weight: bold; margin: 0; }

        .valor-box {
            background-color: #E3F2FD;
            border-left: 4px solid #FF8C00;
            padding: 10px 12px;
            margin: 12px 0;
            text-align: center;
        }
        .valor-label { font-size: 8pt; color: #1A237E; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .valor-num { font-size: 18pt; color: #1A237E; font-weight: bold; }
        .valor-extenso { font-size: 8pt; margin-top: 4px; color: #555; font-style: italic; }

        .secao { margin-bottom: 12px; }
        .secao-titulo {
            background-color: #1A237E;
            color: white;
            padding: 4px 10px;
            font-size: 9pt;
            text-transform: uppercase;
            margin-bottom: 0;
        }
        .grid-table { width: 100%; border-collapse: collapse; }
        .grid-table td { padding: 6px 10px; vertical-align: top; border: 1px solid #e0e0e0; font-size: 9pt; }
        .grid-table tr:nth-child(odd) td { background-color: #fafafa; }
        .info-label { font-size: 7pt; color: #777; display: block; margin-bottom: 1px; }
        .info-valor { font-size: 9pt; font-weight: 500; color: #333; }

        .vinculo-nota {
            background-color: #fff9f0;
            border: 1px dashed #FF8C00;
            padding: 8px 10px;
            margin-top: 15px;
            font-size: 7pt;
            color: #555;
            line-height: 1.4;
        }

        .footer-wrap { margin-top: 20px; padding-top: 10px; text-align: center; }
        .data-emissao { font-size: 8pt; color: #999; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="page">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header-logo">
                    <?php if($empresaLogo): ?>
                        <img src="<?php echo e($empresaLogo); ?>" alt="Logotipo"/>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="empresa-info">
                        <h1><?php echo e($empresaNome); ?></h1>
                        <div class="empresa-detalhes">
                            <?php if($empresaRazao && $empresaRazao !== $empresaNome): ?><?php echo e($empresaRazao); ?><br><?php endif; ?>
                            <?php if($empresaCnpj): ?>CNPJ: <?php echo e($empresaCnpj); ?><br><?php endif; ?>
                            <?php if($enderecoTexto !== ''): ?><?php echo e($enderecoTexto); ?><?php if(!empty($empresa->cep)): ?> · CEP: <?php echo e($empresa->cep); ?><?php endif; ?><br><?php endif; ?>
                            <?php if($empresaTelefone || $empresaEmail): ?><?php if($empresaTelefone): ?><?php echo e($empresaTelefone); ?><?php endif; ?> <?php if($empresaTelefone && $empresaEmail): ?> | <?php endif; ?> <?php if($empresaEmail): ?><?php echo e($empresaEmail); ?><?php endif; ?> <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <p class="recibo-titulo">RECIBO</p>
                    <p class="recibo-numero">Nº <?php echo e($conta->id); ?></p>
                </td>
            </tr>
        </table>

        <div class="valor-box">
            <span class="valor-label">Valor Recebido</span>
            <span class="valor-num">R$ <?php echo e(number_format($valorRecebido, 2, ',', '.')); ?></span>
            <?php if(!empty($valorPorExtenso)): ?>
                <div class="valor-extenso">(<?php echo e($valorPorExtenso); ?>)</div>
            <?php endif; ?>
        </div>

        <div class="secao">
            <div class="secao-titulo">Identificação do Cliente</div>
            <table class="grid-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%">
                        <span class="info-label">Nome/Razão Social</span>
                        <span class="info-valor"><?php echo e($clienteNome); ?></span>
                    </td>
                    <td width="50%">
                        <span class="info-label">CPF/CNPJ</span>
                        <span class="info-valor"><?php echo e($clienteDoc); ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="secao">
            <div class="secao-titulo">Detalhes do Pagamento</div>
            <table class="grid-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%">
                        <span class="info-label">Referente a</span>
                        <span class="info-valor"><?php echo e($referente); ?></span>
                    </td>
                    <td width="50%">
                        <span class="info-label">Forma de Pagamento</span>
                        <span class="info-valor"><?php echo e($formaPagamentoTexto ?? optional($formaPagamento)->nome ?? '-'); ?></span>
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <span class="info-label">Data do Recebimento</span>
                        <span class="info-valor"><?php echo e($dataRecebimento); ?></span>
                    </td>
                    <td width="50%">
                        <span class="info-label">Data de Emissão</span>
                        <span class="info-valor"><?php echo e($dataEmissao); ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vinculo-nota">
            <strong>VINCULAÇÃO DE SEGURANÇA:</strong> Este documento é um registro administrativo de recebimento. Sua validade como prova de quitação está estritamente vinculada à apresentação do comprovante de transação original (seja ele impresso, digital ou bancário) que deu origem a este crédito. Em caso de estorno, divergência ou cancelamento da operação financeira pela instituição mediadora, este recibo perde automaticamente sua validade legal.
        </div>

        <div class="footer-wrap">
            <div class="data-emissao">Documento gerado em <?php echo e($municipio ? $municipio . ', ' . $dataExtenso : $dataExtenso); ?>.</div>
            <div class="data-emissao" style="margin-top: 4px; font-size: 8pt; color: #888;"><?php echo e($pdfFooterTexto); ?></div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\contas-receber\recibo-pagamento.blade.php ENDPATH**/ ?>