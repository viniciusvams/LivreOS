



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Cliente</h1>
    <p class="text-gray-600 dark:text-gray-400">Preencha os dados abaixo para cadastrar um novo cliente</p>
</div>

<?php
    $categorias = \App\Models\CategoriaDocumento::where('ativo', true)->orderBy('nome')->get();
?>

<?php echo $__env->make('clientes._form', ['categorias' => $categorias], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\clientes\create.blade.php ENDPATH**/ ?>