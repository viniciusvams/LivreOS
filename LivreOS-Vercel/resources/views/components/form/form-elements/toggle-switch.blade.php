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

<x-common.component-card title="Toggle switch input">
    <!-- Elements -->
    <div class="mb-6 flex flex-wrap items-center gap-6 sm:gap-8">
        <div x-data="{ switcherToggle: false }">
            <label for="toggle1"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle1" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Default
            </label>
        </div>

        <div x-data="{ switcherToggle: true }">
            <label for="toggle2"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle2" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Checked
            </label>
        </div>

        <div x-data="{ switcherToggle: false }">
            <label for="toggle3"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-400 select-none">
                <div class="relative">
                    <input type="checkbox" id="toggle3" class="sr-only" @change="switcherToggle = !switcherToggle"
                        disabled />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-100 dark:bg-gray-800'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-gray-50 duration-300 ease-linear">
                    </div>
                </div>

                Disabled
            </label>
        </div>
    </div>

    <!-- Elements -->
    <div class="flex flex-wrap items-center gap-6 sm:gap-8">
        <div x-data="{ switcherToggle: false }">
            <label for="toggle11"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle11" class="sr-only" @change="switcherToggle = !switcherToggle" />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-200 dark:bg-gray-800'"></div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Default
            </label>
        </div>

        <div x-data="{ switcherToggle: true }">
            <label for="toggle22"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                <div class="relative">
                    <input type="checkbox" id="toggle22" class="sr-only" @change="switcherToggle = !switcherToggle" />

                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-200 dark:bg-gray-800'"></div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-300 ease-linear">
                    </div>
                </div>

                Checked
            </label>
        </div>

        <div x-data="{ switcherToggle: false }">
            <label for="toggle33"
                class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-400 select-none">
                <div class="relative">
                    <input type="checkbox" id="toggle33" class="sr-only" @change="switcherToggle = !switcherToggle"
                        disabled />
                    <div class="block h-6 w-11 rounded-full"
                        :class="switcherToggle ? 'bg-gray-700 dark:bg-white/10' : 'bg-gray-100 dark:bg-gray-800'">
                    </div>
                    <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                        class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-gray-50 duration-300 ease-linear">
                    </div>
                </div>

                Disabled
            </label>
        </div>
    </div>
</x-common.component-card>
