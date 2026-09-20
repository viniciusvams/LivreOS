



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(format_os_display($ordem)); ?></h1>
        <p class="text-gray-600 dark:text-gray-400"><?php echo e($ordem->cliente?->nome); ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?php echo e(route('ordens-servico.print', $ordem)); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir OS
        </a>
        <?php if(in_array($ordem->status, ['Encerrado', 'Encerrada'], true)): ?>
        <a href="<?php echo e(route('ordens-servico.comprovante', $ordem)); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true" fill="currentColor"><path d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/></svg>
            Imprimir comprovante
        </a>
        <?php endif; ?>
        <button type="button" onclick="abrirModalNotificarCliente(<?php echo e($ordem->id); ?>)" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Notificar cliente
        </button>
        <a href="<?php echo e(route('ordens-servico.edit', $ordem)); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="<?php echo e(route('ordens-servico.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Resumo</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e(format_status_os_display($ordem->status)); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tipo</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($ordem->tipo_os ?: '-'); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Equipamento</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($ordem->equipamento_nome ?: '-'); ?></dd>
            </div>
            <?php if($ordem->equipamento_unlock_type): ?>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <?php if (isset($component)) { $__componentOriginal19681891f37bcc5c6de58e62f523674a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal19681891f37bcc5c6de58e62f523674a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.unlock-pattern-view','data' => ['unlockType' => $ordem->equipamento_unlock_type,'unlockCode' => $ordem->equipamento_unlock_code,'readonly' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('unlock-pattern-view'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['unlockType' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ordem->equipamento_unlock_type),'unlockCode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ordem->equipamento_unlock_code),'readonly' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal19681891f37bcc5c6de58e62f523674a)): ?>
<?php $attributes = $__attributesOriginal19681891f37bcc5c6de58e62f523674a; ?>
<?php unset($__attributesOriginal19681891f37bcc5c6de58e62f523674a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal19681891f37bcc5c6de58e62f523674a)): ?>
<?php $component = $__componentOriginal19681891f37bcc5c6de58e62f523674a; ?>
<?php unset($__componentOriginal19681891f37bcc5c6de58e62f523674a); ?>
<?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Prioridade</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($ordem->prioridade ?: '-'); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Abertura</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e(optional($ordem->data_abertura)->format('d/m/Y H:i')); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Prevista conclusão</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e(optional($ordem->prevista_conclusao)->format('d/m/Y H:i') ?: '-'); ?></dd>
            </div>
        </dl>
        <?php if(!in_array($ordem->status, ['Encerrado', 'Encerrada'])): ?>
        <form method="POST" action="<?php echo e(route('ordens-servico.status', $ordem)); ?>" class="mt-4 flex gap-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__currentLoopData = ['Aberta','Em análise/orçamento','Aguardando aprovação','Aguardando peças','Em execução','Em teste','Concluída','Cancelada']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($status); ?>" <?php echo e($ordem->status == $status ? 'selected' : ''); ?>><?php echo e($status); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Atualizar</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Totais</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Produtos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_produtos, 2, ',', '.')); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Serviços</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_servicos, 2, ',', '.')); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Descontos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_descontos, 2, ',', '.')); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Acréscimos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_acrescimos, 2, ',', '.')); ?></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Impostos</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_impostos, 2, ',', '.')); ?></dd>
            </div>
            <div class="flex justify-between font-semibold">
                <dt class="text-gray-700 dark:text-gray-200">Total geral</dt>
                <dd class="text-gray-800 dark:text-white/90">R$ <?php echo e(number_format($ordem->total_geral, 2, ',', '.')); ?></dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Produtos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Produto</th>
                        <th class="px-3 py-2">Qtd</th>
                        <th class="px-3 py-2">Valor</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php $__empty_1 = true; $__currentLoopData = $ordem->produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2"><?php echo e($item->produto?->nome ?: $item->descricao); ?></td>
                            <td class="px-3 py-2"><?php echo e(format_quantity($item->quantidade)); ?></td>
                            <td class="px-3 py-2">R$ <?php echo e(number_format($item->valor_unitario, 2, ',', '.')); ?></td>
                            <td class="px-3 py-2">R$ <?php echo e(number_format($item->total, 2, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">Sem produtos</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Serviços</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Serviço</th>
                        <th class="px-3 py-2">Técnico</th>
                        <th class="px-3 py-2">Tempo</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php $__empty_1 = true; $__currentLoopData = $ordem->servicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-2"><?php echo e($item->servico?->nome ?: $item->descricao); ?></td>
                            <td class="px-3 py-2"><?php echo e($item->tecnico?->name ?: '-'); ?></td>
                            <td class="px-3 py-2"><?php echo e($item->tempo_real_min ?: $item->tempo_estimado_min ?: '-'); ?> min</td>
                            <td class="px-3 py-2">R$ <?php echo e(number_format($item->total, 2, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">Sem serviços</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Anexos</h2>
        <div class="space-y-2">
            <?php $__empty_1 = true; $__currentLoopData = $ordem->anexos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anexo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white/90"><?php echo e($anexo->nome_arquivo); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($anexo->tipo); ?> <?php echo e($anexo->tags ? '- ' . $anexo->tags : ''); ?></p>
                    </div>
                    <a href="<?php echo e(route('ordens-servico.anexos.file', $anexo)); ?>" target="_blank" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Abrir</a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum anexo</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('ordens_servico.show.extra', $ordem); ?>
<?php endif; ?>

<?php echo $__env->make('ordens_servico._modal_notificar_cliente', ['ordem' => $ordem], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if($ordem->equipamento_unlock_type === 'pattern' && $ordem->equipamento_unlock_code): ?>
<?php $__env->startPush('scripts'); ?>
<script>
<?php echo $__env->make('ordens_servico._unlock_pattern_script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('partials.jodit-assets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
<?php echo $__env->make('ordens_servico._scripts_notificar_cliente_modal', ['ordem' => $ordem], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</script>
<?php if($errors->has('email')): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof abrirModalNotificarCliente === 'function') {
        abrirModalNotificarCliente(<?php echo e($ordem->id); ?>);
    }
});
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ordens_servico\show.blade.php ENDPATH**/ ?>