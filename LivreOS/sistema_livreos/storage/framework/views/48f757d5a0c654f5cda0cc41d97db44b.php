



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Conta a Receber</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova conta a receber</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('financeiro.contas-receber.store')); ?>" method="POST" class="p-6" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <?php echo $__env->make('partials.cliente-autocomplete-field', [
                    'prefix' => 'conta_receber',
                    'clientes' => $clientes,
                    'selectedId' => old('cliente_id'),
                    'label' => 'Cliente',
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de Serviço</label>
                <select name="ordem_servico_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Nenhuma</option>
                    <?php $__currentLoopData = $ordensServico; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $os): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($os->id); ?>" <?php echo e(old('ordem_servico_id') == $os->id ? 'selected' : ''); ?>><?php echo e($os->codigo_interno); ?> - R$ <?php echo e(number_format($os->total_geral, 2, ',', '.')); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="<?php echo e(old('descricao')); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__errorArgs = ['descricao'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total *</label>
                <input type="number" step="0.01" name="valor" value="<?php echo e(old('valor')); ?>" required min="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__errorArgs = ['valor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento *</label>
                <input type="date" name="data_vencimento" value="<?php echo e(old('data_vencimento', now()->format('Y-m-d'))); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__errorArgs = ['data_vencimento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Parcelas</label>
                <input type="number" name="numero_parcelas" value="<?php echo e(old('numero_parcelas', 1)); ?>" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias entre Parcelas</label>
                <input type="number" name="dias_entre_parcelas" value="<?php echo e(old('dias_entre_parcelas', 30)); ?>" min="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                <select name="forma_pagamento_id" id="forma_pagamento_id" onchange="atualizarContaBancaria()" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $formasPagamento; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $forma): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($forma->id); ?>" data-tipo="<?php echo e($forma->tipo); ?>" data-conta-bancaria-id="<?php echo e($forma->conta_bancaria_id ?? ''); ?>" <?php echo e(old('forma_pagamento_id') == $forma->id ? 'selected' : ''); ?>><?php echo e($forma->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta Bancária</label>
                <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $contasBancarias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($conta->id); ?>" <?php echo e(old('conta_bancaria_id') == $conta->id ? 'selected' : ''); ?>><?php echo e($conta->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se não informada, será usada a conta padrão da forma de pagamento selecionada</p>
            </div>

            <?php echo $__env->make('financeiro.partials.conta-receber-cartao-bloco', [
                'prefix' => 'cr_nova',
                'bandeirasCartao' => $bandeirasCartao,
                'formasCartaoMeta' => $formasCartaoMeta,
                'cfg' => [
                    'formaSel' => '#forma_pagamento_id',
                    'valorSel' => 'input[name="valor"]',
                    'parcelasSel' => 'input[name="numero_parcelas"]',
                    'dataRefSel' => 'input[name="data_vencimento"]',
                    'contaBancariaSel' => '#conta_bancaria_id',
                    'defaultAdquirenteId' => old('adquirente_id'),
                    'defaultBandeira' => old('bandeira', 'master'),
                ],
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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
                'escopoCategoria' => 'receber',
                'tagEscopo' => 'conta_receber',
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
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecione um ou mais arquivos (máx. 10 MB cada). Em parcelas múltiplas, anexos não são salvos.</p>
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('financeiro.contas-receber.form.extra', null); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('financeiro.contas-receber.index')); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<script>
function atualizarContaBancaria() {
    const formaPagamentoSelect = document.getElementById('forma_pagamento_id');
    const contaBancariaSelect = document.getElementById('conta_bancaria_id');
    const selectedOption = formaPagamentoSelect.options[formaPagamentoSelect.selectedIndex];
    const contaBancariaId = selectedOption.getAttribute('data-conta-bancaria-id');
    
    // Só atualizar se a conta bancária não estiver preenchida manualmente
    if (contaBancariaId && !contaBancariaSelect.value) {
        contaBancariaSelect.value = contaBancariaId;
    }
}

// Executar ao carregar a página se já houver uma forma de pagamento selecionada
document.addEventListener('DOMContentLoaded', function() {
    atualizarContaBancaria();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\contas-receber\create.blade.php ENDPATH**/ ?>