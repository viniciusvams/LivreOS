

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'name',
    'value',
    'checked' => false,
    'label',
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
    'id',
    'name',
    'value',
    'checked' => false,
    'label',
    'disabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<label for="<?php echo e($id); ?>"
    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'relative flex cursor-pointer select-none items-center gap-3 text-sm font-medium',
        'text-gray-300 dark:text-gray-600 cursor-not-allowed' => $disabled,
        'text-gray-700 dark:text-gray-400' => !$disabled,
        $attributes->get('class'),
    ]); ?>">
    
    <input 
        id="<?php echo e($id); ?>"
        name="<?php echo e($name); ?>"
        type="radio"
        value="<?php echo e($value); ?>"
        <?php echo e($checked ? 'checked' : ''); ?>

        <?php echo e($disabled ? 'disabled' : ''); ?>

        class="sr-only"
        <?php echo e($attributes->except(['class', 'label'])); ?>

    />
    
    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]',
        'border-brand-500 bg-brand-500' => $checked && !$disabled,
        'bg-transparent border-gray-300 dark:border-gray-700' => !$checked && !$disabled,
        'bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-700' => $disabled,
    ]); ?>">
        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'h-2 w-2 rounded-full bg-white',
            'block' => $checked,
            'hidden' => !$checked,
        ]); ?>"></span>
    </span>
    
    <?php echo e($label); ?>

</label><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\form\input\radio.blade.php ENDPATH**/ ?>