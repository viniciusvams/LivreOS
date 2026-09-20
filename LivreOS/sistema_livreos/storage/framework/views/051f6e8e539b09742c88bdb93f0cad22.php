

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Razão - <?php echo e($planoConta->codigo ?? ''); ?> <?php echo e($planoConta->nome ?? ''); ?></title>
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
    <h1>Razão por conta</h1>
    <p class="subtitle">Conta: <?php echo e($planoConta->codigo ?? ''); ?> - <?php echo e($planoConta->nome ?? ''); ?> | Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th class="text-right">Entrada</th>
                <th class="text-right">Saída</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $itens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item['data']); ?></td>
                <td><?php echo e($item['descricao']); ?></td>
                <td class="text-right"><?php echo e($item['entrada'] > 0 ? 'R$ ' . number_format($item['entrada'], 2, ',', '.') : '-'); ?></td>
                <td class="text-right"><?php echo e($item['saida'] > 0 ? 'R$ ' . number_format($item['saida'], 2, ',', '.') : '-'); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($item['saldo'] ?? 0, 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\razao.blade.php ENDPATH**/ ?>