

<?php
    $empresa = \App\Models\Empresa::first();
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Produtos</title>
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
    <p class="subtitulo">Cadastro de Produtos — Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <?php if($produtos->isEmpty()): ?>
        <p>Nenhum produto encontrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>SKU</th>
                    <th>Formato</th>
                    <th>Unidade</th>
                    <th class="text-right">Preço</th>
                    <th class="text-right">Estoque</th>
                    <th>Depósito</th>
                    <th>Categoria</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($p->nome); ?></td>
                    <td><?php echo e($p->codigo_sku ?? '—'); ?></td>
                    <td><?php echo e(ucfirst($p->formato ?? 'simples')); ?></td>
                    <td><?php echo e($p->unidade ?? '—'); ?></td>
                    <td class="text-right"><?php echo e($p->preco_venda ? number_format($p->preco_venda, 2, ',', '.') : '—'); ?></td>
                    <td class="text-right"><?php echo e($p->estoque_quantidade !== null && $p->estoque_quantidade !== '' ? format_quantity($p->estoque_quantidade) : '—'); ?></td>
                    <td><?php echo e($p->deposito?->nome ?? '—'); ?></td>
                    <td><?php echo e($p->categoria?->nome ?? '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">Total: <?php echo e($produtos->count()); ?> produtos</p>
    <?php endif; ?>
    <p class="pdf-footer"><?php echo e($pdfFooterTexto); ?></p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\produtos\pdf.blade.php ENDPATH**/ ?>