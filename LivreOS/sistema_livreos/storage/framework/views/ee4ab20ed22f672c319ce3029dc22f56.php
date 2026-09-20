



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova despesa recorrente</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma despesa que se repete (água, luz, aluguel, condomínio). As contas a pagar serão geradas automaticamente.</p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('financeiro.contas-pagar-recorrentes.store')); ?>" method="POST" class="p-6">
        <?php echo csrf_field(); ?>
        <?php if(request('voltar')): ?><input type="hidden" name="voltar" value="<?php echo e(request('voltar')); ?>"><?php endif; ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fornecedor (opcional)</label>
                <select name="fornecedor_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Nenhum —</option>
                    <?php $__currentLoopData = $fornecedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($f->id); ?>" <?php echo e(old('fornecedor_id') == $f->id ? 'selected' : ''); ?>><?php echo e($f->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex.: concessionária, proprietário do imóvel</p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição *</label>
                <input type="text" name="descricao" value="<?php echo e(old('descricao')); ?>" required placeholder="Ex.: Conta de luz" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">— Selecione —</option>
                    <option value="agua" <?php echo e(old('tipo') === 'agua' ? 'selected' : ''); ?>>Água</option>
                    <option value="luz" <?php echo e(old('tipo') === 'luz' ? 'selected' : ''); ?>>Luz</option>
                    <option value="gas" <?php echo e(old('tipo') === 'gas' ? 'selected' : ''); ?>>Gás</option>
                    <option value="aluguel" <?php echo e(old('tipo') === 'aluguel' ? 'selected' : ''); ?>>Aluguel</option>
                    <option value="condominio" <?php echo e(old('tipo') === 'condominio' ? 'selected' : ''); ?>>Condomínio</option>
                    <option value="telefone" <?php echo e(old('tipo') === 'telefone' ? 'selected' : ''); ?>>Telefone</option>
                    <option value="internet" <?php echo e(old('tipo') === 'internet' ? 'selected' : ''); ?>>Internet</option>
                    <option value="outro" <?php echo e(old('tipo') === 'outro' ? 'selected' : ''); ?>>Outro</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Valor *</label>
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
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência *</label>
                <select name="frequencia" id="frequencia" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="mensal" <?php echo e(old('frequencia', 'mensal') === 'mensal' ? 'selected' : ''); ?>>Mensal</option>
                    <option value="diaria" <?php echo e(old('frequencia') === 'diaria' ? 'selected' : ''); ?>>Diária</option>
                    <option value="semanal" <?php echo e(old('frequencia') === 'semanal' ? 'selected' : ''); ?>>Semanal</option>
                    <option value="quinzenal" <?php echo e(old('frequencia') === 'quinzenal' ? 'selected' : ''); ?>>Quinzenal</option>
                    <option value="bimestral" <?php echo e(old('frequencia') === 'bimestral' ? 'selected' : ''); ?>>Bimestral</option>
                    <option value="trimestral" <?php echo e(old('frequencia') === 'trimestral' ? 'selected' : ''); ?>>Trimestral</option>
                    <option value="semestral" <?php echo e(old('frequencia') === 'semestral' ? 'selected' : ''); ?>>Semestral</option>
                    <option value="anual" <?php echo e(old('frequencia') === 'anual' ? 'selected' : ''); ?>>Anual</option>
                </select>
            </div>
            <div id="gerar_ultimo_dia_wrap" class="<?php echo e(old('frequencia', 'mensal') !== 'mensal' ? 'hidden' : ''); ?>">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento mensal</label>
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="gerar_ultimo_dia_mes" id="gerar_ultimo_dia_mes" value="1" <?php echo e(old('gerar_ultimo_dia_mes') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <label for="gerar_ultimo_dia_mes" class="text-sm text-gray-700 dark:text-gray-300">Sempre no último dia do mês</label>
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data início *</label>
                <input type="date" name="data_inicio" value="<?php echo e(old('data_inicio', now()->format('Y-m-d'))); ?>" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Primeira data de vencimento a ser gerada</p>
                <?php $__errorArgs = ['data_inicio'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data fim (opcional)</label>
                <input type="date" name="data_fim" value="<?php echo e(old('data_fim')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <?php $__errorArgs = ['data_fim'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de pagamento</label>
                <select name="forma_pagamento_id" id="forma_pagamento_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $formasPagamento; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($f->id); ?>" data-conta-bancaria-id="<?php echo e($f->conta_bancaria_id ?? ''); ?>" <?php echo e(old('forma_pagamento_id') == $f->id ? 'selected' : ''); ?>><?php echo e($f->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conta bancária</label>
                <select name="conta_bancaria_id" id="conta_bancaria_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $contasBancarias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cb->id); ?>" <?php echo e(old('conta_bancaria_id') == $cb->id ? 'selected' : ''); ?>><?php echo e($cb->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Plano de contas (despesa)</label>
                <select name="plano_conta_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Selecione...</option>
                    <?php $__currentLoopData = $planoContas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pc->id); ?>" <?php echo e(old('plano_conta_id') == $pc->id ? 'selected' : ''); ?>><?php echo e($pc->nome); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="ativo" id="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo (gerar contas automaticamente)</label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('observacoes')); ?></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(request('voltar', route('financeiro.contas-pagar.index', ['aba' => 'recorrentes']))); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>
<script>
document.getElementById('frequencia').addEventListener('change', function() {
    document.getElementById('gerar_ultimo_dia_wrap').classList.toggle('hidden', this.value !== 'mensal');
});
function aplicarContaPadraoFormaPagamento() {
    var formaSelect = document.getElementById('forma_pagamento_id');
    var contaSelect = document.getElementById('conta_bancaria_id');
    if (!formaSelect || !contaSelect) return;
    var opt = formaSelect.options[formaSelect.selectedIndex];
    var contaId = opt ? opt.getAttribute('data-conta-bancaria-id') : null;
    if (contaId && contaSelect.querySelector('option[value="' + contaId + '"]')) contaSelect.value = contaId;
}
document.getElementById('forma_pagamento_id').addEventListener('change', aplicarContaPadraoFormaPagamento);
document.addEventListener('DOMContentLoaded', function() {
    var contaSelect = document.getElementById('conta_bancaria_id');
    if (contaSelect && !contaSelect.value) aplicarContaPadraoFormaPagamento();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\contas-pagar-recorrentes\create.blade.php ENDPATH**/ ?>