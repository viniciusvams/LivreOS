



<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($title); ?></h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Carregue <strong>.docx</strong> (Word) ou <strong>.html</strong> / <strong>.htm</strong> com tags <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${nome_variavel}</code> (ex.: <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${proposta_descricao}</code>, <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${proposta_numero_sequencial}</code>, <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">${empresa_endereco}</code> — lista completa na ajuda abaixo). Na proposta: Word → descarregar ficheiro; HTML → abrir página no browser.</p>
    </div>
    <a href="<?php echo e(route('propostas-comerciais.modelos-documento.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo modelo</a>
</div>

<?php echo $__env->make('propostas-comerciais.modelos-documento._ajuda-docx', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">Ordem</th>
                    <th class="px-4 py-3 text-left">Nome</th>
                    <th class="px-4 py-3 text-left">Formato</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php $__empty_1 = true; $__currentLoopData = $modelos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3"><?php echo e($m->ordem); ?></td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($m->nome); ?></span>
                        <?php if($m->descricao): ?>
                        <p class="mt-0.5 text-xs text-gray-500"><?php echo e(\Illuminate\Support\Str::limit($m->descricao, 80)); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if($m->isHtml()): ?>
                        <span class="inline-flex items-center rounded-md bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-500/20 dark:text-sky-300" title="Modelo em HTML">HTML</span>
                        <?php else: ?>
                        <span class="inline-flex items-center rounded-md bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-500/20 dark:text-violet-300" title="Modelo Microsoft Word">DOC</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if($m->ativo): ?>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-500/20 dark:text-green-300">Ativo</span>
                        <?php else: ?>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?php echo e(route('propostas-comerciais.modelos-documento.edit', $m)); ?>" class="text-brand-600 hover:underline dark:text-brand-400">Editar</a>
                        <form method="POST" action="<?php echo e(route('propostas-comerciais.modelos-documento.destroy', $m)); ?>" class="ml-3 inline" onsubmit="return confirm('Remover este modelo?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="text-red-600 hover:underline dark:text-red-400">Excluir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhum modelo cadastrado.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($modelos->hasPages()): ?>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800"><?php echo e($modelos->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\propostas-comerciais\modelos-documento\index.blade.php ENDPATH**/ ?>