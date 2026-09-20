


<?php
    use App\Helpers\MenuHelper;
    use App\Models\Empresa;

    $menuGroups = MenuHelper::getMenuGroups();
    $currentPath = request()->path();

    $empresa = Empresa::first();
    $empresaNome = $empresa?->nome ?: $empresa?->razao_social ?: config('app.name');
    $empresaLogoUrl = null;
    if ($empresa?->logo_path) {
        $logoPath = storage_path('app/public/' . $empresa->logo_path);
        if (file_exists($logoPath)) {
            $mime = @mime_content_type($logoPath) ?: 'image/png';
            $empresaLogoUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }
?>

<aside id="sidebar"
    class="fixed top-0 bottom-0 left-0 z-[100000] flex flex-col overflow-y-auto overflow-x-hidden overscroll-contain border-r border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 dark:text-gray-900 xl:z-[99999]"
    x-data="{
        openSubmenus: {},
        init() {
            // Auto-open Dashboard menu on page load
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            const currentPath = '<?php echo e($currentPath); ?>';

            <?php $__currentLoopData = $menuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $menuGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = $menuGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemIndex => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($item['subItems'])): ?>
                        // Check if any submenu item matches current path
                        <?php $__currentLoopData = $item['subItems']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            if (currentPath === '<?php echo e(ltrim($subItem['path'], '/')); ?>' ||
                                window.location.pathname === '<?php echo e($subItem['path']); ?>') {
                                this.openSubmenus['<?php echo e($groupIndex); ?>-<?php echo e($itemIndex); ?>'] = true;
                            } <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];

            // Close all other submenus when opening a new one
            if (newState) {
                this.openSubmenus = {};
            }

            this.openSubmenus[key] = newState;
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        isActive(path) {
            return window.location.pathname === path || '<?php echo e($currentPath); ?>' === path.replace(/^\//, '');
        }
    }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <!-- Botão fechar menu (mobile): visível quando menu aberto, evita depender do header coberto pela sidebar -->
    <div class="absolute top-3 right-3 xl:hidden">
        <button type="button"
            data-mobile-menu-close
            onclick="document.dispatchEvent(new CustomEvent('close-mobile-menu'))"
            class="flex items-center justify-center w-10 h-10 min-h-[44px] min-w-[44px] rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
            aria-label="Fechar menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Logo Section - Nome e logotipo da empresa cadastrada -->
    <div class="pt-8 pb-7 flex"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
        'xl:justify-center' :
        'justify-start'">
        <a href="/" class="flex items-center gap-2 overflow-hidden">
            <?php if($empresaLogoUrl): ?>
                
                <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                    src="<?php echo e($empresaLogoUrl); ?>" alt="<?php echo e($empresaNome); ?>" class="h-10 w-auto max-w-[120px] shrink-0 object-contain" />
                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                    class="truncate text-base font-semibold text-gray-800 dark:text-white/90"><?php echo e($empresaNome); ?></span>
                <img x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
                    src="<?php echo e($empresaLogoUrl); ?>" alt="<?php echo e($empresaNome); ?>" class="h-8 w-8 shrink-0 rounded object-contain"
                    width="32" height="32" />
            <?php else: ?>
                
                <template x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                    <span class="truncate text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($empresaNome); ?></span>
                </template>
                <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                        <?php echo e(strtoupper(mb_substr($empresaNome, 0, 1))); ?>

                    </span>
                </template>
            <?php endif; ?>
        </a>
    </div>

    <!-- Navigation Menu (overflow-y-auto com -webkit-overflow-scrolling: touch para rolagem suave no iOS) -->
    <div class="flex flex-col flex-1 min-h-0 duration-300 ease-linear sidebar-nav-scroll">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                <?php $__currentLoopData = $menuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $menuGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-4 text-xs uppercase flex leading-[20px] text-gray-400"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                            'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span><?php echo e($menuGroup['title']); ?></span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z" fill="currentColor"/>
                                </svg>
                            </template>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            <?php $__currentLoopData = $menuGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemIndex => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <?php if(isset($item['subItems'])): ?>
                                        <!-- Menu Item with Submenu -->
                                        <button @click="toggleSubmenu(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>)"
                                            class="menu-item group w-full"
                                            :class="[
                                                isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) ?
                                                'menu-item-active' : 'menu-item-inactive',
                                                !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                'xl:justify-center' : 'xl:justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span :class="isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) ?
                                                    'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                <?php echo MenuHelper::getIconSvg($item['icon']); ?>

                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                <?php echo e($item['name']); ?>

                                                <?php if(!empty($item['new'])): ?>
                                                    <span class="absolute right-10"
                                                        :class="isActive('<?php echo e($item['path'] ?? ''); ?>') ?
                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                        new
                                                    </span>
                                                <?php endif; ?>
                                            </span>

                                            <!-- Chevron Down Icon -->
                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="ml-auto w-5 h-5 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180 text-brand-500': isSubmenuOpen(<?php echo e($groupIndex); ?>,
                                                        <?php echo e($itemIndex); ?>)
                                                }"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                                            <ul class="mt-2 space-y-1 ml-9">
                                                <?php $__currentLoopData = $item['subItems']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <a href="<?php echo e($subItem['path']); ?>" class="menu-dropdown-item"
                                                            :class="isActive('<?php echo e($subItem['path']); ?>') ?
                                                                'menu-dropdown-item-active' :
                                                                'menu-dropdown-item-inactive'">
                                                            <?php echo e($subItem['name']); ?>

                                                            <span class="flex items-center gap-1 ml-auto">
                                                                <?php if(!empty($subItem['new'])): ?>
                                                                    <span
                                                                        :class="isActive('<?php echo e($subItem['path']); ?>') ?
                                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                                        new
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if(!empty($subItem['pro'])): ?>
                                                                    <span
                                                                        :class="isActive('<?php echo e($subItem['path']); ?>') ?
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-active' :
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-inactive'">
                                                                        pro
                                                                    </span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <!-- Simple Menu Item -->
                                        <a href="<?php echo e($item['path']); ?>" class="menu-item group"
                                            :class="[
                                                isActive('<?php echo e($item['path']); ?>') ? 'menu-item-active' :
                                                'menu-item-inactive',
                                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                'xl:justify-center' :
                                                'justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span
                                                :class="isActive('<?php echo e($item['path']); ?>') ? 'menu-item-icon-active' :
                                                    'menu-item-icon-inactive'">
                                                <?php echo MenuHelper::getIconSvg($item['icon']); ?>

                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                <?php echo e($item['name']); ?>

                                                <?php if(!empty($item['new'])): ?>
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-brand-500 text-white">
                                                        new
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </nav>

        <?php
            $sidebarWidget = function_exists('apply_filters') ? apply_filters('sidebar.widget', null) : null;
        ?>
        <?php if($sidebarWidget): ?>
        <div x-data x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" x-transition class="mt-auto">
            <?php echo $sidebarWidget; ?>

        </div>
        <?php endif; ?>
    </div>
</aside>


<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>