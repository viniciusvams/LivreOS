



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Admin</h1>
    <p class="text-gray-600 dark:text-gray-400">Bem-vindo à área administrativa</p>
</div>

<div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total de Usuários</p>
                <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(\App\Models\User::count()); ?></p>
            </div>
            <div class="rounded-full bg-brand-100 p-3 dark:bg-brand-500/20">
                <svg class="h-6 w-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total de Roles</p>
                <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(\App\Models\Role::count()); ?></p>
            </div>
            <div class="rounded-full bg-success-100 p-3 dark:bg-success-500/20">
                <svg class="h-6 w-6 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total de Permissões</p>
                <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(\App\Models\Permission::count()); ?></p>
            </div>
            <div class="rounded-full bg-warning-100 p-3 dark:bg-warning-500/20">
                <svg class="h-6 w-6 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Admins</p>
                <p class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90"><?php echo e(\App\Models\User::where('is_admin', true)->count()); ?></p>
            </div>
            <div class="rounded-full bg-error-100 p-3 dark:bg-error-500/20">
                <svg class="h-6 w-6 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<?php if(function_exists('do_action')): ?>
<?php do_action('admin.dashboard.cards'); ?>
<?php endif; ?>
<?php if(!empty($extraCards) && is_array($extraCards)): ?>
<?php $extraCards = collect($extraCards)->sortBy('order'); ?>
<div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
    <?php $__currentLoopData = $extraCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <?php if(!empty($card['title'])): ?>
        <h3 class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400"><?php echo e($card['title']); ?></h3>
        <?php endif; ?>
        <div class="text-gray-800 dark:text-white/90"><?php echo $card['content'] ?? ''; ?></div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Ações Rápidas</h2>
        <div class="space-y-3">
            <a href="<?php echo e(route('admin.users.index')); ?>" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                <span class="text-gray-700 dark:text-gray-300">Gerenciar Usuários</span>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <a href="<?php echo e(route('admin.roles.index')); ?>" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                <span class="text-gray-700 dark:text-gray-300">Gerenciar Roles</span>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            <a href="<?php echo e(route('admin.permissions.index')); ?>" class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                <span class="text-gray-700 dark:text-gray-300">Gerenciar Permissões</span>
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Usuários Recentes</h2>
        <div class="space-y-3">
            <?php $__currentLoopData = \App\Models\User::latest()->take(5)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 last:border-0 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                        <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                    </div>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-white/90"><?php echo e($user->name); ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($user->email); ?></p>
                    </div>
                </div>
                <?php if($user->is_admin): ?>
                <span class="rounded-full bg-brand-100 px-2 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">Admin</span>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\admin\dashboard.blade.php ENDPATH**/ ?>