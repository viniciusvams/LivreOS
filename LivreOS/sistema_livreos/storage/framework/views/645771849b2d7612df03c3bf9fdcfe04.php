



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Cadastro de Tags</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie as tags padrão e personalizadas do sistema</p>
    </div>
    <a href="<?php echo e(route('tags.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Tag</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <?php echo e(session('error')); ?>

</div>
<?php endif; ?>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.before_table', 'tag'); ?>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Pré-visualização</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ativo</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90"><?php echo e($tag->nome); ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($tag->tipo === 'padrao' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                            <?php echo e(ucfirst($tag->tipo)); ?>

                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($tag->descricao ?? '-'); ?></td>
                    <td class="px-4 py-3">
                        <button type="button" class="rounded px-3 py-1 text-sm font-medium" style="background-color: <?php echo e($tag->cor_fundo); ?>; color: <?php echo e($tag->cor_fonte); ?>;">
                            <?php echo e($tag->nome); ?>

                        </button>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($tag->ativo ? 'Sim' : 'Não'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <?php if($tag->tipo !== 'padrao'): ?>
                            <a href="<?php echo e(route('tags.edit', $tag)); ?>" class="text-brand-500 hover:underline">Editar</a>
                            <form action="<?php echo e(route('tags.destroy', $tag)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta tag?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-error-500 hover:underline">Excluir</button>
                            </form>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">Não editável</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhuma tag encontrada</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4">
        <?php echo e($tags->links()); ?>

    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.after_table', 'tag'); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\tags\index.blade.php ENDPATH**/ ?>