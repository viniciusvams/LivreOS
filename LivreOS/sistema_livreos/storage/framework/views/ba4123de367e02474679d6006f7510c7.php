



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-6xl">
    <header class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Relatórios e Inteligência de Dados</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Central de relatórios financeiros e indicadores em tempo real</p>
    </header>

    
    <section class="mb-10">
        <h2 class="mb-4 text-base font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Inteligência de Dados
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="<?php echo e(route('financeiro.contas-receber.index')); ?>" class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-500">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">A Receber</span>
                <span class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">R$ <?php echo e(number_format($totalReceber ?? 0, 2, ',', '.')); ?></span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Total em aberto</span>
            </a>
            <a href="<?php echo e(route('financeiro.contas-pagar.index')); ?>" class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-brand-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-500">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">A Pagar</span>
                <span class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-white">R$ <?php echo e(number_format($totalPagar ?? 0, 2, ',', '.')); ?></span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Total em aberto</span>
            </a>
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo Contas</span>
                <span class="mt-2 text-xl font-bold tabular-nums <?php echo e(($saldoContas ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'); ?>">
                    R$ <?php echo e(number_format($saldoContas ?? 0, 2, ',', '.')); ?>

                </span>
                <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Contas bancárias ativas</span>
            </div>
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Mês atual</span>
                <div class="mt-2 space-y-1 text-sm">
                    <p class="tabular-nums text-green-600 dark:text-green-400">Receitas: R$ <?php echo e(number_format($receitasMes ?? 0, 2, ',', '.')); ?></p>
                    <p class="tabular-nums text-red-600 dark:text-red-400">Despesas: R$ <?php echo e(number_format($despesasMes ?? 0, 2, ',', '.')); ?></p>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                    Resultado: R$ <?php echo e(number_format(($receitasMes ?? 0) - ($despesasMes ?? 0), 2, ',', '.')); ?>

                </span>
            </div>
        </div>

        <?php if(($inadimplenteReceber ?? 0) > 0 || ($inadimplentePagar ?? 0) > 0 || ($contasVencendo7Receber ?? 0) > 0 || ($contasVencendo7Pagar ?? 0) > 0): ?>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <?php if(($inadimplenteReceber ?? 0) > 0 || ($inadimplentePagar ?? 0) > 0): ?>
            <div class="flex-1 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Inadimplência</h3>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    Receber vencido: <strong>R$ <?php echo e(number_format($inadimplenteReceber ?? 0, 2, ',', '.')); ?></strong>
                    <?php if(($inadimplentePagar ?? 0) > 0): ?>
                        · Pagar vencido: <strong>R$ <?php echo e(number_format($inadimplentePagar ?? 0, 2, ',', '.')); ?></strong>
                    <?php endif; ?>
                </p>
                <a href="<?php echo e(route('financeiro.relatorios.inadimplencia')); ?>" class="mt-2 inline-block text-xs font-medium text-amber-700 underline dark:text-amber-300">Ver Aging de Cobrança</a>
            </div>
            <?php endif; ?>
            <?php if(($contasVencendo7Receber ?? 0) > 0 || ($contasVencendo7Pagar ?? 0) > 0): ?>
            <div class="flex-1 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Próximos 7 dias</h3>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                    <?php echo e($contasVencendo7Receber ?? 0); ?> conta(s) a receber · <?php echo e($contasVencendo7Pagar ?? 0); ?> conta(s) a pagar
                </p>
                <a href="<?php echo e(route('financeiro.dashboard')); ?>" class="mt-2 inline-block text-xs font-medium text-blue-700 underline dark:text-blue-300">Ver no dashboard</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    
    <?php $__currentLoopData = $categorias ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <section class="mb-10">
        <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($categoria['titulo']); ?></h2>
        <?php if(!empty($categoria['subtitulo'])): ?>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400"><?php echo e($categoria['subtitulo']); ?></p>
        <?php endif; ?>
        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php $__currentLoopData = $categoria['reportes'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                            <?php echo $__env->make('financeiro.relatorios.partials.icon-relatorio', ['icon' => $rel['icon'] ?? 'default'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-gray-800 dark:text-white/90"><?php echo e($rel['name']); ?></h3>
                            <p class="mt-1 text-sm leading-snug text-gray-600 dark:text-gray-400"><?php echo e($rel['description'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                    <a href="<?php echo e(route($rel['route'])); ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Abrir
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <?php if(!empty($rel['pdf_route'])): ?>
                    <a href="<?php echo e(route($rel['pdf_route'])); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        PDF
                    </a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if(function_exists('do_action')): ?>
    <?php do_action('financeiro.relatorios.index.after'); ?>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\index.blade.php ENDPATH**/ ?>