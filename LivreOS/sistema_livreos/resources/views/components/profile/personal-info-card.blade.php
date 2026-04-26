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

<div x-data="{saveProfile(){
    console.log('Saving profile...');
}}">
    <div class="p-5 mb-6 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 lg:mb-6">
                    Personal Information
                </h4>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7 2xl:gap-x-32">
                    <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">First Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">Musharof</p>
                    </div>

                    <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Last Name</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">Chowdhury</p>
                    </div>

                    <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">
                            Email address
                        </p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                            randomuser@pimjo.com
                        </p>
                    </div>

                    <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Phone</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">+09 363 398 46</p>
                    </div>

                    <div>
                        <p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Bio</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">Team Manager</p>
                    </div>
                </div>
            </div>

            <button class="edit-button" @click="$dispatch('open-profile-info-modal')">
                <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z"
                        fill="" />
                </svg>
                Edit
            </button>
        </div>
    </div>
</div>
