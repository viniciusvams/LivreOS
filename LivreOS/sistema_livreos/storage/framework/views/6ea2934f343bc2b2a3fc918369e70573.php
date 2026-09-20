

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 20px; }
        .footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
        p { margin: 0 0 12px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0 0 8px 0; font-size: 20px;">Ordem de Serviço <?php echo e(format_os_display($ordem)); ?></h1>
            <p style="margin: 0; color: #6b7280;">Cliente: <?php echo e($ordem->cliente?->nome ?? '-'); ?></p>
        </div>

        <?php if($mensagem): ?>
            <div style="margin-bottom: 20px;">
                <?php if($mensagemHtml ?? false): ?>
                    <?php echo $mensagem; ?>

                <?php else: ?>
                    <?php echo nl2br(e($mensagem)); ?>

                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Segue em anexo a Ordem de Serviço <?php echo e(format_os_display($ordem)); ?>.</p>
        <?php endif; ?>

        <p><strong>Status:</strong> <?php echo e($ordem->status ?? '-'); ?></p>
        <?php if($ordem->total_geral): ?>
            <p><strong>Valor total:</strong> R$ <?php echo e(number_format($ordem->total_geral, 2, ',', '.')); ?></p>
        <?php endif; ?>

        <div class="footer">
            <p>Este e-mail foi enviado automaticamente pelo <?php echo e(config('app.name')); ?>.</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\emails\ordem-servico.blade.php ENDPATH**/ ?>