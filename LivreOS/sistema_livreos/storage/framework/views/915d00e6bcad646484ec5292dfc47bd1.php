



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <a href="<?php echo e(route('propostas-comerciais.modelos-documento.index')); ?>" class="text-sm text-gray-500 hover:text-brand-600 dark:text-gray-400">← Voltar</a>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($title); ?></h1>
</div>

<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <ul class="list-disc pl-4"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>

<div class="mb-6">
    <?php echo $__env->make('propostas-comerciais.modelos-documento._ajuda-docx', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>

<form method="POST" action="<?php echo e(route('propostas-comerciais.modelos-documento.update', $modelo)); ?>" enctype="multipart/form-data" class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nome <span class="text-red-500">*</span></label>
        <input type="text" name="nome" value="<?php echo e(old('nome', $modelo->nome)); ?>" required maxlength="160" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição</label>
        <textarea name="descricao" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"><?php echo e(old('descricao', $modelo->descricao)); ?></textarea>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Ordem na lista</label>
        <input type="number" name="ordem" value="<?php echo e(old('ordem', $modelo->ordem)); ?>" min="0" max="65535" class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="ativo" id="ativo" value="1" <?php echo e(old('ativo', $modelo->ativo) ? 'checked' : ''); ?> class="rounded border-gray-300">
        <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
    </div>
    <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
        Formato atual:
        <?php if(($modelo->formato ?? 'docx') === 'html'): ?>
        <strong>HTML</strong> (página no browser na proposta)
        <?php else: ?>
        <strong>Word (.docx)</strong> (descarga na proposta)
        <?php endif; ?>
    </p>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Substituir arquivo (.docx, .html ou .htm)</label>
        <input type="file" name="arquivo" accept=".docx,.html,.htm,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/html" class="block w-full text-sm text-gray-600 dark:text-gray-400">
        <p class="mt-1 text-xs text-gray-500">Deixe em branco para manter o ficheiro atual. Ao enviar novo ficheiro, o formato passa a ser o da extensão. Máx. 15 MB.</p>
    </div>
    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        <a href="<?php echo e(route('propostas-comerciais.modelos-documento.index')); ?>" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancelar</a>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\propostas-comerciais\modelos-documento\edit.blade.php ENDPATH**/ ?>