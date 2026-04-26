{{--
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name') }}</title>

    <!-- Scripts -->
    @include('partials.vite-assets')

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('theme');
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                const theme = savedTheme || systemTheme;
                if (document.documentElement && document.body) {
                    if (theme === 'dark') {
                        document.documentElement.classList.add('dark');
                        document.body.classList.add('dark', 'bg-gray-900');
                    } else {
                        document.documentElement.classList.remove('dark');
                        document.body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            } catch (e) {
                console.error('Erro ao aplicar tema:', e);
            }
        })();
    </script>

    {{-- Menu mobile: sidebar e backdrop funcionam com JS puro (não dependem do Alpine) --}}
    <style>
        @media (max-width: 1279px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.mobile-menu-open { transform: translateX(0) !important; width: min(290px, 85vw) !important; }
            /* Evita scroll do fundo quando o menu está aberto no celular; top é definido via JS para manter posição */
            html.mobile-menu-open, body.mobile-menu-open { overflow: hidden; height: 100%; }
            body.mobile-menu-open { position: fixed; width: 100%; left: 0; }
        }
        #sidebar-backdrop.is-open { opacity: 1; pointer-events: auto; touch-action: none; }
    </style>
    @if(function_exists('do_action'))
        <?php do_action('layout.head.before_close'); ?>
    @endif
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">
    @if(function_exists('do_action'))
        <?php do_action('layout.body.start'); ?>
    @endif

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen min-w-0 max-w-full overflow-x-hidden xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex min-h-screen min-w-0 flex-1 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="flex-1 min-w-0 w-full max-w-full p-4 mx-auto md:p-6 {{ !empty($fullWidth) ? '' : 'xl:max-w-[1536px]' }}">
                @if(function_exists('get_admin_notices') && count($notices = get_admin_notices()) > 0)
                <div class="mb-4 space-y-2">
                    @foreach($notices as $notice)
                    <div class="rounded-lg px-4 py-3 text-sm
                        @if($notice['type'] === 'success') bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400
                        @elseif($notice['type'] === 'error') bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-400
                        @elseif($notice['type'] === 'warning') bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400
                        @else bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400
                        @endif
                    ">
                        {{ $notice['message'] }}
                    </div>
                    @endforeach
                </div>
                @endif
                @yield('content')
            </div>
            <footer class="mt-auto border-t border-gray-200 py-4 dark:border-gray-700">
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    LivreOS - ERP Open Source Livre
                    -
                    <a href="https://www.livreos.com.br" target="_blank" rel="noopener noreferrer" class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 underline">
                        Site oficial
                    </a>
                </p>
            </footer>
        </div>

    </div>

</body>

{{-- Menu lateral mobile: toggle com JS puro para funcionar mesmo sem Alpine --}}
<script>
(function() {
    function initMobileMenu() {
        var btn = document.querySelector('[data-mobile-menu-toggle]');
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebar-backdrop');
        if (!btn || !sidebar || !backdrop) return;

        var savedScrollY = 0;
        function isOpen() { return sidebar.classList.contains('mobile-menu-open'); }
        function openMenu() {
            savedScrollY = window.scrollY || window.pageYOffset;
            document.documentElement.classList.add('mobile-menu-open');
            document.body.classList.add('mobile-menu-open');
            document.body.style.top = savedScrollY ? '-' + savedScrollY + 'px' : '';
            sidebar.classList.add('mobile-menu-open');
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
            if (window.Alpine && Alpine.store && Alpine.store('sidebar')) Alpine.store('sidebar').setMobileOpen(true);
        }
        function closeMenu() {
            sidebar.classList.remove('mobile-menu-open');
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('mobile-menu-open');
            document.body.classList.remove('mobile-menu-open');
            document.body.style.top = '';
            if (window.Alpine && Alpine.store && Alpine.store('sidebar')) Alpine.store('sidebar').setMobileOpen(false);
            window.scrollTo(0, savedScrollY);
        }

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (isOpen()) closeMenu(); else openMenu();
        });
        backdrop.addEventListener('click', closeMenu);
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1280 && isOpen()) closeMenu();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen()) closeMenu();
        });
        document.addEventListener('close-mobile-menu', closeMenu);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initMobileMenu);
    else initMobileMenu();
})();
</script>

@stack('scripts')

@if(($run_tarefas_ao_acessar ?? false) && auth()->check())
<script>
(function() {
    var cookieName = 'erp_tarefas_dia';
    var path = '{{ request()->getBasePath() ?: "/" }}';
    var hoje = new Date().toISOString().slice(0, 10);
    function getCookie(n) {
        var v = document.cookie.match('(^|;) ?' + n + '=([^;]*)(;|$)');
        return v ? v[2] : null;
    }
    if (getCookie(cookieName) === hoje) return;
    var url = '{{ url("/run-tarefas-interno") }}';
    fetch(url, { method: 'GET', credentials: 'same-origin', keepalive: true }).then(function() {
        var end = new Date();
        end.setHours(23, 59, 59, 999);
        var sec = Math.max(1, Math.floor((end - new Date()) / 1000));
        document.cookie = cookieName + '=' + hoje + '; path=' + path + '; max-age=' + sec + '; SameSite=Lax';
    }).catch(function() {});
})();
</script>
@endif
@if(function_exists('do_action'))
    <?php do_action('layout.body.end'); ?>
@endif
</html>
