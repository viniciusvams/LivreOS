



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Fornecedores</h1>
        <p class="text-gray-600 dark:text-gray-400">Cadastro de fornecedores</p>
    </div>
    <a href="<?php echo e(route('fornecedores.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo Fornecedor</a>
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
<?php do_action('entity.index.before_table', 'fornecedor'); ?>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Telefone</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Email</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fornecedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90"><?php echo e($fornecedor->nome); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($fornecedor->cliente?->nome ?: '-'); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($fornecedor->telefone ?: '-'); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($fornecedor->email ?: '-'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="<?php echo e(route('fornecedores.edit', $fornecedor)); ?>" class="text-brand-500 hover:underline">Editar</a>
                            <button type="button" onclick="abrirModalExcluirMotivo('<?php echo e(route('fornecedores.destroy', $fornecedor)); ?>')" class="text-error-500 hover:underline">Excluir</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum fornecedor encontrado</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4">
        <?php echo e($fornecedores->links()); ?>

    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.after_table', 'fornecedor'); ?>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-excluir-motivo','data' => ['action' => '','titulo' => 'Excluir fornecedor','descricao' => 'Tem certeza que deseja excluir este fornecedor? Informe o motivo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal-excluir-motivo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => '','titulo' => 'Excluir fornecedor','descricao' => 'Tem certeza que deseja excluir este fornecedor? Informe o motivo.']); ?>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\fornecedores\index.blade.php ENDPATH**/ ?>