



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Limites de Desconto</h1>
        <p class="text-gray-600 dark:text-gray-400">Defina os limites máximos de desconto (em valor e percentual) e os perfis (Roles) que devem respeitá-los. O administrador não está sujeito a esses limites e é o único que pode alterar esta configuração.</p>
    </div>
    <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <ul class="list-disc pl-4">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<form action="<?php echo e(route('configuracoes-sistema.limites-desconto.update')); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Valores dos limites</h2>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Estes limites se aplicam a descontos por item (produtos/serviços), ao desconto global da OS e ao desconto no encerramento. Deixe em branco para não limitar.</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Limite em valor (R$)</label>
                <input type="number" step="0.01" min="0" name="limite_valor" value="<?php echo e(old('limite_valor', $config->limite_valor)); ?>" placeholder="Ex: 100,00" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Desconto máximo em reais por item ou no total/encerramento.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Limite em percentual (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="limite_percentual" value="<?php echo e(old('limite_percentual', $config->limite_percentual)); ?>" placeholder="Ex: 15" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Desconto máximo em % sobre o valor do item ou do total.</p>
            </div>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Bloquear alteração de preço</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Quando ativo, os perfis sujeitos aos limites não poderão alterar o preço unitário de produtos ou serviços que vierem do cadastro (somente descontos permitidos pelos limites acima).</p>
            <label class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                <input type="checkbox" name="bloquear_alteracao_preco" value="1" <?php echo e(old('bloquear_alteracao_preco', $config->bloquear_alteracao_preco ?? false) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="font-medium text-gray-800 dark:text-gray-200">Bloquear alteração de preço de produtos/serviços cadastrados</span>
            </label>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Perfis (Roles) sujeitos aos limites</h2>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Selecione os perfis que devem respeitar os limites acima. Usuários com esses perfis não poderão aplicar descontos acima do permitido em itens, total ou encerramento. O administrador nunca está sujeito.</p>
            <div class="space-y-2">
                <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <label class="flex items-center gap-3 rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                        <input type="checkbox" name="role_ids[]" value="<?php echo e($role->id); ?>" <?php echo e(in_array($role->id, old('role_ids', $config->roles->pluck('id')->toArray())) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo e($role->name); ?></span>
                        <?php if($role->description): ?><span class="text-sm text-gray-500 dark:text-gray-400">— <?php echo e($role->description); ?></span><?php endif; ?>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-sm text-amber-600 dark:text-amber-400">Nenhum perfil cadastrado. Cadastre em Admin &gt; Roles.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('configuracoes.limites-desconto.form.extra', $config); ?>
        <?php endif; ?>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\limites-desconto\edit.blade.php ENDPATH**/ ?>