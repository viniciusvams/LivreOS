

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>MRR - Receita Recorrente Mensal</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Receita Recorrente Mensal (MRR)</h1>
    <p>MRR Total: R$ <?php echo e(number_format($mrrTotal, 2, ',', '.')); ?> — <?php echo e($totalContratos); ?> contrato(s) ativo(s)</p>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="text-right">Valor mensal</th>
                <th class="text-right">Contratos</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $porCliente ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['nome']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($row['valor_mensal'], 2, ',', '.')); ?></td>
                <td class="text-right"><?php echo e($row['quantidade']); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\mrr.blade.php ENDPATH**/ ?>