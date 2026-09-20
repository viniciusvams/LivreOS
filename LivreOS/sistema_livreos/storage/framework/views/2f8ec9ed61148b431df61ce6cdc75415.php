

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Inadimplência - Aging de Cobrança</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        .faixa { margin-bottom: 14px; }
        .faixa-titulo { font-weight: bold; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Inadimplência (Aging de Cobrança)</h1>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <?php $__currentLoopData = $faixas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faixa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(count($faixa['itens'] ?? []) > 0): ?>
    <div class="faixa">
        <div class="faixa-titulo"><?php echo e($faixa['label']); ?> — Total: R$ <?php echo e(number_format($faixa['total'], 2, ',', '.')); ?></div>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Vencimento</th>
                    <th>Dias</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Juros sug.</th>
                    <th class="text-right">Multa sug.</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $faixa['itens']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($item['cliente']); ?></td>
                    <td><?php echo e($item['vencimento']); ?></td>
                    <td><?php echo e($item['dias']); ?></td>
                    <td class="text-right">R$ <?php echo e(number_format($item['valor'], 2, ',', '.')); ?></td>
                    <td class="text-right">R$ <?php echo e(number_format($item['juros_sugerido'] ?? 0, 2, ',', '.')); ?></td>
                    <td class="text-right">R$ <?php echo e(number_format($item['multa_sugerido'] ?? 0, 2, ',', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <p class="footer">Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\inadimplencia.blade.php ENDPATH**/ ?>