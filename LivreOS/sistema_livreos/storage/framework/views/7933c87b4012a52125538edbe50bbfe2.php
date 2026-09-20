



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Status de Ordem de Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">Crie e personalize os status disponíveis para as ordens de serviço.</p>
    </div>
    <a href="<?php echo e(route('configuracoes-sistema.status-os.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <span class="flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Status
        </span>
    </a>
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

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <?php if($statusList->count() > 0): ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ordem</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Cor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Comportamento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                <?php $__currentLoopData = $statusList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?php echo e($s->ordem); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <?php if($s->cor): ?>
                            <span class="inline-block h-4 w-4 rounded-full border border-gray-300" style="background-color: <?php echo e($s->cor); ?>"></span>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($s->nome); ?></span>
                            <?php if($s->sistema): ?>
                            <span class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400">sistema</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?php echo e($s->cor ?? '-'); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                        <?php if($s->marca_inicio): ?> Marca início <?php endif; ?>
                        <?php if($s->marca_conclusao): ?> <?php if($s->marca_inicio): ?> | <?php endif; ?> Marca conclusão <?php endif; ?>
                        <?php if(!$s->marca_inicio && !$s->marca_conclusao): ?> - <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if($s->ativo): ?>
                            <span class="inline-flex rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">Ativo</span>
                        <?php else: ?>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('configuracoes-sistema.status-os.edit', $s)); ?>" class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300">Editar</a>
                            <?php if(!$s->sistema): ?>
                            <form action="<?php echo e(route('configuracoes-sistema.status-os.destroy', $s)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este status?');" class="inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-error-600 hover:text-error-900 dark:text-error-400 dark:hover:text-error-300">Excluir</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
        <?php echo e($statusList->links()); ?>

    </div>
    <?php else: ?>
    <div class="p-8 text-center">
        <p class="text-gray-500 dark:text-gray-400">Nenhum status cadastrado. Execute o seeder ou crie o primeiro status.</p>
        <a href="<?php echo e(route('configuracoes-sistema.status-os.create')); ?>" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Criar primeiro status
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">← Voltar para Configurações</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\status-os\index.blade.php ENDPATH**/ ?>