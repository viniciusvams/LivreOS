

<?php
    $isEdit = isset($categoria) && $categoria->exists;
    $categoria = $isEdit ? $categoria : new \App\Models\CategoriaProduto();
?>

<form action="<?php echo e($isEdit ? route('categorias-produtos.update', $categoria) : route('categorias-produtos.store')); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                <input type="text" name="nome" required value="<?php echo e(old('nome', $categoria->nome)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', $categoria->ativo) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
        </div>
    </div>

    <?php if(function_exists('do_action')): ?>
    <?php do_action('admin.categorias-produtos.form.extra', $categoria ?? null); ?>
    <?php endif; ?>
    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\categorias_produtos\_form.blade.php ENDPATH**/ ?>