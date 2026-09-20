



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Status</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre um novo status para ordens de serviço</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('configuracoes-sistema.status-os.store')); ?>" method="POST" class="p-6">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="<?php echo e(old('nome')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Em execução">
                <?php $__errorArgs = ['nome'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor (hex)</label>
                <div class="flex gap-2">
                    <input type="color" name="cor_picker" value="<?php echo e(old('cor', '#6b7280')); ?>" class="h-10 w-14 cursor-pointer rounded border border-gray-300 dark:border-gray-600" onchange="this.nextElementSibling.value=this.value">
                    <input type="text" name="cor" value="<?php echo e(old('cor', '#6b7280')); ?>" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="#6b7280" maxlength="20">
                </div>
                <?php $__errorArgs = ['cor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de exibição</label>
                <input type="number" name="ordem" value="<?php echo e(old('ordem', 0)); ?>" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__errorArgs = ['ordem'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Menor valor aparece primeiro</p>
            </div>

            <div class="md:col-span-2 space-y-3">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Comportamento ao selecionar</label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marca_inicio" value="1" <?php echo e(old('marca_inicio') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Marcar início da execução (preenche data/hora de início)</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="marca_conclusao" value="1" <?php echo e(old('marca_conclusao') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Marcar conclusão (preenche data de conclusão e aciona baixa de estoque/financeiro)</span>
                </label>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Status ativo</span>
                </label>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('configuracoes.status-os.form.extra', null); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('configuracoes-sistema.status-os.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<div class="mt-4">
    <a href="<?php echo e(route('configuracoes-sistema.status-os.index')); ?>" class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">← Voltar para Status</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\status-os\create.blade.php ENDPATH**/ ?>