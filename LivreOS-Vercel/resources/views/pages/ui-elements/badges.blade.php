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
    <x-common.page-breadcrumb pageTitle="Badges" />

    @php
        use Illuminate\Support\HtmlString;
        $colors = ['primary', 'success', 'error', 'warning', 'info', 'light', 'dark'];

        $plusIcon = new HtmlString('<svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25012 3C5.25012 2.58579 5.58591 2.25 6.00012 2.25C6.41433 2.25 6.75012 2.58579 6.75012 3V5.25012L9.00034 5.25012C9.41455 5.25012 9.75034 5.58591 9.75034 6.00012C9.75034 6.41433 9.41455 6.75012 9.00034 6.75012H6.75012V9.00034C6.75012 9.41455 6.41433 9.75034 6.00012 9.75034C5.58591 9.75034 5.25012 9.41455 5.25012 9.00034L5.25012 6.75012H3C2.58579 6.75012 2.25 6.41433 2.25 6.00012C2.25 5.58591 2.58579 5.25012 3 5.25012H5.25012V3Z" fill=""></path>
    </svg>');
    @endphp

    <div class="space-y-5 sm:space-y-6">
        <x-common.component-card title="With Light Background">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>

        <x-common.component-card title="With Solid Background">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color" variant="solid">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>

        <x-common.component-card title="Light Background with Left Icon">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color" :startIcon="$plusIcon">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>

        <x-common.component-card title="Solid Background with Left Icon">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color" variant="solid" :startIcon="$plusIcon">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>

        <x-common.component-card title="Light Background with Right Icon">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color" :endIcon="$plusIcon">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>

        <x-common.component-card title="Solid Background with Right Icon">
            <div class="flex flex-wrap gap-4 sm:items-center sm:justify-center">
                @foreach ($colors as $color)
                    <x-ui.badge :color="$color" variant="solid" :endIcon="$plusIcon">
                        {{ $color }}
                    </x-ui.badge>
                @endforeach
            </div>
        </x-common.component-card>
    </div>
@endsection
