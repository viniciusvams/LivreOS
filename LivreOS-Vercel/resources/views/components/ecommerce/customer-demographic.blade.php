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

@props(['countries' => []])

@php
    $defaultCountries = [
        [
            'name' => 'USA',
            'flag' => './images/country/country-01.svg',
            'customers' => '2,379',
            'percentage' => 79
        ],
        [
            'name' => 'France',
            'flag' => './images/country/country-02.svg',
            'customers' => '589',
            'percentage' => 23
        ],
    ];
    
    $countriesList = !empty($countries) ? $countries : $defaultCountries;
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
    <div class="flex justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Customers Demographic
            </h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                Number of customer based on country
            </p>
        </div>

         <!-- Dropdown Menu -->
         <x-common.dropdown-menu />
         <!-- End Dropdown Menu -->
    </div>

    <div class="border-gary-200 my-6 overflow-hidden rounded-2xl border bg-gray-50 px-4 py-6 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
        <div id="mapOne" class="mapOne map-btn -mx-4 -my-6 h-[212px] w-[252px] 2xsm:w-[307px] xsm:w-[358px] sm:-mx-6 md:w-[668px] lg:w-[634px] xl:w-[393px] 2xl:w-[554px]"></div>
    </div>

    <div class="space-y-5">
        @foreach($countriesList as $country)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-full max-w-8 items-center rounded-full">
                        <img src="{{ $country['flag'] }}" alt="{{ strtolower($country['name']) }}" />
                    </div>
                    <div>
                        <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ $country['name'] }}
                        </p>
                        <span class="block text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $country['customers'] }} Customers
                        </span>
                    </div>
                </div>

                <div class="flex w-full max-w-[140px] items-center gap-3">
                    <div class="relative block h-2 w-full max-w-[100px] rounded-sm bg-gray-200 dark:bg-gray-800">
                        <div 
                            class="absolute left-0 top-0 flex h-full items-center justify-center rounded-sm bg-brand-500 text-xs font-medium text-white"
                            style="width: {{ $country['percentage'] }}%"
                        ></div>
                    </div>
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                        {{ $country['percentage'] }}%
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
