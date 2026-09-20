



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Termo de Garantia</h1>
    <p class="text-gray-600 dark:text-gray-400">Atualize as informações do termo de garantia</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('configuracoes-sistema.termos-garantia.update', $termo)); ?>" method="POST" class="p-6">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="<?php echo e(old('nome', $termo->nome)); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Termo de Garantia</label>
                <textarea name="termo" id="termo_garantia_editor" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" rows="15"><?php echo e(old('termo', $termo->termo)); ?></textarea>
                <?php $__errorArgs = ['termo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Use o editor para formatar o texto do termo de garantia</p>
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', $termo->ativo) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Termo ativo</span>
                </label>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('configuracoes.termos-garantia.form.extra', $termo); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('configuracoes-sistema.termos-garantia.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<?php echo $__env->make('partials.jodit-assets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = Jodit.make('#termo_garantia_editor', {
        height: 400,
        language: 'pt_br',
        toolbar: true,
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough',
            '|',
            'ul', 'ol',
            '|',
            'outdent', 'indent',
            '|',
            'font', 'fontsize', 'brush', 'paragraph',
            '|',
            'image', 'link', 'table',
            '|',
            'align', 'undo', 'redo',
            '|',
            'hr', 'eraser', 'copyformat',
            '|',
            'fullsize', 'selectall', 'print', 'about'
        ],
        uploader: {
            insertImageAsBase64URI: true
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\termos-garantia\edit.blade.php ENDPATH**/ ?>