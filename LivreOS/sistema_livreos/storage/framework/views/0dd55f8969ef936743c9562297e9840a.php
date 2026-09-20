

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Balancete - <?php echo e($dataBalancete); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Balancete</h1>
    <p class="subtitle">Data: <?php echo e(\Carbon\Carbon::parse($dataBalancete)->format('d/m/Y')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Conta</th>
                <th>Tipo</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $linhas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linha): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($linha['codigo']); ?></td>
                <td><?php echo e($linha['nome']); ?></td>
                <td><?php echo e($linha['tipo']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($linha['saldo'] ?? 0, 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\balancete.blade.php ENDPATH**/ ?>