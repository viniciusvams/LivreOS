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
    tableRowData: [
        {
            id: 'DE124321',
            checked: false,
            customerName: 'John Doe',
            customerEmail: 'johndoe@gmail.com',
            initials: 'JD',
            avatarBg: 'bg-blue-100',
            avatarColor: 'text-blue-500',
            product: 'Software License',
            value: '$18,50.34',
            closeDate: '2024-06-15',
            status: 'Complete',
        },
        {
            id: 'DE124322',
            checked: false,
            customerName: 'Kierra Franci',
            customerEmail: 'kierra@gmail.com',
            initials: 'KF',
            avatarBg: 'bg-[#fdf2fa]',
            avatarColor: 'text-[#dd2590]',
            product: 'Software License',
            value: '$18,50.34',
            closeDate: '2024-06-15',
            status: 'Complete',
        },
        {
            id: 'DE124323',
            checked: false,
            customerName: 'Emerson Workman',
            customerEmail: 'emerson@gmail.com',
            initials: 'EW',
            avatarBg: 'bg-[#f0f9ff]',
            avatarColor: 'text-[#0086c9]',
            product: 'Software License',
            value: '$18,50.34',
            closeDate: '2024-06-15',
            status: 'Pending',
        },
        {
            id: 'DE124324',
            checked: false,
            customerName: 'Chance Philips',
            customerEmail: 'chance@gmail.com',
            initials: 'CP',
            avatarBg: 'bg-[#fff6ed]',
            avatarColor: 'text-[#ec4a0a]',
            product: 'Software License',
            value: '$18,50.34',
            closeDate: '2024-06-15',
            status: 'Complete',
        },
        {
            id: 'DE124325',
            checked: false,
            customerName: 'Terry Geidt',
            customerEmail: 'terry@gmail.com',
            initials: 'TG',
            avatarBg: 'bg-green-50',
            avatarColor: 'text-green-600',
            product: 'Software License',
            value: '$18,50.34',
            closeDate: '2024-06-15',
            status: 'Complete',
        },
    ],
    selectedRows: [],
    selectAll: false,
    handleSelectAll() {
        this.selectAll = !this.selectAll;
        if (this.selectAll) {
            this.selectedRows = this.tableRowData.map(row => row.id);
        } else {
            this.selectedRows = [];
        }
    },
    handleRowSelect(id) {
        if (this.selectedRows.includes(id)) {
            this.selectedRows = this.selectedRows.filter(rowId => rowId !== id);
        } else {
            this.selectedRows.push(id);
        }
    },
    getStatusClass(status) {
        const classes = {
            'Complete': 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500',
            'Pending': 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
            'Cancel': 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500',
        };
        return classes[status] || '';
    },
    deleteRow(id) {
        if (confirm('Are you sure you want to delete this order?')) {
            this.tableRowData = this.tableRowData.filter(row => row.id !== id);
            this.selectedRows = this.selectedRows.filter(rowId => rowId !== id);
        }
    }
}">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-white/[0.05] dark:bg-white/[0.03]">
        <!-- Header -->
        <div class="flex flex-col gap-4 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Recent Orders
                </h3>
            </div>
            <div class="flex items-center gap-3">
                <button class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z" fill="" stroke="" stroke-width="1.5"/>
                        <path d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z" fill="" stroke="" stroke-width="1.5"/>
                    </svg>
                    Filter
                </button>
                <button class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    See all
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="max-w-full overflow-x-auto">
            <table class="w-full">
                <thead class="px-6 py-3.5 border-t border-gray-100 border-y bg-gray-50 dark:border-white/[0.05] dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">
                            <div class="flex items-center gap-3">
                                <div @click="handleSelectAll()"
                                    class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px]"
                                    :class="selectAll ? 'border-blue-500 dark:border-blue-500 bg-blue-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700'">
                                    <svg :class="selectAll ? 'block' : 'hidden'" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Deal ID</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Customer</th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Product/Service</th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Deal Value</th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Close Date</th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 sm:px-6 text-theme-xs dark:text-gray-400 text-start">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in tableRowData" :key="row.id">
                        <tr class="border-b border-gray-100 dark:border-white/[0.05]">
                            <td class="px-4 sm:px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div @click="handleRowSelect(row.id)"
                                        class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px]"
                                        :class="selectedRows.includes(row.id) ? 'border-blue-500 dark:border-blue-500 bg-blue-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700'">
                                        <svg :class="selectedRows.includes(row.id) ? 'block' : 'hidden'" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400" x-text="row.id"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full font-medium text-sm"
                                        :class="[row.avatarBg, row.avatarColor]">
                                        <span x-text="row.initials"></span>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400" x-text="row.customerName"></span>
                                        <span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="row.customerEmail"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="row.product"></p>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="row.value"></p>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="row.closeDate"></p>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium" 
                                    :class="getStatusClass(row.status)" 
                                    x-text="row.status"></span>
                            </td>
                            <td class="px-4 sm:px-6 py-3.5">
                                <button @click="deleteRow(row.id)">
                                    <svg class="text-gray-700 cursor-pointer size-5 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-500" 
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>