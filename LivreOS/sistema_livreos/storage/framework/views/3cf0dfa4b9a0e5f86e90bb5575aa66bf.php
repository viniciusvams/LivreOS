



<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold text-gray-800 dark:text-white/90">Guia de Plugins para Desenvolvedores</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">Documentação técnica completa para criação de plugins no ERP.</p>
    </div>

    <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-900 dark:border-brand-900/40 dark:bg-brand-900/20 dark:text-brand-200">
        <strong>Dica:</strong> este conteúdo vem do arquivo <code class="rounded bg-brand-100 px-1 dark:bg-brand-900/30">docs/CRIANDO-PLUGINS.md</code> para manter o guia sempre atualizado para os devs.
    </div>

    <div class="prose prose-slate max-w-none dark:prose-invert prose-headings:scroll-mt-20 prose-table:w-full prose-th:text-left prose-th:font-semibold prose-td:align-top prose-pre:rounded-lg prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-700 prose-pre:bg-gray-50 dark:prose-pre:bg-gray-900">
        <?php echo $pluginsGuideHtml; ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ajuda\plugins.blade.php ENDPATH**/ ?>