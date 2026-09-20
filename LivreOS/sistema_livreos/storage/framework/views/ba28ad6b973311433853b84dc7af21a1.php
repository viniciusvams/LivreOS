

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fluxo de Caixa - <?php echo e($dataInicio); ?> a <?php echo e($dataFim); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
        .negativo { background-color: #fecaca !important; }
    </style>
</head>
<body>
    <h1>Fluxo de Caixa</h1>
    <p class="subtitle">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?> | Saldo inicial consolidado: R$ <?php echo e(number_format($saldoInicialConsolidado ?? 0, 2, ',', '.')); ?></p>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th class="text-right">Receita Prevista</th>
                <th class="text-right">Receita Realizada</th>
                <th class="text-right">Despesa Prevista</th>
                <th class="text-right">Despesa Realizada</th>
                <th class="text-right">Saldo do Dia</th>
                <th class="text-right">Saldo Acumulado</th>
                <th class="text-right">Saldo Projetado</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $dias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dia): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="<?php echo e(($dia['saldo_projetado_negativo'] ?? false) ? 'negativo' : ''); ?>">
                <td><?php echo e($dia['data_formatada']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['receita_prevista'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['receita_realizada'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['despesa_prevista'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['despesa_realizada'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['saldo_realizado'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['saldo_acumulado'], 2, ',', '.')); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($dia['saldo_projetado'] ?? 0, 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <p class="footer">Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?> — Relatório Financeiro</p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\fluxo-caixa.blade.php ENDPATH**/ ?>