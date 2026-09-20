

<?php
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
?>
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
                <?php if($empresaRazao): ?><p class="emitente-linha"><?php echo e($empresaRazao); ?></p><?php endif; ?>
                <?php if($empresaCnpj): ?><p class="emitente-linha"><strong>CNPJ:</strong> <?php echo e($empresaCnpj); ?></p><?php endif; ?>
                <?php if($enderecoEmpresa): ?><p class="emitente-linha"><?php echo e($enderecoEmpresa); ?></p><?php endif; ?>
            </td>
            <td class="col-contato">
                <?php if($empresaTelefone): ?><p class="contato-linha"><strong>Tel.</strong> <?php echo e($empresaTelefone); ?></p><?php endif; ?>
                <?php if($empresaCelular): ?><p class="contato-linha"><strong>Cel.</strong> <?php echo e($empresaCelular); ?></p><?php endif; ?>
                <?php if($empresaEmail): ?><p class="contato-linha"><?php echo e($empresaEmail); ?></p><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="faixa-doc">
        <tr>
            <td width="58%">
                <div class="titulo-doc">Proposta comercial</div>
                <?php if($proposta->titulo): ?><p style="font-size:10px;color:#6b7280;margin-top:4px"><?php echo e($proposta->titulo); ?></p><?php endif; ?>
            </td>
            <td width="42%">
                <div class="num-doc"><?php echo e($proposta->referenciaGrupo()); ?></div>
                <p style="text-align:right;font-size:9px;color:#6b7280;margin-top:4px"><?php echo e($proposta->status_label); ?></p>
            </td>
        </tr>
    </table>

    <div class="section-title">Dados</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Cliente</div>
            <div class="info-value"><?php echo e($proposta->cliente?->nome ?? '—'); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Emissão</div>
            <div class="info-value"><?php echo e($proposta->data_emissao?->format('d/m/Y') ?? '—'); ?></div>
        </div>
        <?php if($proposta->validade_em): ?>
        <div class="info-row">
            <div class="info-label">Válido até</div>
            <div class="info-value"><?php echo e($proposta->validade_em->format('d/m/Y')); ?></div>
        </div>
        <?php endif; ?>
        <?php if($proposta->vendedor): ?>
        <div class="info-row">
            <div class="info-label">Vendedor</div>
            <div class="info-value"><?php echo e($proposta->vendedor->name); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if(filled($proposta->descricao)): ?>
    <div class="section-title">Descrição geral</div>
    <div class="descricao-geral"><?php echo $proposta->descricao; ?></div>
    <?php endif; ?>

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
            <?php $__currentLoopData = $proposta->itens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($i + 1); ?></td>
                <td>
                    <span class="<?php echo e($item->tipo === 'servico' ? 'tag-servico' : 'tag-produto'); ?>">
                        <?php echo e($item->tipo === 'servico' ? 'Serviço' : 'Produto'); ?>

                    </span>
                </td>
                <td><?php echo e($item->descricao); ?></td>
                <td class="r"><?php echo e(format_quantidade_br($item->quantidade)); ?></td>
                <td class="r">R$ <?php echo e(format_br_decimal($item->preco_unitario)); ?></td>
                <td class="r"><?php echo e($item->desconto > 0 ? '- R$ '.format_br_decimal($item->desconto) : '—'); ?></td>
                <td class="r">R$ <?php echo e(format_br_decimal($item->total)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="r">Subtotal produtos</td>
                <td class="r">R$ <?php echo e(format_br_decimal($proposta->total_produtos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r">Subtotal serviços</td>
                <td class="r">R$ <?php echo e(format_br_decimal($proposta->total_servicos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="color:#dc2626">Descontos</td>
                <td class="r" style="color:#dc2626">- R$ <?php echo e(format_br_decimal($proposta->total_descontos)); ?></td>
            </tr>
            <tr>
                <td colspan="6" class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">Total geral</td>
                <td class="r" style="font-weight:bold;font-size:11px;color:#4f46e5">R$ <?php echo e(format_br_decimal($proposta->total_geral)); ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if(filled($proposta->observacoes)): ?>
    <div class="section-title">Observações</div>
    <div class="obs"><?php echo $proposta->observacoes; ?></div>
    <?php endif; ?>

    <div class="footer">
        Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> · Proposta #<?php echo e($proposta->id); ?> v<?php echo e($proposta->versao); ?> · LivreOS — ERP Open Source Livre
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\propostas-comerciais\partials\_proposta-padrao-corpo.blade.php ENDPATH**/ ?>