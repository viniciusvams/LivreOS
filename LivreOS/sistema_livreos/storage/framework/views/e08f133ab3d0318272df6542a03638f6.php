



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-5xl pb-6">
    <?php if(session('success')): ?>
    <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-500/10 dark:text-success-300"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tickets</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Abra um ticket ou acompanhe o atendimento</p>
        </div>
        <a href="<?php echo e(route('plugin.area-cliente.chamados.create')); ?>" class="inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo ticket
        </a>
    </div>

    
    <div class="mb-6 grid grid-cols-2 gap-3 sm:flex sm:gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($contagemStatus['abertos'] ?? 0); ?></p>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Em aberto</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($contagemStatus['encerrados'] ?? 0); ?></p>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Encerrados</p>
        </div>
    </div>

    
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <form method="GET" action="<?php echo e(route('plugin.area-cliente.chamados.index')); ?>" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3">
            <div class="min-w-0 flex-1 sm:max-w-[220px]">
                <label for="busca" class="sr-only">Busca</label>
                <input type="text" id="busca" name="busca" value="<?php echo e(request('busca')); ?>" placeholder="Buscar por assunto..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
            </div>
            <div class="w-full sm:w-[140px]">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todos os status</option>
                    <?php $__currentLoopData = \AreaCliente\Chamado::STATUS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e(request('status') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="w-full sm:w-[120px]">
                <label for="prioridade" class="sr-only">Prioridade</label>
                <select id="prioridade" name="prioridade" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Todas</option>
                    <?php $__currentLoopData = \AreaCliente\Chamado::PRIORIDADES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($v); ?>" <?php echo e(request('prioridade') == $v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Filtrar</button>
                <?php if(request()->hasAny(['busca', 'status', 'prioridade'])): ?>
                <a href="<?php echo e(route('plugin.area-cliente.chamados.index')); ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <?php if($chamados->count() > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">#</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Assunto</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Prioridade</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Atualizado</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Abrir</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php $__currentLoopData = $chamados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $statusColor = \AreaCliente\AreaClienteHelper::chamadoStatusColor($c->status);
                        $prioridadeColor = \AreaCliente\AreaClienteHelper::chamadoPrioridadeColor($c->prioridade);
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white"><?php echo e($c->id); ?></td>
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('plugin.area-cliente.chamados.show', $c->id)); ?>" class="font-medium text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                <?php echo e(Str::limit($c->titulo, 50)); ?>

                            </a>
                            <?php if($c->anexos->count() > 0): ?>
                            <span class="ml-1 text-gray-400" title="<?php echo e($c->anexos->count()); ?> anexo(s)">📎</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($statusColor); ?>"><?php echo e(\AreaCliente\Chamado::statusLabel($c->status)); ?></span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($prioridadeColor); ?>"><?php echo e(\AreaCliente\Chamado::prioridadeLabel($c->prioridade)); ?></span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo e($c->updated_at->format('d/m/Y H:i')); ?></td>
                        <td class="relative whitespace-nowrap px-4 py-3 text-right">
                            <a href="<?php echo e(route('plugin.area-cliente.chamados.show', $c->id)); ?>" class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium text-sm">Abrir</a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <?php echo e($chamados->links()); ?>

        </div>
        <?php else: ?>
        <div class="px-4 py-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">Nenhum ticket encontrado.</p>
            <a href="<?php echo e(route('plugin.area-cliente.chamados.create')); ?>" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Abrir novo ticket</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\chamados\index.blade.php ENDPATH**/ ?>