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

@php
    $modelo = $checklist->checklistModel;
    $campos = $modelo->campos ?? [];
    $respostas = $checklist->respostas ?? [];
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-700 dark:bg-gray-800" data-checklist-id="{{ $checklist->id }}">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $modelo->nome }}</h3>
            @if($modelo->descricao)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $modelo->descricao }}</p>
            @endif
        </div>
        <button type="button" onclick="if(confirm('Tem certeza que deseja remover este checklist?')) removerChecklist({{ $checklist->id }}, this.closest('[data-checklist-id]'))" class="text-error-500 hover:text-error-700 dark:text-error-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>

    <div class="checklist-form" data-checklist-model-id="{{ $modelo->id }}" data-checklist-answer-id="{{ $checklist->id }}">
        @csrf
        <input type="hidden" name="checklist_model_id" value="{{ $modelo->id }}">
        
        <div class="space-y-4">
            @foreach($campos as $index => $campo)
                <div class="checklist-field" data-field-index="{{ $index }}">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $campo['label'] ?? 'Campo ' . ($index + 1) }}
                        @if($campo['obrigatorio'] ?? false)
                            <span class="text-error-500">*</span>
                        @endif
                    </label>
                    
                    @if(($campo['tipo'] ?? 'texto') === 'texto')
                        <input type="text" 
                               name="respostas[{{ $index }}]" 
                               value="{{ $respostas[$index] ?? '' }}"
                               {{ ($campo['obrigatorio'] ?? false) ? 'required' : '' }}
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    @elseif($campo['tipo'] === 'numero')
                        <input type="number" 
                               step="0.01"
                               name="respostas[{{ $index }}]" 
                               value="{{ $respostas[$index] ?? '' }}"
                               {{ ($campo['obrigatorio'] ?? false) ? 'required' : '' }}
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    @elseif($campo['tipo'] === 'data')
                        <input type="date" 
                               name="respostas[{{ $index }}]" 
                               value="{{ $respostas[$index] ?? '' }}"
                               {{ ($campo['obrigatorio'] ?? false) ? 'required' : '' }}
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    
                    @elseif($campo['tipo'] === 'checkbox')
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="respostas[{{ $index }}]" 
                                   value="1"
                                   {{ ($respostas[$index] ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sim</label>
                        </div>
                    
                    @elseif($campo['tipo'] === 'selecao')
                        <select name="respostas[{{ $index }}]" 
                                {{ ($campo['obrigatorio'] ?? false) ? 'required' : '' }}
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Selecione...</option>
                            @foreach(($campo['opcoes'] ?? []) as $opcao)
                                <option value="{{ $opcao }}" {{ ($respostas[$index] ?? '') === $opcao ? 'selected' : '' }}>
                                    {{ $opcao }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
            <textarea name="observacoes" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $checklist->observacoes ?? '' }}</textarea>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" class="btn-salvar-checklist rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                Salvar Checklist
            </button>
        </div>
    </div>
</div>
