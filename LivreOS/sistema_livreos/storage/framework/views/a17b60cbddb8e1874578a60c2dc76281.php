

<div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Monthly Sales
        </h3>

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

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="chartOne" class="-ml-5 h-full min-w-[690px] pl-2 xl:min-w-full"></div>
    </div>
</div>


<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\ecommerce\monthly-sale.blade.php ENDPATH**/ ?>