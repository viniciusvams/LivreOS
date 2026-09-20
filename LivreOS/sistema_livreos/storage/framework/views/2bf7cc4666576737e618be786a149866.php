



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Detalhes do Modelo de Checklist</h1>
        <p class="text-gray-600 dark:text-gray-400"><?php echo e($checklistModel->nome); ?></p>
    </div>
    <div class="flex gap-3">
        <a href="<?php echo e(route('checklist-models.edit', $checklistModel)); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="<?php echo e(route('checklist-models.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações Básicas</h2>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90"><?php echo e($checklistModel->nome); ?></p>
            </div>
            <?php if($checklistModel->descricao): ?>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90"><?php echo e($checklistModel->descricao); ?></p>
            </div>
            <?php endif; ?>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                <p class="mt-1">
                    <?php if($checklistModel->ativo): ?>
                        <span class="inline-flex rounded-full bg-success-100 px-2 py-1 text-xs font-semibold text-success-800 dark:bg-success-500/20 dark:text-success-400">Ativo</span>
                    <?php else: ?>
                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800 dark:bg-gray-500/20 dark:text-gray-400">Inativo</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Criado em</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white/90"><?php echo e($checklistModel->created_at->format('d/m/Y H:i')); ?></p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Campos do Formulário</h2>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $checklistModel->campos ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $campo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?php echo e($campo['label'] ?? 'Campo ' . ($index + 1)); ?>

                            <?php if($campo['obrigatorio'] ?? false): ?>
                                <span class="text-error-500">*</span>
                            <?php endif; ?>
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <?php echo e(ucfirst($campo['tipo'] ?? 'texto')); ?>

                        </span>
                    </div>
                    <?php if(($campo['tipo'] ?? '') === 'selecao' && !empty($campo['opcoes'])): ?>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Opções: <?php echo e(implode(', ', $campo['opcoes'])); ?>

                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum campo definido.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('admin.checklist-models.show.extra', $checklistModel); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\checklist_models\show.blade.php ENDPATH**/ ?>