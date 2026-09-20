



<?php $__env->startSection('content'); ?>
<?php
    $_auditServExcluir = app(\App\Services\AuditCancelExcluirService::class);
    $_podeExcluirServico = $_auditServExcluir->canExcluir(auth()->user(), 'servico');
?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Serviços</h1>
        <p class="text-gray-600 dark:text-gray-400">Cadastro e controle de serviços</p>
    </div>
    <a href="<?php echo e(route('servicos.create')); ?>" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Novo Serviço</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-5">
        <input type="text" name="nome" placeholder="Buscar por nome" value="<?php echo e(request('nome')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <input type="text" name="codigo_sku" placeholder="Buscar por SKU" value="<?php echo e(request('codigo_sku')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        <select name="categoria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">Categoria</option>
            <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoria): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($categoria->id); ?>" <?php echo e(request('categoria_id') == $categoria->id ? 'selected' : ''); ?>><?php echo e($categoria->nome); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <select name="ordenar_por" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="nome" <?php echo e(request('ordenar_por', 'nome') === 'nome' ? 'selected' : ''); ?>>Ordenar por nome</option>
            <option value="codigo_sku" <?php echo e(request('ordenar_por') === 'codigo_sku' ? 'selected' : ''); ?>>Ordenar por SKU</option>
            <option value="preco" <?php echo e(request('ordenar_por') === 'preco' ? 'selected' : ''); ?>>Ordenar por preço</option>
            <option value="updated_at" <?php echo e(request('ordenar_por') === 'updated_at' ? 'selected' : ''); ?>>Ordenar por atualização</option>
        </select>
        <select name="direcao" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="asc" <?php echo e(request('direcao', 'asc') === 'asc' ? 'selected' : ''); ?>>Crescente</option>
            <option value="desc" <?php echo e(request('direcao') === 'desc' ? 'selected' : ''); ?>>Decrescente</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Filtrar</button>
            <a href="<?php echo e(route('servicos.index')); ?>" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Limpar</a>
        </div>
        <select name="por_pagina" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <?php $__currentLoopData = [15,30,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($pp); ?>" <?php echo e((int) request('por_pagina', 15) === $pp ? 'selected' : ''); ?>><?php echo e($pp); ?> por página</option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </form>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.before_table', 'servico'); ?>
<?php endif; ?>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
        <div class="flex flex-wrap items-center gap-3">
            <span>Total: <?php echo e($servicos->total()); ?> serviços</span>
            <span>Página <?php echo e($servicos->currentPage()); ?> de <?php echo e($servicos->lastPage()); ?></span>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('servicos.export', request()->all())); ?>" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Exportar CSV</a>
            <a href="<?php echo e(route('servicos.export-pdf', request()->all())); ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Exportar PDF
            </a>
        </div>
    </div>

    <?php if($_podeExcluirServico): ?>
    <div id="serv_bulk_panel" class="hidden border-b border-gray-200 bg-amber-50/80 px-4 py-3 dark:border-gray-700 dark:bg-amber-900/20">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700 dark:text-gray-300"><span id="serv_bulk_selected_count">0</span> serviço(s) selecionado(s)</p>
            <button type="button" id="serv_bulk_destroy_btn" disabled onclick="abrirModalExcluirMotivoBulkServicos()" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                Excluir selecionados
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <tr>
                    <?php if($_podeExcluirServico): ?>
                    <th class="w-10 px-2 py-3">
                        <input type="checkbox" id="serv_bulk_select_all" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500" title="Selecionar todos desta página">
                    </th>
                    <?php endif; ?>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Código interno</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Nome</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">SKU</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Unidade</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Preço</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Categoria</th>
                    <th class="px-4 py-3 font-medium text-gray-600 dark:text-gray-400">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $servicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $servico): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <?php if($_podeExcluirServico): ?>
                    <td class="px-2 py-3">
                        <input type="checkbox" class="serv-bulk-checkbox rounded border-gray-300 text-brand-500 focus:ring-brand-500" value="<?php echo e($servico->id); ?>">
                    </td>
                    <?php endif; ?>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($servico->codigo_interno); ?></td>
                    <td class="px-4 py-3 text-gray-800 dark:text-white/90"><?php echo e($servico->nome); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($servico->codigo_sku ?: '-'); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($servico->unidade); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($servico->preco ? number_format($servico->preco, 2, ',', '.') : '-'); ?></td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400"><?php echo e($servico->categoria?->nome ?: '-'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="<?php echo e(route('servicos.show', $servico)); ?>" class="text-brand-500 hover:underline">Ver</a>
                            <a href="<?php echo e(route('servicos.edit', $servico)); ?>" class="text-brand-500 hover:underline">Editar</a>
                            <?php if($_podeExcluirServico): ?>
                            <button type="button" onclick="abrirModalExcluirMotivo('<?php echo e(route('servicos.destroy', $servico)); ?>')" class="text-error-500 hover:underline">Excluir</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e($_podeExcluirServico ? 8 : 7); ?>" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Nenhum serviço encontrado</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4">
        <?php echo e($servicos->links()); ?>

    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('entity.index.after_table', 'servico'); ?>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-excluir-motivo','data' => ['action' => '','titulo' => 'Excluir serviço','descricao' => 'Tem certeza que deseja excluir este serviço? Informe o motivo. Esta ação não pode ser desfeita.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal-excluir-motivo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => '','titulo' => 'Excluir serviço','descricao' => 'Tem certeza que deseja excluir este serviço? Informe o motivo. Esta ação não pode ser desfeita.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $attributes = $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $component = $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php if($_podeExcluirServico): ?>
<?php if (isset($component)) { $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal-excluir-motivo','data' => ['action' => route('servicos.bulk-destroy'),'titulo' => 'Excluir serviços em massa','descricao' => 'Os serviços selecionados nesta página serão excluídos permanentemente. Informe o motivo (mínimo 10 caracteres). Esta ação não pode ser desfeita.','idModal' => 'modalExcluirMotivoBulkServicos','idForm' => 'formExcluirMotivoBulkServicos','idMotivo' => 'motivoExcluirBulkServicos','useDeleteMethod' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal-excluir-motivo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('servicos.bulk-destroy')),'titulo' => 'Excluir serviços em massa','descricao' => 'Os serviços selecionados nesta página serão excluídos permanentemente. Informe o motivo (mínimo 10 caracteres). Esta ação não pode ser desfeita.','idModal' => 'modalExcluirMotivoBulkServicos','idForm' => 'formExcluirMotivoBulkServicos','idMotivo' => 'motivoExcluirBulkServicos','use-delete-method' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
     <?php $__env->slot('camposExtras', null, []); ?> 
        <input type="hidden" name="servico_ids_csv" id="servicos_bulk_ids_csv" value="">
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $attributes = $__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__attributesOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5)): ?>
<?php $component = $__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5; ?>
<?php unset($__componentOriginalcab7fd9f03f92aafb8fc56854afdeea5); ?>
<?php endif; ?>
<?php endif; ?>
<script>
function abrirModalExcluirMotivo(url) {
    document.getElementById('formExcluirMotivo').action = url;
    document.getElementById('motivoExcluir').value = '';
    document.getElementById('modalExcluirMotivo').classList.remove('hidden');
    document.getElementById('modalExcluirMotivo').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
<?php if($_podeExcluirServico): ?>
function abrirModalExcluirMotivoBulkServicos() {
    var checkboxes = Array.from(document.querySelectorAll('.serv-bulk-checkbox'));
    var selectedIds = checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    if (selectedIds.length === 0) {
        alert('Selecione pelo menos 1 serviço.');
        return;
    }
    var hidden = document.getElementById('servicos_bulk_ids_csv');
    if (hidden) hidden.value = selectedIds.join(',');
    document.getElementById('motivoExcluirBulkServicos').value = '';
    document.getElementById('modalExcluirMotivoBulkServicos').classList.remove('hidden');
    document.getElementById('modalExcluirMotivoBulkServicos').classList.add('flex');
    document.body.style.overflow = 'hidden';
}
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('serv_bulk_select_all');
    var checkboxes = Array.from(document.querySelectorAll('.serv-bulk-checkbox'));
    var countEl = document.getElementById('serv_bulk_selected_count');
    var panel = document.getElementById('serv_bulk_panel');
    var destroyBtn = document.getElementById('serv_bulk_destroy_btn');
    function updateServBulk() {
        var selected = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = selected;
        if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        if (panel) panel.classList.toggle('hidden', selected === 0);
        if (destroyBtn) destroyBtn.disabled = selected === 0;
    }
    checkboxes.forEach(function (cb) { cb.addEventListener('change', updateServBulk); });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            updateServBulk();
        });
    }
    updateServBulk();
});
<?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\servicos\index.blade.php ENDPATH**/ ?>