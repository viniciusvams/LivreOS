


<?php if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))): ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
<?php else: ?>
    <?php
        $viteCss = glob(public_path('build/assets/app*.css')) ?: [];
        $viteJs = glob(public_path('build/assets/app*.js')) ?: [];
    ?>
    <?php $__currentLoopData = $viteCss; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $abs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <link rel="stylesheet" href="<?php echo e(asset('build/assets/'.basename($abs))); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php $__currentLoopData = $viteJs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $abs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <script type="module" src="<?php echo e(asset('build/assets/'.basename($abs))); ?>"></script>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php if($viteCss === [] && $viteJs === []): ?>
        <style>
            body { font-family: system-ui, sans-serif; margin: 0; }
            .livreos-vite-missing { background: #fef3c7; color: #92400e; padding: 12px 16px; text-align: center; font-size: 14px; border-bottom: 1px solid #f59e0b; }
        </style>
        <div class="livreos-vite-missing" role="alert">
            Assets de interface não encontrados (falta <code>public/build</code> com <code>manifest.json</code>).
            No servidor de desenvolvimento rode <code>npm run build</code> e envie a pasta <code>build</code> para a pasta pública do site.
            Caminho verificado: <?php echo e(public_path('build/manifest.json')); ?>

        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\partials\vite-assets.blade.php ENDPATH**/ ?>