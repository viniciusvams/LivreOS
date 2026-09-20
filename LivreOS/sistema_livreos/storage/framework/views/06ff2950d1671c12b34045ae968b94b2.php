

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'size' => 'md',          
    'variant' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
    'className' => '',
    'disabled' => false,
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
    'size' => 'md',          
    'variant' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
    'className' => '',
    'disabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // Base classes
    $base = 'inline-flex items-center justify-center font-medium gap-2 rounded-lg transition';

    // Size map
    $sizeMap = [
        'sm' => 'px-4 py-3 text-sm',
        'md' => 'px-5 py-3.5 text-sm',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    // Variant map
    $variantMap = [
        'primary' => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300',
        'outline' => 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300',
    ];
    $variantClass = $variantMap[$variant] ?? $variantMap['primary'];

    // disabled classes
    $disabledClass = $disabled ? 'cursor-not-allowed opacity-50' : '';

    // final classes (merge user className too)
    $classes = trim("{$base} {$sizeClass} {$variantClass} {$className} {$disabledClass}");
?>

<button
    <?php echo e($attributes->merge(['class' => $classes, 'type' => $attributes->get('type', 'button')])); ?>

    <?php if($disabled): ?> disabled <?php endif; ?>
>
    
    <?php if(isset($__env) && $slot->isEmpty() === false): ?> <?php endif; ?>

    <?php if (! empty(trim($__env->yieldContent('startIcon')))): ?>
        <span class="flex items-center">
            <?php echo $__env->yieldContent('startIcon'); ?>
        </span>
    <?php elseif($startIcon): ?>
        <span class="flex items-center"><?php echo $startIcon; ?></span>
    <?php endif; ?>

    
    <?php echo e($slot); ?>


    
    <?php if (! empty(trim($__env->yieldContent('endIcon')))): ?>
        <span class="flex items-center">
            <?php echo $__env->yieldContent('endIcon'); ?>
        </span>
    <?php elseif($endIcon): ?>
        <span class="flex items-center"><?php echo $endIcon; ?></span>
    <?php endif; ?>
</button>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\ui\button.blade.php ENDPATH**/ ?>