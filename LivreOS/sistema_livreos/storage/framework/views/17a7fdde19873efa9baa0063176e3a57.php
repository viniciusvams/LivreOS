

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Curva ABC de Clientes</title>
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
    <h1>Curva ABC de Clientes</h1>
    <p>Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?></p>
    <p>Total recebido: R$ <?php echo e(number_format($totalGeral, 2, ',', '.')); ?></p>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th class="text-right">Receita</th>
                <th class="text-right">% Acum.</th>
                <th>Classe</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $lista ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="classe-<?php echo e(strtolower($item['classe'] ?? 'c')); ?>">
                <td><?php echo e($i + 1); ?></td>
                <td><?php echo e($item['nome']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($item['total'], 2, ',', '.')); ?></td>
                <td class="text-right"><?php echo e(number_format($item['acumulado_pct'] ?? 0, 1, ',', '.')); ?>%</td>
                <td><?php echo e($item['classe'] ?? 'C'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\curva-abc-clientes.blade.php ENDPATH**/ ?>