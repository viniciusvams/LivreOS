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

{{-- Notification Dropdown Component - alimentado via hook header.notifications --}}
@php
    $notifications = function_exists('apply_filters') ? apply_filters('header.notifications', []) : [];
    $viewAllUrl = function_exists('apply_filters') ? apply_filters('header.notifications.view_all_url', route('notificacoes.index')) : route('notificacoes.index');
    // Contagem apenas de notificações do banco (com id), para o badge refletir só o que pode ser marcado como lida/excluída
    $count = count(array_filter($notifications, fn($n) => !empty($n['id'] ?? null)));
    $urlLerTodas = route('notificacoes.ler-todas');
@endphp
<div class="relative" x-data="{
    dropdownOpen: false,
    notifying: {{ $count > 0 ? 'true' : 'false' }},
    count: {{ $count }},
    urlLer: '{{ url('/notificacoes') }}',
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    },
    marcarLida(id) {
        fetch(this.urlLer + '/' + id + '/ler', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        const el = document.getElementById('notif-drop-' + id);
        if (el) el.remove();
        this.count = Math.max(0, this.count - 1);
        if (this.count === 0) this.notifying = false;
    },
    marcarTodas() {
        const csrf = document.querySelector('meta[name=&quot;csrf-token&quot;]');
        if (csrf) {
            fetch('{{ $urlLerTodas }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf.content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });
        }
        document.querySelectorAll('[id^=notif-drop-]').forEach(el => el.remove());
        this.count = 0;
        this.notifying = false;
    }
}" @click.away="closeDropdown()">

    <!-- Notification Button -->
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
    >
        <!-- Badge com contagem -->
        <span x-show="notifying" class="absolute -right-0.5 -top-0.5 z-10 flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-0.5 text-[10px] font-bold text-white leading-none">
            <span x-text="count > 9 ? '9+' : count"></span>
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-60"></span>
        </span>

        <!-- Bell Icon -->
        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z" fill=""/>
        </svg>
    </button>

    <!-- Dropdown -->
    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[240px] mt-[17px] flex w-[350px] flex-col rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[380px] lg:right-0"
        style="display: none; max-height: 520px;"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
            <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">
                Notificações
                @if($count > 0)
                <span class="ml-1.5 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">{{ $count }}</span>
                @endif
            </h5>
            <div class="flex items-center gap-2">
                @if($count > 0)
                <button @click="marcarTodas()" type="button"
                    class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                    Marcar todas lidas
                </button>
                @endif
                <button @click="closeDropdown()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300" type="button">
                    <svg class="fill-current h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" fill=""/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Lista -->
        <ul class="flex flex-col overflow-y-auto" style="max-height: 380px;">
            @forelse ($notifications as $notification)
            @php
                // Normaliza o item para suportar tanto o formato do sistema quanto o formato legado de plugins
                $nId      = $notification['id'] ?? null;
                $nTitulo  = $notification['titulo'] ?? ($notification['userName'] ?? 'Notificação');
                $nMensagem= $notification['mensagem'] ?? ($notification['action'] ?? null);
                $nUrl     = $notification['url'] ?? '#';
                $nIcone   = $notification['icone'] ?? 'info';
                $nTime    = $notification['time'] ?? '';
                $nElemId  = 'notif-drop-' . ($nId ?? $loop->index);
            @endphp
            <li id="{{ $nElemId }}" class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                    {{-- Ícone colorido --}}
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                        {{ $nIcone === 'alerta' ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                        {{ $nIcone === 'erro' ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        {{ $nIcone === 'sucesso' ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' : '' }}
                        {{ !in_array($nIcone, ['alerta','erro','sucesso']) ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                    ">
                        @if($nIcone === 'alerta')
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @elseif($nIcone === 'erro')
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>

                    {{-- Texto --}}
                    <div class="min-w-0 flex-1">
                        <a href="{{ $nUrl }}"
                           @if($nId) @click="marcarLida({{ $nId }})" @endif
                           class="block">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90 leading-snug">{{ $nTitulo }}</p>
                            @if($nMensagem)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $nMensagem }}</p>
                            @endif
                            @if($nTime)
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $nTime }}</p>
                            @endif
                        </a>
                    </div>

                    {{-- Botão dispensar (só para notificações com ID do banco) --}}
                    @if($nId)
                    <button type="button" @click="marcarLida({{ $nId }})"
                        title="Dispensar"
                        class="mt-0.5 shrink-0 rounded p-1 text-gray-300 hover:bg-gray-100 hover:text-gray-600 dark:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @endif
                </div>
            </li>
            @empty
            <li class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                <svg class="mx-auto mb-2 h-8 w-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Nenhuma notificação
            </li>
            @endforelse
        </ul>

        <!-- Ver todas -->
        <div class="border-t border-gray-100 p-3 dark:border-gray-800">
            <a href="{{ $viewAllUrl }}"
               @click="closeDropdown()"
               class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                Ver todas as notificações
            </a>
        </div>
    </div>
</div>
