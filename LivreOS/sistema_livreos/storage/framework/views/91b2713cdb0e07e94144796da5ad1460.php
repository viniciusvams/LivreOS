



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Plugins</h1>
        <p class="text-gray-600 dark:text-gray-400">Instale e gerencie plugins para estender as funcionalidades do ERP. Envie um arquivo .zip contendo uma pasta com plugin.php na raiz. Plugins desativados podem ser excluídos.</p>
    </div>
    <a href="<?php echo e(route('configuracoes-sistema.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400">
    <?php echo e(session('error')); ?>

</div>
<?php endif; ?>

<?php if(session('warning')): ?>
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
    <?php echo e(session('warning')); ?>

</div>
<?php endif; ?>


<div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Instalar novo plugin</h2>
    <form action="<?php echo e(route('configuracoes-sistema.plugins.upload')); ?>" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
        <?php echo csrf_field(); ?>
        <div class="min-w-[220px] flex-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo .zip do plugin</label>
            <input type="file" name="plugin_zip" accept=".zip" required class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/30 dark:file:text-brand-400">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">O ZIP deve conter uma pasta com plugin.php na raiz (ex: meu-plugin/plugin.php). Máx. 10 MB. Para atualizar: desative o plugin e envie o novo .zip. Metadados opcionais no cabeçalho: Requires PHP, Requires ERP.</p>
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Instalar plugin
            </span>
        </button>
    </form>
</div>


<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Plugins instalados</h2>
        <?php if(count($plugins) > 0): ?>
        <form action="<?php echo e(route('configuracoes-sistema.plugins.bulk-action')); ?>" method="POST" id="bulk-form" class="flex items-center gap-2" onsubmit="if(!document.getElementById('bulk-action-select').value){alert('Selecione uma ação'); return false;} return true;">
            <?php echo csrf_field(); ?>
            <select name="action" id="bulk-action-select" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Ações em lote</option>
                <option value="activate">Ativar selecionados</option>
                <option value="deactivate">Desativar selecionados</option>
            </select>
            <button type="submit" class="rounded-lg bg-gray-200 px-3 py-1.5 text-sm hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600">Aplicar</button>
        </form>
        <?php endif; ?>
    </div>
    <?php if(count($plugins) > 0): ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <th class="w-10 px-4 py-3"></th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Plugin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Versão</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Autor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700 dark:text-gray-300">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                <?php $__currentLoopData = $plugins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slug => $plugin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-4">
                        <?php if(empty($plugin['requirements_error'])): ?>
                        <input type="checkbox" name="plugins[]" value="<?php echo e($slug); ?>" form="bulk-form" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <?php else: ?>
                        <span class="text-gray-300" title="<?php echo e($plugin['requirements_error']); ?>">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <a href="<?php echo e(route('configuracoes-sistema.plugins.show', $slug)); ?>" class="font-medium text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300"><?php echo e($plugin['name'] ?? $slug); ?></a>
                            <?php if(!empty($plugin['description'])): ?>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?php echo e(Str::limit($plugin['description'], 60)); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($plugin['requirements_error'])): ?>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400"><?php echo e($plugin['requirements_error']); ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?php echo e($plugin['version'] ?? '-'); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?php echo e($plugin['author'] ?? '-'); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if(!empty($plugin['active'])): ?>
                            <span class="inline-flex rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">Ativo</span>
                        <?php else: ?>
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-3">
                            <?php if(!empty($plugin['active'])): ?>
                                <form action="<?php echo e(route('configuracoes-sistema.plugins.deactivate', $slug)); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form action="<?php echo e(route('configuracoes-sistema.plugins.activate', $slug)); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300">Ativar</button>
                                </form>
                            <?php endif; ?>
                            <?php if(empty($plugin['active'])): ?>
                                <form action="<?php echo e(route('configuracoes-sistema.plugins.destroy', $slug)); ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este plugin? Os arquivos serão removidos permanentemente.');" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-error-600 hover:text-error-900 dark:text-error-400 dark:hover:text-error-300">Excluir</button>
                                </form>
                            <?php else: ?>
                                <span class="cursor-not-allowed text-gray-400 dark:text-gray-500" title="Desative o plugin antes de excluir">Excluir</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center">
        <p class="text-gray-500 dark:text-gray-400">Nenhum plugin instalado. Use o formulário acima para fazer upload de um plugin .zip.</p>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\configuracoes_sistema\plugins\index.blade.php ENDPATH**/ ?>