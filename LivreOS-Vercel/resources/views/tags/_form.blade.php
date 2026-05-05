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
    $isEdit = isset($tag) && $tag->exists;
    $tag = $isEdit ? $tag : new \App\Models\Tag();
@endphp

<form action="{{ $isEdit ? route('tags.update', $tag) : route('tags.store') }}" method="POST" id="tagForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-error-500">*</span></label>
                <input type="text" name="nome" id="nome" required value="{{ old('nome', $tag->nome) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            @if($isEdit && $tag->tipo === 'padrao')
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <input type="text" value="Padrão" disabled class="w-full rounded-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tags padrão do sistema não podem ser editadas</p>
            </div>
            @else
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <input type="text" value="Personalizado" disabled class="w-full rounded-lg border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <input type="hidden" name="tipo" value="personalizado">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Novas tags são sempre do tipo personalizado</p>
            </div>
            @endif

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea name="descricao" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('descricao', $tag->descricao) }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor de Fundo <span class="text-error-500">*</span></label>
                <div class="flex gap-2">
                    <input type="color" id="cor_fundo" value="{{ old('cor_fundo', $tag->cor_fundo ?: '#ffffff') }}" class="h-10 w-20 cursor-pointer rounded border border-gray-300 dark:border-gray-700">
                    <input type="text" name="cor_fundo" id="cor_fundo_text" value="{{ old('cor_fundo', $tag->cor_fundo ?: '#ffffff') }}" pattern="^#[0-9A-Fa-f]{6}$" required class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="#ffffff">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cor da Fonte <span class="text-error-500">*</span></label>
                <div class="flex gap-2">
                    <input type="color" id="cor_fonte" value="{{ old('cor_fonte', $tag->cor_fonte ?: '#000000') }}" class="h-10 w-20 cursor-pointer rounded border border-gray-300 dark:border-gray-700">
                    <input type="text" name="cor_fonte" id="cor_fonte_text" value="{{ old('cor_fonte', $tag->cor_fonte ?: '#000000') }}" pattern="^#[0-9A-Fa-f]{6}$" required class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="#000000">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Pré-visualização</label>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <button type="button" id="previewBtn" class="rounded px-4 py-2 text-sm font-medium" style="background-color: {{ old('cor_fundo', $tag->cor_fundo ?: '#ffffff') }}; color: {{ old('cor_fonte', $tag->cor_fonte ?: '#000000') }};">
                        {{ old('nome', $tag->nome ?: 'Nome da Tag') }}
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="ativo" value="1" {{ old('ativo', $tag->ativo ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
        </div>
    </div>

    @if(function_exists('do_action'))
    <?php do_action('admin.tags.form.extra', $tag ?? null); ?>
    @endif
    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const corFundo = document.getElementById('cor_fundo');
    const corFundoText = document.getElementById('cor_fundo_text');
    const corFonte = document.getElementById('cor_fonte');
    const corFonteText = document.getElementById('cor_fonte_text');
    const nome = document.getElementById('nome');
    const previewBtn = document.getElementById('previewBtn');
    const form = document.getElementById('tagForm');

    // Sincronizar color picker com input de texto
    corFundo.addEventListener('input', function() {
        corFundoText.value = this.value;
        updatePreview();
    });

    corFundoText.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            corFundo.value = this.value;
            updatePreview();
        }
    });

    corFonte.addEventListener('input', function() {
        corFonteText.value = this.value;
        updatePreview();
    });

    corFonteText.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            corFonte.value = this.value;
            updatePreview();
        }
    });

    nome.addEventListener('input', function() {
        updatePreview();
    });

    function updatePreview() {
        const nomeValue = nome.value || 'Nome da Tag';
        const corFundoValue = corFundoText.value || '#ffffff';
        const corFonteValue = corFonteText.value || '#000000';
        
        previewBtn.textContent = nomeValue;
        previewBtn.style.backgroundColor = corFundoValue;
        previewBtn.style.color = corFonteValue;
    }

    // Validação antes de enviar
    form.addEventListener('submit', function(e) {
        // Validar formato hexadecimal
        if (!/^#[0-9A-Fa-f]{6}$/.test(corFundoText.value)) {
            e.preventDefault();
            alert('A cor de fundo deve estar no formato hexadecimal (#RRGGBB)');
            corFundoText.focus();
            return false;
        }

        if (!/^#[0-9A-Fa-f]{6}$/.test(corFonteText.value)) {
            e.preventDefault();
            alert('A cor da fonte deve estar no formato hexadecimal (#RRGGBB)');
            corFonteText.focus();
            return false;
        }
    });

    // Inicializar preview
    updatePreview();
});
</script>
