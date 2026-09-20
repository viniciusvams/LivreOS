



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex-1">
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plano de Contas</h1>
        <p class="text-gray-600 dark:text-gray-400">Gerencie o plano de contas para DRE</p>
    </div>
    <div>
        <a href="<?php echo e(route('financeiro.plano-contas.create')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Conta
        </a>
    </div>
</div>

<div class="space-y-4">
    <?php $__currentLoopData = $contas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($conta->codigo ?? '-'); ?></span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e($conta->nome); ?></span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                <?php if($conta->tipo === 'receita'): ?> bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                <?php else: ?> bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                <?php endif; ?>">
                                <?php echo e(ucfirst($conta->tipo)); ?>

                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-500">(<?php echo e(ucfirst($conta->categoria)); ?>)</span>
                        </div>
                        <?php if($conta->descricao): ?>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400"><?php echo e($conta->descricao); ?></p>
                        <?php endif; ?>
                        <?php if($conta->tipo === 'despesa' && (($conta->total_despesa_pago ?? 0) > 0 || ($conta->total_despesa_aberto ?? 0) > 0)): ?>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Despesas: <span class="text-green-600 dark:text-green-400">R$ <?php echo e(number_format($conta->total_despesa_pago ?? 0, 2, ',', '.')); ?> pago</span>
                                <?php if(($conta->total_despesa_aberto ?? 0) > 0): ?>
                                    · <span class="text-amber-600 dark:text-amber-400">R$ <?php echo e(number_format($conta->total_despesa_aberto ?? 0, 2, ',', '.')); ?> em aberto</span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if($conta->tipo === 'receita' && (($conta->total_receita_recebido ?? 0) > 0 || ($conta->total_receita_aberto ?? 0) > 0)): ?>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Receitas: <span class="text-green-600 dark:text-green-400">R$ <?php echo e(number_format($conta->total_receita_recebido ?? 0, 2, ',', '.')); ?> recebido</span>
                                <?php if(($conta->total_receita_aberto ?? 0) > 0): ?>
                                    · <span class="text-amber-600 dark:text-amber-400">R$ <?php echo e(number_format($conta->total_receita_aberto ?? 0, 2, ',', '.')); ?> a receber</span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?php echo e(route('financeiro.plano-contas.edit', $conta)); ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Editar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php if($conta->filhos->count() > 0): ?>
                <div class="border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="space-y-2">
                        <?php $__currentLoopData = $conta->filhos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filho): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="text-xs text-gray-500"><?php echo e($filho->codigo ?? '-'); ?></span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo e($filho->nome); ?></span>
                                    <?php if($filho->tipo === 'despesa' && (($filho->total_despesa_pago ?? 0) > 0 || ($filho->total_despesa_aberto ?? 0) > 0)): ?>
                                        <span class="text-xs text-gray-500 truncate">
                                            — R$ <?php echo e(number_format($filho->total_despesa_pago ?? 0, 2, ',', '.')); ?> pago
                                            <?php if(($filho->total_despesa_aberto ?? 0) > 0): ?> / R$ <?php echo e(number_format($filho->total_despesa_aberto ?? 0, 2, ',', '.')); ?> aberto <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if($filho->tipo === 'receita' && (($filho->total_receita_recebido ?? 0) > 0 || ($filho->total_receita_aberto ?? 0) > 0)): ?>
                                        <span class="text-xs text-gray-500 truncate">
                                            — R$ <?php echo e(number_format($filho->total_receita_recebido ?? 0, 2, ',', '.')); ?> recebido
                                            <?php if(($filho->total_receita_aberto ?? 0) > 0): ?> / R$ <?php echo e(number_format($filho->total_receita_aberto ?? 0, 2, ',', '.')); ?> a receber <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo e(route('financeiro.plano-contas.edit', $filho)); ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\plano-contas\index.blade.php ENDPATH**/ ?>