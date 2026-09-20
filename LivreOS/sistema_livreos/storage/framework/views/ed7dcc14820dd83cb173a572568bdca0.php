

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e($title); ?></h1>
    <p class="text-gray-600 dark:text-gray-400">Importe XML de NF-e (SEFAZ) para gerar contas a pagar, de-para de produtos e atualização de custo médio.</p>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-error-100 px-4 py-3 text-sm text-error-700 dark:bg-error-500/20 dark:text-error-400"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<?php if(session('warning')): ?>
<div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200"><?php echo e(session('warning')); ?></div>
<?php endif; ?>

<div class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Nova importação</h2>
    <form action="<?php echo e(route('plugin.compras.upload')); ?>" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
        <?php echo csrf_field(); ?>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Arquivo (.xml ou .pdf)</label>
            <input type="file" name="arquivo" accept=".xml,.pdf,application/xml,text/xml,application/pdf" required
                class="block w-full text-sm text-gray-800 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white dark:text-gray-300">
        </div>
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Enviar</button>
    </form>
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">XML: NF-e autorizada (nfeProc). PDF digital: extração em PHP (<code class="rounded bg-gray-100 px-1 dark:bg-gray-900">smalot/pdfparser</code> via Composer). Opcional: <strong>Poppler</strong> (<code class="rounded bg-gray-100 px-1 dark:bg-gray-900">ERP_POPPLER_BIN</code>). PDF escaneado: <strong>Tesseract</strong> + Poppler ou filtro <code class="rounded bg-gray-100 px-1 dark:bg-gray-900">compras.pdf.extrair</code>.</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Histórico</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">NF / Chave</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Fornecedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Usuário</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Data</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $importacoes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="text-sm text-gray-800 dark:text-gray-200">
                    <td class="px-4 py-3"><?php echo e($row->id); ?></td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-700"><?php echo e($row->status); ?></span>
                    </td>
                    <td class="px-4 py-3"><?php echo e($row->tipo_fonte); ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?php echo e($row->numero); ?>/<?php echo e($row->serie); ?> <?php if($row->chave_acesso): ?><br><span class="text-gray-500"><?php echo e(\Illuminate\Support\Str::limit($row->chave_acesso, 24)); ?></span><?php endif; ?></td>
                    <td class="px-4 py-3"><?php echo e(\Illuminate\Support\Str::limit($row->fornecedor_nome, 32)); ?></td>
                    <td class="px-4 py-3"><?php echo e($row->user?->name ?? '—'); ?></td>
                    <td class="px-4 py-3"><?php echo e($row->created_at?->format('d/m/Y H:i')); ?></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="<?php echo e(route('plugin.compras.revisar', $row)); ?>" class="text-brand-600 hover:underline dark:text-brand-400">Abrir</a>
                        <?php if($row->status === 'confirmada'): ?>
                        <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                        <a href="<?php echo e(route('plugin.compras.desfazer.form', $row)); ?>" class="text-red-600 hover:underline dark:text-red-400">Desfazer</a>
                        <?php else: ?>
                        <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                        <form action="<?php echo e(route('plugin.compras.excluir', $row)); ?>" method="post" class="inline" onsubmit="return confirm('Excluir esta importação (rascunho)? O arquivo enviado será removido.');">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-red-600 hover:underline dark:text-red-400">Excluir</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhuma importação ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if(method_exists($importacoes, 'links')): ?>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700"><?php echo e($importacoes->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\compras\views\index.blade.php ENDPATH**/ ?>