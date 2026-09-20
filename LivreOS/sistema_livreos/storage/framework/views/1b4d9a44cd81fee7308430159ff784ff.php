



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Vendas por Vendedor</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Receita recebida no período (data de recebimento): <strong>OS</strong> com rateio por técnico nos itens/apontamentos e <strong>pedidos de venda</strong> atribuídos ao vendedor do pedido.</p>
    </div>
    <form method="get" action="<?php echo e(route('financeiro.relatorios.vendas-vendedor')); ?>" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">De</label>
            <input type="date" name="data_inicio" value="<?php echo e($dataInicio ?? ''); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Até</label>
            <input type="date" name="data_fim" value="<?php echo e($dataFim ?? ''); ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        </div>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Filtrar</button>
    </form>
</div>

<div class="mb-6 flex gap-4">
    <a href="<?php echo e(route('financeiro.relatorios.vendas-vendedor.pdf', request()->query())); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Total recebido no período: R$ <?php echo e(number_format($totalGeral ?? 0, 2, ',', '.')); ?></p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Período: <?php echo e(\Carbon\Carbon::parse($dataInicio ?? now())->format('d/m/Y')); ?> a <?php echo e(\Carbon\Carbon::parse($dataFim ?? now())->format('d/m/Y')); ?></p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vendedor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Títulos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__currentLoopData = $porVendedor ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($row['vendedor_nome']); ?></td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($row['valor'], 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400"><?php echo e($row['quantidade']); ?></td>
                </tr>
                <?php if(!empty($row['ordens'] ?? [])): ?>
                <tr class="bg-gray-50/60 dark:bg-gray-900/40">
                    <td colspan="3" class="px-4 pb-4 pt-2">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Detalhamento por OS e pedidos de venda (itens e total atribuído)</div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/40">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900/80">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">OS</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Cliente</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Serviços</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Produtos</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total atribuído</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Serviços</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Produtos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php $__currentLoopData = $row['ordens']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $os): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                                            <?php if(($os['origem'] ?? '') === 'pedido_venda'): ?>
                                                <span class="mr-1 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">PV</span>
                                            <?php endif; ?>
                                            <?php echo e($os['os_codigo'] ?? (($os['os_id'] ?? null) ? 'OS #'.$os['os_id'] : '—')); ?>

                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300"><?php echo e($os['cliente'] ?? '—'); ?></td>
                                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">R$ <?php echo e(number_format($os['valor_servicos'] ?? 0, 2, ',', '.')); ?></td>
                                        <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">R$ <?php echo e(number_format($os['valor_produtos'] ?? 0, 2, ',', '.')); ?></td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-100">R$ <?php echo e(number_format($os['valor'] ?? 0, 2, ',', '.')); ?></td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            <?php if(!empty($os['servicos'] ?? [])): ?>
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    <?php $__currentLoopData = $os['servicos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <span class="font-medium"><?php echo e($s['nome']); ?></span>
                                                        <span class="text-gray-500 dark:text-gray-400">– Qtd <?php echo e(number_format($s['quantidade'] ?? 0, 2, ',', '.')); ?>, R$ <?php echo e(number_format($s['total'] ?? 0, 2, ',', '.')); ?></span>
                                                    </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ul>
                                            <?php else: ?>
                                                <span class="text-gray-400">Sem serviços detalhados</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                            <?php if(!empty($os['produtos'] ?? [])): ?>
                                                <ul class="list-disc pl-4 space-y-0.5">
                                                    <?php $__currentLoopData = $os['produtos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <span class="font-medium"><?php echo e($p['nome']); ?></span>
                                                        <span class="text-gray-500 dark:text-gray-400">– Qtd <?php echo e(number_format($p['quantidade'] ?? 0, 2, ',', '.')); ?>, R$ <?php echo e(number_format($p['total'] ?? 0, 2, ',', '.')); ?></span>
                                                    </li>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </ul>
                                            <?php else: ?>
                                                <span class="text-gray-400">Sem produtos detalhados</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<?php if(empty($porVendedor) || count($porVendedor) === 0): ?>
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhuma receita no período.
</div>
<?php endif; ?>

<div class="mt-4">
    <a href="<?php echo e(route('financeiro.relatorios.index')); ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\vendas-vendedor.blade.php ENDPATH**/ ?>