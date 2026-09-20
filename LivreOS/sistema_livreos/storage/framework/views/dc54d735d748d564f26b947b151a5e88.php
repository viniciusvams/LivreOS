



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-2xl pb-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <nav class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                <a href="<?php echo e(route('plugin.area-cliente.chamados.index')); ?>" class="hover:text-gray-900 dark:hover:text-white">Tickets</a>
                <span>/</span>
                <span class="text-gray-900 dark:text-white">Novo</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Abrir ticket</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Descreva o problema; você pode anexar fotos ou documentos.</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <form action="<?php echo e(route('plugin.area-cliente.chamados.store')); ?>" method="POST" enctype="multipart/form-data" class="space-y-6" id="form-chamado">
            <?php echo csrf_field(); ?>

            <?php if($errors->any()): ?>
            <div class="rounded-xl bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
                <ul class="list-inside list-disc space-y-0.5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($e); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <?php endif; ?>

            <div>
                <label for="titulo" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Assunto <span class="text-error-500">*</span></label>
                <input type="text" id="titulo" name="titulo" value="<?php echo e(old('titulo')); ?>" required maxlength="255" placeholder="Ex: Câmera 03 fora do ar"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 <?php $__errorArgs = ['titulo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Resuma o problema em poucas palavras.</p>
            </div>

            <div>
                <label for="descricao" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea id="descricao" name="descricao" class="js-jodit w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 <?php $__errorArgs = ['descricao'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Descreva o problema com mais detalhes..."><?php echo old('descricao'); ?></textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional. Use o editor para formatação. Máx. 50.000 caracteres.</p>
            </div>

            <div>
                <label for="prioridade" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade <span class="text-error-500">*</span></label>
                <select id="prioridade" name="prioridade" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 <?php $__errorArgs = ['prioridade'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php $__currentLoopData = \AreaCliente\Chamado::PRIORIDADES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e(old('prioridade', 'media') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Urgente para falhas críticas; Baixa para dúvidas ou melhorias.</p>
            </div>

            <div>
                <label for="anexos" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anexos (fotos ou documentos)</label>
                <input type="file" id="anexos" name="anexos[]" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" multiple
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 dark:border-gray-600 dark:bg-gray-900 dark:file:bg-brand-500/20 dark:file:text-brand-400 <?php $__errorArgs = ['anexos.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional. Você pode selecionar vários arquivos. Imagens (JPEG, PNG, GIF, WebP) ou PDF. Máx. 10 MB por arquivo.</p>
            </div>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:gap-4">
                <button type="submit" class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-500 px-5 py-3.5 text-base font-semibold text-white shadow-lg transition active:scale-[0.98] hover:bg-brand-600 dark:bg-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Enviar ticket
                </button>
                <a href="<?php echo e(route('plugin.area-cliente.chamados.index')); ?>" class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl border-2 border-gray-200 bg-white px-5 py-3.5 text-base font-semibold text-gray-700 transition active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<?php echo $__env->make('area-cliente::partials.jodit-editor', ['formId' => 'form-chamado', 'height' => 220], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\chamados\create.blade.php ENDPATH**/ ?>