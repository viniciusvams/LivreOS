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

{{--
    Jodit 4.2.27 (build es2021) servido localmente em public/vendor/jodit/
    Atualizar ficheiros: npm install && npm run vendor:jodit
--}}
@php
    $joditV = '4.2.27';
@endphp
<link rel="stylesheet" href="{{ asset('vendor/jodit/jodit.min.css') }}?v={{ $joditV }}">
<script src="{{ asset('vendor/jodit/jodit.min.js') }}?v={{ $joditV }}"></script>
