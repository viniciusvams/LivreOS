

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'action' => '',
    'titulo' => 'Excluir',
    'descricao' => 'Tem certeza que deseja excluir? Esta ação não pode ser desfeita.',
    'idModal' => 'modalExcluirMotivo',
    'idForm' => 'formExcluirMotivo',
    'idMotivo' => 'motivoExcluir',
    /** Quando false, envia POST simples (ex.: exclusão em massa). */
    'useDeleteMethod' => true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'action' => '',
    'titulo' => 'Excluir',
    'descricao' => 'Tem certeza que deseja excluir? Esta ação não pode ser desfeita.',
    'idModal' => 'modalExcluirMotivo',
    'idForm' => 'formExcluirMotivo',
    'idMotivo' => 'motivoExcluir',
    /** Quando false, envia POST simples (ex.: exclusão em massa). */
    'useDeleteMethod' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div id="<?php echo e($idModal); ?>" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($titulo); ?></h2>
            <button type="button" onclick="document.getElementById('<?php echo e($idModal); ?>').classList.add('hidden'); document.getElementById('<?php echo e($idModal); ?>').classList.remove('flex'); document.body.style.overflow = '';" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="<?php echo e($idForm); ?>" action="<?php echo e($action); ?>" method="POST" class="p-6">
            <?php echo csrf_field(); ?>
            <?php if($useDeleteMethod): ?>
            <?php echo method_field('DELETE'); ?>
            <?php endif; ?>
            <?php echo e($camposExtras ?? ''); ?>

            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($descricao); ?></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da exclusão * (mín. 10 caracteres)</label>
                <textarea name="motivo" id="<?php echo e($idMotivo); ?>" rows="4" required minlength="10" placeholder="Descreva o motivo da exclusão..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('<?php echo e($idModal); ?>').classList.add('hidden'); document.getElementById('<?php echo e($idModal); ?>').classList.remove('flex'); document.body.style.overflow = '';" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar exclusão</button>
            </div>
        </form>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\modal-excluir-motivo.blade.php ENDPATH**/ ?>