

<?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Textarea input fields']); ?>
    <!-- Elements -->
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            Description
        </label>
        <textarea placeholder="Enter a description..." type="text" rows="6"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"></textarea>
    </div>

    <!-- Elements -->
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300 dark:text-white/15">
            Description
        </label>
        <textarea placeholder="Enter a description..." type="text" rows="6" disabled
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:shadow-focus-ring dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-0 focus:outline-hidden disabled:border-gray-100 disabled:bg-gray-50 disabled:placeholder:text-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:disabled:border-gray-800 dark:disabled:bg-white/[0.03] dark:disabled:placeholder:text-white/15"></textarea>
    </div>

    <!-- Elements -->
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            Description
        </label>
        <textarea placeholder="Enter a description..." type="text" rows="6"
            class="dark:bg-dark-900 border-error-300 shadow-theme-xs focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:focus:border-error-800 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"></textarea>
        <p class="text-theme-xs text-error-500">
            Please enter a message in the textarea.
        </p>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6e5626e553feb0bf161f21170a535399)): ?>
<?php $attributes = $__attributesOriginal6e5626e553feb0bf161f21170a535399; ?>
<?php unset($__attributesOriginal6e5626e553feb0bf161f21170a535399); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6e5626e553feb0bf161f21170a535399)): ?>
<?php $component = $__componentOriginal6e5626e553feb0bf161f21170a535399; ?>
<?php unset($__componentOriginal6e5626e553feb0bf161f21170a535399); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\form\form-elements\text-area-inputs.blade.php ENDPATH**/ ?>