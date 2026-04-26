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
    $scriptPath = resource_path('js/unlock-pattern.js');
    $scriptContent = '';
    if (file_exists($scriptPath)) {
        $scriptContent = file_get_contents($scriptPath);
    }
@endphp
@if($scriptContent)
{!! $scriptContent !!}
@else
console.error('Arquivo unlock-pattern.js não encontrado');
@endif
