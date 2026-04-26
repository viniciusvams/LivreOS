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
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('propostas-comerciais.show', $proposta) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Editar proposta · {{ $proposta->referenciaGrupo() }}</h1>
</div>

@if($errors->any())
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">
    <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
@if(session('error'))
<div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-500/20 dark:text-red-400">{{ session('error') }}</div>
@endif

<form id="form-proposta-comercial"
      method="POST"
      action="{{ route('propostas-comerciais.update', $proposta) }}"
      data-cliente-update-url="{{ route('propostas-comerciais.cliente', $proposta) }}">
    @csrf
    @method('PUT')
    @include('propostas-comerciais._form')
    <div class="flex justify-end gap-3">
        <a href="{{ route('propostas-comerciais.show', $proposta) }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Cancelar</a>
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar alterações</button>
    </div>
</form>
@endsection
