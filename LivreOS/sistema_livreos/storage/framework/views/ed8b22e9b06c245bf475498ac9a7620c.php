



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Produtos e Estoque</h1>
        <p class="text-gray-600 dark:text-gray-400">Configure regras de controle de estoque para vendas e itens na ordem de serviço. O administrador e usuários com permissão específica podem ignorar o bloqueio de estoque zero.</p>
    </div>
    <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-100">Voltar</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<form action="<?php echo e(route('configuracoes-sistema.produtos-estoque.update')); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Controle de estoque na venda</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Quando ativo, o sistema impede incluir na ordem de serviço produtos (do cadastro) que estejam com estoque zero ou com quantidade insuficiente. Aplica-se apenas a produtos com controle de estoque ativo. Usuários com a permissão &quot;Vender com estoque zero&quot; e o administrador não são bloqueados.</p>
        <label class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
            <input type="checkbox" name="estoque_bloquear_venda_zero" value="1" <?php echo e(old('estoque_bloquear_venda_zero', $estoque_bloquear_venda_zero) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
            <span class="font-medium text-gray-800 dark:text-gray-200">Bloquear venda de itens com estoque zero</span>
        </label>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('configuracoes.produtos-estoque.form.extra', $estoque_bloquear_venda_zero ?? false); ?>
        <?php endif; ?>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\produtos_estoque\edit.blade.php ENDPATH**/ ?>