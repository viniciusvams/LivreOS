



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Categorias de Serviços</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as categorias de serviços</p>
    </div>
    <a href="<?php echo e(route('categorias-servicos.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Categoria</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Slug</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90"><?php echo e($categoria->nome); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($categoria->slug); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($categoria->ativo ? 'Ativo' : 'Inativo'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            <a href="<?php echo e(route('categorias-servicos.edit', $categoria)); ?>" class="text-brand-500 hover:underline">Editar</a>
                            <form action="<?php echo e(route('categorias-servicos.status', $categoria)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <input type="hidden" name="ativo" value="<?php echo e($categoria->ativo ? 0 : 1); ?>">
                                <button type="submit" class="text-gray-600 hover:underline dark:text-gray-400">
                                    <?php echo e($categoria->ativo ? 'Inativar' : 'Ativar'); ?>

                                </button>
                            </form>
                            <form action="<?php echo e(route('categorias-servicos.destroy', $categoria)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-error-500 hover:underline">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhuma categoria encontrada</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\servicos\categorias\index.blade.php ENDPATH**/ ?>