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

@extends('pdv::layout-embed')

@section('content')
<div class="min-h-full">
    <div class="mb-4 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Histórico de Vendas</h1>
    </div>
    @include('pdv::_historico-vendas-conteudo', ['embed' => true])
</div>
@endsection
