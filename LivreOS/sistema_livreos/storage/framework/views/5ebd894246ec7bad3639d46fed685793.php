

<?php
    $isEdit = isset($fornecedor) && $fornecedor->exists;
    $fornecedor = $isEdit ? $fornecedor : new \App\Models\Contato();
?>

<form action="<?php echo e($isEdit ? route('fornecedores.update', $fornecedor) : route('fornecedores.store')); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                <input type="text" name="nome" required value="<?php echo e(old('nome', $fornecedor->nome)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <?php echo $__env->make('partials.cliente-autocomplete-field', [
                    'prefix' => 'fornecedor',
                    'clientes' => $clientes,
                    'selectedId' => old('cliente_id', $fornecedor->cliente_id),
                    'required' => false,
                    'label' => 'Cliente (opcional)',
                    'labelClass' => 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300',
                    'inputClass' => 'mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo</label>
                <input type="text" name="cargo" value="<?php echo e(old('cargo', $fornecedor->cargo)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone</label>
                <input type="text" name="telefone" value="<?php echo e(old('telefone', $fornecedor->telefone)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone 2</label>
                <input type="text" name="telefone2" value="<?php echo e(old('telefone2', $fornecedor->telefone2)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="<?php echo e(old('email', $fornecedor->email)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email 2</label>
                <input type="email" name="email2" value="<?php echo e(old('email2', $fornecedor->email2)); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>
    </div>

    <?php if(function_exists('do_action')): ?>
    <?php do_action('admin.fornecedores.form.extra', $fornecedor ?? null); ?>
    <?php endif; ?>
    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\fornecedores\_form.blade.php ENDPATH**/ ?>