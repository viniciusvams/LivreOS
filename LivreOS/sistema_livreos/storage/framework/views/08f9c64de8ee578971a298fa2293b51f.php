


<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'light',
    'size' => 'md',
    'color' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
]));

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

foreach (array_filter(([
    'variant' => 'light',
    'size' => 'md',
    'color' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $baseStyles = 'inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium capitalize';

    $sizeStyles = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
    ];

    $variants = [
        'light' => [
            'primary' => 'bg-blue-50 text-blue-500 dark:bg-blue-500/15 dark:text-blue-400',
            'success' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
            'error' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500',
            'warning' => 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-orange-400',
            'info' => 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-500',
            'light' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80',
            'dark' => 'bg-gray-500 text-white dark:bg-white/5 dark:text-white',
        ],
        'solid' => [
            'primary' => 'bg-blue-500 text-white dark:text-white',
            'success' => 'bg-green-500 text-white dark:text-white',
            'error' => 'bg-red-500 text-white dark:text-white',
            'warning' => 'bg-yellow-500 text-white dark:text-white',
            'info' => 'bg-sky-500 text-white dark:text-white',
            'light' => 'bg-gray-400 dark:bg-white/5 text-white dark:text-white/80',
            'dark' => 'bg-gray-700 text-white dark:text-white',
        ],
    ];

    $sizeClass = $sizeStyles[$size] ?? $sizeStyles['md'];
    $colorStyles = $variants[$variant][$color] ?? $variants['light']['primary'];
?>

<span class="<?php echo e($baseStyles); ?> <?php echo e($sizeClass); ?> <?php echo e($colorStyles); ?>" <?php echo e($attributes); ?>>
    <?php if($startIcon): ?>
        <?php echo $startIcon; ?>

    <?php endif; ?>

    <?php echo e($slot); ?>


    <?php if($endIcon): ?>
        <?php echo $endIcon; ?>

    <?php endif; ?>
</span>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\ui\badge.blade.php ENDPATH**/ ?>