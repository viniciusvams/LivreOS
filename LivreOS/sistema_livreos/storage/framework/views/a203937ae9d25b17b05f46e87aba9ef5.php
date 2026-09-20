



<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Nova Taxa</h1>
    <p class="text-gray-600 dark:text-gray-400">Cadastre uma nova taxa para <?php echo e($adquirente->nome); ?></p>
</div>

<div class="rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <form action="<?php echo e(route('financeiro.taxas.store', $adquirente)); ?>" method="POST" class="p-6">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bandeira *</label>
                <select name="bandeira" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="master" <?php echo e(old('bandeira') === 'master' ? 'selected' : ''); ?>>Mastercard</option>
                    <option value="visa" <?php echo e(old('bandeira') === 'visa' ? 'selected' : ''); ?>>Visa</option>
                    <option value="elo" <?php echo e(old('bandeira') === 'elo' ? 'selected' : ''); ?>>Elo</option>
                    <option value="amex" <?php echo e(old('bandeira') === 'amex' ? 'selected' : ''); ?>>American Express</option>
                    <option value="hipercard" <?php echo e(old('bandeira') === 'hipercard' ? 'selected' : ''); ?>>Hipercard</option>
                    <option value="outros" <?php echo e(old('bandeira') === 'outros' ? 'selected' : ''); ?>>Outros</option>
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Modalidade *</label>
                <select name="modalidade" id="modalidade" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="debito" <?php echo e(old('modalidade') === 'debito' ? 'selected' : ''); ?>>Débito</option>
                    <option value="credito_vista" <?php echo e(old('modalidade') === 'credito_vista' ? 'selected' : ''); ?>>Crédito à Vista</option>
                    <option value="credito_parcelado" <?php echo e(old('modalidade') === 'credito_parcelado' ? 'selected' : ''); ?>>Crédito Parcelado</option>
                </select>
            </div>

            <div id="parcelas-container" class="md:col-span-2 hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Parcelas Mínimas</label>
                        <input type="number" name="parcelas_min" value="<?php echo e(old('parcelas_min')); ?>" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Parcelas Máximas</label>
                        <input type="number" name="parcelas_max" value="<?php echo e(old('parcelas_max')); ?>" min="1" max="120" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:text-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Percentual (%) *</label>
                <input type="number" step="0.01" name="taxa_percentual" value="<?php echo e(old('taxa_percentual', 0)); ?>" min="0" max="100" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex: 2,5% informe 2.5</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa por Parcela (%)</label>
                <input type="number" step="0.01" name="taxa_por_parcela" value="<?php echo e(old('taxa_por_parcela', 0)); ?>" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ex: 0,5% por parcela informe 0.5</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa Fixa (R$)</label>
                <input type="number" step="0.01" name="taxa_fixa" value="<?php echo e(old('taxa_fixa', 0)); ?>" min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias Recebimento Débito</label>
                <input type="number" name="dias_recebimento_debito" value="<?php echo e(old('dias_recebimento_debito', 1)); ?>" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias Recebimento Crédito à Vista</label>
                <input type="number" name="dias_recebimento_credito_vista" value="<?php echo e(old('dias_recebimento_credito_vista', 30)); ?>" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dias entre Parcelas</label>
                <input type="number" name="dias_recebimento_parcela" value="<?php echo e(old('dias_recebimento_parcela', 30)); ?>" min="0" max="365" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo e(old('data_inicio')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo e(old('data_fim')); ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"><?php echo e(old('observacoes')); ?></textarea>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ativo" value="1" <?php echo e(old('ativo', true) ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Taxa ativa</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="usa_antecipacao" id="usa_antecipacao" value="1" <?php echo e(old('usa_antecipacao') ? 'checked' : ''); ?> class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Usa antecipação</span>
                </label>
            </div>

            <div id="taxa_antecipacao_container" class="md:col-span-2 hidden">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Taxa de Antecipação (%)</label>
                <input type="number" step="0.01" name="taxa_antecipacao_percentual" value="<?php echo e(old('taxa_antecipacao_percentual', '')); ?>" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>

        <?php if(function_exists('do_action')): ?>
        <?php do_action('financeiro.taxas-adquirente.form.extra', null); ?>
        <?php endif; ?>
        <div class="mt-6 flex justify-end gap-3">
            <a href="<?php echo e(route('financeiro.taxas.index', $adquirente)); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancelar</a>
            <a href="<?php echo e(route('financeiro.adquirentes.edit', $adquirente)); ?>" class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Voltar para adquirente</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Salvar</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalidade = document.getElementById('modalidade');
    const parcelasContainer = document.getElementById('parcelas-container');
    const usaAntecipacao = document.getElementById('usa_antecipacao');
    const taxaAntecipacaoContainer = document.getElementById('taxa_antecipacao_container');

    modalidade.addEventListener('change', function() {
        if (this.value === 'credito_parcelado') {
            parcelasContainer.classList.remove('hidden');
        } else {
            parcelasContainer.classList.add('hidden');
        }
    });

    usaAntecipacao.addEventListener('change', function() {
        if (this.checked) {
            taxaAntecipacaoContainer.classList.remove('hidden');
        } else {
            taxaAntecipacaoContainer.classList.add('hidden');
        }
    });

    // Trigger inicial
    modalidade.dispatchEvent(new Event('change'));
    usaAntecipacao.dispatchEvent(new Event('change'));
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\financeiro\taxas\create.blade.php ENDPATH**/ ?>