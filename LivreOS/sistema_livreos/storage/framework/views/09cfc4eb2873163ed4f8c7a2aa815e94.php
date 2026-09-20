

<?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Toggle switch input']); ?>
    <!-- Elements -->
    <div class="mb-6 flex flex-wrap items-center gap-6 sm:gap-8">
        <div x-data="{ switcherToggle: false }">
            <label for="toggle1"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle1" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Default
            </label>
        </div>

        <div x-data="{ switcherToggle: true }">
            <label for="toggle2"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle2" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Checked
            </label>
        </div>

        <div x-data="{ switcherToggle: false }">
            <label for="toggle3"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-400 select-none">
                <div class="relative">
                    <input type="checkbox" id="toggle3" class="sr-only" @change="switcherToggle = !switcherToggle"
                        disabled />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-100 dark:bg-gray-800'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-gray-50 duration-300 ease-linear">
                    </div>
                </div>

                Disabled
            </label>
        </div>
    </div>

    <!-- Elements -->
    <div class="flex flex-wrap items-center gap-6 sm:gap-8">
        <div x-data="{ switcherToggle: false }">
            <label for="toggle11"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle11" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-200 dark:bg-gray-800'"></div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Default
            </label>
        </div>

        <div x-data="{ switcherToggle: true }">
            <label for="toggle22"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle22" class="sr-only" @change="switcherToggle = !switcherToggle" />

                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-200 dark:bg-gray-800'"></div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Checked
            </label>
        </div>

        <div x-data="{ switcherToggle: false }">
            <label for="toggle33"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-400 select-none">
                <div class="relative">
                    <input type="checkbox" id="toggle33" class="sr-only" @change="switcherToggle = !switcherToggle"
                        disabled />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-100 dark:bg-gray-800'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-gray-50 duration-300 ease-linear">
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
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\form\form-elements\toggle-switch.blade.php ENDPATH**/ ?>