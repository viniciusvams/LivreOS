



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plano de Contas e Centro de Custo - Encerramento de OS</h1>
    <p class="text-gray-600 dark:text-gray-400">Escolha em qual conta do plano de contas e centro de custo os lançamentos financeiros (contas a receber) serão registrados quando uma ordem de serviço for encerrada.</p>
</div>

<?php if(session('success')): ?>
<div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700 dark:border-green-800 dark:bg-green-500/10 dark:text-green-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
    <?php echo e(session('error')); ?>

</div>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('configuracoes-sistema.plano-conta-os.update')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="space-y-4">
            <div>
                <label for="plano_conta_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta para lançamentos do encerramento de OS</label>
                <select name="plano_conta_id" id="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Padrão (Receita de Serviços - código 1.1)</option>
                    <?php $__currentLoopData = $planoContasReceita; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pc->id); ?>" <?php echo e(($plano_conta_id ?? null) == $pc->id ? 'selected' : ''); ?>>
                            <?php echo e($pc->codigo ? $pc->codigo . ' - ' : ''); ?><?php echo e($pc->nome); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    Ao encerrar uma OS, o sistema cria contas a receber e movimentações financeiras. Esta configuração define em qual conta do plano de contas esses lançamentos serão classificados. Se deixar em branco, será usado o padrão "Receita de Serviços" (código 1.1).
                </p>
            </div>
            <div>
                <label for="centro_custo_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo para lançamentos do encerramento de OS</label>
                <select name="centro_custo_id" id="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhum (não classificar por centro de custo)</option>
                    <?php $__currentLoopData = $centrosCusto; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cc->id); ?>" <?php echo e(($centro_custo_id ?? null) == $cc->id ? 'selected' : ''); ?>>
                            <?php echo e($cc->codigo ? $cc->codigo . ' - ' : ''); ?><?php echo e($cc->nome); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                    Opcional. Define o centro de custo associado às contas a receber e movimentações geradas no encerramento da OS. Útil para relatórios por centro de custo.
                </p>
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
            <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\plano_conta_os.blade.php ENDPATH**/ ?>