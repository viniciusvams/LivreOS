



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Inadimplência (Aging de Cobrança)</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Quem está devendo, há quantos dias e valor total. Guia para bloqueios de serviço.</p>
    </div>
    <a href="<?php echo e(route('financeiro.relatorios.inadimplencia.pdf')); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Exportar PDF
    </a>
</div>

<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Total em atraso: R$ <?php echo e(number_format($totalGeral ?? 0, 2, ',', '.')); ?></p>
</div>

<?php $__currentLoopData = $faixas ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $faixa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php if(count($faixa['contas'] ?? []) > 0): ?>
<div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($faixa['label']); ?></h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Total: R$ <?php echo e(number_format($faixa['total'] ?? 0, 2, ',', '.')); ?> — <?php echo e(count($faixa['contas'])); ?> título(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Juros sug.</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Multa sug.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__currentLoopData = $faixa['contas']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $sug = \App\Http\Controllers\Financeiro\ConfiguracoesFinanceiroController::calcularJurosMultaSugeridos((float)($c->valor_original ?? $c->valor), $c->data_vencimento->format('Y-m-d')); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($c->cliente->nome ?? $c->cliente->razao_social ?? '—'); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e($c->descricao); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e($c->data_vencimento ? $c->data_vencimento->format('d/m/Y') : '—'); ?></td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($c->valor_pendente ?? $c->valor, 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">R$ <?php echo e(number_format($sug['juros'], 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-400">R$ <?php echo e(number_format($sug['multa'], 2, ',', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if(empty($faixas) || collect($faixas ?? [])->sum(fn($f) => count($f['contas'] ?? [])) === 0): ?>
<div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
    Nenhuma conta em atraso no momento.
</div>
<?php endif; ?>

<div class="mt-4">
    <a href="<?php echo e(route('financeiro.relatorios.index')); ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">← Voltar aos relatórios</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\relatorios\inadimplencia.blade.php ENDPATH**/ ?>