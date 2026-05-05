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

@extends('layouts.fullscreen-layout')

@section('content')
<div class="min-h-screen bg-gray-100 px-4 py-8 dark:bg-gray-900">
    <div class="mx-auto w-full max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <h1 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Assinatura indisponível</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $message ?? 'Este link não está mais disponível.' }}</p>
    </div>
</div>
@endsection
