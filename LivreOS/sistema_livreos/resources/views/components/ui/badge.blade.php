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


@props([
    'variant' => 'light',
    'size' => 'md',
    'color' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
])

@php
    $baseStyles = 'inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium capitalize';

    $sizeStyles = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
    ];

    $variants = [
        'light' => [
            'primary' => 'bg-blue-50 text-blue-500 dark:bg-blue-500/15 dark:text-blue-400',
            'success' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
            'error' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500',
            'warning' => 'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-orange-400',
            'info' => 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-500',
            'light' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80',
            'dark' => 'bg-gray-500 text-white dark:bg-white/5 dark:text-white',
        ],
        'solid' => [
            'primary' => 'bg-blue-500 text-white dark:text-white',
            'success' => 'bg-green-500 text-white dark:text-white',
            'error' => 'bg-red-500 text-white dark:text-white',
            'warning' => 'bg-yellow-500 text-white dark:text-white',
            'info' => 'bg-sky-500 text-white dark:text-white',
            'light' => 'bg-gray-400 dark:bg-white/5 text-white dark:text-white/80',
            'dark' => 'bg-gray-700 text-white dark:text-white',
        ],
    ];

    $sizeClass = $sizeStyles[$size] ?? $sizeStyles['md'];
    $colorStyles = $variants[$variant][$color] ?? $variants['light']['primary'];
@endphp

<span class="{{ $baseStyles }} {{ $sizeClass }} {{ $colorStyles }}" {{ $attributes }}>
    @if($startIcon)
        {!! $startIcon !!}
    @endif

    {{ $slot }}

    @if($endIcon)
        {!! $endIcon !!}
    @endif
</span>
