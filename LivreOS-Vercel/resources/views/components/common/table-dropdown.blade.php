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

<div x-data="{
    isOpen: false,
    popperInstance: null,
    init() {
        this.$nextTick(() => {
            this.popperInstance = createPopper(this.$refs.button, this.$refs.content, {
                placement: 'bottom-end',
                strategy: 'fixed',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 4],
                        },
                    },
                ],
            });
        });
    },
    toggle() {
        this.isOpen = !this.isOpen;
        if (this.popperInstance) {
            this.popperInstance.update();
        }
    }
}"
@click.away="isOpen = false">
    <div @click="toggle()" x-ref="button" class="cursor-pointer">
        {{ $button }}
    </div>

    <div class="z-50 fixed" x-ref="content">
        <div x-show="isOpen" x-cloak class="p-2 bg-white border border-gray-200 rounded-2xl shadow-lg dark:border-gray-800 dark:bg-gray-dark w-40">
            <div class="space-y-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                {{ $content }}
            </div>
        </div>
    </div>
</div>
