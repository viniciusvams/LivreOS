

<div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-campo-index="<?php echo e($index); ?>">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Campo <?php echo e($index + 1); ?></span>
        <button type="button" onclick="removerCampo(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">Remover</button>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Label *</label>
            <input type="text" name="campo_label_<?php echo e($index); ?>" required value="<?php echo e($campo['label'] ?? ''); ?>" class="campo-label w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Nome do técnico">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
            <select name="campo_tipo_<?php echo e($index); ?>" required onchange="atualizarTipoCampo(this, <?php echo e($index); ?>)" class="campo-tipo w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="texto" <?php echo e(($campo['tipo'] ?? 'texto') === 'texto' ? 'selected' : ''); ?>>Texto</option>
                <option value="numero" <?php echo e(($campo['tipo'] ?? '') === 'numero' ? 'selected' : ''); ?>>Número</option>
                <option value="selecao" <?php echo e(($campo['tipo'] ?? '') === 'selecao' ? 'selected' : ''); ?>>Seleção</option>
                <option value="checkbox" <?php echo e(($campo['tipo'] ?? '') === 'checkbox' ? 'selected' : ''); ?>>Checkbox</option>
                <option value="data" <?php echo e(($campo['tipo'] ?? '') === 'data' ? 'selected' : ''); ?>>Data</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Obrigatório</label>
            <select name="campo_obrigatorio_<?php echo e($index); ?>" class="campo-obrigatorio w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="0" <?php echo e(!($campo['obrigatorio'] ?? false) ? 'selected' : ''); ?>>Não</option>
                <option value="1" <?php echo e(($campo['obrigatorio'] ?? false) ? 'selected' : ''); ?>>Sim</option>
            </select>
        </div>
        <div class="md:col-span-3 campo-opcoes <?php echo e(($campo['tipo'] ?? '') === 'selecao' ? '' : 'hidden'); ?>">
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Opções (uma por linha) *</label>
            <textarea name="campo_opcoes_<?php echo e($index); ?>" rows="3" class="campo-opcoes-text w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Opção 1&#10;Opção 2&#10;Opção 3" <?php echo e(($campo['tipo'] ?? '') === 'selecao' ? 'required' : ''); ?>><?php echo e(isset($campo['opcoes']) ? implode("\n", $campo['opcoes']) : ''); ?></textarea>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\checklist_models\_campo_item.blade.php ENDPATH**/ ?>