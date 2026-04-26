<?php
/**
 * Template da exibição Portal Suporte na view de detalhes do cliente.
 * Incluído via add_action('admin.clientes.show.portal', ...)
 */
$cliente = $cliente ?? null;
if (!$cliente) return;
$portalSuporte = $cliente->portal_suporte ?? false;
?>
<div>
    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Portal Suporte</dt>
    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90"><?= $portalSuporte ? 'Sim' : 'Não' ?></dd>
</div>
