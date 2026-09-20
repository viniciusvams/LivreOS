

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['countries' => []]));

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

foreach (array_filter((['countries' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $defaultCountries = [
        [
            'name' => 'USA',
            'flag' => './images/country/country-01.svg',
            'customers' => '2,379',
            'percentage' => 79
        ],
        [
            'name' => 'France',
            'flag' => './images/country/country-02.svg',
            'customers' => '589',
            'percentage' => 23
        ],
    ];
    
    $countriesList = !empty($countries) ? $countries : $defaultCountries;
?>

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="flex justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Customers Demographic
            </h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                Number of customer based on country
            </p>
        </div>

         <!-- Dropdown Menu -->
         <?php if (isset($component)) { $__componentOriginaldf8d7ba635c170cbad1bf318f91ed504 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf8d7ba635c170cbad1bf318f91ed504 = $attributes; } ?>
<?php $component = App\View\Components\Common\DropdownMenu::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.dropdown-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\DropdownMenu::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf8d7ba635c170cbad1bf318f91ed504)): ?>
<?php $attributes = $__attributesOriginaldf8d7ba635c170cbad1bf318f91ed504; ?>
<?php unset($__attributesOriginaldf8d7ba635c170cbad1bf318f91ed504); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf8d7ba635c170cbad1bf318f91ed504)): ?>
<?php $component = $__componentOriginaldf8d7ba635c170cbad1bf318f91ed504; ?>
<?php unset($__componentOriginaldf8d7ba635c170cbad1bf318f91ed504); ?>
<?php endif; ?>
         <!-- End Dropdown Menu -->
    </div>

    <div class="border-gary-200 my-6 overflow-hidden rounded-2xl border bg-gray-50 px-4 py-6 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
        <div id="mapOne" class="mapOne map-btn -mx-4 -my-6 h-[212px] w-[252px] 2xsm:w-[307px] xsm:w-[358px] sm:-mx-6 md:w-[668px] lg:w-[634px] xl:w-[393px] 2xl:w-[554px]"></div>
    </div>

    <div class="space-y-5">
        <?php $__currentLoopData = $countriesList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-full max-w-8 items-center rounded-full">
                        <img src="<?php echo e($country['flag']); ?>" alt="<?php echo e(strtolower($country['name'])); ?>" />
                    </div>
                    <div>
                        <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            <?php echo e($country['name']); ?>

                        </p>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            <?php echo e($country['customers']); ?> Customers
                        </span>
                    </div>
                </div>

                <div class="flex w-full max-w-[140px] items-center gap-3">
                    <div class="relative block h-2 w-full max-w-[100px] rounded-sm bg-gray-200 dark:bg-gray-800">
                        <div 
                            class="absolute left-0 top-0 flex h-full items-center justify-center rounded-sm bg-brand-500 text-xs font-medium text-white"
                            style="width: <?php echo e($country['percentage']); ?>%"
                        ></div>
                    </div>
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        <?php echo e($country['percentage']); ?>%
                    </p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\ecommerce\customer-demographic.blade.php ENDPATH**/ ?>