

<div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-index="<?php echo e($index); ?>">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-medium text-gray-700 dark:text-gray-300">Contato <?php echo e($index + 1); ?></h3>
        <?php
            $naoRemovivel = !empty($contato['is_fornecedor'])
                || (!empty($contato['produto_fornecedores_count']) && $contato['produto_fornecedores_count'] > 0);
        ?>
        <button type="button" onclick="removerContato(this)" class="text-error-500 hover:text-error-700 disabled:cursor-not-allowed disabled:text-gray-400" <?php echo e($naoRemovivel ? 'disabled' : ''); ?>>Remover</button>
    </div>
    <?php if($naoRemovivel): ?>
        <div class="mb-3 rounded-lg border border-warning-100 bg-warning-50 px-3 py-2 text-xs text-warning-700 dark:border-warning-900/40 dark:bg-gray-900 dark:text-warning-300">
            Este contato está vinculado a um fornecedor/produto e não pode ser removido aqui.
        </div>
    <?php endif; ?>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <?php if(!empty($contato['id'])): ?>
            <input type="hidden" name="contatos[<?php echo e($index); ?>][id]" value="<?php echo e($contato['id']); ?>">
        <?php endif; ?>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
            <input type="text" name="contatos[<?php echo e($index); ?>][nome]" value="<?php echo e(old("contatos.{$index}.nome", $contato['nome'] ?? '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone</label>
            <input type="text" name="contatos[<?php echo e($index); ?>][telefone]" value="<?php echo e(old("contatos.{$index}.telefone", $contato['telefone'] ?? '')); ?>" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="contatos[<?php echo e($index); ?>][telefone_whatsapp]" value="1" <?php echo e(old("contatos.{$index}.telefone_whatsapp", $contato['telefone_whatsapp'] ?? false) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                WhatsApp
            </label>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone 2</label>
            <input type="text" name="contatos[<?php echo e($index); ?>][telefone2]" value="<?php echo e(old("contatos.{$index}.telefone2", $contato['telefone2'] ?? '')); ?>" class="js-phone-mask w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
            <input type="email" name="contatos[<?php echo e($index); ?>][email]" value="<?php echo e(old("contatos.{$index}.email", $contato['email'] ?? '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail 2</label>
            <input type="email" name="contatos[<?php echo e($index); ?>][email2]" value="<?php echo e(old("contatos.{$index}.email2", $contato['email2'] ?? '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo</label>
            <input type="text" name="contatos[<?php echo e($index); ?>][cargo]" value="<?php echo e(old("contatos.{$index}.cargo", $contato['cargo'] ?? '')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="contatos[<?php echo e($index); ?>][principal]" value="1" <?php echo e(old("contatos.{$index}.principal", $contato['principal'] ?? false) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Principal</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="contatos[<?php echo e($index); ?>][cobranca]" value="1" <?php echo e(old("contatos.{$index}.cobranca", $contato['cobranca'] ?? false) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Cobrança</span>
            </label>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\clientes\_contato.blade.php ENDPATH**/ ?>