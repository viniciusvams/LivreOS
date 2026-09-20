



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($adquirente->nome); ?></h1>
        <p class="text-gray-600 dark:text-gray-400">Detalhes da adquirente</p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo e(route('financeiro.adquirentes.edit', $adquirente)); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Editar</a>
        <a href="<?php echo e(route('financeiro.adquirentes.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações Gerais</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($adquirente->nome); ?></dd>
            </div>
            <?php if($adquirente->codigo): ?>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Código</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($adquirente->codigo); ?></dd>
            </div>
            <?php endif; ?>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Tipo de Antecipação</dt>
                <dd class="text-gray-800 dark:text-white/90">
                    <?php if($adquirente->tipo_antecipacao === 'fluxo'): ?>
                        Fluxo Normal
                    <?php elseif($adquirente->tipo_antecipacao === 'antecipado'): ?>
                        Antecipado
                    <?php else: ?>
                        Ambos
                    <?php endif; ?>
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Dias para Antecipação</dt>
                <dd class="text-gray-800 dark:text-white/90"><?php echo e($adquirente->dias_antecipacao); ?> dias</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="text-gray-800 dark:text-white/90">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($adquirente->ativo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                        <?php echo e($adquirente->ativo ? 'Ativo' : 'Inativo'); ?>

                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Contas Bancárias</h2>
        <?php if($adquirente->contas->count() > 0): ?>
            <ul class="space-y-2 text-sm">
                <?php $__currentLoopData = $adquirente->contas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300"><?php echo e($conta->nome); ?></span>
                        <?php if($conta->pivot->padrao): ?>
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs text-brand-800 dark:bg-brand-900 dark:text-brand-300">Padrão</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma conta vinculada</p>
        <?php endif; ?>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Taxas Configuradas</h2>
            <a href="<?php echo e(route('financeiro.taxas.create', $adquirente)); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Nova Taxa</a>
        </div>
        <?php if($adquirente->taxas->count() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bandeira</th>
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Modalidade</th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Parcelas</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa %</th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Taxa/Parcela</th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php $__currentLoopData = $adquirente->taxas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $taxa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="px-3 py-2"><?php echo e(ucfirst($taxa->bandeira)); ?></td>
                                <td class="px-3 py-2"><?php echo e(ucfirst(str_replace('_', ' ', $taxa->modalidade))); ?></td>
                                <td class="px-3 py-2 text-center">
                                    <?php if($taxa->parcelas_min || $taxa->parcelas_max): ?>
                                        <?php echo e($taxa->parcelas_min ?? '1'); ?>x - <?php echo e($taxa->parcelas_max ?? '∞'); ?>x
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-right"><?php echo e(number_format($taxa->taxa_percentual, 2, ',', '.')); ?>%</td>
                                <td class="px-3 py-2 text-right"><?php echo e(number_format($taxa->taxa_por_parcela, 2, ',', '.')); ?>%</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs <?php echo e($taxa->ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo e($taxa->ativo ? 'Ativa' : 'Inativa'); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma taxa configurada</p>
        <?php endif; ?>
    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('financeiro.adquirentes.show.extra', $adquirente); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\adquirentes\show.blade.php ENDPATH**/ ?>