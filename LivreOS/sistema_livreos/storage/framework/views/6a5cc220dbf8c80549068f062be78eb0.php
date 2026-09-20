



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Centro de Custo</h1>
    <p class="mt-1 text-gray-600 dark:text-gray-400">Preencha os dados do centro de custo.</p>
</div>

<form action="<?php echo e(route('financeiro.centros-custo.store')); ?>" method="POST" class="max-w-2xl space-y-6">
    <?php echo csrf_field(); ?>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="codigo" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" id="codigo" name="codigo" value="<?php echo e(old('codigo')); ?>" maxlength="30" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
            <div>
                <label for="ordem" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem</label>
                <input type="number" id="ordem" name="ordem" value="<?php echo e(old('ordem', 0)); ?>" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
        <div class="mt-4">
            <label for="nome" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
            <input type="text" id="nome" name="nome" value="<?php echo e(old('nome')); ?>" required maxlength="200" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white <?php $__errorArgs = ['nome'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['nome'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mt-4">
            <label for="descricao" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
            <textarea id="descricao" name="descricao" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"><?php echo e(old('descricao')); ?></textarea>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <input type="checkbox" id="ativo" name="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300">
            <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">Salvar</button>
        <a href="<?php echo e(route('financeiro.centros-custo.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Cancelar</a>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\centros-custo\create.blade.php ENDPATH**/ ?>