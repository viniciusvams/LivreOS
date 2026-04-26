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
    'src' => '',
    'alt' => 'User Avatar',
    'size' => 'medium',
    'status' => 'none',
])

@php
    $sizeClasses = [
        'xsmall' => 'h-6 w-6 max-w-6',
        'small' => 'h-8 w-8 max-w-8',
        'medium' => 'h-10 w-10 max-w-10',
        'large' => 'h-12 w-12 max-w-12',
        'xlarge' => 'h-14 w-14 max-w-14',
        'xxlarge' => 'h-16 w-16 max-w-16',
    ];

    $statusSizeClasses = [
        'xsmall' => 'h-1.5 w-1.5 max-w-1.5',
        'small' => 'h-2 w-2 max-w-2',
        'medium' => 'h-2.5 w-2.5 max-w-2.5',
        'large' => 'h-3 w-3 max-w-3',
        'xlarge' => 'h-3.5 w-3.5 max-w-3.5',
        'xxlarge' => 'h-4 w-4 max-w-4',
    ];

    $statusColorClasses = [
        'online' => 'bg-green-500',
        'offline' => 'bg-red-400',
        'busy' => 'bg-yellow-500',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $statusSizeClass = $statusSizeClasses[$size] ?? $statusSizeClasses['medium'];
    $statusColorClass = $statusColorClasses[$status] ?? '';
@endphp

<div class="relative rounded-full {{ $sizeClass }}">
    <img 
        src="{{ $src }}" 
        alt="{{ $alt }}" 
        class="h-full w-full object-cover rounded-full"
    />
    
    @if($status !== 'none')
        <span class="absolute bottom-0 right-0 rounded-full border-[1.5px] border-white dark:border-gray-900 {{ $statusSizeClass }} {{ $statusColorClass }}"></span>
    @endif
</div>