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

@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Alerts" />

    <div class="space-y-5 sm:space-y-6">
        {{-- Success Alert --}}
        <x-common.component-card title="Success Alert">
            <div class="space-y-4">
                <x-ui.alert
                    variant="success"
                    title="Success Message"
                    message="Be cautious when performing this action."
                    :showLink="true"
                    linkHref="/"
                    linkText="Learn more"
                />

                <x-ui.alert
                    variant="success"
                    title="Success Message"
                    message="Be cautious when performing this action."
                    :showLink="false"
                />
            </div>
        </x-common.component-card>

        {{-- Warning Alert --}}
        <x-common.component-card title="Warning Alert">
            <div class="space-y-4">
                <x-ui.alert
                    variant="warning"
                    title="Warning Message"
                    message="Be cautious when performing this action."
                    :showLink="true"
                    linkHref="/"
                    linkText="Learn more"
                />

                <x-ui.alert
                    variant="warning"
                    title="Warning Message"
                    message="Be cautious when performing this action."
                    :showLink="false"
                />
            </div>
        </x-common.component-card>

        {{-- Error Alert --}}
        <x-common.component-card title="Error Alert">
            <div class="space-y-4">
                <x-ui.alert
                    variant="error"
                    title="Error Message"
                    message="Be cautious when performing this action."
                    :showLink="true"
                    linkHref="/"
                    linkText="Learn more"
                />

                <x-ui.alert
                    variant="error"
                    title="Error Message"
                    message="Be cautious when performing this action."
                    :showLink="false"
                />
            </div>
        </x-common.component-card>

        {{-- Info Alert --}}
        <x-common.component-card title="Info Alert">
            <div class="space-y-4">
                <x-ui.alert
                    variant="info"
                    title="Info Message"
                    message="Be cautious when performing this action."
                    :showLink="true"
                    linkHref="/"
                    linkText="Learn more"
                />

                <x-ui.alert
                    variant="info"
                    title="Info Message"
                    message="Be cautious when performing this action."
                    :showLink="false"
                />
            </div>
        </x-common.component-card>

        {{-- Additional Examples --}}
        <x-common.component-card title="Alert Variations">
            <div class="space-y-4">
                {{-- With Slot Content --}}
                <x-ui.alert variant="success" title="Custom Content Alert">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        This alert uses <strong class="text-gray-900 dark:text-white">custom slot content</strong>
                        instead of the message prop.
                    </p>
                    <ul class="mt-2 text-sm text-gray-500 dark:text-gray-400 list-disc list-inside">
                        <li>You can add any HTML content</li>
                        <li>Including lists and formatting</li>
                        <li>Perfect for complex messages</li>
                    </ul>
                </x-alert>

                {{-- Minimal Alert --}}
                <x-ui.alert
                    variant="info"
                    title="Quick Info"
                    message="Sometimes you just need a simple message."
                />

                {{-- Alert with Long Message --}}
                <x-ui.alert
                    variant="warning"
                    title="Important Notice"
                    message="This is a longer message that provides more detailed information about the warning. You should read this carefully before proceeding with your action."
                    :showLink="true"
                    linkHref="/docs"
                    linkText="View documentation"
                />
            </div>
        </x-common.component-card>

        {{-- Interactive Demo --}}
        <x-common.component-card title="Real-World Examples">
            <div class="space-y-4">
                {{-- Payment Success --}}
                <x-ui.alert variant="success" title="Payment Successful">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        Your payment of <strong class="text-gray-900 dark:text-white">$99.00</strong> has been processed successfully.
                    </p>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <p><strong>Order ID:</strong> #TAILADMIN-0014</p>
                        <p><strong>Transaction ID:</strong> TXN-1234567890</p>
                    </div>
                    <a href="/orders" class="inline-block mt-3 text-sm font-medium text-green-600 dark:text-green-400 underline hover:text-green-700">
                        View Order Details
                    </a>
                </x-alert>

                {{-- Account Warning --}}
                <x-ui.alert
                    variant="warning"
                    title="Your trial is ending soon"
                    message="Your 14-day trial will expire in 3 days. Upgrade now to continue using all features."
                    :showLink="true"
                    linkHref="/billing"
                    linkText="Upgrade now"
                />

                {{-- Validation Error --}}
                <x-ui.alert variant="error" title="Form Validation Failed">
                    <ul class="text-sm text-gray-500 dark:text-gray-400 list-disc list-inside space-y-1">
                        <li>Email field is required</li>
                        <li>Password must be at least 8 characters</li>
                        <li>Please accept the terms and conditions</li>
                    </ul>
                </x-alert>

                {{-- System Info --}}
                <x-ui.alert
                    variant="info"
                    title="Scheduled Maintenance"
                    message="Our system will undergo maintenance on November 15, 2025 from 2:00 AM to 4:00 AM EST. Some features may be unavailable during this time."
                    :showLink="true"
                    linkHref="/status"
                    linkText="Check status page"
                />
            </div>
        </x-common.component-card>
    </div>
@endsection
