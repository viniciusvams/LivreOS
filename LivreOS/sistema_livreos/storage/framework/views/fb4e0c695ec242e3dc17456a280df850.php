



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Gerenciar Permissões</h1>
        <p class="text-gray-600 dark:text-gray-400">Lista de todas as permissões do sistema</p>
    </div>
    <a href="<?php echo e(route('admin.permissions.create')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Nova Permissão
    </a>
</div>

<?php if(session('success')): ?>
<div class="mb-6 rounded-lg border border-success-200 bg-success-50 p-4 text-success-700 dark:border-success-800 dark:bg-success-500/10 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Nome</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Slug</th>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</th>
                    <th class="px-6 py-4 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($permission->name); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e($permission->slug); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e($permission->description ?? '-'); ?></td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?php echo e(route('admin.permissions.edit', $permission)); ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Editar</a>
                            <button type="button" onclick="abrirModalExcluirMotivo('<?php echo e(route('admin.permissions.destroy', $permission)); ?>')" class="rounded-lg border border-error-300 bg-white px-3 py-1.5 text-sm font-medium text-error-700 hover:bg-error-50 dark:border-error-700 dark:bg-gray-800 dark:text-error-400 dark:hover:bg-error-500/10">Deletar</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma permissão encontrada</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        <?php echo e($permissions->links()); ?>

    </div>
</div>

<?php if (isset($component)) { $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-excluir-motivo','data' => ['action' => '','titulo' => 'Excluir permissão','descricao' => 'Tem certeza que deseja excluir esta permissão? Informe o motivo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal-excluir-motivo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => '','titulo' => 'Excluir permissão','descricao' => 'Tem certeza que deseja excluir esta permissão? Informe o motivo.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $attributes = $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $component = $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\admin\permissions\index.blade.php ENDPATH**/ ?>