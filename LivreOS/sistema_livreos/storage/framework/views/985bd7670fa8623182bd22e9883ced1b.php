

<?php
    $scriptPath = resource_path('js/unlock-pattern.js');
    $scriptContent = '';
    if (file_exists($scriptPath)) {
        $scriptContent = file_get_contents($scriptPath);
    }
?>
<?php if($scriptContent): ?>
<?php echo $scriptContent; ?>

<?php else: ?>
console.error('Arquivo unlock-pattern.js não encontrado');
<?php endif; ?>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\ordens_servico\_unlock_pattern_script.blade.php ENDPATH**/ ?>