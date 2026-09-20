



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
<?php $component->withAttributes(['pageTitle' => 'User Profile']); ?>
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
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-7">Profile</h3>
        <?php if (isset($component)) { $__componentOriginal87ac2316390badc35d9700a3a95ff747 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal87ac2316390badc35d9700a3a95ff747 = $attributes; } ?>
<?php $component = App\View\Components\Profile\ProfileCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('profile.profile-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Profile\ProfileCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal87ac2316390badc35d9700a3a95ff747)): ?>
<?php $attributes = $__attributesOriginal87ac2316390badc35d9700a3a95ff747; ?>
<?php unset($__attributesOriginal87ac2316390badc35d9700a3a95ff747); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal87ac2316390badc35d9700a3a95ff747)): ?>
<?php $component = $__componentOriginal87ac2316390badc35d9700a3a95ff747; ?>
<?php unset($__componentOriginal87ac2316390badc35d9700a3a95ff747); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginaldd44abfb299e47c71b2bedd66e9aca2b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldd44abfb299e47c71b2bedd66e9aca2b = $attributes; } ?>
<?php $component = App\View\Components\Profile\PersonalInfoCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('profile.personal-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Profile\PersonalInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldd44abfb299e47c71b2bedd66e9aca2b)): ?>
<?php $attributes = $__attributesOriginaldd44abfb299e47c71b2bedd66e9aca2b; ?>
<?php unset($__attributesOriginaldd44abfb299e47c71b2bedd66e9aca2b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldd44abfb299e47c71b2bedd66e9aca2b)): ?>
<?php $component = $__componentOriginaldd44abfb299e47c71b2bedd66e9aca2b; ?>
<?php unset($__componentOriginaldd44abfb299e47c71b2bedd66e9aca2b); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalab0f9f70e29dcb5ed74b6620c37b23ed = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab0f9f70e29dcb5ed74b6620c37b23ed = $attributes; } ?>
<?php $component = App\View\Components\Profile\AddressCard::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('profile.address-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Profile\AddressCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab0f9f70e29dcb5ed74b6620c37b23ed)): ?>
<?php $attributes = $__attributesOriginalab0f9f70e29dcb5ed74b6620c37b23ed; ?>
<?php unset($__attributesOriginalab0f9f70e29dcb5ed74b6620c37b23ed); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab0f9f70e29dcb5ed74b6620c37b23ed)): ?>
<?php $component = $__componentOriginalab0f9f70e29dcb5ed74b6620c37b23ed; ?>
<?php unset($__componentOriginalab0f9f70e29dcb5ed74b6620c37b23ed); ?>
<?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pages\profile.blade.php ENDPATH**/ ?>