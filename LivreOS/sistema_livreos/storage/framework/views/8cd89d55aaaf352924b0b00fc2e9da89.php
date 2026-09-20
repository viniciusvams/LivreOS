



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Centros de Custo</h1>
        <p class="text-gray-600 dark:text-gray-400">Classifique receitas e despesas por centro de custo para relatórios e DRE.</p>
    </div>
    <a href="<?php echo e(route('financeiro.centros-custo.create')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Centro de Custo
    </a>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ordem</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $centros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $centro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($centro->codigo ?? '—'); ?></td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($centro->nome); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e(Str::limit($centro->descricao, 50) ?: '—'); ?></td>
                    <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300"><?php echo e($centro->ordem); ?></td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($centro->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                            <?php echo e($centro->ativo ? 'Ativo' : 'Inativo'); ?>

                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="<?php echo e(route('financeiro.centros-custo.edit', $centro)); ?>" class="text-brand-600 hover:text-brand-800 dark:text-brand-400" title="Editar">Editar</a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum centro de custo cadastrado.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700"><?php echo e($centros->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\centros-custo\index.blade.php ENDPATH**/ ?>