
<?php
    $prefix = $prefix ?? 'cliente_ac';
    $idSuffix = $idSuffix ?? '';
    $buscaId = $prefix . '_cliente_busca' . $idSuffix;
    $datalistId = $prefix . '_clientes_datalist' . $idSuffix;
    $selectId = $prefix . '_cliente_id' . $idSuffix;
    $required = $required ?? true;
    $selectedId = $selectedId ?? old('cliente_id');
    $showBloqueadoVendasHint = $showBloqueadoVendasHint ?? false;
    $label = $label ?? 'Cliente';
    $labelClass = $labelClass ?? 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $inputClass = $inputClass ?? 'mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $extraSelectAttributes = $extraSelectAttributes ?? '';
    $dispatchChangeOnSync = $dispatchChangeOnSync ?? false;
    $errorName = $errorName ?? 'cliente_id';
?>
<div>
    <label class="<?php echo e($labelClass); ?>" for="<?php echo e($buscaId); ?>"><?php echo e($label); ?> <?php if($required): ?><span class="text-red-500">*</span><?php endif; ?></label>
    <input
        type="text"
        id="<?php echo e($buscaId); ?>"
        list="<?php echo e($datalistId); ?>"
        placeholder="Digite nome, razão social, CPF ou CNPJ..."
        class="<?php echo e($inputClass); ?>"
        autocomplete="off"
    >
    <datalist id="<?php echo e($datalistId); ?>">
        <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $docCli = ($cliente->tipo_pessoa ?? '') === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                $rotuloBase = trim($cliente->nome . (($cliente->razao_social ?? '') ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                $bloqueadoHint = ($showBloqueadoVendasHint && !empty($cliente->bloqueado_vendas)) ? ' — bloqueado p/ vendas' : '';
                $rotuloCli = $rotuloBase . $bloqueadoHint;
            ?>
            <option value="<?php echo e($rotuloCli); ?>"></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </datalist>
    <select name="cliente_id" id="<?php echo e($selectId); ?>" <?php if($required): ?> required <?php endif; ?> class="hidden" <?php echo $extraSelectAttributes; ?>>
        <option value="">Selecione...</option>
        <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $docCli = ($cliente->tipo_pessoa ?? '') === 'F' ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? '');
                $rotuloBase = trim($cliente->nome . (($cliente->razao_social ?? '') ? ' - ' . $cliente->razao_social : '') . ($docCli ? ' - ' . $docCli : ''));
                $bloqueadoHint = ($showBloqueadoVendasHint && !empty($cliente->bloqueado_vendas)) ? ' — bloqueado p/ vendas' : '';
                $rotuloCli = $rotuloBase . $bloqueadoHint;
                $buscaCli = strtolower(trim(
                    $cliente->nome . ' ' .
                    ($cliente->razao_social ?? '') . ' ' .
                    ($cliente->cpf ?? '') . ' ' .
                    ($cliente->cnpj ?? '') .
                    ($bloqueadoHint !== '' ? ' bloqueado vendas' : '')
                ));
            ?>
            <option value="<?php echo e($cliente->id); ?>"
                    data-label="<?php echo e($rotuloCli); ?>"
                    data-search="<?php echo e($buscaCli); ?>"
                    data-grupo="<?php echo e($cliente->grupo_economico_id ?? ''); ?>"
                    <?php echo e((string) $selectedId !== '' && (int) $selectedId === (int) $cliente->id ? 'selected' : ''); ?>><?php echo e($rotuloCli); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = [$errorName];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const selectId = <?php echo json_encode($selectId, 15, 512) ?>;
        const buscaId = <?php echo json_encode($buscaId, 15, 512) ?>;
        const dispatchChange = <?php echo json_encode($dispatchChangeOnSync, 15, 512) ?>;
        const clienteSelect = document.getElementById(selectId);
        const clienteBusca = document.getElementById(buscaId);
        if (!clienteSelect || !clienteBusca) return;

        const labelToId = {};
        Array.from(clienteSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const label = (opt.dataset.label || opt.textContent || '').trim();
            if (label) labelToId[label] = opt.value;
        });

        const syncBuscaFromSelect = function () {
            const sel = clienteSelect.selectedOptions && clienteSelect.selectedOptions[0];
            if (sel && sel.value) {
                clienteBusca.value = sel.dataset.label || sel.textContent || '';
            }
        };

        const filtrar = function () {
            const q = (clienteBusca.value || '').toLowerCase().trim();
            Array.from(clienteSelect.options).forEach(function (opt) {
                if (!opt.value) return;
                const hay = (opt.dataset.search || (opt.textContent || '').toLowerCase());
                opt.hidden = q !== '' && !hay.includes(q);
            });
        };

        const syncSelectFromBusca = function () {
            const typed = clienteBusca.value.trim();
            const id = labelToId[typed];
            if (id) {
                clienteSelect.value = id;
                if (dispatchChange) {
                    clienteSelect.dispatchEvent(new Event('change'));
                }
            }
        };

        clienteBusca.addEventListener('input', function () {
            filtrar();
            syncSelectFromBusca();
        });
        clienteBusca.addEventListener('change', syncSelectFromBusca);
        clienteSelect.addEventListener('change', syncBuscaFromSelect);
        syncBuscaFromSelect();
    })();
});
</script>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\partials\cliente-autocomplete-field.blade.php ENDPATH**/ ?>