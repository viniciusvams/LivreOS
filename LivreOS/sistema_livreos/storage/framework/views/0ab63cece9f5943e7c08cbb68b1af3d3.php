



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Propostas comerciais</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Orçamentos formais com versões (diferente do PDV)</p>
    </div>
    <?php if(auth()->user()->is_admin || auth()->user()->hasPermission('create-propostas-comerciais')): ?>
    <a href="<?php echo e(route('propostas-comerciais.create')); ?>"
       class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova proposta
    </a>
    <?php endif; ?>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<?php
    $alertaValProposta = request('alerta_proposta_validade');
?>
<?php if(in_array($alertaValProposta, ['hoje', 'vencida'], true)): ?>
<div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border px-4 py-3 text-sm <?php echo e($alertaValProposta === 'vencida' ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300' : 'border-orange-200 bg-orange-50 text-orange-900 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-200'); ?>">
    <span>
        <?php if($alertaValProposta === 'hoje'): ?>
            Listando propostas em rascunho ou enviadas com <strong>validade hoje</strong>.
        <?php else: ?>
            Listando propostas em rascunho ou enviadas com <strong>validade vencida</strong>.
        <?php endif; ?>
    </span>
    <a href="<?php echo e(route('propostas-comerciais.index')); ?>" class="shrink-0 font-medium underline hover:no-underline">Ver todas</a>
</div>
<?php endif; ?>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" class="flex flex-wrap gap-3">
        <?php if(in_array($alertaValProposta, ['hoje', 'vencida'], true)): ?>
        <input type="hidden" name="alerta_proposta_validade" value="<?php echo e($alertaValProposta); ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="Número, ID, título ou cliente" value="<?php echo e(request('search')); ?>"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 sm:w-64">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
            <option value="">Todos os status</option>
            <?php $__currentLoopData = ['rascunho','enviada','aprovada','expirada','convertida','cancelada']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($s); ?>" <?php echo e(request('status') === $s ? 'selected' : ''); ?>><?php echo e(ucfirst($s)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
        <a href="<?php echo e(route('propostas-comerciais.index')); ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Limpar</a>
    </form>
</div>

<?php if(function_exists('do_action')): ?>
    <?php do_action('entity.index.before_table', 'proposta_comercial') ?>
<?php endif; ?>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Ref.</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Título</th>
                    <th class="px-4 py-3">Emissão</th>
                    <th class="px-4 py-3">Validade</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $propostas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                        <?php if($p->numero_sequencial): ?>
                            <?php echo e($p->numero_sequencial); ?> · v<?php echo e($p->versao); ?>

                        <?php else: ?>
                            #<?php echo e($p->id); ?> · v<?php echo e($p->versao); ?>

                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3"><?php echo e($p->cliente?->nome ?? '—'); ?></td>
                    <td class="px-4 py-3"><?php echo e($p->titulo ?: '—'); ?></td>
                    <td class="px-4 py-3"><?php echo e($p->data_emissao?->format('d/m/Y')); ?></td>
                    <td class="px-4 py-3"><?php echo e($p->validade_em?->format('d/m/Y') ?? '—'); ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200"><?php echo e($p->status_label); ?></span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">R$ <?php echo e(number_format((float) $p->total_geral, 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?php echo e(route('propostas-comerciais.show', $p)); ?>" class="text-brand-600 hover:underline dark:text-brand-400">Abrir</a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhuma proposta encontrada.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($propostas->hasPages()): ?>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800"><?php echo e($propostas->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\propostas-comerciais\index.blade.php ENDPATH**/ ?>