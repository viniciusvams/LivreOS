



<?php $__env->startSection('content'); ?>
<?php
    $currentYear = date('Y');
?>
  <div class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden z-1">
      
      <?php if (isset($component)) { $__componentOriginal167809b0e97e5fdccea89d87d579f7f1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal167809b0e97e5fdccea89d87d579f7f1 = $attributes; } ?>
<?php $component = App\View\Components\Common\CommonGridShape::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.common-grid-shape'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\CommonGridShape::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal167809b0e97e5fdccea89d87d579f7f1)): ?>
<?php $attributes = $__attributesOriginal167809b0e97e5fdccea89d87d579f7f1; ?>
<?php unset($__attributesOriginal167809b0e97e5fdccea89d87d579f7f1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal167809b0e97e5fdccea89d87d579f7f1)): ?>
<?php $component = $__componentOriginal167809b0e97e5fdccea89d87d579f7f1; ?>
<?php unset($__componentOriginal167809b0e97e5fdccea89d87d579f7f1); ?>
<?php endif; ?>
      <!-- Centered Content -->
      <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
          <h1 class="mb-8 font-bold text-gray-800 text-title-md dark:text-white/90 xl:text-title-2xl">
              ERROR
          </h1>

          <img src="/images/error/404.svg" alt="404" class="dark:hidden" />
          <img src="/images/error/404-dark.svg" alt="404" class="hidden dark:block" />

          <p class="mt-10 mb-6 text-base text-gray-700 dark:text-gray-400 sm:text-lg">
              We can't seem to find the page you are looking for!
          </p>

          <a href="/"
              class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
              Back to Home Page
          </a>
      </div>
      <!-- Footer -->
      <p class="absolute text-sm text-center text-gray-500 -translate-x-1/2 bottom-6 left-1/2 dark:text-gray-400">
          &copy; <?php echo e($currentYear); ?> - TailAdmin
      </p>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fullscreen-layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\pages\errors\error-404.blade.php ENDPATH**/ ?>