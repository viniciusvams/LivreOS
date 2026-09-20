



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Conta a Pagar</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova conta a pagar</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('financeiro.contas-pagar.store')); ?>" method="POST" class="p-6" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor / Cliente</label>
                <input
                    type="text"
                    id="conta_pagar_fornecedor_busca"
                    list="conta_pagar_fornecedores_datalist"
                    placeholder="Digite nome, razão social, CPF ou CNPJ..."
                    class="mb-2 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    autocomplete="off"
                >
                <datalist id="conta_pagar_fornecedores_datalist">
                    <?php $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fornecedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $nomeF = $fornecedor->nome ?? $fornecedor->razao_social ?? ('#' . $fornecedor->id);
                            $docF = $fornecedor->cnpj ?? $fornecedor->cpf ?? '';
                            $rotuloF = trim($nomeF . ($fornecedor->razao_social ? ' - ' . $fornecedor->razao_social : '') . ($docF ? ' - ' . $docF : ''));
                        ?>
                        <option value="<?php echo e($rotuloF); ?>"></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </datalist>
                <select name="fornecedor_id" id="conta_pagar_fornecedor_id" class="hidden">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fornecedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $nomeF = $fornecedor->nome ?? $fornecedor->razao_social ?? ('#' . $fornecedor->id);
                            $docF = $fornecedor->cnpj ?? $fornecedor->cpf ?? '';
                            $rotuloF = trim($nomeF . ($fornecedor->razao_social ? ' - ' . $fornecedor->razao_social : '') . ($docF ? ' - ' . $docF : ''));
                            $buscaF = strtolower(trim(
                                ($fornecedor->nome ?? '') . ' ' .
                                ($fornecedor->razao_social ?? '') . ' ' .
                                ($fornecedor->cnpj ?? '') . ' ' .
                                ($fornecedor->cpf ?? '')
                            ));
                        ?>
                        <option value="<?php echo e($fornecedor->id); ?>" data-label="<?php echo e($rotuloF); ?>" data-search="<?php echo e($buscaF); ?>" <?php echo e(old('fornecedor_id') == $fornecedor->id ? 'selected' : ''); ?>><?php echo e($rotuloF); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de Serviço</label>
                <select name="ordem_servico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhuma</option>
                    <?php $__currentLoopData = $ordensServico; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $os): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($os->id); ?>" <?php echo e(old('ordem_servico_id') == $os->id ? 'selected' : ''); ?>><?php echo e($os->codigo_interno); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="<?php echo e(old('descricao')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do Documento</label>
                <input type="text" name="numero_documento" value="<?php echo e(old('numero_documento')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo *</label>
                <select name="tipo" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="operacional" <?php echo e(old('tipo') === 'operacional' ? 'selected' : ''); ?>>Operacional</option>
                    <option value="insumo" <?php echo e(old('tipo') === 'insumo' ? 'selected' : ''); ?>>Insumo</option>
                    <option value="outro" <?php echo e(old('tipo') === 'outro' ? 'selected' : ''); ?>>Outro</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
                <input type="number" step="0.01" name="valor" value="<?php echo e(old('valor')); ?>" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento *</label>
                <input type="date" name="data_vencimento" value="<?php echo e(old('data_vencimento', now()->format('Y-m-d'))); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                <select name="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $formasPagamento; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $forma): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($forma->id); ?>" <?php echo e(old('forma_pagamento_id') == $forma->id ? 'selected' : ''); ?>><?php echo e($forma->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                <select name="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $contasBancarias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($conta->id); ?>" <?php echo e(old('conta_bancaria_id') == $conta->id ? 'selected' : ''); ?>><?php echo e($conta->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de Contas</label>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $planoContas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($conta->id); ?>" <?php echo e(old('plano_conta_id') == $conta->id ? 'selected' : ''); ?>><?php echo e($conta->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Custo</label>
                <select name="centro_custo_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhum</option>
                    <?php $__currentLoopData = $centrosCusto ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cc->id); ?>" <?php echo e(old('centro_custo_id') == $cc->id ? 'selected' : ''); ?>><?php echo e($cc->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <?php echo $__env->make('financeiro.partials.categoria-tags-titulo', [
                'escopoCategoria' => 'pagar',
                'tagEscopo' => 'conta_pagar',
                'categoriaOpcoes' => $categoriaFinanceiraOpcoes ?? [],
                'selectedCategoriaId' => old('categoria_financeira_id'),
                'selectedTags' => $tagsFormulario ?? collect(),
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('observacoes')); ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anexos (opcional)</label>
                <input type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione um ou mais arquivos (máx. 10 MB cada).</p>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('financeiro.contas-pagar.form.extra', null); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('financeiro.contas-pagar.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fornecedorSelect = document.getElementById('conta_pagar_fornecedor_id');
    const fornecedorBusca = document.getElementById('conta_pagar_fornecedor_busca');
    if (!fornecedorSelect || !fornecedorBusca) return;

    const labelToId = {};
    Array.from(fornecedorSelect.options).forEach(opt => {
        if (!opt.value) return;
        const label = (opt.dataset.label || opt.textContent || '').trim();
        if (label) labelToId[label] = opt.value;
    });

    const syncBuscaFromSelect = () => {
        const sel = fornecedorSelect.selectedOptions && fornecedorSelect.selectedOptions[0];
        if (sel && sel.value) fornecedorBusca.value = sel.dataset.label || sel.textContent || '';
    };

    const filtrar = () => {
        const q = (fornecedorBusca.value || '').toLowerCase().trim();
        Array.from(fornecedorSelect.options).forEach(opt => {
            if (!opt.value) return;
            const hay = (opt.dataset.search || '').toLowerCase();
            opt.hidden = q !== '' && !hay.includes(q);
        });
    };

    const syncSelectFromBusca = () => {
        const typed = fornecedorBusca.value.trim();
        const id = labelToId[typed];
        if (id) fornecedorSelect.value = id;
    };

    fornecedorBusca.addEventListener('input', () => {
        filtrar();
        syncSelectFromBusca();
    });
    fornecedorBusca.addEventListener('change', syncSelectFromBusca);
    fornecedorSelect.addEventListener('change', syncBuscaFromSelect);

    syncBuscaFromSelect();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\contas-pagar\create.blade.php ENDPATH**/ ?>