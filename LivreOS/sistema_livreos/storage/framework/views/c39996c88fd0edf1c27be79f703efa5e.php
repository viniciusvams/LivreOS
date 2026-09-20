

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vendas por Vendedor</title>
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
    <h1>Vendas por Vendedor</h1>
    <p>Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?></p>
    <p>Inclui recebimentos de OS (rateio por técnico) e de pedidos de venda (vendedor do pedido), por data de recebimento.</p>
    <p>Total: R$ <?php echo e(number_format($totalGeral, 2, ',', '.')); ?></p>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Vendedor</th>
                <th class="text-right">Valor</th>
                <th class="text-right">Títulos</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $porVendedor ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['vendedor_nome']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($row['valor'], 2, ',', '.')); ?></td>
                <td class="text-right"><?php echo e($row['quantidade']); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\vendas-vendedor.blade.php ENDPATH**/ ?>