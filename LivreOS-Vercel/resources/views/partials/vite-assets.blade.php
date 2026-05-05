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
    Evita 500 quando manifest.json ou hot não existem (ex.: deploy sem npm run build).
    1) Vite normal (dev server ou manifest)
    2) Fallback: assets em public/build/assets/app* (manifest ausente mas build copiado incompleto)
    3) Último recurso: estilo mínimo + aviso (login ainda funciona)
--}}
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    @php
        $viteCss = glob(public_path('build/assets/app*.css')) ?: [];
        $viteJs = glob(public_path('build/assets/app*.js')) ?: [];
    @endphp
    @foreach ($viteCss as $abs)
        <link rel="stylesheet" href="{{ asset('build/assets/'.basename($abs)) }}">
    @endforeach
    @foreach ($viteJs as $abs)
        <script type="module" src="{{ asset('build/assets/'.basename($abs)) }}"></script>
    @endforeach
    @if ($viteCss === [] && $viteJs === [])
        <style>
            body { font-family: system-ui, sans-serif; margin: 0; }
            .livreos-vite-missing { background: #fef3c7; color: #92400e; padding: 12px 16px; text-align: center; font-size: 14px; border-bottom: 1px solid #f59e0b; }
        </style>
        <div class="livreos-vite-missing" role="alert">
            Assets de interface não encontrados (falta <code>public/build</code> com <code>manifest.json</code>).
            No servidor de desenvolvimento rode <code>npm run build</code> e envie a pasta <code>build</code> para a pasta pública do site.
            Caminho verificado: {{ public_path('build/manifest.json') }}
        </div>
    @endif
@endif
