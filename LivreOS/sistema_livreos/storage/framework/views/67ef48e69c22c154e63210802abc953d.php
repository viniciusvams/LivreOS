




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
<?php $component->withAttributes(['pageTitle' => 'Videos']); ?>
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

    <div class="grid grid-cols-1 gap-5 sm:gap-6 xl:grid-cols-2">

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
<?php $component->withAttributes(['title' => 'Video Ratio 16:9']); ?>
                <?php if (isset($component)) { $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f = $attributes; } ?>
<?php $component = App\View\Components\Ui\YoutubeEmbed::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.youtube-embed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\YoutubeEmbed::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['videoId' => 'dQw4w9WgXcQ']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $attributes = $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $component = $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Video Ratio 4:3']); ?>
                <?php if (isset($component)) { $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f = $attributes; } ?>
<?php $component = App\View\Components\Ui\YoutubeEmbed::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.youtube-embed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\YoutubeEmbed::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['videoId' => 'dQw4w9WgXcQ','aspectRatio' => '4:3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $attributes = $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $component = $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Video Ratio 21:9']); ?>
                <?php if (isset($component)) { $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f = $attributes; } ?>
<?php $component = App\View\Components\Ui\YoutubeEmbed::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.youtube-embed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\YoutubeEmbed::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['videoId' => 'dQw4w9WgXcQ','aspectRatio' => '21:9']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $attributes = $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $component = $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
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
<?php $component->withAttributes(['title' => 'Video Ratio 1:1']); ?>
                <?php if (isset($component)) { $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f = $attributes; } ?>
<?php $component = App\View\Components\Ui\YoutubeEmbed::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.youtube-embed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ui\YoutubeEmbed::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['videoId' => 'dQw4w9WgXcQ','aspectRatio' => '1:1']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $attributes = $__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__attributesOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f)): ?>
<?php $component = $__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f; ?>
<?php unset($__componentOriginal6b744995c1f7de9fbfb65140d7cfc72f); ?>
<?php endif; ?>
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

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pages\ui-elements\videos.blade.php ENDPATH**/ ?>