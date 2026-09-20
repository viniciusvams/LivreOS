



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Categorias de Produtos</h1>
        <p class="text-gray-600 dark:text-gray-400">Organize seus produtos por categoria</p>
    </div>
    <a href="<?php echo e(route('categorias-produtos.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Categoria</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <input type="text" name="nome" placeholder="Buscar por nome" value="<?php echo e(request('nome')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
    </form>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.before_table', 'categoria_produto'); ?>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Slug</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90"><?php echo e($categoria->nome); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($categoria->slug); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($categoria->ativo ? 'Sim' : 'Não'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="<?php echo e(route('categorias-produtos.edit', $categoria)); ?>" class="text-brand-500 hover:underline">Editar</a>
                            <form action="<?php echo e(route('categorias-produtos.destroy', $categoria)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
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

    <div class="p-4">
        <?php echo e($categorias->links()); ?>

    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.after_table', 'categoria_produto'); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\categorias_produtos\index.blade.php ENDPATH**/ ?>