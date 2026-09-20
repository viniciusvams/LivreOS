

<?php if($contas->isEmpty()): ?>
    <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum título (conta a pagar) gerado por esta despesa recorrente.</p>
<?php else: ?>
    <div class="max-h-96 overflow-y-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Vencimento</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php $__currentLoopData = $contas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300"><?php echo e($c->descricao); ?></td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400"><?php echo e($c->data_vencimento->format('d/m/Y')); ?></td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($c->valor, 2, ',', '.')); ?></td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                <?php if($c->status === 'pago'): ?> bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                <?php elseif($c->status === 'parcial'): ?> bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                <?php elseif($c->esta_vencido): ?> bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                <?php elseif($c->status === 'cancelado'): ?> bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                <?php else: ?> bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                <?php endif; ?>"><?php echo e(ucfirst($c->status)); ?></span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="<?php echo e(route('financeiro.contas-pagar.edit', $c)); ?>" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300" title="Abrir conta">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if($contas->count() >= 200): ?>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Exibindo os 200 títulos mais recentes.</p>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\contas-pagar\_lancamentos-recorrentes-recorrente.blade.php ENDPATH**/ ?>