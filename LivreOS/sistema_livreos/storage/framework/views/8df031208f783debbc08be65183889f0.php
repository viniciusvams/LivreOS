

<?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Checkboxes']); ?>
    <div class="flex flex-wrap items-center gap-8">
        <div x-data="{ checkboxToggle: false }">
            <label for="checkboxLabelOne"
                class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="checkboxLabelOne" class="sr-only"
                        @change="checkboxToggle = !checkboxToggle" />
                    <div :class="checkboxToggle ? 'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                        class="f hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                        <span :class="checkboxToggle ? '' : 'opacity-0'">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>
                Default
            </label>
        </div>

        <div x-data="{ checkboxToggle: true }">
            <label for="checkboxLabelTwo"
                class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="checkboxLabelTwo" class="sr-only"
                        @change="checkboxToggle = !checkboxToggle" />
                    <div :class="checkboxToggle ? 'border-brand-500 bg-brand-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                        class="hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                        <span :class="checkboxToggle ? '' : 'opacity-0'">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>
                Checked
            </label>
        </div>

        <div x-data="{ checkboxToggle: true }">
            <label for="checkboxLabelThree"
                class="flex cursor-pointer items-center text-sm font-medium text-gray-300 select-none dark:text-gray-700">
                <div class="relative">
                    <input type="checkbox" id="checkboxLabelThree" class="peer sr-only"
                        @change="checkboxToggle = !checkboxToggle" disabled />
                    <div :class="checkboxToggle ? 'bg-transparent border-gray-200 dark:border-gray-800' :
                        'border-brand-500 bg-brand-500'"
                        class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                        <span :class="checkboxToggle ? '' : 'opacity-0'">
                            <svg class="stroke-gray-200 dark:stroke-gray-800" width="14" height="14"
                                viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="" stroke-width="2.33333"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>
                Disabled
            </label>
        </div>
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
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\form\form-elements\checkbox-component.blade.php ENDPATH**/ ?>