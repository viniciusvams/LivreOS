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

@php
    $images = [
        [
            'src' => asset('images/grid-image/image-04.png'),
            'alt' => 'Grid image 1',
        ],
        [
            'src' => asset('images/grid-image/image-05.png'),
            'alt' => 'Grid image 2',
        ],
        [
            'src' => asset('images/grid-image/image-06.png'),
            'alt' => 'Grid image 3',
        ],
    ];
@endphp

@section('content')
    {{-- Page Breadcrumb --}}
    <x-common.page-breadcrumb pageTitle="Images" />

    <div class="space-y-5 sm:space-y-6">

        <x-common.component-card title="Responsive Image">
            <div class="relative">
                <div id="pane" class="overflow-hidden">
                    <img src="{{ asset('images/grid-image/image-01.png') }}" alt="Cover"
                        class="w-full border border-gray-200 rounded-xl dark:border-gray-800" />
                </div>
                <div id="ghostpane" class="absolute top-0 left-0 duration-300 ease-in-out"></div>
            </div>
        </x-common.component-card>

        <x-common.component-card title="Image in 2 Grid">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <img src="{{ asset('images/grid-image/image-02.png') }}" alt="grid"
                        class="w-full border border-gray-200 rounded-xl dark:border-gray-800" />
                </div>

                <div>
                    <img src="{{ asset('images/grid-image/image-03.png') }}" alt="grid"
                        class="w-full border border-gray-200 rounded-xl dark:border-gray-800" />
                </div>
            </div>
        </x-common.component-card>

        <x-common.component-card title="Image in 3 Grid">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                @foreach ($images as $image)
                    <div>
                        <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}"
                            class="w-full border border-gray-200 rounded-xl dark:border-gray-800" />
                    </div>
                @endforeach
            </div>
        </x-common.component-card>

    </div>
@endsection
