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
    $campaigns = [
        [
            'creator' => ['name' => 'Wilson Gouse', 'imageUrl' => '/images/user/user-01.jpg'],
            'brand' => ['name' => 'Brand 1', 'logo' => '/images/brand/brand-01.svg'],
            'title' => 'Grow your brand by...',
            'type' => 'Ads campaign',
            'status' => 'Success',
        ],
        [
            'creator' => ['name' => 'Terry Franci', 'imageUrl' => '/images/user/user-02.jpg'],
            'brand' => ['name' => 'Brand 2', 'logo' => '/images/brand/brand-02.svg'],
            'title' => 'Make Better Ideas...',
            'type' => 'Ads campaign',
            'status' => 'Pending',
        ],
        [
            'creator' => ['name' => 'Alena Franci', 'imageUrl' => '/images/user/user-03.jpg'],
            'brand' => ['name' => 'Brand 3', 'logo' => '/images/brand/brand-03.svg'],
            'title' => 'Increase your website tra...',
            'type' => 'Ads campaign',
            'status' => 'Success',
        ],
        [
            'creator' => ['name' => 'Jocelyn Kenter', 'imageUrl' => '/images/user/user-04.jpg'],
            'brand' => ['name' => 'Brand 4', 'logo' => '/images/brand/brand-04.svg'],
            'title' => 'Digital Marketing that...',
            'type' => 'Ads campaign',
            'status' => 'Failed',
        ],
        [
            'creator' => ['name' => 'Brandon Philips', 'imageUrl' => '/images/user/user-05.jpg'],
            'brand' => ['name' => 'Brand 2', 'logo' => '/images/brand/brand-02.svg'],
            'title' => 'Self branding',
            'type' => 'Ads campaign',
            'status' => 'Success',
        ],
        [
            'creator' => ['name' => 'James Lipshutz', 'imageUrl' => '/images/user/user-06.jpg'],
            'brand' => ['name' => 'Brand 3', 'logo' => '/images/brand/brand-03.svg'],
            'title' => 'Increase your website tra...',
            'type' => 'Ads campaign',
            'status' => 'Success',
        ],
    ];

    function getStatusClass($status) {
        $baseClasses = 'rounded-full px-2 text-theme-xs font-medium';
        switch ($status) {
            case 'Success':
                return "$baseClasses bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500";
            case 'Pending':
                return "$baseClasses bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400";
            case 'Failed':
                return "$baseClasses bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500";
            default:
                return $baseClasses;
        }
    }
@endphp

<div
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
>
    <div class="flex justify-between gap-2 mb-4 sm:items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Featured Campaigns</h3>
        </div>

        <div class="relative"></div>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <!-- table header start -->
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    <th class="py-3 font-normal">
                        <div class="flex items-center">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">Creator</p>
                        </div>
                    </th>
                    <th class="py-3 font-normal">
                        <div class="flex items-center">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">Campaign</p>
                        </div>
                    </th>
                    <th class="py-3 font-normal">
                        <div class="flex items-center">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">Status</p>
                        </div>
                    </th>
                </tr>
            </thead>
            <!-- table header end -->

            <!-- table body start -->
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($campaigns as $campaign)
                    <tr>
                        <td class="py-3">
                            <div class="flex items-center gap-[18px]">
                                <div class="w-10 h-10 overflow-hidden rounded-full">
                                    <img src="{{ $campaign['creator']['imageUrl'] }}" alt="{{ $campaign['creator']['name'] }}" />
                                </div>
                                <div>
                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                        {{ $campaign['creator']['name'] }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="flex items-center">
                                <div class="flex items-center w-full gap-5">
                                    <div class="w-full max-w-8">
                                        <img src="{{ $campaign['brand']['logo'] }}" class="size-8" alt="{{ $campaign['brand']['name'] }}" />
                                    </div>
                                    <div class="truncate">
                                        <p class="mb-0.5 truncate text-theme-sm font-medium text-gray-700 dark:text-gray-400">
                                            {{ $campaign['title'] }}
                                        </p>
                                        <span class="text-gray-500 text-theme-xs dark:text-gray-400">
                                            {{ $campaign['type'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="flex items-center">
                                <span class="{{ getStatusClass($campaign['status']) }}">
                                    {{ $campaign['status'] }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <!-- table body end -->
        </table>
    </div>
</div>