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

<div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-campo-index="{{ $index }}">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Campo {{ $index + 1 }}</span>
        <button type="button" onclick="removerCampo(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">Remover</button>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Label *</label>
            <input type="text" name="campo_label_{{ $index }}" required value="{{ $campo['label'] ?? '' }}" class="campo-label w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Nome do técnico">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
            <select name="campo_tipo_{{ $index }}" required onchange="atualizarTipoCampo(this, {{ $index }})" class="campo-tipo w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="texto" {{ ($campo['tipo'] ?? 'texto') === 'texto' ? 'selected' : '' }}>Texto</option>
                <option value="numero" {{ ($campo['tipo'] ?? '') === 'numero' ? 'selected' : '' }}>Número</option>
                <option value="selecao" {{ ($campo['tipo'] ?? '') === 'selecao' ? 'selected' : '' }}>Seleção</option>
                <option value="checkbox" {{ ($campo['tipo'] ?? '') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                <option value="data" {{ ($campo['tipo'] ?? '') === 'data' ? 'selected' : '' }}>Data</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Obrigatório</label>
            <select name="campo_obrigatorio_{{ $index }}" class="campo-obrigatorio w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="0" {{ !($campo['obrigatorio'] ?? false) ? 'selected' : '' }}>Não</option>
                <option value="1" {{ ($campo['obrigatorio'] ?? false) ? 'selected' : '' }}>Sim</option>
            </select>
        </div>
        <div class="md:col-span-3 campo-opcoes {{ ($campo['tipo'] ?? '') === 'selecao' ? '' : 'hidden' }}">
            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Opções (uma por linha) *</label>
            <textarea name="campo_opcoes_{{ $index }}" rows="3" class="campo-opcoes-text w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Opção 1&#10;Opção 2&#10;Opção 3" {{ ($campo['tipo'] ?? '') === 'selecao' ? 'required' : '' }}>{{ isset($campo['opcoes']) ? implode("\n", $campo['opcoes']) : '' }}</textarea>
        </div>
    </div>
</div>
