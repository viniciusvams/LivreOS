

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receitas x Despesas - <?php echo e($dataInicio); ?> a <?php echo e($dataFim); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 4px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .valor-pos { color: #0d9488; }
        .valor-neg { color: #dc2626; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Receitas x Despesas por Mês</h1>
    <p class="subtitle">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?><?php if(!empty($centroCusto)): ?> | Centro de custo: <?php echo e($centroCusto->nome); ?><?php endif; ?></p>

    <table>
        <thead>
            <tr>
                <th>Mês</th>
                <th class="text-right">Receitas</th>
                <th class="text-right">Despesas</th>
                <th class="text-right">Resultado</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $meses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['mes_nome']); ?></td>
                <td class="text-right valor-pos">R$ <?php echo e(number_format($row['receitas'], 2, ',', '.')); ?></td>
                <td class="text-right valor-neg">R$ <?php echo e(number_format($row['despesas'], 2, ',', '.')); ?></td>
                <td class="text-right <?php echo e($row['resultado'] >= 0 ? 'valor-pos' : 'valor-neg'); ?>">R$ <?php echo e(number_format($row['resultado'], 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\receitas-despesas.blade.php ENDPATH**/ ?>