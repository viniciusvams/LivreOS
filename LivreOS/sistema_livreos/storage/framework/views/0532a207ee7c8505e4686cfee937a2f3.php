



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-6xl pb-6">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Central de tickets</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atenda os tickets do portal e converta em Ordem de Serviço quando necessário</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700 sm:px-5">
            <span class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Filtros</span>
        </div>
        <form method="GET" action="<?php echo e(route('plugin.area-cliente.admin.chamados.index')); ?>" class="flex flex-col gap-4 p-4 sm:flex-row sm:flex-wrap sm:items-end sm:gap-4 sm:p-5">
            <div class="w-full sm:min-w-[180px] sm:flex-1">
                <label for="busca" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Busca (ID, assunto, descrição)</label>
                <input type="text" id="busca" name="busca" value="<?php echo e(request('busca')); ?>" placeholder="Ex: 5 ou câmera" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="w-full sm:w-auto">
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = \AreaCliente\Chamado::STATUS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e(request('status') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="prioridade" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Prioridade</label>
                <select id="prioridade" name="prioridade" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    <?php $__currentLoopData = \AreaCliente\Chamado::PRIORIDADES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e(request('prioridade') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="w-full sm:w-auto">
                <label for="cliente_id" class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-400">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $clientes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cli): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cli->id); ?>" <?php echo e(request('cliente_id') == $cli->id ? 'selected' : ''); ?>><?php echo e($cli->nome ?? $cli->razao_social); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex w-full gap-3 sm:w-auto">
                <button type="submit" class="min-h-[48px] flex-1 rounded-xl bg-brand-500 px-5 py-3 text-base font-semibold text-white transition hover:bg-brand-600 sm:flex-none">Aplicar</button>
                <?php if(request()->hasAny(['busca', 'status', 'prioridade', 'cliente_id'])): ?>
                <a href="<?php echo e(route('plugin.area-cliente.admin.chamados.index')); ?>" class="min-h-[48px] flex-1 rounded-xl border-2 border-gray-200 px-5 py-3 text-center text-base font-semibold text-gray-700 transition dark:border-gray-600 dark:text-gray-300 sm:flex-none">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Assunto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Atualizado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">OS</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php $__empty_1 = true; $__currentLoopData = $chamados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($c->status);
                        $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($c->prioridade);
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($c->id); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300"><?php echo e(Str::limit($c->titulo, 50)); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo e($c->cliente ? ($c->cliente->nome ?? $c->cliente->razao_social) : '–'); ?></td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($statusColor); ?>"><?php echo e(\AreaCliente\Chamado::statusLabel($c->status)); ?></span></td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($prioridadeColor); ?>"><?php echo e(\AreaCliente\Chamado::prioridadeLabel($c->prioridade)); ?></span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo e($c->updated_at->format('d/m/Y H:i')); ?></td>
                        <td class="px-4 py-3">
                            <?php if($c->ordem_servico_id && ($c->ordemServico ?? null)): ?>
                            <a href="<?php echo e(url('/ordens-servico/' . $c->ordem_servico_id)); ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400"><?php echo e($c->ordemServico->codigo_interno ?? '#' . $c->ordem_servico_id); ?></a>
                            <?php else: ?>
                            <span class="text-sm text-gray-400 dark:text-gray-500">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?php echo e(route('plugin.area-cliente.admin.chamados.show', $c->id)); ?>" class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">Atender</a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Nenhum ticket encontrado.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($chamados->hasPages()): ?>
        <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700"><?php echo e($chamados->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views/admin/chamados/index.blade.php ENDPATH**/ ?>