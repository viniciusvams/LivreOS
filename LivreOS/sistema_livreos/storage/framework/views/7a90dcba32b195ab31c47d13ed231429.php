

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Movimentações - <?php echo e($dataInicio); ?> a <?php echo e($dataFim); ?></title>
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
    <h1>Movimentações Financeiras</h1>
    <p class="subtitle">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Conta</th>
                <th>Tipo</th>
                <th class="text-right">Valor</th>
                <th>Conciliado</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $movimentacoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($m->data_movimentacao ? $m->data_movimentacao->format('d/m/Y') : '—'); ?></td>
                <td><?php echo e($m->descricao); ?></td>
                <td><?php echo e($m->contaBancaria->nome ?? '—'); ?></td>
                <td><?php echo e($m->tipo === 'entrada' ? 'Entrada' : 'Saída'); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($m->valor, 2, ',', '.')); ?></td>
                <td><?php echo e($m->conciliado ? 'Sim' : 'Não'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — <?php echo e($movimentacoes->count()); ?> registro(s)</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\movimentacoes.blade.php ENDPATH**/ ?>