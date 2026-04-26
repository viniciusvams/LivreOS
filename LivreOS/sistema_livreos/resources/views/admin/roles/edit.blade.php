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
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Editar Role</h1>
</div>

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>

        <div class="mb-4">
            <label for="slug" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        </div>

        <div class="mb-4">
            <label for="description" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
            <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $role->description) }}</textarea>
        </div>

        <div class="mb-6">
            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Permissões (por perfil/módulo)</label>
            <div class="space-y-6">
                @foreach($permissionsGrouped as $groupName => $groupPermissions)
                <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <div class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $groupName }}</div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($groupPermissions as $permission)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $role->permissions->contains($permission->id) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if(function_exists('do_action'))
        <?php do_action('admin.roles.form.extra', $role); ?>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Atualizar Role</button>
            <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Cancelar</a>
        </div>
    </form>
</div>
@endsection
