

<?php
    $empresa = \App\Models\Empresa::first();
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Ordens de Serviço</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .text-right { text-align: right; }
        .pdf-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1><?php echo e($empresaNome); ?></h1>
    <p class="subtitulo">Ordens de Serviço — Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <?php if($ordens->isEmpty()): ?>
        <p>Nenhuma ordem de serviço encontrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Abertura</th>
                    <th>Conclusão</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $ordens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e(format_os_display($o)); ?></td>
                    <td><?php echo e($o->cliente?->nome ?? '—'); ?></td>
                    <td><?php echo e($o->status ?? '—'); ?></td>
                    <td><?php echo e($o->prioridade ?? '—'); ?></td>
                    <td><?php echo e($o->data_abertura ? $o->data_abertura->format('d/m/Y') : '—'); ?></td>
                    <td><?php echo e($o->real_conclusao ? \Carbon\Carbon::parse($o->real_conclusao)->format('d/m/Y') : ($o->prevista_conclusao ? \Carbon\Carbon::parse($o->prevista_conclusao)->format('d/m/Y') : '—')); ?></td>
                    <td class="text-right"><?php echo e($o->total_geral ? number_format($o->total_geral, 2, ',', '.') : '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">Total: <?php echo e($ordens->count()); ?> ordens</p>
    <?php endif; ?>
    <p class="pdf-footer"><?php echo e($pdfFooterTexto); ?></p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ordens_servico\pdf.blade.php ENDPATH**/ ?>