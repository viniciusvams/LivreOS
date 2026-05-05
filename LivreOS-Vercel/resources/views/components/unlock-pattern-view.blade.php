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

@props(['unlockType', 'unlockCode', 'readonly' => true])

@php
    $patternId = 'pattern_view_' . md5($unlockCode . time());
@endphp

@if($unlockType && $unlockCode)
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha de Desbloqueio</label>
        
        @if($unlockType === 'pattern')
            <div>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Tipo: <strong>Padrão (Desenho/Android)</strong></p>
                <div id="{{ $patternId }}" class="flex justify-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg"></div>
            </div>
        @elseif($unlockType === 'pin')
            <div>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Tipo: <strong>Senha Numérica (PIN) / Texto</strong></p>
                <div class="rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <code class="text-lg font-mono">{{ $unlockCode }}</code>
                </div>
            </div>
        @elseif($unlockType === 'none')
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    <strong>Biometria / Sem Senha</strong> - Cliente virá para desbloquear ou aparelho sem senha
                </p>
            </div>
        @endif
    </div>
@endif

@if($unlockType === 'pattern' && $unlockCode)
    @push('scripts')
    <script>
        (function() {
            const containerId = '{{ $patternId }}';
            const container = document.getElementById(containerId);
            if (!container) return;
            
            // Aguardar o componente estar disponível
            function initPattern() {
                if (window.UnlockPattern) {
                    try {
                        const pattern = JSON.parse(@json($unlockCode));
                        new UnlockPattern(containerId, {
                            rows: 3,
                            cols: 3,
                            readonly: true,
                            pattern: pattern
                        });
                    } catch (e) {
                        console.error('Erro ao carregar padrão:', e);
                    }
                } else {
                    setTimeout(initPattern, 100);
                }
            }
            // Aguardar DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPattern);
            } else {
                initPattern();
            }
        })();
    </script>
    @endpush
@endif
