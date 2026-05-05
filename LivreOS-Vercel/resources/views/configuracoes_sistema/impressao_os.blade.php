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
    <a href="{{ route('configuracoes-sistema.index') }}" class="mb-4 inline-flex text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400">← Voltar às configurações</a>
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Impressão de Ordens de Serviço</h1>
    <p class="text-gray-600 dark:text-gray-400">Dois textos globais (valem para todas as OS). Cada um pode ser habilitado ou desabilitado nas impressões correspondentes.</p>
</div>

@if(session('success'))
<div class="mb-4 rounded-lg bg-success-100 px-4 py-3 text-sm text-success-700 dark:bg-success-500/20 dark:text-success-400">{{ session('success') }}</div>
@endif

@php
    $printAtivoVal = old('os_impressao_observacao_antes_assinatura_ativo', $os_impressao_observacao_antes_assinatura_ativo ? '1' : '0');
    $compAtivoVal = old('os_comprovante_observacao_antes_assinatura_ativo', $os_comprovante_observacao_antes_assinatura_ativo ? '1' : '0');
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form method="POST" action="{{ route('configuracoes-sistema.impressao-os.update') }}" id="form_impressao_os">
        @csrf
        @method('PUT')

        {{-- Impressão da OS (/print) --}}
        <div class="border-b border-gray-200 pb-8 dark:border-gray-700">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Observação antes das assinaturas (impressão da OS)</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Exibida em <code class="text-xs">/ordens-servico/…/print</code>, antes das linhas de assinatura.</p>
                </div>
                <div class="shrink-0">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Uso na impressão</p>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-600">
                        <button type="button" data-toggle-group="print" data-toggle-value="1" class="toggle-os-print rounded-md px-3 py-1.5 text-sm font-medium transition">Habilitar</button>
                        <button type="button" data-toggle-group="print" data-toggle-value="0" class="toggle-os-print rounded-md px-3 py-1.5 text-sm font-medium transition">Desabilitar</button>
                    </div>
                    <input type="hidden" name="os_impressao_observacao_antes_assinatura_ativo" id="input_os_impressao_ativo" value="{{ $printAtivoVal }}">
                </div>
            </div>
            <div>
                <label for="os_impressao_observacao_antes_assinatura" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Conteúdo</label>
                <textarea id="os_impressao_observacao_antes_assinatura" name="os_impressao_observacao_antes_assinatura" rows="6" class="js-jodit-impressao-os w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('os_impressao_observacao_antes_assinatura', $os_impressao_observacao_antes_assinatura) !!}</textarea>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Com <strong>habilitado</strong> e campo vazio, nada é exibido. Com <strong>desabilitado</strong>, o bloco não aparece mesmo que haja texto.</p>
            </div>
        </div>

        {{-- Comprovante OS encerrada --}}
        <div class="pt-8">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Observação antes das assinaturas (impressão de OS encerrada)</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Exibida no <code class="text-xs">/ordens-servico/…/comprovante</code>, antes das assinaturas.</p>
                </div>
                <div class="shrink-0">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Uso no comprovante</p>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-600">
                        <button type="button" data-toggle-group="comprovante" data-toggle-value="1" class="toggle-os-comprovante rounded-md px-3 py-1.5 text-sm font-medium transition">Habilitar</button>
                        <button type="button" data-toggle-group="comprovante" data-toggle-value="0" class="toggle-os-comprovante rounded-md px-3 py-1.5 text-sm font-medium transition">Desabilitar</button>
                    </div>
                    <input type="hidden" name="os_comprovante_observacao_antes_assinatura_ativo" id="input_os_comprovante_ativo" value="{{ $compAtivoVal }}">
                </div>
            </div>
            <div>
                <label for="os_comprovante_observacao_antes_assinatura" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Conteúdo</label>
                <textarea id="os_comprovante_observacao_antes_assinatura" name="os_comprovante_observacao_antes_assinatura" rows="6" class="js-jodit-impressao-os w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{!! old('os_comprovante_observacao_antes_assinatura', $os_comprovante_observacao_antes_assinatura) !!}</textarea>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Com <strong>habilitado</strong> e campo vazio, é usado o texto padrão: declaração de recebimento dos materiais reparados. Com <strong>desabilitado</strong>, esse bloco não é impresso.</p>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
            <a href="{{ route('configuracoes-sistema.index') }}" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

@include('partials.jodit-assets')
<script>
(function() {
    function styleToggleButtons() {
        var printVal = document.getElementById('input_os_impressao_ativo');
        var compVal = document.getElementById('input_os_comprovante_ativo');
        var pv = printVal ? printVal.value : '1';
        var cv = compVal ? compVal.value : '1';
        document.querySelectorAll('.toggle-os-print').forEach(function(btn) {
            var on = btn.getAttribute('data-toggle-value') === pv;
            btn.classList.toggle('bg-brand-500', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('shadow-sm', on);
            btn.classList.toggle('text-gray-700', !on);
            btn.classList.toggle('dark:text-gray-200', !on);
        });
        document.querySelectorAll('.toggle-os-comprovante').forEach(function(btn) {
            var on = btn.getAttribute('data-toggle-value') === cv;
            btn.classList.toggle('bg-brand-500', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('shadow-sm', on);
            btn.classList.toggle('text-gray-700', !on);
            btn.classList.toggle('dark:text-gray-200', !on);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        styleToggleButtons();

        document.querySelectorAll('[data-toggle-group="print"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var inp = document.getElementById('input_os_impressao_ativo');
                if (inp) inp.value = btn.getAttribute('data-toggle-value');
                styleToggleButtons();
            });
        });
        document.querySelectorAll('[data-toggle-group="comprovante"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var inp = document.getElementById('input_os_comprovante_ativo');
                if (inp) inp.value = btn.getAttribute('data-toggle-value');
                styleToggleButtons();
            });
        });

        if (typeof Jodit === 'undefined') return;
        document.querySelectorAll('.js-jodit-impressao-os').forEach(function(textarea) {
            if (textarea.jodit) return;
            var editor = Jodit.make(textarea, {
                theme: 'default',
                toolbarAdaptive: false,
                toolbarSticky: false,
                showCharsCounter: true,
                showWordsCounter: true,
                askBeforePasteHTML: false,
                askBeforePasteFromWord: false,
                defaultActionOnPaste: 'insert_clear_html',
                controls: {
                    font: {
                        list: {
                            'Arial,Helvetica,sans-serif': 'Arial',
                            'Tahoma,Geneva,sans-serif': 'Tahoma',
                            'Verdana,Geneva,sans-serif': 'Verdana',
                            'Times New Roman,Times,serif': 'Times New Roman'
                        }
                    }
                },
                buttons: [
                    'undo', 'redo', '|',
                    'paragraph', 'font', 'fontsize', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', '|',
                    'brush', 'eraser', '|',
                    'ul', 'ol', 'outdent', 'indent', '|',
                    'align', '|',
                    'table', 'link', 'image', '|',
                    'hr', 'copyformat', '|',
                    'fullsize', 'source'
                ],
                buttonsMD: [
                    'undo', 'redo', '|',
                    'paragraph', 'fontsize', '|',
                    'bold', 'italic', 'underline', '|',
                    'ul', 'ol', '|',
                    'align', 'link', '|',
                    'source'
                ],
                buttonsSM: [
                    'undo', 'redo', '|',
                    'bold', 'italic', 'underline', '|',
                    'ul', 'ol', '|',
                    'link', 'source'
                ],
                buttonsXS: [
                    'bold', 'italic', '|',
                    'ul', 'ol', '|',
                    'source'
                ],
                height: 280
            });
            editor.events.on('change', function() { textarea.value = editor.value; });
        });

        var form = document.getElementById('form_impressao_os');
        if (form) {
            form.addEventListener('submit', function() {
                document.querySelectorAll('.js-jodit-impressao-os').forEach(function(ta) {
                    if (ta.jodit && typeof ta.jodit.value === 'string') ta.value = ta.jodit.value;
                });
            });
        }
    });
})();
</script>
@endsection
