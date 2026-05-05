<?php
/**
 * Template do checkbox Portal Suporte no formulário de cliente.
 * Incluído via add_action('admin.clientes.form.portal', ...)
 */
$cliente = $cliente ?? null;
$checked = $cliente ? ($cliente->portal_suporte ?? false) : (bool) old('portal_suporte');
?>
<div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Permissões Portal do Cliente</h2>
    <div class="flex gap-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="portal_suporte" value="1" <?= $checked ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Suporte</span>
        </label>
    </div>
</div>
