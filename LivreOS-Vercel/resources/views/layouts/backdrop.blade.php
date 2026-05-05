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

{{-- Overlay do menu lateral no mobile. Visibilidade controlada por JS (classe .sidebar-backdrop-open). --}}
<div id="sidebar-backdrop"
    class="fixed inset-0 z-[99998] h-screen w-full bg-gray-900/50 opacity-0 pointer-events-none transition-opacity duration-200 xl:hidden"
    data-mobile-menu-backdrop
    aria-hidden="true"
    role="button"
    tabindex="-1"
    title="Fechar menu"></div>
