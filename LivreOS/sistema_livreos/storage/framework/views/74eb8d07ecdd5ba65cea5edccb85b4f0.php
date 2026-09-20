



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($plugin['name'] ?? $plugin['slug']); ?></h1>
        <p class="text-gray-600 dark:text-gray-400"><?php echo e($plugin['description'] ?? 'Sem descrição'); ?></p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?php echo e(route('configuracoes-sistema.plugins.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
        <?php if(!empty($plugin['active'])): ?>
            <form action="<?php echo e(route('configuracoes-sistema.plugins.deactivate', $plugin['slug'])); ?>" method="POST" class="inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Desativar</button>
            </form>
        <?php elseif(empty($plugin['requirements_error'])): ?>
            <form action="<?php echo e(route('configuracoes-sistema.plugins.activate', $plugin['slug'])); ?>" method="POST" class="inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Ativar</button>
            </form>
        <?php endif; ?>
        <?php if(empty($plugin['active'])): ?>
            <form action="<?php echo e(route('configuracoes-sistema.plugins.destroy', $plugin['slug'])); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este plugin?');" class="inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="rounded-lg border border-error-300 bg-error-50 px-4 py-2.5 text-sm font-medium text-error-700 hover:bg-error-100 dark:border-error-700 dark:bg-error-900/30 dark:text-error-400">Excluir</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($plugin['requirements_error'])): ?>
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
    <strong>Incompatível:</strong> <?php echo e($plugin['requirements_error']); ?>

</div>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Detalhes</h2>
    </div>
    <div class="p-6">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['name'] ?? '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                <dd class="mt-1 font-mono text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['slug'] ?? '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Versão</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['version'] ?? '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Autor</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['author'] ?? '-'); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    <?php if(!empty($plugin['active'])): ?>
                        <span class="inline-flex rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">Ativo</span>
                    <?php else: ?>
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php if(!empty($plugin['requires_php'])): ?>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Requer PHP</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['requires_php']); ?></dd>
            </div>
            <?php endif; ?>
            <?php if(!empty($plugin['requires_erp'])): ?>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Requer ERP</dt>
                <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['requires_erp']); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
        <?php if(!empty($plugin['description'])): ?>
        <div class="mt-6">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição</dt>
            <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?php echo e($plugin['description']); ?></dd>
        </div>
        <?php endif; ?>
        <div class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <strong>Desativação de emergência:</strong> Se um plugin causar erro ao carregar, renomeie a pasta do plugin no servidor (ex: <code>meu-plugin</code> → <code>meu-plugin.disabled</code>) para desativá-lo sem acessar o painel.
            </p>
        </div>
    </div>
</div>

<?php if(!empty($settingsView)): ?>
<?php echo $__env->make($settingsView, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\plugins\show.blade.php ENDPATH**/ ?>