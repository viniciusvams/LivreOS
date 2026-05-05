<?php

/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

/**
 * Template do select de Cliente (Portal) nos formulários de usuário.
 * Incluído via add_action('admin.users.form.extra', ...)
 */
$clientes = $clientes ?? \App\Models\Cliente::orderBy('nome')->get();
$selected = $selected ?? ($user ? ($user->cliente_id ?? null) : old('cliente_id'));
?>
<div class="mb-4">
    <label for="cliente_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente (Portal)</label>
    <select id="cliente_id" name="cliente_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500">
        <option value="">— Nenhum —</option>
        <?php foreach ($clientes as $c): ?>
        <option value="<?= e($c->id) ?>" <?= ($selected && (string)$selected === (string)$c->id) ? 'selected' : '' ?>>
            <?= e($c->nome ?? $c->razao_social ?? 'Cliente #' . $c->id) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
