



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Numeração das Ordens de Serviço</h1>
        <p class="text-gray-600 dark:text-gray-400">Configure com segurança qual será o próximo número da OS.</p>
    </div>
    <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
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

<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Última OS criada</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($ultimo_numero > 0 ? $ultimo_numero : 'Nenhuma'); ?></p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo natural</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($proximo_natural); ?></p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Próximo efetivo</p>
        <p class="mt-1 text-2xl font-semibold text-brand-600 dark:text-brand-400"><?php echo e($proximo_efetivo); ?></p>
    </div>
</div>

<form action="<?php echo e(route('configuracoes-sistema.numeracao-os.update')); ?>" method="POST" class="mt-6">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <label for="proximo_numero" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Definir próximo número da OS</label>
        <input
            type="number"
            id="proximo_numero"
            name="proximo_numero"
            min="1"
            step="1"
            value="<?php echo e(old('proximo_numero', $proximo_configurado)); ?>"
            class="w-full max-w-sm rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Se já existe OS, o valor deve ser maior que a última criada. Exemplo: última OS 10, próximo número 1000.
        </p>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                Salvar próxima numeração
            </button>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\numeracao_os.blade.php ENDPATH**/ ?>