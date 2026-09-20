

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Ordens de Compra</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Crie, aprove e receba compras usando fornecedores e produtos já cadastrados no ERP.</p>
    </div>
    <a href="<?php echo e(route('plugin.compras.index')); ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Voltar para NF-e</a>
</div>

<?php if(session('success')): ?>
<div class="mb-4 rounded-lg bg-emerald-100 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-300"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Nova ordem de compra</h2>
    <form action="<?php echo e(route('plugin.compras.ordens.criar')); ?>" method="POST" class="space-y-5">
        <?php echo csrf_field(); ?>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor</label>
                <select name="fornecedor_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="">Selecione</option>
                    <?php $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fornecedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($fornecedor->id); ?>"><?php echo e($fornecedor->nome ?: ($fornecedor->cliente->nome ?? 'Fornecedor')); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Previsão de entrega</label>
                <input type="date" name="data_previsao" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
            <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"></textarea>
        </div>

        <div class="rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-700">
            <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Itens</h3>
            <div id="itens-compra" class="space-y-3">
                <div class="grid gap-3 md:grid-cols-5">
                    <select name="itens[0][produto_id]" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
                        <option value="">Produto</option>
                        <?php $__currentLoopData = $produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($produto->id); ?>"><?php echo e($produto->nome); ?> (<?php echo e($produto->codigo_sku ?? 'SKU'); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="number" name="itens[0][quantidade]" step="0.0001" min="0.0001" placeholder="Qtd" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
                    <input type="number" name="itens[0][preco_unitario]" step="0.01" min="0" placeholder="Preço unitário" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
                    <input type="text" name="itens[0][fornecedor_codigo]" placeholder="Código fornecedor" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <input type="text" name="itens[0][observacoes]" placeholder="Obs." class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                </div>
            </div>
            <button type="button" onclick="addItemCompra()" class="mt-4 rounded-lg border border-brand-500 px-3 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">Adicionar item</button>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar ordem</button>
        </div>
    </form>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Ordens cadastradas</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">N°</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Fornecedor</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Total</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Data</th>
                    <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $ordens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ordem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200"><?php echo e($ordem->numero_ordem); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200"><?php echo e($ordem->fornecedor->nome ?? '—'); ?></td>
                    <td class="px-4 py-3 text-sm"><span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700"><?php echo e($ordem->status); ?></span></td>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">R$ <?php echo e(number_format((float) $ordem->valor_total, 2, ',', '.')); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200"><?php echo e($ordem->data_emissao?->format('d/m/Y')); ?></td>
                    <td class="px-4 py-3 text-right text-sm">
                        <a href="<?php echo e(route('plugin.compras.ordens.show', $ordem)); ?>" class="text-brand-600 hover:underline dark:text-brand-400">Abrir</a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nenhuma ordem registrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if(method_exists($ordens, 'links')): ?>
    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700"><?php echo e($ordens->links()); ?></div>
    <?php endif; ?>
</div>

<script>
let itemIndex = 1;
function addItemCompra() {
    const container = document.getElementById('itens-compra');
    const row = document.createElement('div');
    row.className = 'grid gap-3 md:grid-cols-5';
    row.innerHTML = `
        <select name="itens[${itemIndex}][produto_id]" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
            <option value="">Produto</option>
            <?php $__currentLoopData = $produtos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $produto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($produto->id); ?>"><?php echo e($produto->nome); ?> (<?php echo e($produto->codigo_sku ?? 'SKU'); ?>)</option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <input type="number" name="itens[${itemIndex}][quantidade]" step="0.0001" min="0.0001" placeholder="Qtd" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
        <input type="number" name="itens[${itemIndex}][preco_unitario]" step="0.01" min="0" placeholder="Preço unitário" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200" required>
        <input type="text" name="itens[${itemIndex}][fornecedor_codigo]" placeholder="Código fornecedor" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        <input type="text" name="itens[${itemIndex}][observacoes]" placeholder="Obs." class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
    `;
    container.appendChild(row);
    itemIndex++;
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\compras\views/ordens/index.blade.php ENDPATH**/ ?>