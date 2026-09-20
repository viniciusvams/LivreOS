

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Posição de Estoque e Curva ABC</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .classe-a { background: #d4edda; }
        .classe-b { background: #fff3cd; }
        .classe-c { background: #e2e3e5; }
    </style>
</head>
<body>
    <h1>Posição de Estoque e Curva ABC</h1>
    <p>Valor total em estoque: R$ <?php echo e(number_format($valorTotalEstoque, 2, ',', '.')); ?></p>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Código</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Valor total</th>
                <th>ABC</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $lista ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="classe-<?php echo e(strtolower($item['classe_abc'] ?? 'c')); ?>">
                <td><?php echo e($item['nome']); ?></td>
                <td><?php echo e($item['codigo'] ?? '—'); ?></td>
                <td class="text-right"><?php echo e(number_format($item['quantidade'] ?? 0, 0, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($item['valor_total'] ?? 0, 2, ',', '.')); ?></td>
                <td><?php echo e($item['classe_abc'] ?? 'C'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\estoque-posicao.blade.php ENDPATH**/ ?>