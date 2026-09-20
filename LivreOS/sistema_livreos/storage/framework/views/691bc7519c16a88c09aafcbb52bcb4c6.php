



<?php
    use Illuminate\Support\HtmlString;

    // Page title
    $currentPageTitle = 'Buttons';

    // Define BoxIcon once as an HtmlString (so it won’t get escaped)
    $BoxIcon = new HtmlString('
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M9.77692 3.24224C9.91768 3.17186 10.0834 3.17186 10.2241 3.24224L15.3713 5.81573L10.3359 8.33331C10.1248 8.43888 9.87626 8.43888 9.66512 8.33331L4.6298 5.81573L9.77692 3.24224ZM3.70264 7.0292V13.4124C3.70264 13.6018 3.80964 13.775 3.97903 13.8597L9.25016 16.4952L9.25016 9.7837C9.16327 9.75296 9.07782 9.71671 8.99432 9.67496L3.70264 7.0292ZM10.7502 16.4955V9.78396C10.8373 9.75316 10.923 9.71683 11.0067 9.67496L16.2984 7.0292V13.4124C16.2984 13.6018 16.1914 13.775 16.022 13.8597L10.7502 16.4955ZM9.41463 17.4831L9.10612 18.1002C9.66916 18.3817 10.3319 18.3817 10.8949 18.1002L16.6928 15.2013C17.3704 14.8625 17.7984 14.17 17.7984 13.4124V6.58831C17.7984 5.83076 17.3704 5.13823 16.6928 4.79945L10.8949 1.90059C10.3319 1.61908 9.66916 1.61907 9.10612 1.90059L9.44152 2.57141L9.10612 1.90059L3.30823 4.79945C2.63065 5.13823 2.20264 5.83076 2.20264 6.58831V13.4124C2.20264 14.17 2.63065 14.8625 3.30823 15.2013L9.10612 18.1002L9.41463 17.4831Z"
                fill="currentColor"
            />
        </svg>
    ');
?>

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
<?php $component->withAttributes(['pageTitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($currentPageTitle)]); ?>
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
<?php $component->withAttributes(['title' => 'Primary Button']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'primary']); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'primary']); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Primary Button with Left Icon']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'primary','startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'primary','startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Primary Button with Right Icon']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'primary','endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'primary','endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Outline Button']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'outline']); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'outline']); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Outline Button with Left Icon']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'outline','startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'outline','startIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Outline Button with Right Icon']); ?>
            <div class="flex items-center gap-5">
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'outline','endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3 = $attributes; } ?>
<?php $component = App\View\Components\Ui\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'md','variant' => 'outline','endIcon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($BoxIcon)]); ?>Button Text <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $attributes = $__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__attributesOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3)): ?>
<?php $component = $__componentOriginal238d5211cc9d80261ec96f21f6b79bb3; ?>
<?php unset($__componentOriginal238d5211cc9d80261ec96f21f6b79bb3); ?>
<?php endif; ?>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pages\ui-elements\buttons.blade.php ENDPATH**/ ?>