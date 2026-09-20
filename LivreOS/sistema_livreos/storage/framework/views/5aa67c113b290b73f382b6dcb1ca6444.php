

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['unlockType', 'unlockCode', 'readonly' => true]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['unlockType', 'unlockCode', 'readonly' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $patternId = 'pattern_view_' . md5($unlockCode . time());
?>

<?php if($unlockType && $unlockCode): ?>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha de Desbloqueio</label>
        
        <?php if($unlockType === 'pattern'): ?>
            <div>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Tipo: <strong>Padrão (Desenho/Android)</strong></p>
                <div id="<?php echo e($patternId); ?>" class="flex justify-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg"></div>
            </div>
        <?php elseif($unlockType === 'pin'): ?>
            <div>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Tipo: <strong>Senha Numérica (PIN) / Texto</strong></p>
                <div class="rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <code class="text-lg font-mono"><?php echo e($unlockCode); ?></code>
                </div>
            </div>
        <?php elseif($unlockType === 'none'): ?>
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    <strong>Biometria / Sem Senha</strong> - Cliente virá para desbloquear ou aparelho sem senha
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if($unlockType === 'pattern' && $unlockCode): ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
        (function() {
            const containerId = '<?php echo e($patternId); ?>';
            const container = document.getElementById(containerId);
            if (!container) return;
            
            // Aguardar o componente estar disponível
            function initPattern() {
                if (window.UnlockPattern) {
                    try {
                        const pattern = JSON.parse(<?php echo json_encode($unlockCode, 15, 512) ?>);
                        new UnlockPattern(containerId, {
                            rows: 3,
                            cols: 3,
                            readonly: true,
                            pattern: pattern
                        });
                    } catch (e) {
                        console.error('Erro ao carregar padrão:', e);
                    }
                } else {
                    setTimeout(initPattern, 100);
                }
            }
            // Aguardar DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPattern);
            } else {
                initPattern();
            }
        })();
    </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\unlock-pattern-view.blade.php ENDPATH**/ ?>