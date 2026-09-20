



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Adquirente</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova adquirente (maquininha/gateway)</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('financeiro.adquirentes.store')); ?>" method="POST" class="p-6">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                <input type="text" name="nome" value="<?php echo e(old('nome')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" name="codigo" value="<?php echo e(old('codigo')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Código interno da adquirente</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Antecipação *</label>
                <select name="tipo_antecipacao" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="fluxo" <?php echo e(old('tipo_antecipacao') === 'fluxo' ? 'selected' : ''); ?>>Fluxo Normal</option>
                    <option value="antecipado" <?php echo e(old('tipo_antecipacao') === 'antecipado' ? 'selected' : ''); ?>>Antecipado</option>
                    <option value="ambos" <?php echo e(old('tipo_antecipacao') === 'ambos' ? 'selected' : ''); ?>>Ambos</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias para Antecipação *</label>
                <input type="number" name="dias_antecipacao" value="<?php echo e(old('dias_antecipacao', 1)); ?>" min="1" max="30" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Geralmente 1 ou 2 dias</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contas Bancárias</label>
                <div class="space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $contasBancarias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="contas[]" value="<?php echo e($conta->id); ?>" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <input type="radio" name="contas_padrao" value="<?php echo e($conta->id); ?>" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo e($conta->nome); ?> - <?php echo e($conta->banco ?? 'N/A'); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta bancária cadastrada</p>
                    <?php endif; ?>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione as contas e marque a padrão (radio)</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Formas de Pagamento</label>
                <div class="space-y-2">
                    <?php $__empty_1 = true; $__currentLoopData = $formasPagamento; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $forma): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="formas_pagamento[]" value="<?php echo e($forma->id); ?>" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <input type="radio" name="formas_pagamento_padrao" value="<?php echo e($forma->id); ?>" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo e($forma->nome); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma forma de pagamento cadastrada</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('observacoes')); ?></textarea>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Adquirente ativa</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="permite_antecipacao_parcelado" value="1" <?php echo e(old('permite_antecipacao_parcelado') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Permite antecipação de parcelas</span>
                </label>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('financeiro.adquirentes.form.extra', null); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('financeiro.adquirentes.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\adquirentes\create.blade.php ENDPATH**/ ?>