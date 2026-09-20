



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Transferências entre contas</h1>
        <p class="text-gray-600 dark:text-gray-400">Histórico de transferências realizadas e canceladas</p>
    </div>
    <a href="<?php echo e(route('financeiro.transferencias.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Nova transferência</a>
</div>

<?php if(session('success')): ?>
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<!-- Filtro por status -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Exibir:</span>
    <a href="<?php echo e(route('financeiro.transferencias.index', ['status' => 'todas'] + request()->except('status'))); ?>" class="rounded-lg px-3 py-1.5 text-sm <?php echo e(($statusFiltro ?? 'todas') === 'todas' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'); ?>">Todas</a>
    <a href="<?php echo e(route('financeiro.transferencias.index', ['status' => 'ativas'] + request()->except('status'))); ?>" class="rounded-lg px-3 py-1.5 text-sm <?php echo e(($statusFiltro ?? '') === 'ativas' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'); ?>">Ativas</a>
    <a href="<?php echo e(route('financeiro.transferencias.index', ['status' => 'canceladas'] + request()->except('status'))); ?>" class="rounded-lg px-3 py-1.5 text-sm <?php echo e(($statusFiltro ?? '') === 'canceladas' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'); ?>">Canceladas</a>
    <a href="<?php echo e(route('financeiro.movimentacoes.index', ['origem' => 'transferencia'])); ?>" class="ml-2 text-sm text-brand-600 hover:underline dark:text-brand-400">Ver nas movimentações</a>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Valor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Conta origem</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Conta destino</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Descrição</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Cancelamento</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $transferencias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saida): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $entrada = $saida->transferenciaEntrada;
                        $cancelada = $saida->trashed();
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($saida->data_movimentacao->format('d/m/Y')); ?></td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($saida->valor, 2, ',', '.')); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($saida->contaBancaria->nome ?? '—'); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e($entrada && $entrada->contaBancaria ? $entrada->contaBancaria->nome : '—'); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e(Str::limit($saida->descricao, 40)); ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if($cancelada): ?>
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Cancelada</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Ativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            <?php if($cancelada && $saida->data_cancelamento): ?>
                                <div class="space-y-0.5">
                                    <p><strong>Em:</strong> <?php echo e($saida->data_cancelamento->format('d/m/Y H:i')); ?></p>
                                    <p><strong>Por:</strong> <?php echo e($saida->canceladoPor->name ?? '—'); ?></p>
                                    <?php if($saida->motivo_cancelamento_transferencia): ?>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" title="<?php echo e($saida->motivo_cancelamento_transferencia); ?>"><strong>Motivo:</strong> <?php echo e(Str::limit($saida->motivo_cancelamento_transferencia, 60)); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if(!$cancelada): ?>
                                <?php if (\Illuminate\Support\Facades\Blade::check('isAdmin')): ?>
                                    <button type="button"
                                        class="rounded-lg bg-red-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600"
                                        onclick="window.openCancelarTransferenciaModal('<?php echo e(route('financeiro.transferencias.cancelar', $saida->id)); ?>')">
                                        Cancelar
                                    </button>
                                <?php else: ?>
                                    <?php if (\Illuminate\Support\Facades\Blade::check('hasPermission', 'financeiro.transferencias.cancelar')): ?>
                                        <button type="button"
                                            class="rounded-lg bg-red-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600"
                                            onclick="window.openCancelarTransferenciaModal('<?php echo e(route('financeiro.transferencias.cancelar', $saida->id)); ?>')">
                                            Cancelar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <?php if(($statusFiltro ?? 'todas') === 'canceladas'): ?>
                                Nenhuma transferência cancelada.
                            <?php elseif(($statusFiltro ?? '') === 'ativas'): ?>
                                Nenhuma transferência ativa.
                            <?php else: ?>
                                Nenhuma transferência encontrada.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
        <?php echo e($transferencias->withQueryString()->links()); ?>

    </div>
</div>

<!-- Modal de cancelamento (motivo obrigatório) -->
<div id="modalCancelarTransferencia" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50" onclick="window.closeCancelarTransferenciaModal()">
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl dark:bg-gray-800" onclick="event.stopPropagation()">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cancelar transferência</h2>
            <button type="button" onclick="window.closeCancelarTransferenciaModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formCancelarTransferencia" action="" method="POST" class="p-6">
            <?php echo csrf_field(); ?>
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Esta ação cancela a transferência e remove (soft delete) as duas movimentações do par. Informe o motivo do cancelamento.
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo do cancelamento *</label>
                <textarea name="motivo_cancelamento_transferencia" id="motivoCancelamentoTransferencia" rows="4" required maxlength="2000" placeholder="Descreva o motivo do cancelamento..." class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="window.closeCancelarTransferenciaModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">Confirmar cancelamento</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openCancelarTransferenciaModal = function(actionUrl) {
        const modal = document.getElementById('modalCancelarTransferencia');
        const form = document.getElementById('formCancelarTransferencia');
        const motivo = document.getElementById('motivoCancelamentoTransferencia');
        form.action = actionUrl;
        if (motivo) motivo.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    window.closeCancelarTransferenciaModal = function() {
        const modal = document.getElementById('modalCancelarTransferencia');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\transferencias\index.blade.php ENDPATH**/ ?>