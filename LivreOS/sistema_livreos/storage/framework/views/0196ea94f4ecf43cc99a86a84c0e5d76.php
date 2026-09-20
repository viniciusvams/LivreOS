



<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginald07245451647f5715f9bac44fc38d4f4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald07245451647f5715f9bac44fc38d4f4 = $attributes; } ?>
<?php $component = App\View\Components\Common\PageBreadcrumb::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.page-breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\PageBreadcrumb::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['pageTitle' => 'Badges']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald07245451647f5715f9bac44fc38d4f4)): ?>
<?php $attributes = $__attributesOriginald07245451647f5715f9bac44fc38d4f4; ?>
<?php unset($__attributesOriginald07245451647f5715f9bac44fc38d4f4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald07245451647f5715f9bac44fc38d4f4)): ?>
<?php $component = $__componentOriginald07245451647f5715f9bac44fc38d4f4; ?>
<?php unset($__componentOriginald07245451647f5715f9bac44fc38d4f4); ?>
<?php endif; ?>

    <?php
        use Illuminate\Support\HtmlString;
        $colors = ['primary', 'success', 'error', 'warning', 'info', 'light', 'dark'];

        $plusIcon = new HtmlString('<svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25012 3C5.25012 2.58579 5.58591 2.25 6.00012 2.25C6.41433 2.25 6.75012 2.58579 6.75012 3V5.25012L9.00034 5.25012C9.41455 5.25012 9.75034 5.58591 9.75034 6.00012C9.75034 6.41433 9.41455 6.75012 9.00034 6.75012H6.75012V9.00034C6.75012 9.41455 6.41433 9.75034 6.00012 9.75034C5.58591 9.75034 5.25012 9.41455 5.25012 9.00034L5.25012 6.75012H3C2.58579 6.75012 2.25 6.41433 2.25 6.00012C2.25 5.58591 2.58579 5.25012 3 5.25012H5.25012V3Z" fill=""></path>
    </svg>');
    ?>

    <div class="space-y-5 sm:space-y-6">
        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'With Light Background']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color)]); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'With Solid Background']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'variant' => 'solid']); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Light Background with Left Icon']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plusIcon)]); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Solid Background with Left Icon']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'variant' => 'solid','startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plusIcon)]); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Light Background with Right Icon']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plusIcon)]); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        <?php if (isset($component)) { $__componentOriginal6e5626e553feb0bf161f21170a535399 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e5626e553feb0bf161f21170a535399 = $attributes; } ?>
<?php $component = App\View\Components\Common\ComponentCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.component-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\ComponentCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Solid Background with Right Icon']); ?>
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                <?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginal1ed410a8a98c5bef16bedf3004907725 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ed410a8a98c5bef16bedf3004907725 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Badge::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Badge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($color),'variant' => 'solid','endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plusIcon)]); ?>
                        <?php echo e($color); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $attributes = $__attributesOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__attributesOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ed410a8a98c5bef16bedf3004907725)): ?>
<?php $component = $__componentOriginal1ed410a8a98c5bef16bedf3004907725; ?>
<?php unset($__componentOriginal1ed410a8a98c5bef16bedf3004907725); ?>
<?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pages\ui-elements\badges.blade.php ENDPATH**/ ?>