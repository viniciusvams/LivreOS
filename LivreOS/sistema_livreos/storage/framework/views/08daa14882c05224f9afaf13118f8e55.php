



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Posição de Estoque e Curva ABC</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Valor financeiro do estoque e classificação por giro (A = 80% do valor, B até 95%, C restante).</p>
    </div>
    <a href="<?php echo e(route('financeiro.relatorios.estoque-posicao.pdf')); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Valor total em estoque: R$ <?php echo e(number_format($valorTotalEstoque ?? 0, 2, ',', '.')); ?></p>
    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo e(count($lista ?? [])); ?> produto(s) com controle de estoque.</p>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Produto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Código</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Qtd</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Custo unit.</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Curva ABC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__currentLoopData = $lista ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($item['nome']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e($item['codigo'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400"><?php echo e(number_format($item['quantidade'] ?? 0, 0, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">R$ <?php echo e(number_format($item['custo_unit'] ?? 0, 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($item['valor_total'] ?? 0, 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if(($item['classe_abc'] ?? '') === 'A'): ?>
                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">A</span>
                        <?php elseif(($item['classe_abc'] ?? '') === 'B'): ?>
                        <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">B</span>
                        <?php else: ?>
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">C</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<?php if(empty($lista) || count($lista) === 0): ?>
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhum produto com controle de estoque.
</div>
<?php endif; ?>

<div class="mt-4">
    <a href="<?php echo e(route('financeiro.relatorios.index')); ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\estoque-posicao.blade.php ENDPATH**/ ?>