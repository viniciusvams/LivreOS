

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vendas por Serviço e Produto</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        h2 { font-size: 11px; margin: 12px 0 6px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Vendas por Serviço e Produto</h1>
    <?php $critPdf = $criterio ?? 'operacional'; ?>
    <p>
        Período: <?php echo e(\Carbon\Carbon::parse($dataInicio)->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim)->format('d/m/Y')); ?>

        <?php if(!empty($tecnico_id)): ?>
            · Técnico #<?php echo e($tecnico_id); ?>

        <?php endif; ?>
        <?php if(!empty($sla_filtro)): ?>
            · SLA: <?php echo e($sla_filtro); ?>

        <?php endif; ?>
    </p>
    <?php $incPdf = $incluir_pedidos ?? false; ?>
    <?php if($critPdf === 'operacional'): ?>
    <p>Fonte (sem financeiro): OS fechadas (Encerrada/Concluída/Finalizado etc.) com COALESCE(data conclusão, última alteração) no período.<?php if($incPdf): ?> Inclui pedidos (data do pedido).<?php else: ?> Sem pedidos de venda.<?php endif; ?> Linhas faturáveis; OS com os_gerada_id excluídas da soma por OS.</p>
    <?php else: ?>
    <p>Fonte: recebimentos em contas a receber (pago/parcial, data de recebimento no período), rateados pelas linhas faturáveis do pedido ou OS; linhas sem quantidade/horas na OS ficam fora (orçamento de referência). Adiantamentos de OS excluídos.</p>
    <?php endif; ?>
    <p>Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <h2>Serviços</h2>
    <table>
        <thead>
            <tr>
                <th>Serviço</th>
                <th class="text-right">Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $servicos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['nome']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($row['total'], 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <h2>Produtos</h2>
    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th class="text-right">Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $produtos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['nome']); ?></td>
                <td class="text-right">R$ <?php echo e(number_format($row['total'], 2, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\pdf\vendas-servico-produto.blade.php ENDPATH**/ ?>