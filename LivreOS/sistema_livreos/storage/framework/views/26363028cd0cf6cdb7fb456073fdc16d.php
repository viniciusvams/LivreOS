



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center gap-4">
        <a href="<?php echo e(route('configuracoes-sistema.plugins.show', 'area-cliente')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Voltar ao plugin</a>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Importar dados — Área do Cliente</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use um arquivo .zip ou .json exportado pelo plugin para restaurar tickets, respostas e anexos.</p>
        </div>
        <div class="p-6">
            <?php if(session('success')): ?>
                <div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"><?php echo e(session('error')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                    <ul class="list-inside list-disc">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($e); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('plugin.area-cliente.configuracoes.import.process')); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label for="arquivo" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo (.zip ou .json)</label>
                    <input type="file" name="arquivo" id="arquivo" accept=".zip,.json" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-700">Importar</button>
                    <a href="<?php echo e(route('configuracoes-sistema.plugins.show', 'area-cliente')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\settings-import.blade.php ENDPATH**/ ?>