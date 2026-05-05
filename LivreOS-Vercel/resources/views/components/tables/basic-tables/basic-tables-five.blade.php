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


@php
    $orders = [
        [
            'product' => 'TailGrids',
            'category' => 'UI Kit',
            'countryFlag' => '/images/country/country-01.svg',
            'country' => 'USA',
            'cr' => 'Dashboard',
            'value' => '$12,499',
        ],
        [
            'product' => 'GrayGrids',
            'category' => 'Templates',
            'countryFlag' => '/images/country/country-03.svg',
            'country' => 'UK',
            'cr' => 'Dashboard',
            'value' => '$5,498',
        ],
        [
            'product' => 'Uideck',
            'category' => 'Templates',
            'countryFlag' => '/images/country/country-04.svg',
            'country' => 'Canada',
            'cr' => 'Dashboard',
            'value' => '$4,521',
        ],
        [
            'product' => 'FormBold',
            'category' => 'SaaS',
            'countryFlag' => '/images/country/country-05.svg',
            'country' => 'Australia',
            'cr' => 'Dashboard',
            'value' => '$13,843',
        ],
        [
            'product' => 'NextAdmin',
            'category' => 'Dashboard',
            'countryFlag' => '/images/country/country-06.svg',
            'country' => 'Germany',
            'cr' => 'Dashboard',
            'value' => '$7,523',
        ],
        [
            'product' => 'Form Builder',
            'category' => 'SaaS',
            'countryFlag' => '/images/country/country-07.svg',
            'country' => 'France',
            'cr' => 'Dashboard',
            'value' => '$1,377',
        ],
        [
            'product' => 'AyroUI',
            'category' => 'UI Kit',
            'countryFlag' => '/images/country/country-08.svg',
            'country' => 'Japan',
            'cr' => 'Dashboard',
            'value' => '$599,00',
        ],
    ];
@endphp

<div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]"
>
    <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Orders</h3>
        </div>

        <div class="flex items-center gap-3">
            <button
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            >
                <svg
                    class="stroke-current fill-white dark:fill-gray-800"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M2.29004 5.90393H17.7067"
                        stroke=""
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M17.7075 14.0961H2.29085"
                        stroke=""
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                        fill=""
                        stroke=""
                        stroke-width="1.5"
                    />
                    <path
                        d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                        fill=""
                        stroke=""
                        stroke-width="1.5"
                    />
                </svg>

                Filter
            </button>

            <button
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
            >
                See all
            </button>
        </div>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <!-- table header start -->
            <thead>
                <tr class="border-gray-100 border-y dark:border-white/[0.05]">
                    <th class="px-6 py-3">
                        <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Products</p>
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Category</p>
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center col-span-2">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Country</p>
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center col-span-2">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">CR</p>
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center col-span-2">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Value</p>
                        </div>
                    </th>
                </tr>
            </thead>
            <!-- table header end -->

            <!-- table body start -->
            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.05]">
                @foreach ($orders as $order)
                    <tr>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                    {{ $order['product'] }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $order['category'] }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center">
                                <div class="w-5 h-5 overflow-hidden rounded-full">
                                    <img src="{{ $order['countryFlag'] }}" alt="{{ $order['country'] }}" />
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                    {{ $order['cr'] }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center">
                                <p class="text-theme-sm text-success-600">{{ $order['value'] }}</p>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <!-- table body end -->
        </table>
    </div>
</div>