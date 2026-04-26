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
    $isEdit = isset($checklistModel) && $checklistModel->exists;
    $checklistModel = $isEdit ? $checklistModel : new \App\Models\ChecklistModel();
    $campos = old('campos', $checklistModel->campos ?? []);
@endphp

<form action="{{ $isEdit ? route('checklist-models.update', $checklistModel) : route('checklist-models.store') }}" method="POST" id="checklistModelForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Informações Básicas</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Checklist <span class="text-error-500">*</span></label>
                <input type="text" name="nome" required value="{{ old('nome', $checklistModel->nome) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Checklist de Instalação">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="ativo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="1" {{ old('ativo', $checklistModel->ativo ?? true) ? 'selected' : '' }}>Ativo</option>
                    <option value="0" {{ !old('ativo', $checklistModel->ativo ?? true) ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea name="descricao" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Descrição opcional do checklist">{{ old('descricao', $checklistModel->descricao) }}</textarea>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Campos do Formulário</h2>
            <button type="button" onclick="adicionarCampo()" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                + Adicionar Campo
            </button>
        </div>

        <div id="campos_container" class="space-y-4">
            @if(!empty($campos))
                @foreach($campos as $index => $campo)
                    @include('checklist_models._campo_item', ['index' => $index, 'campo' => $campo])
                @endforeach
            @endif
        </div>

        <input type="hidden" name="campos" id="campos_json" value="{{ json_encode($campos) }}" data-original-value="{{ json_encode($campos) }}">
    </div>

    @if(function_exists('do_action'))
    <?php do_action('admin.checklist-models.form.extra', $checklistModel ?? null); ?>
    @endif
    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('checklist-models.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
        <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
    </div>
</form>

<script>
let campoIndex = {{ count($campos) }};

function adicionarCampo() {
    const container = document.getElementById('campos_container');
    const index = campoIndex++;
    
    const html = `
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-campo-index="${index}">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Campo ${index + 1}</span>
                <button type="button" onclick="removerCampo(this)" class="text-error-500 hover:text-error-700 dark:text-error-400">Remover</button>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Label *</label>
                    <input type="text" name="campo_label_${index}" required class="campo-label w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Ex: Nome do técnico">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                    <select name="campo_tipo_${index}" required onchange="atualizarTipoCampo(this, ${index})" class="campo-tipo w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="texto">Texto</option>
                        <option value="numero">Número</option>
                        <option value="selecao">Seleção</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="data">Data</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Obrigatório</label>
                    <select name="campo_obrigatorio_${index}" class="campo-obrigatorio w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                <div class="md:col-span-3 campo-opcoes hidden">
                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">Opções (uma por linha) *</label>
                    <textarea name="campo_opcoes_${index}" rows="3" class="campo-opcoes-text w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    atualizarCamposJSON();
}

function removerCampo(btn) {
    btn.closest('[data-campo-index]').remove();
    atualizarCamposJSON();
}

function atualizarTipoCampo(select, index) {
    const wrapper = select.closest('[data-campo-index]');
    const opcoesWrap = wrapper.querySelector('.campo-opcoes');
    const opcoesText = wrapper.querySelector('.campo-opcoes-text');
    
    if (select.value === 'selecao') {
        opcoesWrap.classList.remove('hidden');
        if (opcoesText) {
            opcoesText.required = true;
        }
    } else {
        opcoesWrap.classList.add('hidden');
        if (opcoesText) {
            opcoesText.required = false;
            opcoesText.value = '';
        }
    }
    atualizarCamposJSON();
}

function atualizarCamposJSON() {
    const container = document.getElementById('campos_container');
    const campos = [];
    
    container.querySelectorAll('[data-campo-index]').forEach((item, index) => {
        const label = item.querySelector('.campo-label')?.value?.trim();
        const tipo = item.querySelector('.campo-tipo')?.value;
        const obrigatorio = item.querySelector('.campo-obrigatorio')?.value === '1';
        const opcoesText = item.querySelector('.campo-opcoes-text')?.value?.trim();
        
        if (!label || !tipo) return;
        
        const campo = {
            label: label,
            tipo: tipo,
            obrigatorio: obrigatorio,
        };
        
        if (tipo === 'selecao' && opcoesText) {
            campo.opcoes = opcoesText.split('\n').filter(o => o.trim()).map(o => o.trim());
        }
        
        campos.push(campo);
    });
    
    document.getElementById('campos_json').value = JSON.stringify(campos);
}

// Atualizar JSON ao modificar campos
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('campos_container');
    if (container) {
        container.addEventListener('input', function(e) {
            if (e.target.classList.contains('campo-label') || 
                e.target.classList.contains('campo-tipo') || 
                e.target.classList.contains('campo-obrigatorio') ||
                e.target.classList.contains('campo-opcoes-text')) {
                atualizarCamposJSON();
            }
        });
        
        // Inicializar campos existentes
        container.querySelectorAll('[data-campo-index]').forEach(item => {
            const select = item.querySelector('.campo-tipo');
            if (select) {
                const index = item.dataset.campoIndex;
                atualizarTipoCampo(select, index);
            }
        });
        
        atualizarCamposJSON();
    }
    
    // Validar antes de submeter
    document.getElementById('checklistModelForm')?.addEventListener('submit', function(e) {
        const camposJson = document.getElementById('campos_json').value || '[]';
        const campos = JSON.parse(camposJson);
        
        if (campos.length === 0) {
            e.preventDefault();
            alert('É necessário adicionar pelo menos um campo ao checklist.');
            return false;
        }
        
        // Validar campos de seleção
        for (let campo of campos) {
            if (campo.tipo === 'selecao' && (!campo.opcoes || campo.opcoes.length === 0)) {
                e.preventDefault();
                alert(`O campo "${campo.label}" do tipo Seleção deve ter pelo menos uma opção definida.`);
                return false;
            }
        }
        
        // Converter campos JSON para array antes de enviar
        const form = e.target;
        const camposInput = form.querySelector('input[name="campos"]');
        if (camposInput) {
            camposInput.value = camposJson;
        } else {
            // Criar input hidden se não existir
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'campos';
            hiddenInput.value = camposJson;
            form.appendChild(hiddenInput);
        }
    });
});
</script>
