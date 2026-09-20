

<?php
    $empresa = \App\Models\Empresa::first();
    $empresaNome = $empresa?->nome ?? config('app.name', 'ERP');
    $pdfFooterTexto = config('app.name') . ' - ' . config('app.tagline', 'ERP Open Source Livre');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Clientes</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; padding: 0; background: #fff; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .subtitulo { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
        .pdf-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1><?php echo e($empresaNome); ?></h1>
    <p class="subtitulo">Cadastro de Clientes — Gerado em <?php echo e(now()->format('d/m/Y H:i')); ?></p>

    <?php if($clientes->isEmpty()): ?>
        <p>Nenhum cliente encontrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Razão Social</th>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Cidade/UF</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $endereco = $c->enderecos->first(); $cidadeUf = $endereco ? ($endereco->cidade ?? '') . '/' . ($endereco->estado ?? '') : '—'; ?>
                <tr>
                    <td><?php echo e($c->nome); ?></td>
                    <td><?php echo e($c->razao_social ?? '—'); ?></td>
                    <td><?php echo e($c->tipo_pessoa_texto); ?></td>
                    <td><?php echo e($c->documento_principal ?? '—'); ?></td>
                    <td><?php echo e($cidadeUf); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <p class="subtitulo" style="margin-top: 10px;">Total: <?php echo e($clientes->count()); ?> clientes</p>
    <?php endif; ?>
    <p class="pdf-footer"><?php echo e($pdfFooterTexto); ?></p>
</body>
</html>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\clientes\pdf.blade.php ENDPATH**/ ?>