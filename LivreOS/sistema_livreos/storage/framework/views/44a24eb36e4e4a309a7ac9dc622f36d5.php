

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contas a Pagar - <?php echo e($dataInicio); ?> a <?php echo e($dataFim); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 4px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 12px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Contas a Pagar por Período</h1>
    <p class="subtitle">Vencimento: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Fornecedor</th>
                <th>Descrição</th>
                <th>Vencimento</th>
                <th class="text-right">Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $contas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($c->fornecedor->nome ?? $c->fornecedor->razao_social ?? '—'); ?></td>
                <td><?php echo e($c->descricao); ?></td>
                <td><?php echo e($c->data_vencimento ? $c->data_vencimento->format('d/m/Y') : '—'); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($c->valor, 2, ',', '.')); ?></td>
                <td><?php echo e(ucfirst($c->status ?? '')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — <?php echo e($contas->count()); ?> registro(s)</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\contas-pagar.blade.php ENDPATH**/ ?>