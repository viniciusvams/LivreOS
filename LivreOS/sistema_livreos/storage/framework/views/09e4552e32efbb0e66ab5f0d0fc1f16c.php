

<?php
    $empresa = class_exists(\App\Models\Empresa::class) ? \App\Models\Empresa::first() : null;
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $empresaRazao = $empresa?->razao_social ?? null;
    $empresaCnpj = $empresa?->cnpj ?? null;
    $empresaTelefone = $empresa?->telefone ?? null;
    $empresaCelular = $empresa?->celular ?? null;
    $empresaEmail = $empresa?->email ?? null;
    $empresaSite = $empresa?->site ?? null;
    $empresaLogo = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/' . $empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogo = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        }
    }
    $enderecoEmpresa = null;
    if ($empresa && ($empresa->logradouro || $empresa->cidade)) {
        $enderecoEmpresa = trim(
            ($empresa->logradouro ? $empresa->logradouro . ' ' : '')
            . ($empresa->numero ?? '')
            . ($empresa->complemento ? ' - ' . $empresa->complemento : '')
            . ($empresa->bairro ? ', ' . $empresa->bairro : '')
            . ($empresa->cidade ? ' - ' . $empresa->cidade . '/' . ($empresa->estado ?? '') : '')
        );
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Pedido de Venda — <?php echo e($pedido->numero_sequencial); ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; background: #fff; }
    .page { padding: 18px 22px 24px; }
    .cabecalho-empresa { width: 100%; border-collapse: collapse; margin-bottom: 12px; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; }
    .cabecalho-empresa td { vertical-align: top; padding: 0 8px 0 0; }
    .cabecalho-empresa .col-logo { width: 130px; }
    .cabecalho-empresa .logo-box img { max-width: 120px; max-height: 72px; height: auto; display: block; }
    .cabecalho-empresa .emitente-nome { font-size: 16px; font-weight: bold; color: #1e1b4b; margin-bottom: 4px; }
    .cabecalho-empresa .emitente-linha { font-size: 9.5px; color: #374151; line-height: 1.45; margin: 1px 0; }
    .cabecalho-empresa .col-contato { width: 32%; text-align: right; }
    .cabecalho-empresa .contato-linha { font-size: 9.5px; color: #4b5563; margin: 2px 0; }
    .cabecalho-empresa .contato-linha strong { color: #1f2937; }

    table.faixa-doc { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
    table.faixa-doc td { vertical-align: top; padding: 0; }
    .titulo-doc { font-size: 13px; font-weight: bold; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.04em; }
    .sub-doc { font-size: 9px; color: #6b7280; margin-top: 2px; }
    .footer-doc-aviso { font-size: 7.5px; color: #9ca3af; margin-top: 8px; line-height: 1.4; font-weight: normal; }
    .num-pedido { font-size: 20px; font-weight: bold; color: #4f46e5; text-align: right; }
    .status-pedido { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; background: #fef3c7; color: #92400e; margin-top: 4px; float: right; clear: both; }

    .section-title { font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #6b7280; letter-spacing: .06em; margin: 12px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
    .info-grid { display: table; width: 100%; }
    .info-row { display: table-row; }
    .info-label { display: table-cell; font-weight: bold; color: #4b5563; padding: 2px 10px 2px 0; width: 118px; font-size: 9.5px; }
    .info-value { display: table-cell; color: #111827; padding: 2px 0; font-size: 10px; }
    table.itens { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.itens thead th { background: #4f46e5; color: #fff; font-size: 9px; padding: 6px 5px; text-align: left; }
    table.itens thead th.r { text-align: right; }
    table.itens tbody tr { border-bottom: 1px solid #e5e7eb; }
    table.itens tbody tr:nth-child(even) { background: #f9fafb; }
    table.itens tbody td { padding: 5px 5px; font-size: 10px; vertical-align: top; }
    table.itens tbody td.r { text-align: right; }
    .kit-detalhe { font-size: 8.5px; color: #4b5563; margin-top: 3px; padding-left: 6px; line-height: 1.35; }
    .kit-detalhe ul { margin: 2px 0 0 10px; padding: 0; }
    .kit-detalhe li { margin: 0; }
    .variacao-detalhe { font-size: 8.5px; color: #4b5563; margin-top: 2px; }
    table.itens tfoot td { font-size: 10px; padding: 5px 5px; }
    table.itens tfoot td.r { text-align: right; }
    .tag-produto { background: #ede9fe; color: #5b21b6; padding: 1px 5px; border-radius: 6px; font-size: 8.5px; }
    .tag-servico { background: #faf5ff; color: #7c3aed; padding: 1px 5px; border-radius: 6px; font-size: 8.5px; }
    .total-box { float: right; width: 260px; margin-top: 4px; }
    .total-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 10.5px; }
    .total-row.final { border-top: 2px solid #4f46e5; padding-top: 6px; font-size: 12px; font-weight: bold; color: #4f46e5; margin-top: 4px; }
    .obs { background: #f9fafb; border-left: 3px solid #6366f1; padding: 8px 10px; font-size: 10px; color: #374151; line-height: 1.5; margin-top: 4px; white-space: pre-wrap; word-wrap: break-word; }
    .footer { margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 8.5px; color: #9ca3af; line-height: 1.5; }
    .footer .livreos-credit { margin-top: 6px; font-size: 8px; color: #9ca3af; }
    .clearfix::after { content: ''; display: table; clear: both; }
</style>
</head>
<body>
<div class="page">

    <table class="cabecalho-empresa">
        <tr>
            <td class="col-logo">
                <div class="logo-box">
                    <?php if($empresaLogo): ?>
                        <img src="<?php echo e($empresaLogo); ?>" alt="">
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div class="emitente-nome"><?php echo e($empresaNome); ?></div>
                <?php if($empresaRazao): ?>
                    <p class="emitente-linha"><?php echo e($empresaRazao); ?></p>
                <?php endif; ?>
                <?php if($empresaCnpj): ?>
                    <p class="emitente-linha"><strong>CNPJ:</strong> <?php echo e($empresaCnpj); ?></p>
                <?php endif; ?>
                <?php if($empresa?->inscricao_estadual): ?>
                    <p class="emitente-linha"><strong>IE:</strong> <?php echo e($empresa->inscricao_estadual); ?></p>
                <?php endif; ?>
                <?php if($enderecoEmpresa): ?>
                    <p class="emitente-linha"><?php echo e($enderecoEmpresa); ?></p>
                <?php endif; ?>
                <?php if($empresa?->cep): ?>
                    <p class="emitente-linha"><strong>CEP:</strong> <?php echo e($empresa->cep); ?></p>
                <?php endif; ?>
            </td>
            <td class="col-contato">
                <?php if($empresaTelefone): ?>
                    <p class="contato-linha"><strong>Tel.</strong> <?php echo e($empresaTelefone); ?></p>
                <?php endif; ?>
                <?php if($empresaCelular): ?>
                    <p class="contato-linha"><strong>Cel.</strong> <?php echo e($empresaCelular); ?></p>
                <?php endif; ?>
                <?php if($empresaEmail): ?>
                    <p class="contato-linha"><?php echo e($empresaEmail); ?></p>
                <?php endif; ?>
                <?php if($empresaSite): ?>
                    <p class="contato-linha"><?php echo e($empresaSite); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="faixa-doc">
        <tr>
            <td width="58%">
                <div class="titulo-doc">Pedido de Venda</div>
            </td>
            <td width="42%" style="text-align:right">
                <div class="num-pedido"><?php echo e($pedido->numero_sequencial); ?></div>
                <div><span class="status-pedido"><?php echo e(ucfirst($pedido->status)); ?></span></div>
            </td>
        </tr>
    </table>

    <div class="section-title">Dados do Pedido</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Cliente</div>
            <div class="info-value"><?php echo e($pedido->cliente?->nome ?? '—'); ?></div>
        </div>
        <?php if(in_array($pedido->status, ['faturado', 'entregue'], true)): ?>
        <div class="info-row">
            <div class="info-label">Faturado em</div>
            <div class="info-value">
                <?php if($pedido->data_faturamento): ?>
                    <?php echo e($pedido->data_faturamento->format('d/m/Y H:i')); ?>

                <?php else: ?>
                    <?php echo e($pedido->data_pedido?->format('d/m/Y') ?? '—'); ?>

                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="info-row">
            <div class="info-label">Data do orçamento / proposta</div>
            <div class="info-value"><?php echo e($pedido->data_pedido?->format('d/m/Y') ?? '—'); ?></div>
        </div>
        <?php endif; ?>
        <?php if($pedido->data_prevista_entrega): ?>
        <div class="info-row">
            <div class="info-label">Entrega Prevista</div>
            <div class="info-value"><?php echo e($pedido->data_prevista_entrega->format('d/m/Y')); ?></div>
        </div>
        <?php endif; ?>
        <?php
            $vdPdf = $pedido->validadeOrcamentoDiasEfetiva();
            $exibirValidadePdf = $vdPdf && ! in_array($pedido->status, ['faturado', 'entregue', 'cancelado'], true);
        ?>
        <?php if($exibirValidadePdf): ?>
        <div class="info-row">
            <div class="info-label">Validade do orçamento</div>
            <div class="info-value"><?php echo e($vdPdf); ?> dia(s) corrido(s) a partir da data do orçamento/pedido</div>
        </div>
        <?php endif; ?>
        <?php if($pedido->vendedor): ?>
        <div class="info-row">
            <div class="info-label">Vendedor</div>
            <div class="info-value"><?php echo e($pedido->vendedor->name); ?></div>
        </div>
        <?php endif; ?>
        <?php if($pedido->canal_venda): ?>
        <div class="info-row">
            <div class="info-label">Canal</div>
            <div class="info-value"><?php echo e(ucfirst($pedido->canal_venda)); ?></div>
        </div>
        <?php endif; ?>
    </div>

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
            <?php $__currentLoopData = $pedido->itens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($i+1); ?></td>
                <td>
                    <span class="<?php echo e($item->tipo === 'servico' ? 'tag-servico' : 'tag-produto'); ?>">
                        <?php echo e($item->tipo === 'servico' ? 'Serviço' : 'Produto'); ?>

                    </span>
                </td>
                <td>
                    <?php echo e($item->descricao); ?>

                    <?php if($item->tipo === 'produto' && $item->produto): ?>
                        <?php
                            $prodLinha = $item->produto;
                            if (! $prodLinha->relationLoaded('composicoes')) {
                                $prodLinha->load('composicoes.componente');
                            }
                            $compsLinha = $prodLinha->composicoes ?? collect();
                        ?>
                        <?php if(($prodLinha->formato ?? '') === 'composicao' && $compsLinha->isNotEmpty()): ?>
                            <div class="kit-detalhe">
                                <strong>Composição do kit:</strong>
                                <ul>
                                    <?php $__currentLoopData = $compsLinha; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($c->componente?->nome ?? '—'); ?> — qtd. <?php echo e(format_quantity($c->quantidade ?? 1)); ?> (por kit)</li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if($item->variacao): ?>
                        <?php
                            $v = $item->variacao;
                            $op = is_array($v->opcoes_valores ?? null) ? implode(', ', $v->opcoes_valores) : (string) ($v->opcoes_valores ?? '');
                        ?>
                        <div class="variacao-detalhe">
                            <strong>Variação:</strong> <?php echo e(trim(($v->referencia_sku ?? '') . ($op !== '' ? ' (' . $op . ')' : ''))); ?>

                        </div>
                    <?php endif; ?>
                </td>
                <td class="r"><?php echo e(format_quantidade_br($item->quantidade)); ?></td>
                <td class="r">R$ <?php echo e(format_br_decimal($item->preco_unitario)); ?></td>
                <td class="r"><?php echo e($item->desconto > 0 ? '- R$ ' . format_br_decimal($item->desconto) : '—'); ?></td>
                <td class="r">R$ <?php echo e(format_br_decimal($item->total)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="r">Subtotal Produtos</td>
                <td class="r">R$ <?php echo e(format_br_decimal($pedido->total_produtos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r">Subtotal Serviços</td>
                <td class="r">R$ <?php echo e(format_br_decimal($pedido->total_servicos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="color:#dc2626">Descontos</td>
                <td class="r" style="color:#dc2626">- R$ <?php echo e(format_br_decimal($pedido->total_descontos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">Total Geral</td>
                <td class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">R$ <?php echo e(format_br_decimal($pedido->total_geral)); ?></td>
            </tr>
        </tfoot>
    </table>

    <?php
        $observacoesPdf = '';
        if (! empty($pedido->observacoes)) {
            $rawObs = (string) $pedido->observacoes;
            $rawObs = html_entity_decode($rawObs, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $rawObs = preg_replace('#<br\s*/?>#i', "\n", $rawObs);
            $rawObs = preg_replace('#</p>\s*#i', "\n\n", $rawObs);
            $rawObs = preg_replace('#</div>\s*#i', "\n", $rawObs);
            $rawObs = strip_tags($rawObs);
            $rawObs = preg_replace('/[ \t]+/u', ' ', $rawObs);
            $observacoesPdf = trim(preg_replace("/\n{3,}/u", "\n\n", $rawObs));
        }
    ?>
    <?php if($observacoesPdf !== ''): ?>
    <div class="section-title">Observações</div>
    <div class="obs"><?php echo e($observacoesPdf); ?></div>
    <?php endif; ?>

    <div class="footer">
        Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> · <?php echo e($pedido->numero_sequencial); ?>

        <?php if(in_array($pedido->status, ['faturado', 'entregue'], true)): ?>
        <div class="footer-doc-aviso">Documento não fiscal · Conferir condições comerciais no sistema.</div>
        <?php endif; ?>
        <div class="livreos-credit">
            LivreOS — <?php echo e(config('app.tagline', 'ERP Open Source Livre')); ?>

        </div>
    </div>

</div>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pedidos-venda\pdf.blade.php ENDPATH**/ ?>