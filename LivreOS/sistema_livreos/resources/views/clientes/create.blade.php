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
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Novo Cliente</h1>
    <p class="text-gray-600 dark:text-gray-400">Preencha os dados abaixo para cadastrar um novo cliente</p>
</div>

@php
    $categorias = \App\Models\CategoriaDocumento::where('ativo', true)->orderBy('nome')->get();
@endphp

@include('clientes._form', ['categorias' => $categorias])
@endsection
